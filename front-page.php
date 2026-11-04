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
            <div class="container section-hero-container">
                <div class="section-hero-content">
                    <?php if (!empty($section_hero['title'])) : ?>
                        <h1 id="section-hero-title"><?= strip_tags($section_hero['title'], '<strong>');  ?></h1>
                    <?php endif; ?>
                    <div class="section-hero-actions">
                        <?php if (!empty($section_hero['button']['url'])) : ?>
                            <div class="button-background-primary">
                                <a href="<?= $section_hero['button']['url']; ?>" class="btn" <?= !empty($section_hero['button']['target']) ? 'target="' . $section_hero['button']['target'] . '"' : ''; ?>><?= $section_hero['button']['title']; ?></a>
                            </div>
                        <?php endif; ?>
                        <?php if (is_active_sidebar('trustindex')) : ?>
                            <div id="trustindex">
                                <?php dynamic_sidebar('trustindex'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($section_hero['items'])) : ?>
                        <div>
                            <ul class="bullet-list">
                                <?php foreach ($section_hero['items'] as $item) : ?>
                                    <li><?= $item['text']; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="section-hero-image-container">
                    <div class="section-img animation-moving">
                        <div class="flying-img-container-primary">
                            <?php echo file_get_contents(get_template_directory() . '/resources/images/decoX.svg'); ?>
                        </div>
                        <div class="flying-img-container-secondary">
                            <?php echo file_get_contents(get_template_directory() . '/resources/images/decoX.svg'); ?>
                        </div>
                        <div class="flying-img-container-tertiary">
                            <?php echo file_get_contents(get_template_directory() . '/resources/images/decoX.svg'); ?>
                        </div>
                    </div>
                    <?php if (!empty($section_hero['image']['section-hero-image']) || !empty($section_hero['image']['url'])) : ?>
                        <div class="section-hero-image">
                            <img src="<?= $section_hero['image']['section-hero-image']['url'] ?? $section_hero['image']['url']; ?>" fetchpriority="high" alt="<?= $section_hero['image']['section-hero-image']['alt'] ?? $section_hero['image']['alt']; ?>">
                            <?php if (!empty($badge_swiss)) : ?>
                                <div class="badge-swiss" aria-label="<?= $badge_swiss['title']; ?>">
                                    <img src="<?= $badge_swiss['img']['url'] ?? $badge_swiss['img']['url']; ?>" fetchpriority="high" alt="<?= $badge_swiss['img']['alt'] ?? $badge_swiss['img']['alt']; ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
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
                <div class="section-values-container">
                    <div class="section-values-images">
                        <?php if (!empty($section_values['image_left']['url'])) : ?>
                            <div class="img-container image-left">
                                <img src="<?= $section_values['image_left']['url']; ?>" alt="<?= $section_values['image_left']['alt']; ?>">
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($section_values['image_right']['url'])) : ?>
                            <div class="img-container image-right">
                                <img src="<?= $section_values['image_right']['url']; ?>" alt="<?= $section_values['image_right']['alt']; ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="section-values-content">
                        <div class="section-values-content-container section-header">
                            <?php if (!empty($section_values['title'])) : ?>
                                <h2 id="section-values-title" class="section-title"><?= strip_tags($section_values['title'], '<strong>');  ?></h2>
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
                                    <div class="section-values-first-row-container">
                                        <div class="icon-container-primary-xl">
                                            <div class="icon">
                                                <?= Base::inline_svg_from_attachment($first_row['image']) ?? Base::inline_svg('icon-primary.svg'); ?>
                                            </div>
                                        </div>
                                        <div class="section-values-first-row-content">
                                            <div class="title">
                                                <?= $first_row['text']; ?>
                                            </div>
                                            <div class="content-wysiwyg">
                                                <?= $first_row['wysiwyg']; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="second-row">
                    <?php if (!empty($section_values['second_row'])) : ?>
                        <div class="section-values-second-row-container">
                            <?php foreach ($section_values['second_row'] as $second_row) : ?>
                                <div class="section-values-second-row-item">
                                    <div class="icon-container-tertiary-xl">
                                        <div class="icon">
                                            <?= Base::inline_svg_from_attachment($second_row['image']) ?? Base::inline_svg('icon-primary.svg'); ?>
                                        </div>
                                    </div>
                                    <div class="section-values-second-row-content">
                                        <div class="title">
                                            <?= $second_row['text']; ?>
                                        </div>
                                        <div class="content-wysiwyg">
                                            <?= $second_row['wysiwyg']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($section_values['button']['url'])) : ?>
                    <div class="button-container">
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
                <div class="section-step-enum-header section-header">
                    <?php if (!empty($section_step_enum['title'])) : ?>
                        <h2 id="section-step-enum-title" class="section-title"><?= $section_step_enum['title']; ?></h2>
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
                            <div class="item">
                                <div class="icon-container-tertiary-xl whimsy">
                                    <div class="icon">
                                        <?= Base::inline_svg_from_attachment($item['image']) ?? Base::inline_svg('icon-primary.svg'); ?>
                                    </div>
                                    <div class="deco">
                                        <?php echo file_get_contents(get_template_directory() . '/resources/images/decoX.svg'); ?>
                                    </div>
                                    <div class="deco">
                                        <?php echo file_get_contents(get_template_directory() . '/resources/images/decoX.svg'); ?>
                                    </div>
                                    <div class="deco">
                                        <?php echo file_get_contents(get_template_directory() . '/resources/images/decoX.svg'); ?>
                                    </div>
                                    <div class="deco">
                                        <?php echo file_get_contents(get_template_directory() . '/resources/images/decoX.svg'); ?>
                                    </div>
                                    <div class="deco">
                                        <?php echo file_get_contents(get_template_directory() . '/resources/images/decoX.svg'); ?>
                                    </div>
                                    <div class="deco">
                                        <?php echo file_get_contents(get_template_directory() . '/resources/images/decoX.svg'); ?>
                                    </div>
                                    <div class="count"></div>
                                </div>

                                <h3 class="title"><?= $item['text']; ?></h3>
                                <div class="content-wysiwyg">
                                    <?= wpautop($item['wysiwyg']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($section_step_enum['button']['url'])) : ?>
                    <div class="button-container">
                        <div class="button-background-primary">
                            <a href="<?= $section_step_enum['button']['url']; ?>" class="btn" <?= !empty($section_step_enum['button']['target']) ? 'target="' . $section_step_enum['button']['target'] . '"' : ''; ?>><?= $section_step_enum['button']['title']; ?></a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php
    $section_reviews = get_field('section_reviews', $object->ID) ?? [];
    ?>
    <?php if (!empty($section_reviews)) : ?>
        <section class="section-reviews" aria-labelledby="section-reviews-title">
            <div class="container">
                <div class="section-reviews-header section-header">
                    <?php if (!empty($section_reviews['title'])) : ?>
                        <h2 id="section-reviews-title" class="section-title"><?= strip_tags($section_reviews['title'], '<strong>');  ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($section_reviews['text'])) : ?>
                        <div class="content-wysiwyg">
                            <?= wpautop($section_reviews['text']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (is_active_sidebar('trustindex_slider')) : ?>
                    <div id="trustindex_slider">
                        <?php dynamic_sidebar('trustindex_slider'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php get_footer(); ?>