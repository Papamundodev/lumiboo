<?php
$product = $args['product'] ?? wc_get_product(get_the_ID());
$related = $args['related'] ?? false;
?>

<!-- TAG -->
<?php
$tag_id = get_field('products_default_tag', 'option') ?? '';
$tag = get_term($tag_id);
?>
<div class="product-preview-content ">
    <?php if ($tag && $tag_id && has_term($tag->term_id, 'product_tag', $product->get_id())) : ?>
        <div class="product-tag-wrapper">
            <span class="product-tag"><?= esc_html($tag->name); ?></span>
        </div>
    <?php else : ?>
        <div class="product-tag-empty"></div>
    <?php endif; ?>

    <!-- Title -->
    <a href="<?= esc_url(get_permalink($product->get_id())); ?>" class="product-title-wrapper">
        <h3 class="product-title"><?= esc_html(wp_trim_words($product->get_title(), $num_words = 30, $more = '...')); ?></h3>
    </a>

    <?php if ($related) : ?>
        <?php
        $category = get_the_terms($product->get_id(), 'product_cat');
        if ($category) : ?>
            <div class="product-category-wrapper">
                <?php foreach ($category as $cat) : ?>
                    <?php
                    $term_category_title_variation = get_field('title_variation', $cat);
                    ?>
                    <a href="<?= esc_url(get_term_link($cat)); ?>" class="btn">
                        <span class="product-category <?= $cat->parent !== 0 ? 'main-category' : 'child-category'; ?>"><?= esc_html($term_category_title_variation ? $term_category_title_variation : $cat->name); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="product-category-empty"></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($product->get_short_description()) : ?>
        <div class="product-short-description-container">
            <p class="product-short-description"><?= $product->get_short_description(); ?></p>
        </div>
    <?php endif; ?>


    <!-- Price -->
    <div class="product-price-container">
        <span class="product-price"><?= $product->get_price_html(); ?></span>
    </div>

    <!-- Button -->
    <div class="button-wrapper">
        <div class="button-container button-background-primary">
            <a href="<?= esc_url(get_permalink($product->get_id())); ?>" class="btn">
                <?= __('Voir le produit', 'theme_base'); ?>
            </a>
        </div>
    </div>
</div>