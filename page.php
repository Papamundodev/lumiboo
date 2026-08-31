<?php
get_header();
$theme_template_name = basename(__FILE__, ".php");
$title = get_the_title();
$content = get_the_content();
?>

<main id="main-<?= $theme_template_name ?>" class="main" role="main">
    <div class="container">
        <h1 class="page-title"><?= $title; ?></h1>

        <?php
        // WooCommerce pages (cart / checkout / account) render through their shortcodes,
        // which pick up the overrides in woocommerce/.
        if (function_exists('is_cart') && is_cart()) : ?>
            <?= do_shortcode('[woocommerce_cart]'); ?>
        <?php elseif (function_exists('is_checkout') && is_checkout()) : ?>
            <?= do_shortcode('[woocommerce_checkout]'); ?>
        <?php elseif (function_exists('is_account_page') && is_account_page()) : ?>
            <?= do_shortcode('[woocommerce_my_account]'); ?>
        <?php else : ?>
            <div class="page-content content-wysiwyg"><?= apply_filters('the_content', $content); ?></div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
