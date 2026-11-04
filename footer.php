<?php

use Theme_base\Base;
use Theme_base\Form;
use Theme_base\Woocommerce;

$object = get_queried_object();
/**
 * La section FAQ est fournie par le mu-plugin `faq`.
 * Sans cet appel, elle serait rendue automatiquement dans wp_footer().
 */

$section_faq = get_field('section_faq', 'option');
if (!empty($section_faq)) {
?>
    <section aria-labelledby="section-faq-title" class="section-faq">
        <div class="container">
            <div class="section-faq-header section-header">
                <h2 id="section-faq-title" class="section-title"><?= strip_tags($section_faq['title'], '<strong>'); ?></h2>
                <?php if (!empty($section_faq['text'])) : ?>
                    <div class="content-wysiwyg">
                        <?= wpautop($section_faq['text']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php do_action('theme_faq_render'); ?>
        </div>
    </section>
<?php }
do_action('theme_faq_render');


$section_menu_footer = "footer";
$menu_items_footer = \Theme_base\Base::wp_get_menu_array($section_menu_footer);
$section_menu_footer_contact = "footer-contact";
$menu_items_footer_contact = \Theme_base\Base::wp_get_menu_array($section_menu_footer_contact);
$section_menu_footer_copyright = "footer-copyright";
$menu_items_footer_copyright = \Theme_base\Base::wp_get_menu_array($section_menu_footer_copyright);
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

<?php if (!(function_exists('is_checkout') && is_checkout()) && !(function_exists('is_account_page') && is_account_page())) :
    $form_title = function_exists('get_field') ? get_field('form_title', 'option') : '';
    $form_image = function_exists('get_field') ? get_field('form_preview_image', 'option') : null;
    $form_image_url = is_array($form_image) ? ($form_image['url'] ?? '') : '';
    $form_image_alt = is_array($form_image) ? ($form_image['alt'] ?? '') : '';
?>
    <section aria-labelledby="section-form-title" class="section-contact-form">
        <?php if (is_string($form_title) && $form_title !== '') : ?>
            <h2 id="section-form-title" class="section-title"><?= esc_html($form_title); ?></h2>
        <?php endif; ?>
        <div class="section-content-img">
            <?php if ($form_image_url !== '') : ?>
                <div class="img-container">
                    <img src="<?= esc_url($form_image_url); ?>" alt="<?= esc_attr($form_image_alt); ?>">
                </div>
            <?php endif; ?>
            <?php Form::render(); ?>
        </div>
    </section>
<?php endif; ?>

<footer>
    <?php
    $section_footer = get_field('section_footer', 'option');
    ?>
    <?php if (!empty($section_footer)) : ?>
        <section class="section-footer">
            <div class="container">
                <?php if (!empty($section_footer['content'])) : ?>
                    <div class="footer-content">
                        <?php if (!empty($section_footer['content']['logo'])) : ?>
                            <div class="footer-logo">
                                <img src="<?= $section_footer['content']['logo']['url'] ?>" alt="<?= $section_footer['content']['logo']['alt'] ?? 'Logo' ?>">
                            </div>
                        <?php endif; ?>
                        <div class="content-wysiwyg">
                            <?= wpautop($section_footer['content']['wysiwyg']); ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($section_footer['menu'])) : ?>
                    <div class="footer-menu">
                        <?php if (!empty($section_footer['menu']['title'])) : ?>
                            <h3><?= $section_footer['menu']['title']; ?></h3>
                        <?php endif; ?>
                        <?php if (is_array($menu_items_footer) && count($menu_items_footer) > 0): ?>
                            <nav id="navmenu-<?= $section_menu_footer ?>" class="navmenu navmenu-footer">
                                <ul class="">
                                    <?php foreach ($menu_items_footer as $item): ?>
                                        <?php if (empty($item['children'])): ?>
                                            <li class=" <?= Base::get_parent_active_class($item, $object) ?> nav-item">
                                                <a class="nav-link"
                                                    href="<?= $item['url'] ?>"
                                                    target="<?= $item['target'] ?>"
                                                    rel="<?= $item['target'] === '_blank' ? 'noopener' : '' ?>"><?= $item['title'] ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="footer-location">
                    <?php get_template_part('partials/section/location', null, ['class' => 'footer']); ?>
                </div>
                <?php if (!empty($section_footer['contact'])) : ?>
                    <div class="footer-contact">
                        <?php if (!empty($section_footer['contact']['title'])) : ?>
                            <h3><?= $section_footer['contact']['title']; ?></h3>
                        <?php endif; ?>
                        <?php if (is_array($menu_items_footer_contact) && count($menu_items_footer_contact) > 0): ?>
                            <nav id="navmenu-<?= $section_menu_footer_contact ?>" class="navmenu navmenu-footer-contact">
                                <ul class="">
                                    <?php foreach ($menu_items_footer_contact as $item): ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="<?= $item['url'] ?>" target="<?= $item['target'] ?>" rel="<?= $item['target'] === '_blank' ? 'noopener' : '' ?>"><?= $item['title'] ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        <?php if (!empty($section_footer['contact']['items'])) : ?>
                            <div class="footer-contact-items">
                                <?php foreach ($section_footer['contact']['items'] as $item): ?>
                                    <div class="footer-contact-item">
                                        <?php if (!empty($item['icon'])) : ?>
                                            <div class="footer-contact-item-icon">
                                                <?= Base::inline_svg_from_attachment($item['icon']) ?? Base::inline_svg('phone.svg'); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($item['title'])) : ?>
                                            <span><?= $item['title']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>


    <?php if (is_array($menu_items_footer_copyright) && count($menu_items_footer_copyright) > 0): ?>
        <nav id="navmenu-<?= $section_menu_footer_copyright ?>" class="navmenu navmenu-footer-copyright">
            <ul class="">
                <?php foreach ($menu_items_footer_copyright as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $item['url'] ?>" target="<?= $item['target'] ?>" rel="<?= $item['target'] === '_blank' ? 'noopener' : '' ?>"><?= $item['title'] ?></a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item">Copyright <?= date('Y'); ?> <?= get_bloginfo('name'); ?></li>
            </ul>
        </nav>
    <?php endif; ?>

    </div>
</footer>

<div class="icon-container-secondary scroll-top-container">
    <a href="#" id="scroll-top" class="scroll-top btn" title="<?= __('Scroll to top', 'theme_base'); ?>">
        <?= Base::inline_svg('arrow-up.svg'); ?>
    </a>
</div>

<?php wp_footer();
