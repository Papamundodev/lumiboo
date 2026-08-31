<?php

use Theme_base\Base;

get_header('shop');
$object = get_queried_object();
?>

<?php if (is_shop()) : ?>
    <?php get_template_part('partials/woocommerce/shop', 'shop'); ?>
<?php endif; ?>

<?php woocommerce_output_all_notices(); ?>

<?php if ($object instanceof WP_Term) : ?>
    <?php $theme_sorting_cats = [
        'has_no_parent' => Base::cat_has_no_parent_category($object),
        'middle' => Base::cat_middle_category($object),
        'child' => Base::cat_child_category($object),
    ]; ?>

    <?php if ($theme_sorting_cats['has_no_parent']) : ?>
        <?php get_template_part('partials/woocommerce/parent-category-product', 'parent-category-product'); ?>
    <?php elseif ($theme_sorting_cats['middle'] || $theme_sorting_cats['child']) : ?>
        <?php get_template_part('partials/woocommerce/child-category-product', 'child-category-product'); ?>
    <?php endif; ?>
<?php endif; ?>

<?php get_footer('shop'); ?>
