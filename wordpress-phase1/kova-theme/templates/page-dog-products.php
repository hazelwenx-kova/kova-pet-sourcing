<?php /* Template Name: KOVA Dog Products */ defined("ABSPATH") || exit; get_header(); ?>
<main id="main"><section class="wrap dog-intro"><div>
<p class="eyebrow"><?php echo kova_text("text_2"); ?></p>
<h1><?php echo kova_text("text_3"); ?><br/><?php echo kova_text("text_4"); ?></h1>
</div>
<div class="dog-intro-copy">
<p class="lead"><?php echo kova_text("text_5"); ?></p>
<p><?php echo kova_text("text_6"); ?></p>
<p class="dog-position-note"><?php echo kova_text("text_7"); ?></p>
</div></section><nav aria-label="On this page" class="wrap dog-page-nav"><a href="<?php echo esc_url(kova_link("link_46")); ?>"><?php echo kova_text("text_47"); ?></a><a href="<?php echo esc_url(kova_link("link_48")); ?>"><?php echo kova_text("text_49"); ?></a><a href="<?php echo esc_url(kova_link("link_50")); ?>"><?php echo kova_text("text_51"); ?></a></nav><section class="wrap dog-catalog" id="dog-categories"><div class="section-head">
<div>
<p class="eyebrow"><?php echo kova_text("text_8"); ?></p>
<h2><?php echo kova_text("text_9"); ?></h2>
</div>
<p><?php echo kova_text("text_10"); ?><br/><?php echo kova_text("text_11"); ?></p>
</div>
<div class="category-grid"><?php kova_render_categories('dog'); ?></div>
<p class="asset-note"><?php echo kova_text("text_12"); ?></p>
<p class="dog-range-link"><?php echo kova_text("text_13"); ?><a class="text-link" href="<?php echo esc_url(kova_link("link_52")); ?>"><?php echo kova_text("text_53"); ?><svg aria-hidden="true" class="arrow-icon" fill="none" viewbox="0 0 20 20"><path d="M5 15 15 5M5 5h10v10" stroke="currentColor" stroke-width="1.2"></path></svg></a></p></section><section class="dog-customisation-band" id="dog-customisation"><div class="wrap dog-customisation-layout">
<div class="dog-customisation-art">
<div aria-label="<?php echo esc_attr(kova_image_alt("image_1","dog-products")); ?>" class="photo-tile dog-materials-photo" role="img" style="<?php echo esc_attr(kova_image_style("image_1","dog-products")); ?>"></div>
</div>
<div>
<p class="eyebrow"><?php echo kova_text("text_14"); ?></p>
<h2><?php echo kova_text("text_15"); ?><br/><?php echo kova_text("text_16"); ?></h2>
<p class="dog-customisation-lead"><?php echo kova_text("text_17"); ?></p>
<div class="dog-options">
<article><h3><?php echo kova_text("text_18"); ?></h3>
<p><?php echo kova_text("text_19"); ?></p></article><article><h3><?php echo kova_text("text_20"); ?></h3>
<p><?php echo kova_text("text_21"); ?></p></article><article><h3><?php echo kova_text("text_22"); ?></h3>
<p><?php echo kova_text("text_23"); ?></p></article><article><h3><?php echo kova_text("text_24"); ?></h3>
<p><?php echo kova_text("text_25"); ?></p></article><article><h3><?php echo kova_text("text_26"); ?></h3>
<p><?php echo kova_text("text_27"); ?></p></article>
</div>
</div>
</div></section><section class="wrap dog-support-layout" id="dog-support"><div>
<p class="eyebrow"><?php echo kova_text("text_28"); ?></p>
<h2><?php echo kova_text("text_29"); ?><br/><?php echo kova_text("text_30"); ?></h2>
<p><?php echo kova_text("text_31"); ?></p>
<a class="text-link" href="<?php echo esc_url(kova_link("link_54")); ?>"><?php echo kova_text("text_55"); ?><svg aria-hidden="true" class="arrow-icon" fill="none" viewbox="0 0 20 20"><path d="M5 15 15 5M5 5h10v10" stroke="currentColor" stroke-width="1.2"></path></svg></a>
</div>
<ol class="dog-support-list">
<li>
<span>01</span><div>
<h3><?php echo kova_text("text_32"); ?></h3>
<p><?php echo kova_text("text_33"); ?></p>
</div>
</li>
<li>
<span>02</span><div>
<h3><?php echo kova_text("text_34"); ?></h3>
<p><?php echo kova_text("text_35"); ?></p>
</div>
</li>
<li>
<span>03</span><div>
<h3><?php echo kova_text("text_36"); ?></h3>
<p><?php echo kova_text("text_37"); ?></p>
</div>
</li>
<li>
<span>04</span><div>
<h3><?php echo kova_text("text_38"); ?></h3>
<p><?php echo kova_text("text_39"); ?></p>
</div>
</li>
<li>
<span>05</span><div>
<h3><?php echo kova_text("text_40"); ?></h3>
<p><?php echo kova_text("text_41"); ?></p>
</div>
</li>
</ol></section><section class="final-cta dog-closing"><div class="wrap">
<p class="eyebrow"><?php echo kova_text("text_42"); ?></p>
<h2><?php echo kova_text("text_43"); ?><br/><?php echo kova_text("text_44"); ?></h2>
<p><?php echo kova_text("text_45"); ?></p>
<a class="button dark" href="<?php echo esc_url(kova_link("link_56")); ?>"><?php echo kova_text("text_57"); ?><svg aria-hidden="true" class="arrow-icon" fill="none" viewbox="0 0 20 20"><path d="M5 15 15 5M5 5h10v10" stroke="currentColor" stroke-width="1.2"></path></svg></a>
</div></section></main>
<?php get_footer(); ?>