<?php defined('ABSPATH') || exit; ?><!doctype html>
<html <?php language_attributes(); ?> class="<?php echo esc_attr(kova_layout_class()); ?>"><head>
<meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="<?php echo esc_url(get_template_directory_uri().'/assets/favicon.svg'); ?>" type="image/svg+xml">
<?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip" href="#main">Skip to content</a><div class="top-note">Independent sourcing for thoughtful pet brands <span>Shenzhen, China</span>
</div><header class="site-header wrap"><a aria-label="KOVA Sourcing home" class="logo" href="<?php echo esc_url(home_url('/')); ?>">kova<span>.</span><small>SOURCING</small></a><button aria-controls="navigation" aria-expanded="false" class="menu-toggle">Menu <span aria-hidden="true"><svg aria-hidden="true" class="arrow-icon" viewbox="0 0 20 20"><path d="M3 5h14M3 10h14M3 15h14" fill="none" stroke="currentColor" stroke-width="1.2"></path></svg></span></button><nav aria-label="Main navigation" id="navigation"><?php kova_navigation(); ?></nav></header>
