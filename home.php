<?php
get_header();
global $wp_query;
$theme_template_name = basename(__FILE__, ".php");
$page_for_posts = (int) get_option('page_for_posts');
$default_term_id = (int) get_option('default_category');
?>

<main id="main-<?= $theme_template_name ?>" class="main" role="main">

    <div class="container">
        <h1 class="archive-title"><?= $page_for_posts ? get_the_title($page_for_posts) : __('Blog', 'theme_base'); ?></h1>
    </div>

    <?php get_template_part('partials/section/tag_category', null, ['taxonomy' => 'category', 'taxonomy_name' => 'category']); ?>
    <?php get_template_part('partials/section/tag_category', null, ['taxonomy' => 'post_tag', 'taxonomy_name' => 'tag']); ?>

    <section id="section-home-blog" class="section-home-blog">
        <div class="container">
            <div class="card-container">
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('partials/article/post-preview', null, ['post' => get_post()]); ?>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </div>
            <?php get_template_part('pagination', null, ['id' => 'section-home-blog']); ?>
        </div>
    </section>
    <?php $products_query = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => 10,
        'tax_query' => [
            [
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => ['exclude-from-catalog', 'exclude-from-search'],
                'operator' => 'NOT IN',
            ],
        ],
    ]);
    $products = $products_query->posts;
    ?>


    <?php if (is_array($products) && count($products) > 0) : ?>
        <div class="products-grid">
            <?php foreach ($products as $post) : setup_postdata($post); ?>
                <?php $p = wc_get_product($post->ID); ?>
                <?php if (! ($p instanceof \WC_Product)) {
                    continue;
                } ?>
                <div class="product-preview">
                    <?php get_template_part('partials/woocommerce/products/product-preview', null, ['product' => $p, 'post' => $post]); ?>
                </div>
            <?php endforeach;
            wp_reset_postdata(); ?>
        </div>
    <?php endif; ?>
</main>

<?php
get_footer();
