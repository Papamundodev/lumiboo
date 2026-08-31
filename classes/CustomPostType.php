<?php

namespace Theme_base;

class CustomPostType
{
    private string $slug;
    private string $singular;
    private string $plural;
    private int $menu_pos;
    private string $desc;
    private string $domain;
    private array $labels;
    private array $args;

    public function __construct(string $domain = "theme_base", string $slug = "product", string $singular = "Product", string $plural = "Products", int $menu_pos = 2, string $desc = "")
    {
        $this->slug = $slug;
        $this->singular = $singular;
        $this->plural = $plural;
        $this->menu_pos = $menu_pos;
        $this->desc = $desc;
        $this->domain = $domain;

        $this->labels = $this->getLabels();
        $this->args = $this->getArgs();
    }

    private function getLabels(): array
    {
        return array(
            'name'                  => $this->plural,
            'singular_name'         => $this->singular,
            'menu_name'             => $this->plural,
            'name_admin_bar'        => $this->singular,
            'archives'              => $this->singular . __(' Archives', $this->domain),
            'parent_item_colon'     => __('Parent ', $this->domain) . $this->singular . ':',
            'all_items'             => __('All ', $this->domain) . $this->plural,
            'add_new_item'          => __('Add New ', $this->domain) . $this->singular,
            'add_new'               => __('Add New', $this->domain),
            'new_item'              => __('New ', $this->domain) . $this->singular,
            'edit_item'             => __('Edit ', $this->domain) . $this->singular,
            'update_item'           => __('Update ', $this->domain) . $this->singular,
            'view_item'             => __('View ', $this->domain) . $this->singular,
            'search_items'          => __('Search ', $this->domain) . $this->singular,
            'not_found'             => __('Not found', $this->domain),
            'not_found_in_trash'    => __('Not found in Trash', $this->domain),
            'featured_image'        => __('Featured Image', $this->domain),
            'set_featured_image'    => __('Set featured image', $this->domain),
            'remove_featured_image' => __('Remove featured image', $this->domain),
            'use_featured_image'    => __('Use as featured image', $this->domain),
            'insert_into_item'      => __('Insert into ', $this->domain) . $this->singular,
            'uploaded_to_this_item' => __('Uploaded to this ', $this->domain) . $this->singular,
            'items_list'            => $this->plural . __(' list', $this->domain),
            'items_list_navigation' => $this->plural . __(' list navigation', $this->domain),
            'filter_items_list'     => __('Filter ', $this->domain) . $this->plural . __(' list', $this->domain),
        );
    }

    public function getArgs(): array
    {
        return array(
            'label'                 => $this->singular,
            'description'           => $this->desc,
            'labels'                => $this->labels,
            'supports'              => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
            'taxonomies'            => array(),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => $this->menu_pos,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'rest_base'             => $this->slug,
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );
    }


    public function register(): void
    {
        register_post_type($this->getSlug(), $this->getArgs());
    }


    public function getSlug(): string
    {
        return $this->slug;
    }

    public function updateArgs(array $argsToUpdate): void
    {
        // Fusionner les nouveaux arguments avec les arguments existants
        $this->args = array_merge($this->args, $argsToUpdate);

        // Réenregistrer le type de publication personnalisée avec les nouveaux arguments
        add_action('init', function () {
            register_post_type($this->getSlug(), $this->args);
        });
    }
}
