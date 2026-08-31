<?php

use Theme_base\Base;
use Theme_base\Woocommerce;

$object = get_queried_object();
/**
 * La section FAQ est fournie par le mu-plugin `faq`.
 * Sans cet appel, elle serait rendue automatiquement dans wp_footer().
 */
do_action('theme_faq_render');
?>

<?php if (!is_home() && !is_search() && !is_cart() && !is_checkout() && !is_category() && !is_single()) : ?>
    <section aria-labelledby="section-recent-posts-title" class="section-recent-posts">
        <div class="container">
            <?php
            $taxonomy_and_term_id = Base::get_logic_taxonomy_and_term_id($object, 'category');
            $term_id = (int) $taxonomy_and_term_id['term_id'];
            $taxonomy = $taxonomy_and_term_id['taxonomy'];
            $term = get_term($term_id, $taxonomy);
            $section_recent_posts_title = __('Tous nos conseils et actus', 'theme_base');
            $section_recent_posts_link = array(
                'url' => get_permalink(get_option('page_for_posts')),
                'title' => __('Découvrir nos conseils', 'theme_base'),
                'target' => '_self',
            );
            if (!empty($term_id) && !is_wp_error($term_id) && !is_front_page()) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term_id
                );
                $section_recent_posts_link = array(
                    'url' => get_term_link($term_id, $taxonomy),
                    'title' => __(sprintf('Tous nos conseils et actus en %s', strtolower($term->name ?: '')), 'theme_base'),
                    'target' => '_self',
                );
            }
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'post__not_in' => !empty($object->ID) ? [(int) $object->ID] : [],
                'posts_per_page' => 3,
                'orderby' => 'date',
                'order' => 'DESC',
            );
            if (!empty($tax_query)) {
                $args['tax_query'] = $tax_query;
            }
            $query = new WP_Query($args);
            $items = $query->posts;

            $section_recent_posts = get_field('section_recent_posts', $object) ?: null;
            if (!empty($section_recent_posts['selected_posts'])) {
                pre_debug($section_recent_posts);
                if (isset($section_recent_posts['title'])) {
                    $section_recent_posts_title = $section_recent_posts['title'];
                }
                if (isset($section_recent_posts['link'])) {
                    $section_recent_posts_link = $section_recent_posts['link'];
                }
                if (isset($section_recent_posts['selected_posts'])) {
                    $items = $section_recent_posts['selected_posts'];
                }
            }

            ?>
            <?php if (!empty($items)) : ?>
                <h2 id="section-recent-posts-title" class="section-title"><?= $section_recent_posts_title; ?></h2>
                <div class="card-container">
                    <?php foreach ($items as $post) : setup_postdata($post); ?>
                        <?php get_template_part('partials/article/post-preview', null, ['post' => $post]); ?>
                    <?php endforeach;
                    wp_reset_postdata(); ?>
                </div>
                <div class="button-wrapper">
                    <div class="button-background-primary">
                        <a href="<?= $section_recent_posts_link['url']; ?>" target="<?= $section_recent_posts_link['target'] ?? '_self'; ?>" class=" btn"><?= $section_recent_posts_link['title']; ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
    </section>
<?php endif; ?>

<?php
/**
 * La section "avis Google" est fournie par le mu-plugin `google-reviews`.
 * Sans cet appel, elle serait rendue automatiquement dans wp_footer().
 */
do_action('theme_google_reviews_render');
?>

<footer>
    <div class="container">
        <p>Copyright <?= date('Y'); ?> <?= get_bloginfo('name'); ?></p>
    </div>
</footer>

<div class="icon-container scroll-top-container">
    <a href="#" id="scroll-top" class="scroll-top btn" title="<?= __('Scroll to top', 'theme_base'); ?>">
        <?= Base::inline_svg('arrow-up.svg'); ?>
    </a>
</div>

<?php wp_footer();
