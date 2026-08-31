<?php

use Theme_base\Base;

$object = get_queried_object();
$theme_template_name = basename(__FILE__, ".php");
$theme_location = $args['theme_location'] ?? "header";
$menu_items = \Theme_base\Base::wp_get_menu_array($theme_location);
$megamenu = get_field('megamenu', $theme_location) ?? null;
?>


<?php if (is_array($menu_items) && count($menu_items) > 0): ?>
    <nav id="navmenu-<?= $theme_location ?>" class="navmenu navmenu-desktop">
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
                            <nav aria-label="navigation" tabindex="0" class="dropdown-hover dropdown dropdown-nav">
                                <div class="dropdown-link ">
                                    <div class="dropdown-title btn nav-link"><?= $item['title'] ?></div>
                                    <div class="dropdown-menu">
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
                                    </div>
                                </div>
                            </nav>
                        </li>
                    <?php endif; ?>

                <?php endforeach; ?>
            </ul>

        <?php endif; ?>
    </nav>
<?php endif; ?>