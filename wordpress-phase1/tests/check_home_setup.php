<?php
/** CLI local fixture only; domain values are simulated in process, never connected remotely. */
if(PHP_SAPI!=='cli')exit;
$root=getenv('KOVA_WP_ROOT');if(!$root)throw new RuntimeException('Set KOVA_WP_ROOT to an isolated local fixture.');
require rtrim($root,'/').'/wp-load.php';
if(wp_get_environment_type()!=='local')throw new RuntimeException('Local fixture only.');
function expect_home($ok,$message){if(!$ok)throw new RuntimeException($message);}
$options=['show_on_front','page_on_front','page_for_posts','_kova_home_setup_v1','_kova_home_setup_lock'];$before=[];
foreach($options as $key)$before[$key]=get_option($key,null);
$hostBefore=$_SERVER['HTTP_HOST']??null;$userBefore=get_current_user_id();
$domain='https://staging2.kovasourcing.com';
$homeFilter=function()use(&$domain){return $domain;};
add_filter('pre_option_home',$homeFilter);add_filter('pre_option_siteurl',$homeFilter);
$pagesBefore=get_posts(['post_type'=>['page','post'],'post_status'=>'any','numberposts'=>-1]);
$metaBefore=[];foreach($pagesBefore as $p)$metaBefore[$p->ID]=get_post_meta($p->ID);
function blocked_home($id,$nonce,$confirmation=true){$failed=false;try{kova_home_setup_apply($id,$nonce,$confirmation);}catch(RuntimeException $e){$failed=true;}expect_home($failed,'Expected setup rejection');}
try{
 wp_set_current_user(1);$_SERVER['HTTP_HOST']='staging2.kovasourcing.com';
 delete_option('_kova_home_setup_v1');delete_option('_kova_home_setup_lock');
 $candidates=kova_home_setup_candidates();expect_home(count($candidates)>0,'Missing fixture Home');$id=$candidates[0]->ID;
 update_option('show_on_front','posts');update_option('page_on_front',0);
 $postsBefore=get_option('page_for_posts');$nonce=wp_create_nonce('kova_staging2_home_setup');
 blocked_home($id,'invalid');blocked_home($id,$nonce,false);blocked_home(999999,$nonce);
 $catPages=get_posts(['post_type'=>'page','post_status'=>'publish','meta_key'=>'_wp_page_template','meta_value'=>'templates/page-cat-products.php']);
 if($catPages){blocked_home($catPages[0]->ID,$nonce);update_option('page_for_posts',$catPages[0]->ID);$postsBefore=get_option('page_for_posts');}
 $roles=wp_get_current_user()->roles;wp_get_current_user()->roles=['editor'];blocked_home($id,$nonce);wp_get_current_user()->roles=$roles;
 add_option('_kova_home_setup_lock',time(),'',false);blocked_home($id,$nonce);delete_option('_kova_home_setup_lock');
 $_SERVER['REQUEST_METHOD']='GET';require_once ABSPATH.'wp-admin/includes/template.php';
 ob_start();kova_home_setup_screen();$initialScreen=ob_get_clean();
 expect_home(str_contains($initialScreen,'ID '.$id)&&str_contains($initialScreen,'name="_wpnonce"')&&str_contains($initialScreen,'name="kova_confirm"'),'Confirmation form missing ID/nonce/checkbox');
 $domain='https://kovasourcing.com';blocked_home($id,$nonce);
 $domain='https://staging3.kovasourcing.com';blocked_home($id,$nonce);
 $domain='http://staging2.kovasourcing.com';blocked_home($id,$nonce);
 $domain='https://staging2.kovasourcing.com';$_SERVER['HTTP_HOST']='kovasourcing.com';blocked_home($id,$nonce);
 $_SERVER['HTTP_HOST']='staging2.kovasourcing.com';wp_set_current_user(0);blocked_home($id,$nonce);wp_set_current_user(1);
 expect_home(get_option('show_on_front')==='posts'&&(int)get_option('page_on_front')===0,'Rejected requests changed config');
 $state=kova_home_setup_apply($id,$nonce,true);
 expect_home($state['show_on_front']==='page'&&$state['page_on_front']===$id&&$state['page_is_published_home']&&$state['completed'],'Setup verification failed');
 expect_home(get_option('page_for_posts')===$postsBefore,'Changed page_for_posts');
 expect_home(get_permalink($id)===home_url('/'),'Home permalink is not root');
 $query=new WP_Query();$query->query([]);expect_home($query->get_queried_object_id()===$id&&$query->is_front_page(),'Root query did not select KOVA Home');
 blocked_home($id,$nonce);expect_home((int)get_option('page_on_front')===$id,'Repeated request changed setup');
 foreach($pagesBefore as $p)expect_home(get_post_meta($p->ID)===$metaBefore[$p->ID],'Modified page metadata/ACF');
 expect_home(count(get_posts(['post_type'=>['page','post'],'post_status'=>'any','numberposts'=>-1]))===count($pagesBefore),'Deleted/added pages or posts');
 $_SERVER['REQUEST_METHOD']='GET';ob_start();kova_home_setup_screen();$screen=ob_get_clean();
 expect_home(str_contains($screen,'page_for_posts')&&str_contains($screen,'已完成')&&!str_contains($screen,'name="kova_home_id"'),'Completed admin screen incorrect');
 echo 'PASS: staging2/HTTPS/request-host restrictions, admin, nonce, confirmation, invalid page, one-time guard, saved settings, root query/permalink, unchanged page_for_posts/ACF/posts and completion screen.'.PHP_EOL;
}finally{
 remove_filter('pre_option_home',$homeFilter);remove_filter('pre_option_siteurl',$homeFilter);
 foreach($before as $key=>$value){if($value===null)delete_option($key);else update_option($key,$value);}
 if($hostBefore===null)unset($_SERVER['HTTP_HOST']);else $_SERVER['HTTP_HOST']=$hostBefore;
 wp_set_current_user($userBefore);
}
