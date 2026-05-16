<?php

namespace FooPlugins\FooConvert;

use WP_Post_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and manages the single popup post type used by FooConvert.
 */
class PostType {

    /**
     * Prevent duplicate admin hook registration when the CPT is registered.
     *
     * @var bool
     */
    private static bool $admin_hooks_registered = false;

    /**
     * Hooks popup post type registration into WordPress.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }

    /**
     * Registers the popup post type and related integrations.
     *
     * @return void
     */
    public function init(): void {
        $post_type = $this->register();
        if ( !$post_type instanceof WP_Post_Type ) {
            return;
        }

        FooConvert::plugin()->compatibility->register();
        FooConvert::plugin()->display_rules->register();
        FooConvert::plugin()->shortcodes->register();
    }

    /**
     * Registers the popup post type and popup-specific meta.
     *
     * @return false|WP_Post_Type
     */
    public function register() {
        $post_type = register_post_type( FOOCONVERT_CPT_POPUP, array(
            'labels'        => array(
                'name'               => __( 'Popups', 'fooconvert' ),
                'singular_name'      => __( 'Popup', 'fooconvert' ),
                'add_new'            => __( 'Add Popup', 'fooconvert' ),
                'add_new_item'       => __( 'Add New Popup', 'fooconvert' ),
                'edit_item'          => __( 'Edit Popup', 'fooconvert' ),
                'new_item'           => __( 'New Popup', 'fooconvert' ),
                'view_item'          => __( 'View Popups', 'fooconvert' ),
                'search_items'       => __( 'Search Popups', 'fooconvert' ),
                'not_found'          => __( 'No Popups found', 'fooconvert' ),
                'not_found_in_trash' => __( 'No Popups found in Trash', 'fooconvert' ),
                'all_items'          => __( 'Popups', 'fooconvert' )
            ),
            'has_archive'   => false,
            'public'        => false,
            'show_ui'       => true,
            'show_in_rest'  => true,
            'show_in_menu'  => FOOCONVERT_MENU_SLUG,
            'supports'      => array( 'title', 'editor', 'author', 'custom-fields' ),
            'template'      => array(
                array( $this->get_template_block_name() )
            ),
            'template_lock' => 'all'
        ) );

        register_post_meta( FOOCONVERT_CPT_POPUP, FOOCONVERT_META_KEY_POPUP_TYPE, array(
            'single'            => true,
            'type'              => 'string',
            'default'           => FOOCONVERT_POPUP_TYPE_OVERLAY,
            'sanitize_callback' => 'fooconvert_sanitize_popup_type',
            'auth_callback'     => static function () {
                return current_user_can( 'edit_posts' );
            },
            'show_in_rest'      => array(
                'schema' => array(
                    'type'    => 'string',
                    'default' => FOOCONVERT_POPUP_TYPE_OVERLAY,
                    'enum'    => fooconvert_get_popup_types(),
                ),
            ),
        ) );

        $this->register_admin_hooks();

        return $post_type;
    }

    /**
     * Check if the current page is the popup editor.
     *
     * @return bool True if the current page is the popup editor, otherwise false.
     */
    public function is_editor(): bool {
        return Utils::is_post_type_editor( FOOCONVERT_CPT_POPUP );
    }

    /**
     * Returns the root template block for the current popup create request.
     *
     * @return string
     */
    private function get_template_block_name(): string {
        $popup_type = fooconvert_get_requested_popup_type();
        if ( $popup_type === '' ) {
            $popup_type = FOOCONVERT_POPUP_TYPE_OVERLAY;
        }

        $block_name = fooconvert_get_popup_type_block_name( $popup_type );

        return $block_name !== '' ? $block_name : 'fc/overlay';
    }

    /**
     * Registers popup list table hooks.
     *
     * @return void
     */
    private function register_admin_hooks(): void {
        if ( !is_admin() || self::$admin_hooks_registered ) {
            return;
        }

        add_filter( 'manage_' . FOOCONVERT_CPT_POPUP . '_posts_columns', array( $this, 'filter_list_table_columns' ) );
        add_action( 'manage_' . FOOCONVERT_CPT_POPUP . '_posts_custom_column', array( $this, 'render_list_table_column' ), 10, 2 );
        add_action( 'restrict_manage_posts', array( $this, 'render_list_table_filters' ) );
        add_action( 'pre_get_posts', array( $this, 'filter_list_table_query' ) );

        self::$admin_hooks_registered = true;
    }

    /**
     * Adds the logical popup type column to the popup list table.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function filter_list_table_columns( array $columns ): array {
        $updated = array();
        $type_label = __( 'Type', 'fooconvert' );
        $inserted = false;

        foreach ( $columns as $column_name => $label ) {
            if ( $column_name === 'fc_popup_type' ) {
                continue;
            }

            $updated[ $column_name ] = $label;
            if ( $column_name === 'title' ) {
                $updated['fc_popup_type'] = $type_label;
                $inserted = true;
            }
        }

        if ( !$inserted ) {
            $updated['fc_popup_type'] = $type_label;
        }

        return $updated;
    }

    /**
     * Renders the logical popup type column content.
     *
     * @param string $column_name Column name.
     * @param int    $post_id Post ID.
     * @return void
     */
    public function render_list_table_column( string $column_name, int $post_id ): void {
        if ( $column_name !== 'fc_popup_type' ) {
            return;
        }

        echo esc_html( fooconvert_get_popup_type_label( $post_id ) );
    }

    /**
     * Renders the list table popup type filter.
     *
     * @param string $post_type Current post type.
     * @return void
     */
    public function render_list_table_filters( string $post_type ): void {
        if ( $post_type !== FOOCONVERT_CPT_POPUP ) {
            return;
        }

        $selected_popup_type = isset( $_GET['fooconvert_popup_type'] ) ? fooconvert_normalize_popup_type( sanitize_key( wp_unslash( $_GET['fooconvert_popup_type'] ) ) ) : '';
        ?>
        <select name="fooconvert_popup_type">
            <option value=""><?php esc_html_e( 'All Types', 'fooconvert' ); ?></option>
            <?php foreach ( fooconvert_get_popup_types() as $popup_type ) : ?>
                <option value="<?php echo esc_attr( $popup_type ); ?>" <?php selected( $selected_popup_type, $popup_type ); ?>>
                    <?php echo esc_html( fooconvert_get_popup_type_label( $popup_type ) ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Applies popup type filtering to the popup list table query.
     *
     * @param mixed $query WP_Query instance.
     * @return void
     */
    public function filter_list_table_query( $query ): void {
        if ( !is_admin() || !$query->is_main_query() ) {
            return;
        }

        if ( $query->get( 'post_type' ) !== FOOCONVERT_CPT_POPUP ) {
            return;
        }

        $selected_popup_type = isset( $_GET['fooconvert_popup_type'] ) ? fooconvert_normalize_popup_type( sanitize_key( wp_unslash( $_GET['fooconvert_popup_type'] ) ) ) : '';
        if ( $selected_popup_type === '' ) {
            return;
        }

        $meta_query = $query->get( 'meta_query' );
        if ( !is_array( $meta_query ) ) {
            $meta_query = array();
        }

        $meta_query[] = array(
            'key'   => FOOCONVERT_META_KEY_POPUP_TYPE,
            'value' => $selected_popup_type,
        );

        $query->set( 'meta_query', $meta_query );
    }
}
