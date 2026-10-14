<?php
/** Configurable real Fluent Forms embed. No demo ID, no static submission, no SMTP setup. */
defined('ABSPATH') || exit;
add_action('acf/init',function(){
    acf_add_local_field_group([
        'key'=>'group_kova_contact_integration','title'=>'KOVA — Fluent Forms integration',
        'fields'=>[
            ['key'=>'field_kova_fluent_form_id','name'=>'kova_fluent_form_id','label'=>'正式询盘表单 ID','type'=>'number','min'=>0,'step'=>1,'default_value'=>0,'instructions'=>'在 Fluent Forms → All Forms 创建正式 Sourcing Inquiry，复制它的 ID。不要绑定 Contact Form Demo。'],
            ['key'=>'field_kova_fluent_enabled','name'=>'kova_fluent_enabled','label'=>'启用已配置的正式表单','type'=>'true_false','ui'=>1,'default_value'=>0,'instructions'=>'仅在你确认字段、垃圾信息防护与邮件通知配置后启用；提交和邮件送达须在 staging2 测试。'],
            ['key'=>'field_kova_contact_setup_notice','name'=>'kova_contact_setup_notice','label'=>'未配置时的开发提示','type'=>'textarea','rows'=>3,'new_lines'=>'','default_value'=>'Online enquiries are not enabled yet. Please email info@kovasourcing.com to discuss your sourcing requirements.'],
            ['key'=>'field_kova_contact_privacy','name'=>'kova_contact_privacy','label'=>'表单隐私说明','type'=>'textarea','rows'=>3,'new_lines'=>'','default_value'=>'Inquiry details are used to review and respond to your sourcing request. Please include only information needed for the inquiry.'],
        ],
        'location'=>[[['param'=>'page_template','operator'=>'==','value'=>'templates/page-contact.php']]],
    ]);
});
function kova_contact_form_state(int $pageId): array {
    $id=(int)get_post_meta($pageId,'kova_fluent_form_id',true);
    if(!get_post_meta($pageId,'kova_fluent_enabled',true))return ['ready'=>false,'reason'=>'disabled','id'=>$id];
    if($id<1)return ['ready'=>false,'reason'=>'missing_id','id'=>$id];
    if(!shortcode_exists('fluentform') || !function_exists('wpFluent'))return ['ready'=>false,'reason'=>'plugin_unavailable','id'=>$id];
    // Fluent Forms owns the record and all submission/validation handlers.
    try{$form=wpFluent()->table('fluentform_forms')->find($id);}catch(Throwable $e){return ['ready'=>false,'reason'=>'unavailable','id'=>$id];}
    if(!$form || ($form->status??'')!=='published')return ['ready'=>false,'reason'=>'form_unavailable','id'=>$id];
    if(stripos((string)$form->title,'demo')!==false)return ['ready'=>false,'reason'=>'demo_rejected','id'=>$id];
    return ['ready'=>true,'reason'=>'configured','id'=>$id];
}
function kova_contact_render(int $pageId): void {
    $state=kova_contact_form_state($pageId);
    if(!$state['ready']){
        $notice=get_post_meta($pageId,'kova_contact_setup_notice',true);
        if($notice==='')$notice='Online enquiries are not enabled yet. Please email info@kovasourcing.com to discuss your sourcing requirements.';
        echo '<div class="submission-setup" role="note"><h3>Development preview</h3><p>'.esc_html($notice).'</p><a class="text-link" href="mailto:info@kovasourcing.com">info@kovasourcing.com</a></div>';
        if(current_user_can('manage_options')){
            $messages=['disabled'=>'尚未启用正式表单。','missing_id'=>'尚未填写正式表单 ID。','plugin_unavailable'=>'Fluent Forms 未启用或其集成接口不可用。','unavailable'=>'无法读取表单配置。','form_unavailable'=>'表单不存在或未发布。','demo_rejected'=>'不能绑定 Demo 表单，请创建正式询盘表单。'];
            echo '<p class="small">管理员检查：'.esc_html($messages[$state['reason']]??'请检查配置。').'</p>';
        }
        return;
    }
    echo '<p class="selected-category-note" id="kova-selected-category" hidden></p>';
    // The allow-listed integer is the only shortcode input. Success/errors are entirely owned by Fluent Forms.
    echo do_shortcode('[fluentform id="'.(int)$state['id'].'"]');
    $privacy=get_post_meta($pageId,'kova_contact_privacy',true);
    if($privacy==='')$privacy='Inquiry details are used to review and respond to your sourcing request. Please include only information needed for the inquiry.';
    echo '<p class="privacy">'.esc_html($privacy).'</p>';
}
