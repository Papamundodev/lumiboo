<?php

use Theme_base\Base;

$author_id = (int) ($args['author_id'] ?? get_the_author_meta('ID'));
$author = $author_id > 0 ? get_userdata($author_id) : false;
if (! $author) {
    return;
}

$acf_user = 'user_' . $author_id;
$author_data = get_field('author_data', $acf_user);
$author_data = is_array($author_data) ? $author_data : [];
$author_bio = get_the_author_meta('description', $author_id);
$facebook_icon = get_field('facebook_icon', "options");
$instagram_icon = get_field('instagram_icon', "options");
$linkedin_icon = get_field('linkedin_icon', "options");
$author_image = get_avatar($author_id, 120, '', $author->display_name, ['class' => 'author-preview-avatar']);
?>

<section class="author-preview" aria-label="<?= esc_attr__('Auteur de l\'article', 'theme_base'); ?>">
    <div class="container">
        <div class="author-preview-card">
            <?php if (!empty($author_image) && !is_wp_error($author_image)) : ?>
                <div class="author-preview-image">
                    <?= $author_image; ?>
                </div>
            <?php endif; ?>
            <div class="author-preview-content">
                <h2 class="author-preview-name">
                    <a href="<?= esc_url(get_author_posts_url($author_id)); ?>">
                        <?= esc_html($author->display_name); ?>
                    </a>
                </h2>
                <?php if (! empty($author_data['profession'])) : ?>
                    <p class="author-preview-profession"><?= esc_html($author_data['profession']); ?></p>
                <?php endif; ?>
                <?php if (! empty($author_bio)) : ?>
                    <div class="author-preview-bio">
                        <?= wpautop($author_bio); ?>
                    </div>
                <?php endif; ?>
                <ul class="author-preview-socials">
                    <?php if (! empty($author_data['facebook'])) : ?>
                        <li>
                            <a href="<?= esc_url($author_data['facebook']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php if (!empty($facebook_icon)) : ?>
                                    <div class="img-container">
                                        <?= Base::inline_svg_from_attachment($facebook_icon) ?? Base::inline_svg('facebook-icon.svg'); ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (! empty($author_data['instagram'])) : ?>
                        <li>
                            <a href="<?= esc_url($author_data['instagram']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php if (!empty($instagram_icon)) : ?>
                                    <div class="img-container">
                                        <?= Base::inline_svg_from_attachment($instagram_icon) ?? Base::inline_svg('instagram-icon.svg'); ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (! empty($author_data['linkedin'])) : ?>
                        <li>
                            <a href="<?= esc_url($author_data['linkedin']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php if (!empty($linkedin_icon)) : ?>
                                    <div class="img-container">
                                        <?= Base::inline_svg_from_attachment($linkedin_icon) ?? Base::inline_svg('linkedin-icon.svg'); ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</section>