<?php
/** Local fixture only. Temporarily exercise conflicts, idempotence and Dog edits, restoring test edits. */
if(PHP_SAPI!=='cli')exit;
$root=getenv('KOVA_WP_ROOT');if(!$root)throw new RuntimeException('Set local KOVA_WP_ROOT.');
require rtrim($root,'/').'/wp-load.php';if(wp_get_environment_type()!=='local')throw new RuntimeException('Local only.');wp_set_current_user(1);
function ensure_phase2($ok,$message){if(!$ok)throw new RuntimeException($message);}
$first=kova_phase1_data();$protected=[];
foreach(['home','cat-products'] as $key){$id=kova_seed_find('page','page:'.$key);ensure_phase2($id>0,'Missing Phase 1 fixture');$protected[$id]=['post'=>get_post($id,ARRAY_A),'meta'=>get_post_meta($id)];}
foreach(kova_categories('cat') as $cat)$protected[$cat->ID]=['post'=>get_post($cat->ID,ARRAY_A),'meta'=>get_post_meta($cat->ID)];
$options=[];foreach(['page_on_front','show_on_front','page_for_posts','permalink_structure','_kova_home_setup_v1'] as $name)$options[$name]=get_option($name);
// On the first fixture run, a conflicting old page must stop the whole import before any new records.
$conflict=0;$before=wp_count_posts('page');
if(!kova_seed_find('page','page:dog-products')){
 $conflict=wp_insert_post(['post_type'=>'page','post_status'=>'draft','post_title'=>'Existing untouched backup','post_name'=>'dog-products']);
 try{$failed=false;try{kova_phase2_import();}catch(RuntimeException $e){$failed=true;}ensure_phase2($failed,'Slug conflict did not block import');ensure_phase2(!kova_seed_find('page','page:contact'),'Conflict caused partial page creation');}
 finally{wp_delete_post($conflict,true);}
}
$initial=kova_phase2_import();$repeat=kova_phase2_import();
ensure_phase2(!$repeat['pages_created']&&!$repeat['categories_created']&&!$repeat['media_created'],'Repeat duplicated records');
foreach($protected as $id=>$snapshot)ensure_phase2(get_post($id,ARRAY_A)===$snapshot['post']&&get_post_meta($id)===$snapshot['meta'],'Phase 1 content/metadata changed');
foreach($options as $name=>$value)ensure_phase2(get_option($name)===$value,'Changed settings: '.$name);
$dogs=kova_categories('dog');ensure_phase2(count($dogs)===8,'Expected eight Dog categories');
$defaults=kova_phase2_data()['categories'];foreach($dogs as $i=>$dog){
 ensure_phase2($dog->post_title===$defaults[$i]['title'],'Dog order/title mismatch');
 foreach(['description','options','customization'] as $key)ensure_phase2(get_post_meta($dog->ID,'kova_'.$key,true)===$defaults[$i][$key],'Dog copy mismatch');
 ensure_phase2(is_file(get_attached_file((int)get_post_meta($dog->ID,'kova_image',true))),'Missing dog image');
 ensure_phase2(!has_term('cat','kova_species',$dog->ID),'Dog assigned to Cat');
}
$id=$dogs[7]->ID;$saved=get_post_meta($id);
try{
 update_post_meta($id,'kova_order',0);ensure_phase2(kova_categories('dog')[0]->ID===$id,'Dog sorting failed');
 update_post_meta($id,'kova_visible',0);update_post_meta($id,'kova_description','Local manual edit');
 ensure_phase2(count(kova_categories('dog'))===7&&count(kova_categories('cat'))===8,'Species visibility leaked');
 kova_phase2_import();ensure_phase2(get_post_meta($id,'kova_description',true)==='Local manual edit'&&!get_post_meta($id,'kova_visible',true),'Import overwrote edit');
}finally{foreach(['kova_order','kova_visible','kova_description'] as $name)update_post_meta($id,$name,$saved[$name][0]);}
// Explicit adoption of an existing matching page requires confirmation and preserves all fields.
$adoptId=kova_seed_find('page','page:about-kova');$adoptMeta=get_post_meta($adoptId);
try{
 delete_post_meta($adoptId,'_kova_seed_key');$blocked=false;try{kova_phase2_import();}catch(RuntimeException $e){$blocked=true;}
 ensure_phase2($blocked,'Unconfirmed existing page adoption allowed');
 $associated=kova_phase2_import([$adoptId]);ensure_phase2($associated['associated']===1,'Explicit association failed');
 foreach($adoptMeta as $name=>$value)ensure_phase2(get_post_meta($adoptId,$name) === $value,'Association overwrote values');
}finally{update_post_meta($adoptId,'_kova_seed_key','page:about-kova');}
// An explicit empty field must not be restored on a completed page.
$pageId=kova_seed_find('page','page:about-kova');$field=array_key_first(kova_phase2_data()['pages']['about-kova']['fields']);$old=get_post_meta($pageId,$field,true);
try{update_post_meta($pageId,$field,'');kova_phase2_import();ensure_phase2(get_post_meta($pageId,$field,true)==='','Empty manual edit overwritten');}
finally{update_post_meta($pageId,$field,$old);}
$contactId=kova_seed_find('page','page:contact');ensure_phase2(!kova_contact_form_state($contactId)['ready'],'Contact auto enabled');
$locked=false;wp_set_current_user(0);try{kova_phase2_import();}catch(RuntimeException $e){$locked=true;}finally{wp_set_current_user(1);}ensure_phase2($locked,'Anonymous import allowed');
echo json_encode(['initial'=>$initial,'repeat'=>$repeat]).PHP_EOL;
echo 'PASS: five-page/eight-Dog import, conflict preflight, idempotence, original Dog defaults, edit/order/visibility preservation, Cat/Home/settings unchanged, admin guard and disabled integration.'.PHP_EOL;
