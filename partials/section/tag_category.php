    <?php
    $page_for_posts = (int) get_option('page_for_posts');
    $taxonomy = $args['taxonomy'] ?? 'category';
    $taxonomy_name = $args['taxonomy_name'] ?? 'category';
    ?>



    <section id="section-<?= $taxonomy_name; ?>-menu" aria-labelledby="section-<?= $taxonomy_name; ?>-title" class="section-post container">
        <?php
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false, // we'll filter manually
        ]);

        $get_categories = [];

        if (! is_wp_error($terms)) {
            foreach ($terms as $term) {
                $has_blog_post = (new WP_Query([
                    'post_type'      => 'post',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                    'tax_query'      => [
                        [
                            'taxonomy' => $taxonomy,
                            'field'    => 'term_id',
                            'terms'    => [$term->term_id],
                        ],
                    ],
                ]))->have_posts();

                if ($has_blog_post) {
                    $get_categories[] = $term;
                }
            }
        }
        ?>
        <div class="<?= $taxonomy_name; ?>-menu">
            <h2 id="section-<?= $taxonomy_name; ?>-title"><?= __($taxonomy_name, 'theme_base'); ?></h2>
            <?php foreach ($get_categories as $term) : ?>
                <div class="<?= $taxonomy_name; ?>-preview">
                    <a href="<?= get_term_link($term); ?>">
                        <h3 class="<?= $taxonomy_name; ?>-title"><?= $term->name; ?></h3>
                    </a>
                </div>
            <?php endforeach; ?>
            <div class="<?= $taxonomy_name; ?>-preview active">
                <a href="<?= get_permalink($page_for_posts); ?>">
                    <h3 class="<?= $taxonomy_name; ?>-title"><?= __('Tout', 'theme_base'); ?></h3>
                </a>
            </div>
        </div>
    </section>