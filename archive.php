<?php
get_header();
global $wp_query;
$object = get_queried_object();
$theme_template_name = basename(__FILE__, ".php");
?>
<main id="main-<?= $theme_template_name ?>" class="main" role="main">

    <div class="container">
        <h1 class="archive-title"><?= get_the_archive_title(); ?></h1>
        <?php if (get_the_archive_description()) : ?>
            <div class="archive-description content-wysiwyg"><?= get_the_archive_description(); ?></div>
        <?php endif; ?>
    </div>

    <section id="section-post-categories-menu" aria-labelledby="section-categories-title" class="section-post-categories-menu container">
        <?php
        $terms = get_terms([
            'taxonomy'   => 'faq_category',
            'hide_empty' => false, // we'll filter manually
        ]);

        $get_categories = [];

        if (! is_wp_error($terms)) {
            foreach ($terms as $term) {
                $has_blog_post = (new WP_Query([
                    'post_type'      => 'faq',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                    'tax_query'      => [
                        [
                            'taxonomy' => 'faq_category',
                            'field'    => 'term_id',
                            'terms'    => [$term->term_id],
                        ],
                    ],
                ]))->have_posts();

                if ($has_blog_post) {
                    $get_categories[] = $term;
                }
            }
        }
        ?>
        <div class="categories-menu">
            <h2 id="section-categories-title"><?= __('Catégories de FAQ', 'theme_base'); ?></h2>
            <?php foreach ($get_categories as $category) : ?>
                <div class="category-preview <?= isset($object->term_id) && $category->term_id === $object->term_id ? 'active' : ''; ?>">
                    <a href="<?= get_term_link($category); ?>">
                        <h3 class="category-title"><?= $category->name; ?></h3>
                    </a>
                </div>
            <?php endforeach; ?>
            <div class="category-preview">
                <a href="<?= get_permalink(get_option('page_for_posts')); ?>">
                    <h3 class="category-title"><?= __('Tout', 'theme_base'); ?></h3>
                </a>
            </div>
        </div>
    </section>

    <section id="section-post-tags-menu" aria-labelledby="section-tags-title" class="section-post-tags-menu container">
        <?php
        $terms = get_terms([
            'taxonomy'   => 'post_tag',
            'hide_empty' => false, // we'll filter manually
        ]);

        $get_tags = [];

        if (! is_wp_error($terms)) {
            foreach ($terms as $term) {
                $has_blog_post = (new WP_Query([
                    'post_type'      => 'post',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                    'tax_query'      => [
                        [
                            'taxonomy' => 'post_tag',
                            'field'    => 'term_id',
                            'terms'    => [$term->term_id],
                        ],
                    ],
                ]))->have_posts();

                if ($has_blog_post) {
                    $get_tags[] = $term;
                }
            }
        }
        ?>
        <button id="wrapper-reveal" class="wrapper-reveal btn">
            <div class="categories-menu">
                <h2 id="section-tags-title"><?= __('Tags', 'theme_base'); ?></h2>
                <?php foreach ($get_tags as $tag) : ?>
                    <div class="category-preview <?= isset($object->term_id) && $tag->term_id === $object->term_id ? 'active' : ''; ?>">
                        <a href="<?= get_term_link($tag); ?>">
                            <h3 class="category-title"><?= $tag->name; ?></h3>
                        </a>
                    </div>
                <?php endforeach; ?>
                <div class="category-preview">
                    <a href="<?= get_permalink(get_option('page_for_posts')); ?>">
                        <h3 class="category-title"><?= __('Tout', 'theme_base'); ?></h3>
                    </a>
                </div>
            </div>
        </button>
    </section>

    <section id="section-category-blog" class="section-category-blog">
        <div class="container">
            <div class="card-container">
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('partials/article/post-preview', null, ['post' => get_post()]); ?>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </div>
            <?php get_template_part('pagination', null, ['id' => 'section-category-blog']); ?>
        </div>
    </section>

    <?php if (is_category() && isset($object->term_id)) : ?>
        <?php
        $related_products_query = new WP_Query([
            'post_type' => 'product',
            'posts_per_page' => 10,
            'tax_query' => [
                [
                    'taxonomy' => 'category',
                    'field' => 'term_id',
                    'terms' => [$object->term_id],
                ],
            ],
        ]);
        get_template_part('partials/woocommerce/products/sliders/related-products', null, [
            'related_products' => $related_products_query->posts,
            'category' => $object,
        ]);
        ?>
    <?php endif; ?>
</main>

<?php
get_footer();
