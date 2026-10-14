<?php /* Template Name: KOVA Contact */ defined("ABSPATH") || exit; get_header(); ?>
<main id="main"><section class="page-intro wrap"><p class="eyebrow"><?php echo kova_text("text_1"); ?></p>
<h1><?php echo kova_text("text_2"); ?><br/><?php echo kova_text("text_3"); ?></h1>
<p class="lead"><?php echo kova_text("text_4"); ?></p></section><section class="wrap contact-layout"><aside><h2><?php echo kova_text("text_5"); ?><br/><?php echo kova_text("text_6"); ?></h2>
<p><?php echo kova_text("text_7"); ?></p>
<section aria-labelledby="get-in-touch" class="contact-information"><h3 id="get-in-touch"><?php echo kova_text("text_8"); ?></h3>
<dl>
<dt><?php echo kova_text("text_9"); ?></dt>
<dd class="business-email"><a href="<?php echo esc_url(kova_link("link_13")); ?>"><?php echo kova_text("text_14"); ?></a></dd>
<dt><?php echo kova_text("text_10"); ?></dt>
<dd><?php echo kova_text("text_11"); ?></dd>
</dl></section><p class="small"><?php echo kova_text("text_12"); ?></p></aside><div class="kova-fluent-wrapper"><?php kova_render_inquiry_form(); ?></div></section></main>
<?php get_footer(); ?>