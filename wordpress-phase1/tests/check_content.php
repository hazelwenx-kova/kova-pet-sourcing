<?php
/** Local fixture only: KOVA_WP_ROOT=/path/to/wordpress php check_content.php */
if (PHP_SAPI !== 'cli') exit;
$root=getenv('KOVA_WP_ROOT');
if(!$root)throw new RuntimeException('Set KOVA_WP_ROOT to an isolated local WordPress fixture.');
require rtrim($root,'/').'/wp-load.php';
if(wp_get_environment_type()!=='local')throw new RuntimeException('This edit test is restricted to WP_ENVIRONMENT_TYPE=local.');
wp_set_current_user(1);
function ensure($condition,$message){if(!$condition)throw new RuntimeException($message);}
$initial=kova_run_import();$repeat=kova_run_import();
ensure(!$repeat['pages_created']&&!$repeat['categories_created']&&!$repeat['media_created'],'Repeated import duplicated content');
$cats=kova_categories('cat');ensure(count($cats)===8,'Expected eight initial Cat categories');
$defaults=kova_content_data()['categories'];
foreach($cats as $i=>$cat){
 ensure($cat->post_title===$defaults[$i]['title'],'Category order/title mismatch');
 foreach(['description','options','customization'] as $key)ensure(get_post_meta($cat->ID,'kova_'.$key,true)===$defaults[$i][$key],'Original category copy mismatch');
 ensure(is_file(get_attached_file((int)get_post_meta($cat->ID,'kova_image',true))),'Missing independent media image');
}
$id=$cats[7]->ID;$old=[];foreach(['kova_order','kova_visible','kova_description'] as $name)$old[$name]=get_post_meta($id,$name,true);
try{
 update_post_meta($id,'kova_order',0);ensure(kova_categories('cat')[0]->ID===$id,'Sort order did not apply');
 update_post_meta($id,'kova_visible',0);ensure(count(kova_categories('cat'))===7,'Hidden category still displayed');
 update_post_meta($id,'kova_description','Local test edit');kova_run_import();
 ensure(get_post_meta($id,'kova_description',true)==='Local test edit','Import overwrote copy edit');
 ensure(!(bool)get_post_meta($id,'kova_visible',true),'Import overwrote visibility');
 ensure((int)get_post_meta($id,'kova_order',true)===0,'Import overwrote sort order');
 wp_set_current_user(0);$blocked=false;
 try{kova_run_import();}catch(RuntimeException $e){$blocked=true;}
 ensure($blocked,'Import allowed anonymous user');
}finally{
 wp_set_current_user(1);foreach($old as $name=>$value)update_post_meta($id,$name,$value);
}
ensure(function_exists('acf_add_local_field_group'),'ACF Free is not active');
$groups=acf_get_field_groups();ensure(count($groups)>=4,'ACF page/category groups missing');
echo 'PASS: initial/repeated import, exact eight category defaults, independent media, ordering, visibility, edit preservation, access control and ACF groups.'.PHP_EOL;
