<?php

namespace Theme_base;

class Taxonomy
{
    private string $slug;
    private string $singular;
    private string $plural;
    private string $domain;
    private array $cpt_slug;

    private array $labels;
    private array $args;

    public function __construct(string $domain = "theme_base", string $slug = "product_category", string $singular = "Categorie", string $plural = "Categories", array $cpt_slug = array('product'))
    {
        $this->slug = $slug;
        $this->singular = $singular;
        $this->plural = $plural;
        $this->domain = $domain;
        $this->cpt_slug = $cpt_slug;

        $this->labels = $this->getLabels();
        $this->args = $this->getArgs();
    }

    private function getLabels(): array
    {
        return array(
            'name' => $this->singular,
            'singular_name' => $this->singular,
            'plural_name' => $this->plural,
            'search_items' => __('Search ', $this->domain) . $this->plural,
            'all_items' => __('All ', $this->domain) . $this->plural,
            'edit_item' => __('Edit ', $this->domain) . $this->singular,
            'update_item' => __('Update ', $this->domain) . $this->singular,
            'add_new_item' => __('Add new ', $this->domain) . $this->singular,
            'new_item_name' => __('Add new ', $this->domain) . $this->singular,
            'menu_name' => $this->singular,
        );
    }

    public function getArgs(): array
    {
        return array(
            'labels' => $this->labels,
            'show_in_rest' => true,
            'hierarchical' => true,
            'has_archive' => true,
            'show_tagcloud' => true,
        );
    }


    public function associateToCustomPostType(array $cpt_slug = array('post')): void
    {
        add_action('init', function () use ($cpt_slug) {
            register_taxonomy($this->getSlug(), $cpt_slug, $this->getArgs());
        });
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}
