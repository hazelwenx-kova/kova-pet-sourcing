<?php
/** Temporary local admin-edit pages only. Never touches imported pages or existing backups. */
if(PHP_SAPI!=='cli')exit;
require rtrim(getenv('KOVA_WP_ROOT')?:'','/').'/wp-load.php';
if(wp_get_environment_type()!=='local')throw new RuntimeException('Local only');wp_set_current_user(1);
if(($argv[1]??'')==='create'){
 $ids=[];foreach(kova_phase2_data()['pages'] as $key=>$page){
  $id=wp_insert_post(['post_type'=>'page','post_status'=>'draft','post_title'=>'Local Phase2 editor fixture '.$key]);
  update_post_meta($id,'_wp_page_template','templates/page-'.$key.'.php');update_post_meta($id,'_kova_editor_fixture',1);$ids[$key]=$id;
 }
 $expiration=time()+3600;
 echo json_encode(['ids'=>$ids,'cookie'=>wp_generate_auth_cookie(1,$expiration,'logged_in'),'name'=>LOGGED_IN_COOKIE,'auth'=>wp_generate_auth_cookie(1,$expiration,'auth'),'auth_name'=>AUTH_COOKIE]);
}elseif(($argv[1]??'')==='cleanup'){
 foreach(get_posts(['post_type'=>'page','post_status'=>'any','meta_key'=>'_kova_editor_fixture','meta_value'=>1,'numberposts'=>-1]) as $page)wp_delete_post($page->ID,true);
}
