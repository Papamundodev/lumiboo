<?php

use Theme_base\Base;

get_header();
$object = get_queried_object();
$theme_template_name = basename(__FILE__, ".php");
?>
<main id="main-<?= $theme_template_name ?>" class="main">


    <?php
    $section_hero = get_field('section_hero', $object->ID) ?? [];
    $badge_swiss = get_field('badge_swiss', "option");
    ?>
    <?php if (!empty($section_hero)) : ?>
        <section class="section-hero" aria-labelledby="section-hero-title">
            <div class="container">
                <div class="section-hero-content">
                    <div class="badge-swiss">
                        <?= $badge_swiss ? Base::inline_svg_from_attachment($badge_swiss['img']) : Base::inline_svg('badge-swiss.svg'); ?>
                        <span><?= $badge_swiss['title']; ?></span>
                    </div>
                    <h1 id="section-hero-title"><?= $section_hero['title']; ?></h1>
                    <div>
                        <?php if (!empty($section_hero['button']['url'])) : ?>
                            <div class="button-background-primary">
                                <a href="<?= $section_hero['button']['url']; ?>" class="btn" <?= !empty($section_hero['button']['target']) ? 'target="' . $section_hero['button']['target'] . '"' : ''; ?>><?= $section_hero['button']['title']; ?></a>
                            </div>
                        <?php endif; ?>
                        <?php if (is_active_sidebar('trustindex')) : ?>
                            <?php dynamic_sidebar('trustindex'); ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($section_hero['items'])) : ?>
                        <div>
                            <ul>
                                <?php foreach ($section_hero['items'] as $item) : ?>
                                    <li><?= $item['text']; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($section_hero['image']['url'])) : ?>
                    <div class="section-hero-image">
                        <img src="<?= $section_hero['image']['url']; ?>" alt="<?= $section_hero['image']['alt']; ?>">
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>


    <?php
    /**
     * La section "banner" (logos partenaires) est fournie par le mu-plugin `banner`.
     * Sans cet appel, elle serait rendue automatiquement dans wp_footer().
     */
    do_action('theme_banner_render');
    ?>

    <?php
    $section_values = get_field('section_values', $object->ID) ?? [];
    $separator_title = get_field('separator_title', "option");
    ?>
    <?php if (!empty($section_values)) : ?>
        <section class="section-values" aria-labelledby="section-values-title">
            <div class="container">
                <div>
                    <?php if (!empty($section_values['title'])) : ?>
                        <h2 id="section-values-title"><?= $section_values['title']; ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($separator_title)) : ?>
                        <div>
                            <?= Base::inline_svg_from_attachment($separator_title) ?? Base::inline_svg('separator-title.svg'); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($section_values['description'])) : ?>
                        <div class="content-wysiwyg">
                            <?= wpautop($section_values['description']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="first-row">
                    <?php if (!empty($section_values['first_row'])) : ?>
                        <?php foreach ($section_values['first_row'] as $first_row) : ?>
                            <div>
                                <div class="img-container">
                                    <img src="<?= $first_row['image']['url']; ?>" alt="<?= $first_row['image']['alt']; ?>">
                                </div>
                                <div class="title">
                                    <?= $first_row['text']; ?>
                                </div>
                                <div class="content-wysiwyg">
                                    <?= $first_row['wysiwyg']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="second-row">
                    <?php if (!empty($section_values['second_row'])) : ?>
                        <?php foreach ($section_values['second_row'] as $second_row) : ?>
                            <div>
                                <div class="img-container">
                                    <img src="<?= $second_row['image']['url']; ?>" alt="<?= $second_row['image']['alt']; ?>">
                                </div>
                            </div>
                            <div class="title">
                                <?= $second_row['text']; ?>
                            </div>
                            <div class="content-wysiwyg">
                                <?= $second_row['wysiwyg']; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($section_values['button']['url'])) : ?>
                    <div>
                        <div class="button-background-primary">
                            <a href="<?= $section_values['button']['url']; ?>" class="btn" <?= !empty($section_values['button']['target']) ? 'target="' . $section_values['button']['target'] . '"' : ''; ?>><?= $section_values['button']['title']; ?></a>
                        </div>
                    </div>
                <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php
    $section_step_enum = get_field('section_step_enum', $object->ID) ?? [];
    $separator_title = get_field('separator_title', "option") ?? [];
    ?>
    <?php if (!empty($section_step_enum)) : ?>
        <section class="section-step-enum" aria-labelledby="section-step-enum-title">
            <div class="container">
                <div>
                    <?php if (!empty($section_step_enum['title'])) : ?>
                        <h2 id="section-step-enum-title"><?= $section_step_enum['title']; ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($separator_title)) : ?>
                        <div>
                            <?= Base::inline_svg_from_attachment($separator_title) ?? Base::inline_svg('separator-title.svg'); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($section_step_enum['wysiwyg'])) : ?>
                        <div class="content-wysiwyg">
                            <?= wpautop($section_step_enum['wysiwyg']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($section_step_enum['items'])) : ?>
                    <div class="items">
                        <?php foreach ($section_step_enum['items'] as $item) : ?>
                            <div>
                                <div class="number"></div>
                                <div class="title">
                                    <?= $item['text']; ?>
                                </div>
                                <div class="content-wysiwyg">
                                    <?= wpautop($item['wysiwyg']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($section_step_enum['button']['url'])) : ?>
                    <div>
                        <div class="button-background-primary">
                            <a href="<?= $section_step_enum['button']['url']; ?>" class="btn" <?= !empty($section_step_enum['button']['target']) ? 'target="' . $section_step_enum['button']['target'] . '"' : ''; ?>><?= $section_step_enum['button']['title']; ?></a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>


    <?php get_template_part('partials/section/location', null, ['class' => 'front-page']); ?>

    <div class="container">

        <div class="design-system">
            <div class="design-system-buttons">
                <h2 class="section-title">Buttons</h2>
                <div class="design-system-buttons-container">
                    <div>
                        <div class="button-background-primary">
                            <a href="#" class="btn">Click me</a>
                        </div>
                    </div>

                    <div>
                        <div class="button-background-secondary">
                            <a href="#" class="btn">Click me</a>
                        </div>
                    </div>

                    <div>
                        <div class="button-background-tertiary">
                            <a href="#" class="btn">Click me</a>
                        </div>
                    </div>

                    <div>
                        <div class="button-background-quaternary">
                            <a href="#" class="btn">Click me</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <?php
        $products_query = new WP_Query(array(
            'post_type' => 'product',
            'posts_per_page' => 10,
        )); ?>
        <?php if (is_array($products_query->posts) && count($products_query->posts) > 0): ?>
            <section aria-labelledby="section-products-title" id="section-products-front-page" class="section-products slider " data-slider-products data-count="<?= count($products_query->posts); ?>">
                <h2 id="section-products-title" class="section-title"><?= __('Carrousel de produits', 'theme_base'); ?></h2>
                <div class="slider-wrapper products-wrapper" data-slider-wrapper-products>
                    <?php if (is_array($products_query->posts) && count($products_query->posts) > 0): ?>
                        <?php $i = 0;
                        foreach ($products_query->posts as $post) : ?>
                            <?php $p = wc_get_product($post->ID); ?>
                            <?php if (! ($p instanceof \WC_Product)) {
                                continue;
                            } ?>
                            <article class="product-preview reveal-item slide">
                                <h3><?= $p->get_name(); ?></h3>
                                <p><?= $p->get_price_html(); ?></p>
                                <div class="content-wysiwyg">
                                    <?= wpautop($p->get_description()); ?>
                                </div>
                                <div class="button-background-primary">
                                    <a href="<?= $p->get_permalink(); ?>" class="btn"><?= __('Voir le produit', 'theme_base'); ?></a>
                                </div>
                            </article>
                        <?php $i++;
                        endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (count($products_query->posts) > 1) : ?>
                    <div class="wrapper-button-container">
                        <div class="slide-button-container">
                            <button class="slide-button-prev slide-button btn" data-slider-prev-products aria-label="<?php echo esc_attr__('Produits précédent', 'theme_base'); ?>" title="<?php echo esc_attr__('Produits précédent', 'theme_base'); ?>">
                                <?php echo file_get_contents(get_template_directory() . '/resources/images/arrow-left.svg'); ?>
                            </button>
                        </div>
                        <div class="slide-button-container">
                            <button class="slide-button-next slide-button btn" data-slider-next-products aria-label="<?php echo esc_attr__('Produits suivant', 'theme_base'); ?>" title="<?php echo esc_attr__('Produits suivant', 'theme_base'); ?>">
                                <?php echo file_get_contents(get_template_directory() . '/resources/images/arrow-right.svg'); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>