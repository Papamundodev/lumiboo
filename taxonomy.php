<?php
get_header();
global $wp_query;
$object = get_queried_object();
$theme_template_name = basename(__FILE__, ".php");
?>

<main id="main-<?= $theme_template_name ?>" class="main" role="main">

    <section id="section-taxonomy-blog" class="section-taxonomy-blog">
        <div class="container">
            <div class="page-title">
                <h1><?= $object->name; ?></h1>
            </div>

            <?php if (is_array($wp_query->posts) && count($wp_query->posts) > 0) : ?>
                <div class="card-container">
                    <?php foreach ($wp_query->posts as $post) : setup_postdata($post); ?>
                        <?php get_template_part('partials/article/post-preview', null, ['post' => $post]); ?>
                    <?php endforeach;
                    wp_reset_postdata(); ?>
                </div>
                <?php get_template_part('pagination', null, ['id' => 'section-taxonomy-blog']); ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
get_footer();
