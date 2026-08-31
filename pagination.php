<?php

use Theme_base\Base;

global $wp_query;

$id = $args['id'] ?? '';
$anchor = $id ? '#' . $id : '';
$next_post = get_next_posts_page_link();
$previous_post = get_previous_posts_page_link();
$paged = max(1, (int) get_query_var('paged'));
$max_num_page = (int) $wp_query->max_num_pages;
$simple_chevron_left = get_field('simple_chevron_left', 'option');
$simple_chevron_right = get_field('simple_chevron_right', 'option');
$double_chevron_left = get_field('double_chevron_left', 'option');
$double_chevron_right = get_field('double_chevron_right', 'option');

if ($next_post) {
    $next_post .= $anchor;
}
if ($previous_post) {
    $previous_post .= $anchor;
}
?>
<?php if ($max_num_page > 1) : ?>

    <nav aria-label="<?= esc_attr__('Pagination', 'theme_base'); ?>">
        <ul class="pagination">
            <?php if ($paged > 1) : ?>
                <li class="page-item">
                    <a href="<?= get_pagenum_link(1) . $anchor; ?>" class="page-link" aria-label="<?php esc_attr_e('Première page', 'theme_base'); ?>" title="<?php esc_attr_e('Première page', 'theme_base'); ?>">
                        <div class="img-container">
                            <?= Base::inline_svg_from_attachment($double_chevron_left) ?: '&laquo;'; ?>
                        </div>
                    </a>
                </li>
                <li class="page-item">
                    <a href="<?= $previous_post; ?>" class="page-link" aria-label="<?php esc_attr_e('Page précédente', 'theme_base'); ?>" title="<?php esc_attr_e('Page précédente', 'theme_base'); ?>">
                        <div class="img-container">
                            <?= Base::inline_svg_from_attachment($simple_chevron_left) ?: '&laquo;'; ?>
                        </div>
                    </a>
                </li>
            <?php endif; ?>

            <?php
            $pagination = Base::pagination($wp_query);
            $current_page = $paged;
            $min_num_page = 1;
            $space_link_allowed = 1;

            foreach ($pagination as $page) {
                if ($page->isCurrent) {
                    $current_page = (int) $page->page;
                    break;
                }
            }

            // Keep the pages around the current one, plus the first and the last.
            $links_to_show = array_filter($pagination, function ($link) use ($current_page, $max_num_page, $min_num_page, $space_link_allowed) {
                return (
                    ($link->page <= $current_page + $space_link_allowed && $link->page >= $current_page - $space_link_allowed)
                    || ($link->page == $max_num_page)
                    || ($link->page == $min_num_page)
                );
            });

            $prev_page = null;
            ?>
            <?php foreach ($links_to_show as $link) : ?>
                <?php if ($prev_page !== null && ((int) $link->page - $prev_page) > 1) : ?>
                    <li class="page-item disabled">
                        <span class="page-link">…</span>
                    </li>
                <?php endif; ?>
                <li class="page-item <?= $link->isCurrent ? 'active' : ''; ?>">
                    <a href="<?= esc_url($link->url) . $anchor; ?>" class="page-link" aria-label="<?php printf(esc_attr__('Page %d', 'theme_base'), (int) $link->page); ?>" title="<?php printf(esc_attr__('Page %d', 'theme_base'), (int) $link->page); ?>">
                        <?= (int) $link->page; ?>
                    </a>
                </li>
                <?php $prev_page = (int) $link->page; ?>
            <?php endforeach; ?>

            <?php if ($paged !== $max_num_page) : ?>
                <li class="page-item">
                    <a href="<?= $next_post; ?>" class="page-link" aria-label="<?php esc_attr_e('Page suivante', 'theme_base'); ?>" title="<?php esc_attr_e('Page suivante', 'theme_base'); ?>">
                        <div class="img-container">
                            <?= Base::inline_svg_from_attachment($simple_chevron_right) ?: '&rsaquo;'; ?>
                        </div>
                    </a>
                </li>
                <li class="page-item">
                    <a href="<?= get_pagenum_link($max_num_page) . $anchor; ?>" class="page-link" aria-label="<?php esc_attr_e('Dernière page', 'theme_base'); ?>" title="<?php esc_attr_e('Dernière page', 'theme_base'); ?>">
                        <div class="img-container">
                            <?= Base::inline_svg_from_attachment($double_chevron_right) ?: '&raquo;'; ?>
                        </div>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

<?php endif; ?>