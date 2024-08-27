<?php

namespace FooPlugins\FooConvert;

if ( ! class_exists( __NAMESPACE__ . '\FooConvert' ) ) {

    /**
     * The core plugin class manages the overall flow of the plugin.
     */
    class FooConvert {

        //region static members

        /**
         * Callback for the `register_activation_hook` method.
         *
         * @param bool $network_wide True if WPMU superadmin uses "Network Activate" action, otherwise false if WPMU is
         * disabled or plugin is activated on an individual blog.
         */
        public static function activated( bool $network_wide ) {
            $plugin_data = get_site_option( FOOCONVERT_OPTION_DATA );
            $save_data = false;
            if ( false === $plugin_data ) {
                $plugin_data = array(
                    'version' => FOOCONVERT_VERSION,
                    'first_version' => FOOCONVERT_VERSION,
                    'first_install' => time()
                );
                $save_data = true;
            } else {
                $version = $plugin_data['version'];

                if ( $version !== FOOCONVERT_VERSION ) {
                    //the version has been updated

                    $plugin_data['version'] = FOOCONVERT_VERSION;
                    $save_data = true;
                }
            }

            if ( $save_data ) {
                update_site_option( FOOCONVERT_OPTION_DATA, $plugin_data );
            }
        }

        /**
         * Stores the internal instance used to implement the singleton pattern.
         *
         * @var ?FooConvert
         * @access private
         *
         * @since 1.0.0
         */
        private static ?FooConvert $_instance = null;

        /**
         * Get the instance of the plugin.
         *
         * @return FooConvert
         *
         * @since 1.0.0
         */
        public static function plugin() : FooConvert {
            if ( self::$_instance === null ) {
                self::$_instance = new FooConvert();
            }
            return self::$_instance;
        }

        //endregion

        /**
         * The base constructor for the plugin. This class can not be instantiated directly, instead use the
         * `FooConvert::plugin()` method to ensure only a single instance of the plugin is created.
         *
         * @access private
         */
        private function __construct() {
            add_action( 'init', array( $this, 'load_translations' ) );
            add_action( 'init', array( $this, 'register_frontend_assets' ) );
            add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_assets' ) );
            add_action( 'admin_menu', array( $this, 'register_menu' ) );
            add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );

            $this->components = new FooConvert_Components();
            $this->display_rules = new FooConvert_Display_Rules();
            $this->blocks = new FooConvert_Blocks();
            $this->widgets = new FooConvert_Widgets();
        }

        /**
         * Utility classes and methods for custom element dynamic blocks.
         *
         * @var FooConvert_Components
         * @access public
         *
         * @since 1.0.0
         */
        public FooConvert_Components $components;

        /**
         * Contains the logic for the widget display rules.
         *
         * @var FooConvert_Display_Rules
         * @access public
         *
         * @since 1.0.0
         */
        public FooConvert_Display_Rules $display_rules;

        /**
         * Contains utility methods as well as all block instances for the plugin.
         *
         * @remarks
         * Blocks are different to widgets within the context of the plugin. A widget has its own custom post type
         * and cannot be used outside of it. A block can be used by all widgets, but will only be visible to widgets.
         *
         * @var FooConvert_Blocks
         * @access public
         *
         * @since 1.0.0
         */
        public FooConvert_Blocks $blocks;

        /**
         * Contains utility methods as well as all widget instances for the plugin.
         *
         * @remarks
         * Widgets are different to blocks within the context of the plugin. A widget has its own custom post type
         * and cannot be used outside of it. A block can be used by all widgets, but will only be visible to widgets.
         *
         * @var FooConvert_Widgets
         * @access public
         *
         * @since 1.0.0
         */
        public FooConvert_Widgets $widgets;

        /**
         * Callback for the `admin_menu` action.
         *
         * This hook registers the root FooConvert menu for the plugin.
         *
         * @access public
         * @since 1.0.0
         */
        function register_menu() {
            /** @noinspection PhpUndefinedFunctionInspection - shows as unresolvable in my IDE - see https://developer.wordpress.org/reference/functions/add_menu_page/ */
            add_menu_page(
                __( 'FooConvert', 'fooconvert' ),
                __( 'FooConvert', 'fooconvert' ),
                'edit_posts',
                FOOCONVERT_MENU_SLUG,
                '',
                'dashicons-admin-plugins'
            );
        }

        /**
         * Callback for the `init` action.
         *
         * This hook loads the translations for the plugin.
         *
         * @access public
         * @since 1.0.0
         */
        public function load_translations() {
            $plugin_rel_path = dirname( plugin_basename( FOOCONVERT_FILE ) ) . '/languages/';
            load_plugin_textdomain( FOOCONVERT_SLUG, false, $plugin_rel_path );
            // load translations for the editor JS
            wp_set_script_translations( FOOCONVERT_EDITOR_ASSET_HANDLE, 'fooconvert' );
        }

        /**
         * Callback for the `init` action.
         *
         * This hook registers the shared frontend assets for the plugin.
         *
         * Unlike the editor assets, these are only registered and not enqueued. They will be enqueued only if a dependent
         * block requires them.
         *
         * @access public
         * @since 1.0.0
         */
        public function register_frontend_assets() {
            $frontend = include FOOCONVERT_ASSETS_PATH . 'frontend.asset.php';
            if ( Utils::has_keys( $frontend, array( 'dependencies', 'version' ) ) ) {
                wp_register_style( FOOCONVERT_FRONTEND_ASSET_HANDLE, FOOCONVERT_ASSETS_URL . 'frontend.css', array(), $frontend['version'] );
                wp_register_script( FOOCONVERT_FRONTEND_ASSET_HANDLE, FOOCONVERT_ASSETS_URL . 'frontend.js', $frontend['dependencies'], $frontend['version'], true );
                /**
                 * This action allows for additional assets to be enqueued after the frontend assets are enqueued.
                 *
                 * @param string $handle The handle used to register the frontend assets.
                 * @since 1.0.0
                 *
                 */
                do_action( 'fooconvert_registered_frontend_assets', FOOCONVERT_FRONTEND_ASSET_HANDLE );
            }
        }

        /**
         * Callback for the `enqueue_block_assets` action.
         *
         * This hook enqueues the shared editor assets for the plugin.
         *
         * @access public
         * @since 1.0.0
         */
        public function enqueue_editor_assets() {
            if ( $this->widgets->is_editor() ) {
                $editor = include FOOCONVERT_ASSETS_PATH . 'editor.asset.php';
                if ( Utils::has_keys( $editor, array( 'dependencies', 'version' ) ) ) {
                    wp_enqueue_style( FOOCONVERT_EDITOR_ASSET_HANDLE, FOOCONVERT_ASSETS_URL . 'editor.css', array(), $editor['version'] );
                    wp_enqueue_script( FOOCONVERT_EDITOR_ASSET_HANDLE, FOOCONVERT_ASSETS_URL . 'editor.js', $editor['dependencies'], $editor['version'], true );
                    /**
                     * This action allows for additional assets to be enqueued after the editor assets are enqueued.
                     *
                     * @param string $handle The handle used to enqueue the editor assets.
                     * @since 1.0.0
                     *
                     */
                    do_action( 'fooconvert_enqueued_editor_assets', FOOCONVERT_EDITOR_ASSET_HANDLE );
                }
            }
        }

        /**
         * Callback for the `block_categories_all` filter.
         *
         * This hook creates a FooConvert block category for the plugin.
         *
         * @param array{slug:string,title:string} $categories The current block categories.
         *
         * @return array{slug:string,title:string} The modified block categories.
         *
         * @access public
         * @since 1.0.0
         */
        public function register_block_category( array $categories ) : array {
            // using unshift to place our category first
            array_unshift( $categories, array(
                'slug' => FOOCONVERT_SLUG,
                'title' => FOOCONVERT_NAME
            ) );
            return $categories;
        }
    }
}