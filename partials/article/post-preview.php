<?php

use Theme_base\Base;

$post = $args['post'] ?? null;
if (! $post instanceof WP_Post) {
    return;
}

$title = $post->post_title;
$excerpt = $post->post_excerpt;
if (empty($excerpt)) {
    $excerpt = $post->post_content;
}
$content = wp_trim_words($excerpt, 15, '...');
$featured_image = get_the_post_thumbnail_url($post, 'post-preview-image');
$alt_text = get_post_meta(get_post_thumbnail_id($post), '_wp_attachment_image_alt', true);
$post_terms = get_the_terms($post, 'post_tag');
$post_terms = (! $post_terms || is_wp_error($post_terms)) ? [] : $post_terms;
?>

<article role="article" class="post-preview reveal-item">
    <div class="post-preview-wrapper">
        <?php if ($featured_image) : ?>
            <a href="<?= get_permalink($post); ?>" class="img-container">
                <img src="<?= esc_url($featured_image); ?>" alt="<?= esc_attr($alt_text); ?>" loading="lazy">
            </a>
        <?php endif; ?>
        <div class="post-preview-content">
            <a href="<?= get_permalink($post); ?>" class="post-preview-title-wrapper">
                <h3 class="post-preview-title"><?= $title; ?></h3>
            </a>
            <?php if ($post_terms) : ?>
                <div class="terms-container">
                    <?php foreach ($post_terms as $term) : ?>
                        <a href="<?= get_term_link($term); ?>" class="term"><?= $term->name; ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="post-preview-excerpt">
                <p><?= $content; ?></p>
            </div>

            <div class="button-background-primary">
                <a href="<?= get_permalink($post); ?>" class="btn"><?= __('Lire l\'article', 'theme_base'); ?></a>
            </div>
        </div>
    </div>
</article>