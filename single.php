<?php
get_header();
$theme_template_name = basename(__FILE__, ".php");
?>

<main id="main-<?= $theme_template_name ?>" class="main" role="main">

    <?php if (have_posts() && get_post_type() !== 'product') : ?>

        <?php while (have_posts()) : ?>
            <?php the_post(); ?>
            <?php get_template_part('partials/article/post-full'); ?>
        <?php endwhile; ?>

    <?php endif; ?>

</main>

<?php
get_footer();
