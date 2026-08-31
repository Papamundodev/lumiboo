<?php
get_header();
global $wp_query;
$object = get_queried_object();
$theme_template_name = basename(__FILE__, ".php");
?>
<main id="main-<?= $theme_template_name ?>" class="main" role="main">

    <div class="container-small">
        <h1 class="archive-title"><?= single_tag_title('', false); ?></h1>
        <?php if ($object->description) : ?>
            <div class="archive-description content-wysiwyg"><?= wpautop($object->description); ?></div>
        <?php endif; ?>
    </div>

    <section id="section-post-tags-menu" aria-labelledby="section-tags-title" class="section-post-tags-menu container-small">
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

        // Current tag first.
        $current_tag_id = $object->term_id ?? null;

        if ($current_tag_id) {
            $get_tags = array_merge(
                array_filter($get_tags, static function ($tag) use ($current_tag_id) {
                    return (int) $tag->term_id === (int) $current_tag_id;
                }),
                array_filter($get_tags, static function ($tag) use ($current_tag_id) {
                    return (int) $tag->term_id !== (int) $current_tag_id;
                })
            );
        }
        ?>
        <button id="wrapper-reveal" class="wrapper-reveal btn">
            <div class="categories-menu">
                <h2 id="section-tags-title"><?= __('Tags', 'theme_base'); ?></h2>
                <?php foreach ($get_tags as $tag) : ?>
                    <div class="category-preview <?= $tag->term_id === $object->term_id ? 'active' : ''; ?>">
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
        <div class="container-small">
            <div class="products-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('partials/article/post-preview', null, ['post' => get_post()]); ?>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </div>
            <?php get_template_part('pagination', null, ['id' => 'section-category-blog']); ?>
        </div>
    </section>

    <?php
    $related_products_query = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => 10,
        'tax_query' => [
            [
                'taxonomy' => 'post_tag',
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
</main>

<?php
get_footer();
