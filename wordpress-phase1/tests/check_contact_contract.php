<?php
/** Local integration contract with an explicit fake provider, NOT a Fluent Forms submission/email test. */
if(PHP_SAPI!=='cli')exit;
$root=getenv('KOVA_WP_ROOT');if(!$root)throw new RuntimeException('Set local KOVA_WP_ROOT.');
require rtrim($root,'/').'/wp-load.php';if(wp_get_environment_type()!=='local')throw new RuntimeException('Local only.');
if(shortcode_exists('fluentform'))throw new RuntimeException('Use a fixture without real Fluent Forms for this mocked-provider contract.');
class KovaTestFormProvider {
 function table($table){if($table!=='fluentform_forms')throw new RuntimeException('Unexpected provider table');return $this;}
 function find($id){return $GLOBALS['kova_contract_forms'][$id]??null;}
}
function wpFluent(){return new KovaTestFormProvider();}
function contract_ensure($ok,$message){if(!$ok)throw new RuntimeException($message);}
$id=wp_insert_post(['post_type'=>'page','post_status'=>'draft','post_title'=>'Local isolated form contract']);
$shortcodeBefore=$GLOBALS['shortcode_tags']['fluentform']??null;
try{
 contract_ensure(!kova_contact_form_state($id)['ready'],'Empty configuration enabled');
 update_post_meta($id,'kova_fluent_enabled',1);contract_ensure(kova_contact_form_state($id)['reason']==='missing_id','Missing ID allowed');
 update_post_meta($id,'kova_fluent_form_id',12);contract_ensure(kova_contact_form_state($id)['reason']==='plugin_unavailable','Missing provider allowed');
 add_shortcode('fluentform',function($atts){$GLOBALS['kova_contract_id']=$atts['id'];return '<form data-contract-test="1"></form>';});
 contract_ensure(kova_contact_form_state($id)['reason']==='form_unavailable','Nonexistent form allowed');
 $GLOBALS['kova_contract_forms'][12]=(object)['status'=>'published','title'=>'Contact Form Demo'];
 contract_ensure(kova_contact_form_state($id)['reason']==='demo_rejected','Demo allowed');
 $GLOBALS['kova_contract_forms'][12]=(object)['status'=>'unpublished','title'=>'Sourcing Inquiry'];contract_ensure(!kova_contact_form_state($id)['ready'],'Unpublished form allowed');
 $GLOBALS['kova_contract_forms'][12]=(object)['status'=>'published','title'=>'Sourcing Inquiry'];contract_ensure(kova_contact_form_state($id)['ready'],'Configured provider blocked');
 ob_start();kova_contact_render($id);$html=ob_get_clean();
 contract_ensure($GLOBALS['kova_contract_id']==='12'&&str_contains($html,'data-contract-test')&&str_contains($html,'privacy'),'Not delegated to shortcode');
 contract_ensure(!str_contains($html,'inquiry-form')&&!str_contains($html,'form-status'),'Legacy simulated form leaked');
 update_post_meta($id,'kova_fluent_enabled',0);ob_start();kova_contact_render($id);$html=ob_get_clean();
 contract_ensure(!str_contains($html,'<form')&&str_contains($html,'Development preview'),'Disabled embed still presents form');
 echo 'PASS: empty/missing/inactive/unpublished/demo guards, explicit enable, integer shortcode delegation, privacy and honest disabled notice. Mock contract only; no actual submission or email tested.'.PHP_EOL;
}finally{
 if($shortcodeBefore)$GLOBALS['shortcode_tags']['fluentform']=$shortcodeBefore;else remove_shortcode('fluentform');
 wp_delete_post($id,true);
}
