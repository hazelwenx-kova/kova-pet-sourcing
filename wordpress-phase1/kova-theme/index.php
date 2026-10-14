<?php defined('ABSPATH') || exit; get_header(); ?>
<main id="main" class="wrap section">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article><h1><?php the_title(); ?></h1><?php the_content(); ?></article>
<?php endwhile; else : ?><h1>Page not found.</h1><p><a href="<?php echo esc_url(home_url('/')); ?>">Return to Home</a></p><?php endif; ?>
</main><?php get_footer(); ?>
