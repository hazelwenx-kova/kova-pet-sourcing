<?php /* Template Name: KOVA Cat Products */ defined("ABSPATH") || exit; get_header(); ?>
<main id="main"><section class="wrap cat-intro"><div>
<p class="eyebrow"><?php echo kova_text("text_3"); ?></p>
<h1><?php echo kova_text("text_4"); ?><br/><?php echo kova_text("text_5"); ?></h1>
</div>
<div class="cat-intro-copy">
<p class="lead"><?php echo kova_text("text_6"); ?></p>
<p><?php echo kova_text("text_7"); ?></p>
<p class="cat-position-note"><?php echo kova_text("text_8"); ?></p>
</div></section><nav aria-label="On this page" class="wrap cat-page-nav"><a href="<?php echo esc_url(kova_link("link_47")); ?>"><?php echo kova_text("text_48"); ?></a><a href="<?php echo esc_url(kova_link("link_49")); ?>"><?php echo kova_text("text_50"); ?></a><a href="<?php echo esc_url(kova_link("link_51")); ?>"><?php echo kova_text("text_52"); ?></a></nav><section class="wrap cat-catalog" id="cat-categories"><div class="section-head">
<div>
<p class="eyebrow"><?php echo kova_text("text_9"); ?></p>
<h2><?php echo kova_text("text_10"); ?></h2>
</div>
<p><?php echo kova_text("text_11"); ?><br/><?php echo kova_text("text_12"); ?></p>
</div>
<div class="category-grid"><?php kova_render_categories('cat'); ?></div>
<p class="asset-note"><?php echo kova_text("text_13"); ?></p>
<p class="cat-range-link"><?php echo kova_text("text_14"); ?><a class="text-link" href="<?php echo esc_url(kova_link("link_53")); ?>"><?php echo kova_text("text_54"); ?><svg aria-hidden="true" class="arrow-icon" fill="none" viewbox="0 0 20 20"><path d="M5 15 15 5M5 5h10v10" stroke="currentColor" stroke-width="1.2"></path></svg></a></p></section><section class="cat-customisation-band" id="cat-customisation"><div class="wrap cat-customisation-layout">
<div class="cat-customisation-art">
<div aria-label="<?php echo esc_attr(kova_image_alt("image_1","cat-products")); ?>" class="photo-tile cat-new-photo cat-new-materials" role="img" style="<?php echo esc_attr(kova_image_style("image_1","cat-products")); ?>"></div>
<div aria-label="<?php echo esc_attr(kova_image_alt("image_2","cat-products")); ?>" class="photo-tile cat-new-photo cat-new-packaging" role="img" style="<?php echo esc_attr(kova_image_style("image_2","cat-products")); ?>"></div>
</div>
<div>
<p class="eyebrow"><?php echo kova_text("text_15"); ?></p>
<h2><?php echo kova_text("text_16"); ?><br/><?php echo kova_text("text_17"); ?></h2>
<p class="cat-customisation-lead"><?php echo kova_text("text_18"); ?></p>
<div class="cat-options">
<article><h3><?php echo kova_text("text_19"); ?></h3>
<p><?php echo kova_text("text_20"); ?></p></article><article><h3><?php echo kova_text("text_21"); ?></h3>
<p><?php echo kova_text("text_22"); ?></p></article><article><h3><?php echo kova_text("text_23"); ?></h3>
<p><?php echo kova_text("text_24"); ?></p></article><article><h3><?php echo kova_text("text_25"); ?></h3>
<p><?php echo kova_text("text_26"); ?></p></article><article><h3><?php echo kova_text("text_27"); ?></h3>
<p><?php echo kova_text("text_28"); ?></p></article>
</div>
</div>
</div></section><section class="wrap cat-support-layout" id="cat-support"><div>
<p class="eyebrow"><?php echo kova_text("text_29"); ?></p>
<h2><?php echo kova_text("text_30"); ?><br/><?php echo kova_text("text_31"); ?></h2>
<p><?php echo kova_text("text_32"); ?></p>
<a class="text-link" href="<?php echo esc_url(kova_link("link_55")); ?>"><?php echo kova_text("text_56"); ?><svg aria-hidden="true" class="arrow-icon" fill="none" viewbox="0 0 20 20"><path d="M5 15 15 5M5 5h10v10" stroke="currentColor" stroke-width="1.2"></path></svg></a>
</div>
<ol class="cat-support-list">
<li>
<span>01</span><div>
<h3><?php echo kova_text("text_33"); ?></h3>
<p><?php echo kova_text("text_34"); ?></p>
</div>
</li>
<li>
<span>02</span><div>
<h3><?php echo kova_text("text_35"); ?></h3>
<p><?php echo kova_text("text_36"); ?></p>
</div>
</li>
<li>
<span>03</span><div>
<h3><?php echo kova_text("text_37"); ?></h3>
<p><?php echo kova_text("text_38"); ?></p>
</div>
</li>
<li>
<span>04</span><div>
<h3><?php echo kova_text("text_39"); ?></h3>
<p><?php echo kova_text("text_40"); ?></p>
</div>
</li>
<li>
<span>05</span><div>
<h3><?php echo kova_text("text_41"); ?></h3>
<p><?php echo kova_text("text_42"); ?></p>
</div>
</li>
</ol></section><section class="final-cta cat-closing"><div class="wrap">
<p class="eyebrow"><?php echo kova_text("text_43"); ?></p>
<h2><?php echo kova_text("text_44"); ?><br/><?php echo kova_text("text_45"); ?></h2>
<p><?php echo kova_text("text_46"); ?></p>
<a class="button dark" href="<?php echo esc_url(kova_link("link_57")); ?>"><?php echo kova_text("text_58"); ?><svg aria-hidden="true" class="arrow-icon" fill="none" viewbox="0 0 20 20"><path d="M5 15 15 5M5 5h10v10" stroke="currentColor" stroke-width="1.2"></path></svg></a>
</div></section></main>
<?php get_footer(); ?>