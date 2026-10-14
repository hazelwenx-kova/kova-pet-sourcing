<?php
defined('ABSPATH') || exit;
function kova_import_allowed(): bool {
    $host=(string)wp_parse_url(home_url(),PHP_URL_HOST);
    if(in_array(strtolower($host),['kovasourcing.com','www.kovasourcing.com'],true))return false;
    return in_array(wp_get_environment_type(),['local','development','staging'],true)
        || in_array($host,['localhost','127.0.0.1'],true)
        || (bool)preg_match('/^staging[0-9]*\./i',$host);
}
function kova_seed_meta(int $id,string $name,$value,string $field_key): void {
    if(!metadata_exists('post',$id,$name)){
        update_post_meta($id,$name,$value);
        update_post_meta($id,'_'.$name,$field_key);
    }
}
function kova_seed_find(string $type,string $key): int {
    $ids=get_posts(['post_type'=>$type,'post_status'=>['publish','draft','private','pending','future','trash','inherit'],'meta_key'=>'_kova_seed_key','meta_value'=>$key,'numberposts'=>1,'fields'=>'ids']);
    if($ids && get_post_status($ids[0])==='trash')throw new RuntimeException('A seeded item is in Trash: '.$key.'. Restore it before importing again.');
    return $ids?(int)$ids[0]:0;
}
function kova_seed_image(string $path,string $alt,array &$report): int {
    $key='asset:'.$path;
    $id=kova_seed_find('attachment',$key);
    if($id && is_file((string)get_attached_file($id)))return $id;
    if($id)throw new RuntimeException('Seed image is missing its file. Restore it in Media Library before retrying: '.$path);
    $source=get_theme_root().'/kova-theme/'.$path;
    if(!is_file($source))throw new RuntimeException('Theme asset unavailable: '.$path.'. Install the kova-theme folder first.');
    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/media.php';
    require_once ABSPATH.'wp-admin/includes/image.php';
    $tmp=wp_tempnam(basename($path));
    if(!$tmp||!copy($source,$tmp))throw new RuntimeException('Could not prepare media upload.');
    $id=media_handle_sideload(['name'=>basename($path),'tmp_name'=>$tmp],0,$alt);
    if(is_wp_error($id)){if(is_file($tmp))unlink($tmp);throw new RuntimeException($id->get_error_message());}
    update_post_meta($id,'_kova_seed_key',$key);update_post_meta($id,'_wp_attachment_image_alt',$alt);
    $report['media_created']++;return (int)$id;
}
function kova_run_import_content(): array {
    if(!current_user_can('manage_options'))throw new RuntimeException('Administrator permission required.');
    if(!kova_import_allowed())throw new RuntimeException('Import is limited to local/staging environments. It is disabled on the live domain.');
    if(!is_file(get_theme_root().'/kova-theme/style.css'))throw new RuntimeException('Install the KOVA theme first; activation is not required for import.');
    $report=['pages_created'=>0,'categories_created'=>0,'media_created'=>0,'existing_items_preserved'=>0,'page_ids'=>[]];
    $data=kova_content_data();
    foreach($data['pages'] as $key=>$page){
        $id=kova_seed_find('page','page:'.$key);
        if(!$id){
            $id=wp_insert_post(['post_type'=>'page','post_status'=>'draft','post_title'=>$page['title'],'post_name'=>$key==='home'?'kova-home':$key],true);
            if(is_wp_error($id))throw new RuntimeException($id->get_error_message());
            update_post_meta($id,'_kova_seed_key','page:'.$key);
            update_post_meta($id,'_wp_page_template','templates/page-'.$key.'.php');
            $report['pages_created']++;
        }else $report['existing_items_preserved']++;
        $report['page_ids'][$key]=$id;
        foreach($page['fields'] as $name=>$field){
            if(metadata_exists('post',$id,$name))continue;
            $value=$field['type']==='image'?kova_seed_image($field['default'],$field['alt'],$report):$field['default'];
            kova_seed_meta($id,$name,$value,'field_kova_'.$key.'_'.$name);
        }
        if($key==='home')foreach($data['global'] as $name=>$field)kova_seed_meta($id,$name,$field['default'],'field_kova_'.$name);
    }
    foreach(['cat'=>'Cat Products','dog'=>'Dog Products'] as $slug=>$name){
        if(!term_exists($slug,'kova_species')){
            $term=wp_insert_term($name,'kova_species',['slug'=>$slug]);
            if(is_wp_error($term))throw new RuntimeException($term->get_error_message());
        }
    }
    foreach($data['categories'] as $category){
        $key='category:'.$category['key'];$id=kova_seed_find('kova_category',$key);
        if(!$id){
            $id=wp_insert_post(['post_type'=>'kova_category','post_status'=>'publish','post_title'=>$category['title'],'post_name'=>$category['key']],true);
            if(is_wp_error($id))throw new RuntimeException($id->get_error_message());
            update_post_meta($id,'_kova_seed_key',$key);
            wp_set_object_terms($id,$category['species'],'kova_species');$report['categories_created']++;
        }else $report['existing_items_preserved']++;
        foreach(['description','options','customization','order'] as $field)kova_seed_meta($id,'kova_'.$field,$category[$field],'field_kova_'.$field);
        kova_seed_meta($id,'kova_visible',1,'field_kova_visible');
        if(!metadata_exists('post',$id,'kova_image'))kova_seed_meta($id,'kova_image',kova_seed_image($category['image'],$category['alt'],$report),'field_kova_image');
    }
    return $report;
}
function kova_run_import(): array {
    if (!current_user_can('manage_options')) throw new RuntimeException('Administrator permission required.');
    if (!add_option('_kova_phase1_import_lock', time(), '', false)) throw new RuntimeException('An import is already running. If an earlier request crashed, ask your administrator to clear _kova_phase1_import_lock after confirming no import is active.');
    try { return kova_run_import_content(); }
    finally { delete_option('_kova_phase1_import_lock'); }
}
add_action('admin_menu',function(){add_management_page('KOVA Phase 1 Import','KOVA Phase 1 Import','manage_options','kova-import','kova_import_screen');});
function kova_import_screen(): void {
    if(!current_user_can('manage_options'))return;
    echo '<div class="wrap"><h1>KOVA Phase 1 Import</h1><p>Creates Home and Cat Products as drafts, eight Cat categories and independent Media Library images. Existing seeded values, status, order and visibility are preserved. No site settings or emails are changed.</p>';
    if(!kova_import_allowed()){echo '<div class="notice notice-error"><p>Import disabled: use a local or staging WordPress environment.</p></div></div>';return;}
    if(isset($_POST['kova_import'])){
        check_admin_referer('kova_phase1_import');
        if(empty($_POST['kova_confirm']))echo '<p>Please confirm this is the test site.</p>';
        else {
            try {$report=kova_run_import();echo '<div class="notice notice-success"><p>Import completed. '.esc_html(wp_json_encode($report)).'</p></div>';}
            catch(Throwable $e){echo '<div class="notice notice-error"><p>'.esc_html($e->getMessage()).' Import may be partial; resolve the issue and repeat safely.</p></div>';}
        }
    }
    echo '<form method="post">';wp_nonce_field('kova_phase1_import');
    echo '<p><label><input type="checkbox" name="kova_confirm" value="1" required> I confirm this is a local/staging site and have backed up its database and uploads.</label></p>';
    submit_button('Import missing Phase 1 content','primary','kova_import');echo '</form></div>';
}
