<?php
/*
Template Name: About
*/

use Theme_base\Base;

get_header();
$object = get_queried_object();
$theme_template_name = basename(__FILE__, ".php");
$title = get_the_title();
$content = get_the_content();
?>

<main id="main-<?= $theme_template_name ?>" class="main" role="main">

    <?php
    $section_intro = get_field('section_intro', $object->ID) ?: [];
    if (!empty($section_intro)) : ?>
        <section class="section-intro" aria-labelledby="section-intro-title">
            <div class="container">
                <?php if (!empty($section_intro['title'])) : ?>
                    <h1 id="section-intro-title" class="page-title"><?= $section_intro['title']; ?></h1>
                <?php endif; ?>
                <?php if (!empty($section_intro['wysiwyg'])) : ?>
                    <span class="section-intro-content"><?= $section_intro['wysiwyg']; ?></span>
                <?php endif; ?>
                <?php if (!empty($section_intro['button'])) : ?>
                    <div>
                        <div class="button-background-primary">
                            <a href="<?= $section_intro['button']['url']; ?>" class="btn" <?= !empty($section_intro['button']['target']) ? 'target="' . $section_intro['button']['target'] . '"' : ''; ?>><?= $section_intro['button']['title']; ?></a>
                        </div>
                    </div>
                <?php endif; ?>
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
    /**
     * La section "chiffres clés" est fournie par le mu-plugin `odometer`.
     * Sans cet appel, elle serait rendue automatiquement dans wp_footer().
     */
    do_action('theme_odometer_render');
    ?>

    <?php
    $section_left_right = get_field('section_left_right', $object->ID) ?: [];
    if (!empty($section_left_right)) : ?>
        <section class="section-left-right" aria-labelledby="section-left-right-title">
            <div class="container">
                <?php if (!empty($section_left_right['title'])) : ?>
                    <h2 id="section-left-right-title"><?= $section_left_right['title']; ?></h2>
                <?php endif; ?>
                <?php foreach ($section_left_right['items'] as $item) : ?>
                    <div class="section-left-right-container">
                        <div class="section-left-right-content">
                            <?php if (!empty($item['title'])) : ?>
                                <h3><?= $item['title']; ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($item['wysiwyg'])) : ?>
                                <div class="content-wysiwyg">
                                    <?= wpautop($item['wysiwyg']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="section-left-right-image">
                            <?php if (!empty($item['image'])) : ?>
                                <img src="<?= $item['image']['url']; ?>" alt="<?= $item['image']['alt']; ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php
    $section_authors = get_field('section_authors', $object->ID) ?: [];
    if (!empty($section_authors)) : ?>
        <section class="section-authors" aria-labelledby="section-authors-title">
            <div class="container">
                <?php if (!empty($section_authors['title'])) : ?>
                    <h2 id="section-authors-title"><?= $section_authors['title']; ?></h2>
                <?php endif; ?>
                <div class="section-authors-container">
                    <?php foreach ($section_authors['items'] as $item) : ?>
                        <div class="section-authors-card">
                            <?php if (!empty($item['image'])) : ?>
                                <img src="<?= $item['image']['url']; ?>" alt="<?= $item['image']['alt']; ?>">
                            <?php endif; ?>
                            <?php if (!empty($item['name'])) : ?>
                                <h3><?= $item['name']; ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($item['title'])) : ?>
                                <span><?= $item['title']; ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['wysiwyg'])) : ?>
                                <div class="content-wysiwyg">
                                    <?= wpautop($item['wysiwyg']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
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

    <?php get_template_part('partials/section/location', null, ['class' => 'about']); ?>


</main>

<?php
get_footer();
