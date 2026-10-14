<?php
/**
 * Plugin Name: KOVA Content
 * Description: Sourcing category management, ACF Free fields and a non-destructive phase-specific importers.
 * Version: 0.2.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;
function kova_phase1_data(): array {
    static $data;
    if ($data === null) $data = json_decode(file_get_contents(__DIR__ . '/data/initial-content.json'), true);
    return $data ?: [];
}
function kova_phase2_data(): array {
    static $data;
    if ($data === null) $data = json_decode(file_get_contents(__DIR__ . '/data/phase2-content.json'), true);
    return $data ?: [];
}
function kova_content_data(): array {
    $initial = kova_phase1_data(); $second = kova_phase2_data();
    // Existing Phase 1 names/keys/defaults always win; its source manifest is unchanged.
    $initial['pages'] += $second['pages'] ?? [];
    $initial['categories'] = array_merge($initial['categories'], $second['categories'] ?? []);
    return $initial;
}
add_action('init', function () {
    register_post_type('kova_category', [
        'labels' => ['name'=>'Sourcing Categories','singular_name'=>'Sourcing Category','add_new_item'=>'Add Sourcing Category','edit_item'=>'Edit Sourcing Category'],
        'public'=>false, 'show_ui'=>true, 'show_in_menu'=>true, 'show_in_rest'=>false,
        'menu_icon'=>'dashicons-tag', 'supports'=>['title'], 'capability_type'=>'post',
        'map_meta_cap'=>true, 'rewrite'=>false, 'has_archive'=>false,
    ]);
    register_taxonomy('kova_species','kova_category',[
        'labels'=>['name'=>'Cat / Dog','singular_name'=>'Species'],
        'public'=>false,'show_ui'=>true,'show_admin_column'=>true,'hierarchical'=>true,'rewrite'=>false,
        'capabilities'=>['manage_terms'=>'manage_options','edit_terms'=>'manage_options','delete_terms'=>'manage_options','assign_terms'=>'edit_posts'],
    ]);
});
function kova_categories(string $species): array {
    $posts=get_posts(['post_type'=>'kova_category','post_status'=>'publish','numberposts'=>-1,'tax_query'=>[['taxonomy'=>'kova_species','field'=>'slug','terms'=>$species]]]);
    $posts=array_filter($posts,fn($p)=>!metadata_exists('post',$p->ID,'kova_visible') || (bool)get_post_meta($p->ID,'kova_visible',true));
    usort($posts,function($a,$b){
        $order=fn($p)=>metadata_exists('post',$p->ID,'kova_order')?(int)get_post_meta($p->ID,'kova_order',true):999;
        return ($order($a)<=>$order($b)) ?: ($a->ID<=>$b->ID);
    });
    return $posts;
}
add_filter('manage_kova_category_posts_columns',function($cols){$cols['kova_order']='Order';$cols['kova_visible']='Visible';return $cols;});
add_action('manage_kova_category_posts_custom_column',function($col,$id){
    if($col==='kova_order')echo esc_html(get_post_meta($id,'kova_order',true));
    if($col==='kova_visible')echo (!metadata_exists('post',$id,'kova_visible')||get_post_meta($id,'kova_visible',true))?'Yes':'No';
},10,2);
add_action('acf/init',function(){
    $data=kova_content_data();
    foreach($data['pages'] as $page=>$config){
        $fields=[];
        foreach($config['fields'] as $name=>$field){
            $f=['key'=>'field_kova_'.$page.'_'.$name,'name'=>$name,'label'=>$field['label'],'type'=>$field['type'],'instructions'=>$field['instructions']??'Edit the text only. Line breaks and emphasis in the page layout stay fixed.','default_value'=>$field['type']==='image'?'':$field['default']];
            if($field['type']==='image')$f+=['return_format'=>'id','preview_size'=>'medium','library'=>'all','mime_types'=>'jpg,jpeg,png,webp','instructions'=>'Choose an independent image. Update its Alt Text in the Media Library. Keep conceptual imagery disclosure until replaced.'];
            if($field['type']==='textarea')$f+=['rows'=>3,'new_lines'=>''];
            $fields[]=$f;
        }
        // Named tabs map directly to the fixed template sections, with image and link fields separately.
        $grouped=[];$text=[];$links=[];$images=[];
        foreach($fields as $f){if($f['type']==='image')$images[]=$f;elseif(str_starts_with($f['name'],'link_'))$links[]=$f;else $text[]=$f;}
        foreach(['Page copy'=>$text,'Buttons & links'=>$links,'Images'=>$images] as $tab=>$list){
            $grouped[]=['key'=>'field_kova_tab_'.$page.'_'.sanitize_key($tab),'label'=>$tab,'type'=>'tab','placement'=>'top'];
            foreach($list as $f)$grouped[]=$f;
        }
        acf_add_local_field_group(['key'=>'group_kova_'.$page,'title'=>'KOVA — '.$config['title'].' content','fields'=>$grouped,'location'=>[[['param'=>'page_template','operator'=>'==','value'=>'templates/page-'.$page.'.php']]],'position'=>'normal','style'=>'default','hide_on_screen'=>['the_content']]);
    }
    $globals=[];
    foreach($data['global'] as $name=>$f)$globals[]=['key'=>'field_kova_'.$name,'name'=>$name,'label'=>$f['label'],'type'=>'textarea','rows'=>2,'new_lines'=>'','default_value'=>$f['default']];
    acf_add_local_field_group(['key'=>'group_kova_global','title'=>'KOVA — shared footer','fields'=>$globals,'location'=>[[['param'=>'page_template','operator'=>'==','value'=>'templates/page-home.php']]]]);
    $fields=[
        ['key'=>'field_kova_image','name'=>'kova_image','label'=>'Category image','type'=>'image','return_format'=>'id','preview_size'=>'medium','mime_types'=>'jpg,jpeg,png,webp','required'=>1],
        ['key'=>'field_kova_description','name'=>'kova_description','label'=>'Short introduction','type'=>'textarea','rows'=>3,'new_lines'=>'','required'=>1],
        ['key'=>'field_kova_options','name'=>'kova_options','label'=>'Product options','type'=>'textarea','rows'=>3,'new_lines'=>''],
        ['key'=>'field_kova_customization','name'=>'kova_customization','label'=>'Customization to discuss','type'=>'textarea','rows'=>3,'new_lines'=>''],
        ['key'=>'field_kova_order','name'=>'kova_order','label'=>'Display order','type'=>'number','min'=>0,'step'=>1,'default_value'=>999,'instructions'=>'Lower numbers appear first. Cat and Dog lists are sorted independently.'],
        ['key'=>'field_kova_visible','name'=>'kova_visible','label'=>'Display on category page','type'=>'true_false','ui'=>1,'default_value'=>1,'instructions'=>'Also requires Published status and the matching Cat or Dog assignment.'],
    ];
    acf_add_local_field_group(['key'=>'group_kova_category','title'=>'Sourcing category details','fields'=>$fields,'location'=>[[['param'=>'post_type','operator'=>'==','value'=>'kova_category']]]]);
});
add_action('admin_notices',function(){
    if(!function_exists('acf_add_local_field_group'))echo '<div class="notice notice-warning"><p>KOVA Content: Install and activate Advanced Custom Fields (Free) for content editing. Existing content remains readable without it.</p></div>';
});
// Never import automatically on activation, never change front page/theme/email settings.
require __DIR__.'/import.php';
// Fixed KOVA templates use a compact classic admin screen with ACF fields, not a layout canvas.
add_filter('use_block_editor_for_post',function($use,$post){
    return in_array(get_post_meta($post->ID,'_wp_page_template',true),array_map(fn($key)=>'templates/page-'.$key.'.php',array_keys(kova_content_data()['pages'])),true)?false:$use;
},10,2);

// Explicit staging2-only homepage setup; no activation side effects.
require __DIR__ . '/home-setup.php';

require __DIR__ . '/phase2-import.php';
require __DIR__ . '/contact-integration.php';
