<?php

use Theme_base\Base;

$section_location = get_field('section_location', 'option') ?? [];
$location_icon = get_field('location_icon', "option") ?? [];
$class = $args['class'] ?? '';
?>
<?php if (!empty($section_location)) : ?>
    <section class="section-location section-<?= $class; ?>" aria-labelledby="section-location-title">
        <div class="container">
            <div>
                <?php if (!empty($section_location['title'])) : ?>
                    <h2 id="section-location-title"><?= $section_location['title']; ?></h2>
                <?php endif; ?>
                <?php if (!empty($section_location['wysiwyg'])) : ?>
                    <div class="content-wysiwyg">
                        <?= wpautop($section_location['wysiwyg']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($section_location['items'])) : ?>
                <div class="items">
                    <?php foreach ($section_location['items'] as $item) : ?>
                        <div>
                            <?php if (!empty($item['link']['url'])) : ?>
                                <a href="<?= $item['link']['url']; ?>" <?= !empty($item['link']['target']) ? 'target="' . $item['link']['target'] . '"' : ''; ?>>
                                    <?php if (!empty($location_icon)) : ?>
                                        <div class="img-container">
                                            <?= Base::inline_svg_from_attachment($location_icon) ?? Base::inline_svg('location-icon.svg'); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="title">
                                        <?= $item['link']['title']; ?>
                                    </div>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>