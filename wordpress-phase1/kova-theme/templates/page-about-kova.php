<?php /* Template Name: KOVA About KOVA */ defined("ABSPATH") || exit; get_header(); ?>
<main id="main"><section class="wrap about-introduction"><p class="eyebrow"><?php echo kova_text("text_2"); ?></p>
<h1><?php echo kova_text("text_3"); ?><br/><?php echo kova_text("text_4"); ?></h1>
<p class="lead"><?php echo kova_text("text_5"); ?></p></section><section class="wrap about-identity"><figure><img alt="<?php echo esc_attr(kova_image_alt("image_1","about-kova")); ?>" fetchpriority="high" height="1024" src="<?php echo esc_url(kova_image_url("image_1","about-kova")); ?>" width="1536"/><figcaption><?php echo kova_text("text_6"); ?></figcaption></figure><div>
<p class="eyebrow"><?php echo kova_text("text_7"); ?></p>
<h2><?php echo kova_text("text_8"); ?><br/><?php echo kova_text("text_9"); ?></h2>
<p><?php echo kova_text("text_10"); ?></p>
<p><?php echo kova_text("text_11"); ?></p>
<p><?php echo kova_text("text_12"); ?></p>
<p class="identity-scope"><?php echo kova_text("text_13"); ?></p>
<a class="text-link" href="<?php echo esc_url(kova_link("link_49")); ?>"><?php echo kova_text("text_50"); ?><svg aria-hidden="true" class="arrow-icon" fill="none" viewbox="0 0 20 20"><path d="M5 15 15 5M5 5h10v10" stroke="currentColor" stroke-width="1.2"></path></svg></a>
</div></section><section class="about-background-band"><div class="wrap about-background">
<div>
<p class="eyebrow"><?php echo kova_text("text_14"); ?></p>
<h2><?php echo kova_text("text_15"); ?></h2>
<p><?php echo kova_text("text_16"); ?></p>
<p class="background-context"><?php echo kova_text("text_17"); ?></p>
</div>
<div class="background-experience">
<p class="experience-caption"><?php echo kova_text("text_18"); ?><span><?php echo kova_text("text_19"); ?></span><?php echo kova_text("text_20"); ?></p>
<ul>
<li>
<h3><?php echo kova_text("text_21"); ?></h3>
<p><?php echo kova_text("text_22"); ?></p>
</li>
<li>
<h3><?php echo kova_text("text_23"); ?></h3>
<p><?php echo kova_text("text_24"); ?></p>
</li>
<li>
<h3><?php echo kova_text("text_25"); ?></h3>
<p><?php echo kova_text("text_26"); ?></p>
</li>
<li>
<h3><?php echo kova_text("text_27"); ?></h3>
<p><?php echo kova_text("text_28"); ?></p>
</li>
</ul>
</div>
</div></section><section class="wrap about-advantages"><div>
<p class="eyebrow"><?php echo kova_text("text_29"); ?></p>
<h2><?php echo kova_text("text_30"); ?><br/><?php echo kova_text("text_31"); ?></h2>
<p><?php echo kova_text("text_32"); ?></p>
</div>
<div class="about-advantage-grid">
<article><span class="advantage-index">01</span><h3><?php echo kova_text("text_33"); ?></h3>
<p><?php echo kova_text("text_34"); ?></p></article><article><span class="advantage-index">02</span><h3><?php echo kova_text("text_35"); ?></h3>
<p><?php echo kova_text("text_36"); ?></p></article><article><span class="advantage-index">03</span><h3><?php echo kova_text("text_37"); ?></h3>
<p><?php echo kova_text("text_38"); ?></p></article><article><span class="advantage-index">04</span><h3><?php echo kova_text("text_39"); ?></h3>
<p><?php echo kova_text("text_40"); ?></p></article>
</div></section><section class="about-approach-band"><div class="wrap about-approach">
<div>
<p class="eyebrow"><?php echo kova_text("text_41"); ?></p>
<h2><?php echo kova_text("text_42"); ?><br/><?php echo kova_text("text_43"); ?></h2>
</div>
<div>
<p><?php echo kova_text("text_44"); ?></p>
<p><?php echo kova_text("text_45"); ?></p>
<a class="text-link" href="<?php echo esc_url(kova_link("link_51")); ?>"><?php echo kova_text("text_52"); ?><svg aria-hidden="true" class="arrow-icon" fill="none" viewbox="0 0 20 20"><path d="M5 15 15 5M5 5h10v10" stroke="currentColor" stroke-width="1.2"></path></svg></a>
</div>
</div></section><section class="final-cta about-closing"><div class="wrap">
<p class="eyebrow"><?php echo kova_text("text_46"); ?></p>
<h2><?php echo kova_text("text_47"); ?></h2>
<p><?php echo kova_text("text_48"); ?></p>
<a class="button dark" href="<?php echo esc_url(kova_link("link_53")); ?>"><?php echo kova_text("text_54"); ?><svg aria-hidden="true" class="arrow-icon" fill="none" viewbox="0 0 20 20"><path d="M5 15 15 5M5 5h10v10" stroke="currentColor" stroke-width="1.2"></path></svg></a>
</div></section></main>
<?php get_footer(); ?>