<?php
/*
Template Name: Landing Page
*/

use Theme_base\Base;

get_header();
$object = get_queried_object();
$theme_template_name = basename(__FILE__, ".php");
?>

<main id="main-<?= $theme_template_name ?>" class="main" role="main">

    <h1>Hello World</h1>
    <?php
    $section_intro = get_field('section_intro', $object->ID) ?: [];
    $badge_swiss = get_field('badge_swiss', "option");
    if (!empty($section_intro)) : ?>
        <section class="section-intro" aria-labelledby="section-intro-title">
            <div class="container">
                <?php if (!empty($section_intro['image'])) : ?>
                    <div class="section-intro-image">
                        <img src="<?= $section_intro['image']['url']; ?>" alt="<?= $section_intro['image']['alt']; ?>">
                    </div>
                <?php endif; ?>
                <div class="section-intro-content">
                    <?php if (!empty($badge_swiss)) : ?>
                        <div class="badge-swiss">
                            <?= $badge_swiss ? Base::inline_svg_from_attachment($badge_swiss['img']) : Base::inline_svg('badge-swiss.svg'); ?>
                            <span><?= $badge_swiss['title']; ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($section_intro['title'])) : ?>
                        <h1 id="section-intro-title" class="page-title"><?= $section_intro['title']; ?></h1>
                    <?php endif; ?>
                    <?php if (is_active_sidebar('trustindex')) : ?>
                        <?php dynamic_sidebar('trustindex'); ?>
                    <?php endif; ?>
                    <?php if (!empty($section_intro['items'])) : ?>
                        <div>
                            <ul>
                                <?php foreach ($section_intro['items'] as $item) : ?>
                                    <li><?= $item['text']; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($section_intro['button']['url'])) : ?>
                        <div class="button-background-primary">
                            <a href="<?= $section_intro['button']['url']; ?>" class="btn" <?= !empty($section_intro['button']['target']) ? 'target="' . $section_intro['button']['target'] . '"' : ''; ?>><?= $section_intro['button']['title']; ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>


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

    <?php
    $section_carrousel_left = get_field('section_carrousel_left', $object->ID) ?? [];
    $arrow_left = get_field('arrow_left', "option") ?? [];
    $arrow_right = get_field('arrow_right', "option") ?? [];
    if (!empty($section_carrousel_left)) : ?>
        <section class="section-carrousel-left" aria-labelledby="section-carrousel-left-title">
            <div class="container">
                <?php if (!empty($section_carrousel_left['title'])) : ?>
                    <h2 id="section-carrousel-left-title"><?= $section_carrousel_left['title']; ?></h2>
                <?php endif; ?>
                <div class="section-carrousel-left-container">
                    <div class="section-carrousel-left-carrousel">
                        <?php if (!empty($section_carrousel_left['items'])) : ?>
                            <div class="slider-wrapper carrousel-left-wrapper" data-slider-wrapper-carrousel-left>
                                <?php if (is_array($section_carrousel_left['items']) && count($section_carrousel_left['items']) > 0): ?>
                                    <?php $i = 0;
                                    foreach ($section_carrousel_left['items'] as $item) : ?>
                                        <div>
                                            <img src="<?= $item['image']['url']; ?>" alt="<?= $item['image']['alt']; ?>">
                                        </div>
                                    <?php $i++;
                                    endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php if (count($section_carrousel_left['items']) > 1) : ?>
                                <div class="wrapper-button-container">
                                    <div class="slide-button-container">
                                        <button class="slide-button-prev slide-button btn" data-slider-prev-carrousel-left aria-label="<?php echo esc_attr__('Carrousel gauche précédent', 'theme_base'); ?>" title="<?php echo esc_attr__('Carrousel gauche précédent', 'theme_base'); ?>">
                                            <?= Base::inline_svg_from_attachment($arrow_left) ? Base::inline_svg_from_attachment($arrow_left) : Base::inline_svg('arrow-left.svg'); ?>
                                        </button>
                                    </div>
                                    <div class="slide-button-container">
                                        <button class="slide-button-next slide-button btn" data-slider-next-carrousel-left aria-label="<?php echo esc_attr__('Carrousel gauche suivant', 'theme_base'); ?>" title="<?php echo esc_attr__('Carrousel gauche suivant', 'theme_base'); ?>">
                                            <?= Base::inline_svg_from_attachment($arrow_right) ? Base::inline_svg_from_attachment($arrow_right) : Base::inline_svg('arrow-right.svg'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="section-carrousel-left-content">
                        <?php $content = $section_carrousel_left['content'] ?? []; ?>
                        <?php if (!empty($content['wysiwyg'])) : ?>
                            <div class="content-wysiwyg">
                                <?= wpautop($content['wysiwyg']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($content['items'])) : ?>
                            <div>
                                <ul>
                                    <?php foreach ($content['items'] as $item) : ?>
                                        <li><?= $item['text']; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($content['button']['url'])) : ?>
                            <div class="button-background-primary">
                                <a href="<?= $content['button']['url']; ?>" class="btn" <?= !empty($content['button']['target']) ? 'target="' . $content['button']['target'] . '"' : ''; ?>><?= $content['button']['title']; ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php
    $section_products_table = get_field('section_products_table', $object->ID) ?? [];
    ?>
    <?php if (!empty($section_products_table)) : ?>
        <section class="section-products-table" aria-labelledby="section-products-table-title">
            <div class="container">
                <h2 id="section-products-table-title"><?= $section_products_table['title']; ?></h2>
                <?php if (!empty($section_products_table['image'])) : ?>
                    <div class="section-products-table-image">
                        <img src="<?= $section_products_table['image']['url']; ?>" alt="<?= $section_products_table['image']['alt']; ?>">
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php
    $section_trustindex_slider = get_field('section_trustindex_slider', $object->ID) ?: [];
    if (!empty($section_trustindex_slider)) : ?>
        <section class="section-trustindex-slider" aria-labelledby="section-trustindex-slider-title">
            <div class="container">
                <div class="section-trustindex-slider-container">
                    <?php if (!empty($section_trustindex_slider['title'])) : ?>
                        <h2 id="section-trustindex-slider-title"><?= $section_trustindex_slider['title']; ?></h2>
                    <?php endif; ?>
                    <?php if (is_active_sidebar('trustindex_slider')) : ?>
                        <?php dynamic_sidebar('trustindex_slider'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>


    <?php
    $section_image_left_builder = get_field('section_image_left_builder', $object->ID) ?? [];
    if (!empty($section_image_left_builder)) : ?>
        <section class="section-image-left-builder" aria-labelledby="section-image-left-builder-title">
            <div class="container">
                <?php if (!empty($section_image_left_builder['title'])) : ?>
                    <h2 id="section-image-left-builder-title"><?= $section_image_left_builder['title']; ?></h2>
                <?php endif; ?>
                <div class="section-image-left-builder-container">
                    <div class="section-image-left-builder-carrousel">
                        <?php if (!empty($section_image_left_builder['image'])) : ?>
                            <div>
                                <img src="<?= $section_image_left_builder['image']['url']; ?>" alt="<?= $section_image_left_builder['image']['alt']; ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="section-carrousel-left-content">
                        <?php if (!empty($section_image_left_builder['items'])) : ?>
                            <div>
                                <ul>
                                    <?php foreach ($section_image_left_builder['items'] as $item) : ?>
                                        <li><?= $item['text']; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($section_image_left_builder['button']['url'])) : ?>
                            <div class="button-background-primary">
                                <a href="<?= $section_image_left_builder['button']['url']; ?>" class="btn" <?= !empty($section_image_left_builder['button']['target']) ? 'target="' . $section_image_left_builder['button']['target'] . '"' : ''; ?>><?= $section_image_left_builder['button']['title']; ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php
    $section_title_description = get_field('section_title_description', $object->ID) ?: [];
    if (!empty($section_title_description)) : ?>
        <section class="section-title-description" aria-labelledby="section-title-description-title">
            <div class="container">
                <?php if (!empty($section_title_description['title'])) : ?>
                    <h2 id="section-title-description-title"><?= $section_title_description['title']; ?></h2>
                <?php endif; ?>
                <?php if (!empty($section_title_description['wysiwyg'])) : ?>
                    <div class="content-wysiwyg">
                        <?= wpautop($section_title_description['wysiwyg']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php
    $section_details = get_field('section_details', $object->ID) ?: [];
    $arrow_up_right = get_field('arrow_up_right', "option") ?? [];
    if (!empty($section_details)) : ?>
        <section class="section-details" aria-labelledby="section-details-title">
            <div class="container">
                <h2 id="section-details-title"><?= $section_details['title']; ?></h2>
                <?php if (!empty($section_details['items'])) : ?>
                    <?php $details_id = 'details-' . $i; ?>
                    <details id="<?= esc_attr($details_id); ?>" name="dropdown-details" class="dropdown-details dropdown reveal-item">
                        <summary class="default-color">
                            <p class="dropdown-title"><?= esc_html($item['text']); ?></p>
                            <div class="svg-container">
                                <?= Base::inline_svg_from_attachment($arrow_up_right) ? Base::inline_svg_from_attachment($arrow_up_right) : Base::inline_svg('arrow-up-right.svg'); ?>
                            </div>
                        </summary>
                        <div>
                            <?php if (!empty($item['image'])) : ?>
                                <div class="section-details-image">
                                    <img src="<?= $item['image']['url']; ?>" alt="<?= $item['image']['alt']; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($item['wysiwyg'])) : ?>
                                <div class="section-details-content">
                                    <div class="content-wysiwyg">
                                        <?= wpautop($item['wysiwyg']); ?>
                                    </div>
                                    <?php if (!empty($item['button']['url'])) : ?>
                                        <div class="button-background-primary">
                                            <a href="<?= $item['button']['url']; ?>" class="btn" <?= !empty($item['button']['target']) ? 'target="' . $item['button']['target'] . '"' : ''; ?>><?= $item['button']['title']; ?></a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>


</main>

<?php get_footer(); ?>