<?php
/** Phase 2 only: explicit preflight, drafts and incremental records. No Phase 1 writes. */
defined('ABSPATH') || exit;
function kova_phase2_plan(): array {
    $plan=[];$data=kova_phase2_data();
    foreach($data['pages'] as $key=>$page){
        $seed='page:'.$key;$template='templates/page-'.$key.'.php';$id=kova_seed_find('page',$seed);
        $row=['type'=>'page','key'=>$key,'seed'=>$seed,'title'=>$page['title'],'id'=>$id,'state'=>$id?'preserve':'create','message'=>'','adopt_id'=>0];
        if($id){
            $row['message']='保留已有记录、状态和编辑；当前 slug：'.get_post($id)->post_name;
        }else{
            $collisions=get_posts(['post_type'=>['page','post'],'post_status'=>['publish','draft','private','pending','future'],'name'=>$page['slug'],'numberposts'=>-1]);
            if(!$collisions){
                $possible=get_posts(['post_type'=>'page','post_status'=>['publish','draft','private','pending','future'],'numberposts'=>-1]);
                $collisions=array_values(array_filter($possible,fn($p)=>!get_post_meta($p->ID,'_kova_seed_key',true) && (strcasecmp($p->post_title,$page['title'])===0 || get_post_meta($p->ID,'_wp_page_template',true)===$template)));
            }
            if($collisions){
                $row['state']='conflict';$row['id']=$collisions[0]->ID;$row['message']='目标 slug 已被占用，禁止创建 -2 页面。';
                if(count($collisions)===1 && $collisions[0]->post_type==='page'
                    && $collisions[0]->post_name===$page['slug']
                    && get_post_meta($row['id'],'_wp_page_template',true)===$template
                    && !get_post_meta($row['id'],'_kova_seed_key',true)){
                    $row['adopt_id']=$row['id'];$row['message'].=' 可人工确认关联：仅补充缺少的字段，不覆盖内容。';
                }
            }
        }
        $plan[]=$row;
    }
    foreach($data['categories'] as $category){
        $seed='category:'.$category['key'];$id=kova_seed_find('kova_category',$seed);
        $row=['type'=>'category','key'=>$category['key'],'seed'=>$seed,'title'=>$category['title'],'id'=>$id,'state'=>$id?'preserve':'create','message'=>'','adopt_id'=>0];
        if(!$id){
            $possible=get_posts(['post_type'=>'kova_category','post_status'=>['publish','draft','private','pending'],'numberposts'=>-1,'tax_query'=>[['taxonomy'=>'kova_species','field'=>'slug','terms'=>'dog']]]);
            $matches=array_values(array_filter($possible,fn($p)=>strcasecmp($p->post_title,$category['title'])===0 || $p->post_name===$category['key']));
            if($matches){
                $row['state']='conflict';$row['id']=$matches[0]->ID;$row['message']='已有同名/同 slug 的 Dog 分类，需确认关联，禁止重复创建。';
                if(count($matches)===1 && !get_post_meta($row['id'],'_kova_seed_key',true)
                    && !has_term('cat','kova_species',$row['id']))$row['adopt_id']=$row['id'];
            }
        }
        $plan[]=$row;
    }
    return $plan;
}
function kova_phase2_record_fields(int $id,array $fields,string $page,array &$report): void {
    if(get_post_meta($id,'_kova_phase2_complete',true))return;
    foreach($fields as $name=>$field){
        if(metadata_exists('post',$id,$name))continue;
        $value=$field['type']==='image'?kova_seed_image($field['default'],$field['alt'],$report):$field['default'];
        kova_seed_meta($id,$name,$value,'field_kova_'.$page.'_'.$name);
    }
    update_post_meta($id,'_kova_phase2_complete',1);
}
function kova_phase2_import(array $adopt=[]): array {
    if(!current_user_can('manage_options')||!kova_import_allowed())throw new RuntimeException('仅测试/本地环境管理员可以导入。');
    if(!is_file(get_theme_root().'/kova-theme/style.css'))throw new RuntimeException('请先安装新版 KOVA 主题。');
    if(!add_option('_kova_phase2_import_lock',time(),'',false))throw new RuntimeException('Phase 2 导入正在执行；请勿同时提交。');
    try{
        $plan=kova_phase2_plan();$data=kova_phase2_data();$allowedAdopt=[];
        foreach($plan as $row){
            if($row['state']==='conflict'){
                if(!$row['adopt_id']||!in_array($row['adopt_id'],$adopt,true))throw new RuntimeException($row['title'].'：'.$row['message'].' 未执行任何导入。');
                $allowedAdopt[]=$row['adopt_id'];
            }
        }
        foreach($adopt as $id)if(!in_array($id,$allowedAdopt,true))throw new RuntimeException('关联请求与当前预检不匹配，请刷新。');
        foreach(array_keys($data['pages']) as $key)if(!is_file(get_theme_root().'/kova-theme/templates/page-'.$key.'.php'))throw new RuntimeException('缺少 Phase 2 主题模板，请先更新主题。');
        $report=['pages_created'=>0,'categories_created'=>0,'media_created'=>0,'preserved'=>0,'associated'=>0,'page_ids'=>[]];
        foreach($plan as $row){
            $id=$row['id'];$key=$row['key'];$isPage=$row['type']==='page';
            if($row['state']==='create'){
                $entry=$isPage?$data['pages'][$key]:current(array_filter($data['categories'],fn($c)=>$c['key']===$key));
                $id=wp_insert_post(['post_type'=>$isPage?'page':'kova_category','post_status'=>'draft','post_title'=>$row['title'],'post_name'=>$isPage?$entry['slug']:$key],true);
                if(is_wp_error($id))throw new RuntimeException($id->get_error_message());
                update_post_meta($id,'_kova_seed_key',$row['seed']);
                if($isPage){update_post_meta($id,'_wp_page_template','templates/page-'.$key.'.php');$report['pages_created']++;}
                else{
                    if(!term_exists('dog','kova_species')){
                        $term=wp_insert_term('Dog Products','kova_species',['slug'=>'dog']);if(is_wp_error($term))throw new RuntimeException($term->get_error_message());
                    }
                    $term=wp_set_object_terms($id,'dog','kova_species');if(is_wp_error($term))throw new RuntimeException($term->get_error_message());
                    update_post_meta($id,'_kova_phase2_pending_publish',1);$report['categories_created']++;
                }
            }elseif($row['state']==='conflict'){
                update_post_meta($id,'_kova_seed_key',$row['seed']);$report['associated']++;
            }else $report['preserved']++;
            if($isPage){
                kova_phase2_record_fields($id,$data['pages'][$key]['fields'],$key,$report);$report['page_ids'][$key]=$id;
            }elseif(!get_post_meta($id,'_kova_phase2_complete',true)){
                $entry=current(array_filter($data['categories'],fn($c)=>$c['key']===$key));
                foreach(['description','options','customization','order'] as $f)kova_seed_meta($id,'kova_'.$f,$entry[$f],'field_kova_'.$f);
                kova_seed_meta($id,'kova_visible',1,'field_kova_visible');
                if(!metadata_exists('post',$id,'kova_image'))kova_seed_meta($id,'kova_image',kova_seed_image($entry['image'],$entry['alt'],$report),'field_kova_image');
                // New draft category becomes visible only once fully populated. Existing/adopted status is retained.
                if(get_post_meta($id,'_kova_phase2_pending_publish',true)){
                    $published=wp_update_post(['ID'=>$id,'post_status'=>'publish'],true);
                    if(is_wp_error($published))throw new RuntimeException($published->get_error_message());
                    delete_post_meta($id,'_kova_phase2_pending_publish');
                }
                update_post_meta($id,'_kova_phase2_complete',1);
            }
        }
        return $report;
    }finally{delete_option('_kova_phase2_import_lock');}
}
add_action('admin_menu',function(){add_management_page('KOVA Phase 2 Import','KOVA Phase 2 Import','manage_options','kova-phase2-import','kova_phase2_import_screen');});
function kova_phase2_import_screen(): void {
    if(!current_user_can('manage_options'))return;
    echo '<div class="wrap"><h1>KOVA Phase 2 Import</h1><p>仅导入五个新页面和八个 Dog 分类。Home、Cat、首页设置、菜单、固定链接、Fluent Forms 和旧内容均不修改。新页面为草稿。</p>';
    if(!kova_import_allowed()){echo '<p>正式环境禁止导入。</p></div>';return;}
    if(isset($_POST['kova_phase2_import'])){
        check_admin_referer('kova_phase2_import');
        try{
            if(($_POST['kova_confirm']??'')!=='1')throw new RuntimeException('请确认测试站和备份。');
            $adoptRaw=$_POST['adopt']??[];if(!is_array($adoptRaw))throw new RuntimeException('关联请求无效。');
            $adopt=[];foreach($adoptRaw as $v){if(!is_string($v)||!ctype_digit($v))throw new RuntimeException('关联 ID 无效。');$adopt[]=(int)$v;}
            $report=kova_phase2_import($adopt);
            echo '<div class="notice notice-success"><p>导入完成：'.esc_html(wp_json_encode($report)).'</p></div>';
        }catch(Throwable $e){echo '<div class="notice notice-error"><p>'.esc_html($e->getMessage()).' 如媒体导入中断，修复后可安全重试。</p></div>';}
    }
    if(get_option('permalink_structure')==='')echo '<p>当前固定链接为普通模式；若需 /dog-products/ 等路径，请由你在测试站设置固定链接。工具不会自动改变它。</p>';
    echo '<form method="post">';wp_nonce_field('kova_phase2_import');
    echo '<table class="widefat striped"><thead><tr><th>内容</th><th>ID</th><th>预检</th><th>说明</th></tr></thead><tbody>';
    try{
        foreach(kova_phase2_plan() as $row){
            echo '<tr><td>'.esc_html($row['title']).'</td><td>'.esc_html((string)$row['id']).'</td><td>'.esc_html($row['state']).'</td><td>'.esc_html($row['message']);
            if($row['adopt_id'])echo '<br><label><input type="checkbox" name="adopt[]" value="'.esc_attr((string)$row['adopt_id']).'">确认关联此已有记录，保留现有值，仅补充缺少字段</label>';
            echo '</td></tr>';
        }
    }catch(Throwable $e){echo '<tr><td colspan="4">'.esc_html($e->getMessage()).'</td></tr>';}
    echo '</tbody></table><p><label><input type="checkbox" name="kova_confirm" value="1" required>确认正在操作测试站，已备份并检查上述预检结果。</label></p>';
    submit_button('Import missing Phase 2 content','primary','kova_phase2_import');echo '</form></div>';
}
