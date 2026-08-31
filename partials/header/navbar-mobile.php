<?php

use Theme_base\Base;

$object = get_queried_object();
$theme_template_name = basename(__FILE__, ".php");
$theme_location = $args['theme_location'] ?? "header";
$page_for_posts = get_option('page_for_posts');
$menu_items = \Theme_base\Base::wp_get_menu_array($theme_location);
$megamenu = get_field('megamenu', $theme_location) ?? null;
?>


<?php if (is_array($menu_items) && count($menu_items) > 0): ?>
    <nav popover="manual" id="navmenu-<?= $theme_location ?>-mobile" class="navmenu navmenu-mobile container">
        <?php if ($megamenu): ?>
            <h1>Megamenu</h1>
        <?php else: ?>
            <ul class="">
                <?php foreach ($menu_items as $item): ?>

                    <?php if (empty($item['children'])): ?>
                        <li class="<?= Base::get_active_class($item) ?> nav-item">
                            <a class="nav-link"
                                href="<?= $item['url'] ?>"
                                target="<?= $item['target'] ?>"
                                rel="<?= $item['target'] === '_blank' ? 'noopener' : '' ?>"><?= $item['title'] ?></a>
                        </li>
                    <?php else: ?>
                        <li class="<?= \Theme_base\Base::get_active_class($item) ?> <?= \Theme_base\Base::get_parent_active_class($item, $object) ?>">
                            <ul class="">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li class="<?= \Theme_base\Base::get_active_class($child) ?>">
                                        <a class="nav-link"
                                            href="<?= $child['url'] ?>"
                                            title="<?= $child['title'] ?>"
                                            target="<?= $child['target'] ?>"
                                            rel="<?= $child['target'] === '_blank' ? 'noopener' : '' ?>"><?= $child['title'] ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>

                <?php endforeach; ?>
            </ul>

        <?php endif; ?>
    </nav>
<?php endif; ?>