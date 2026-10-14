<?php
/** Local-only menu integration test, no external site access. */
if(PHP_SAPI!=='cli')exit;
$root=getenv('KOVA_WP_ROOT');if(!$root)throw new RuntimeException('Set local KOVA_WP_ROOT.');
require rtrim($root,'/').'/wp-load.php';
if(wp_get_environment_type()!=='local')throw new RuntimeException('Local fixture only.');
require_once ABSPATH.'wp-admin/includes/plugin.php';
wp_set_current_user(1);
$GLOBALS['admin_page_hooks']['tools.php']='tools';
$GLOBALS['submenu']=[];$_SERVER['HTTP_HOST']='127.0.0.1:8100';$_SERVER['REQUEST_METHOD']='GET';
$before=[];foreach(['show_on_front','page_on_front','page_for_posts','_kova_home_setup_v1'] as $name)$before[$name]=get_option($name);
do_action('admin_menu');
$items=array_filter($GLOBALS['submenu']['tools.php']??[],fn($row)=>$row[2]==='kova-home-setup');
if(count($items)!==1)throw new RuntimeException('Tool is hidden on mismatching domain.');
ob_start();kova_home_setup_screen();$html=ob_get_clean();
if(!str_contains($html,'HTTP_HOST')||!str_contains($html,'0.1.2')||!str_contains($html,'操作已禁用')||str_contains($html,'name="kova_home_id"'))throw new RuntimeException('Read-only diagnostic screen incorrect.');
foreach($before as $name=>$value)if(get_option($name)!==$value)throw new RuntimeException('Diagnostics changed configuration.');
wp_set_current_user(0);$GLOBALS['submenu']=[];do_action('admin_menu');
foreach($GLOBALS['submenu']['tools.php']??[] as $row)if($row[2]==='kova-home-setup')throw new RuntimeException('Anonymous menu visibility.');
echo 'PASS: real admin_menu registration on mismatching host, administrator-only visibility, read-only diagnostic screen and unchanged configuration.'.PHP_EOL;
