<?php

use Theme_base\Base;

global $post;

$title = get_the_title();
$content = apply_filters('the_content', get_the_content());
$featured_image = get_the_post_thumbnail_url($post, 'large');
$date_published = get_the_date();
$reading_time = Base::get_reading_time($content);
$tags = get_the_terms(get_the_ID(), 'post_tag');
$tags = (! $tags || is_wp_error($tags)) ? [] : $tags;
$categories = get_the_terms(get_the_ID(), 'category');
$categories = (! $categories || is_wp_error($categories)) ? [] : $categories;
$category_id = Base::get_post_primary_term($post, 'category');
$category = $category_id ? get_term($category_id, 'category') : null;
$breadcrumbs = Base::get_breadcrumbs();
?>

<div class="post-full">

    <header class="post-full-header container">
        <h1 class="post-full-title"><?= $title; ?></h1>
        <div class="post-full-meta">
            <time datetime="<?= esc_attr(get_the_date('c')); ?>"><?= $date_published; ?></time>
            <span class="reading-time"><?= $reading_time; ?></span>
        </div>
        <?php if ($categories) : ?>
            <div class="categories-container">
                <?php foreach ($categories as $item) : ?>
                    <a href="<?= get_term_link($item); ?>" class="term"><?= $item->name; ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($tags) : ?>
            <div class="terms-container">
                <?php foreach ($tags as $tag) : ?>
                    <a href="<?= get_term_link($tag); ?>" class="term"><?= $tag->name; ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($featured_image) : ?>
            <div class="img-container">
                <img src="<?= esc_url($featured_image); ?>" alt="<?= esc_attr($title); ?>" fetchpriority="high">
            </div>
        <?php endif; ?>
    </header>

    <div id="breadcrumbs" class="breadcrumbs-custom container">
        <div class="svg-container home-icon">
            <?= Base::inline_svg('home.svg'); ?>
        </div>
        <?php foreach ($breadcrumbs as $breadcrumb) : ?>
            <a href="<?= $breadcrumb['url']; ?>"><?= $breadcrumb['text']; ?></a>
            <?php if ($breadcrumb !== end($breadcrumbs)) : ?>
                <div class="svg-container separator-icon">
                    <?= Base::inline_svg('simple-chevron-right.svg'); ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <section aria-label="<?= esc_attr__('Contenu de l\'article', 'theme_base'); ?>" class="section-content">
        <div class="container">
            <div class="section-content content-wysiwyg">
                <?= $content; ?>
            </div>
        </div>
    </section>

    <?php if ($category_id) : ?>
        <?php
        $related_products_query = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 10,
            'post__not_in' => [get_the_ID()],
            'tax_query' => [
                [
                    'taxonomy' => 'category',
                    'field' => 'term_id',
                    'terms' => [$category_id],
                ],
            ],
        ]);
        $related_posts = $related_products_query->posts;
        ?>

        <?php if ($related_posts) : ?>
            <div class="container">
                <h2 class="section-title"><?= __('Articles similaires', 'theme_base'); ?></h2>
                <div class="card-container">
                    <?php foreach ($related_posts as $post) : ?>
                        <?php get_template_part('partials/article/post-preview', null, ['post' => $post]); ?>
                    <?php endforeach; ?>
                </div>
                <?php get_template_part('pagination', null, ['id' => 'section-related-posts']); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>