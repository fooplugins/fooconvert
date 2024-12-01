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
            $plugin_data = get_site_option( FOOCONVERT_OPTION_VERSION );
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
                update_site_option( FOOCONVERT_OPTION_VERSION, $plugin_data );
            }

            // Make sure the database tables are created.
            $schema = new Data\Schema();
            $schema->create_event_table_if_needed();
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
            add_action( 'wp_enqueue_scripts', array( $this, 'ensure_frontend_assets_enqueued' ) );
            add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_assets' ) );
            add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );
            add_filter( 'fooconvert_event_data', array( $this, 'adjust_event_data' ), 10, 2 );

            $this->components = new FooConvert_Components();
            $this->compatibility = new FooConvert_Compatibility();
            $this->display_rules = new FooConvert_Display_Rules();
            $this->blocks = new FooConvert_Blocks();
            $this->widgets = new FooConvert_Widgets();

            if ( is_admin() ) {
                new Admin\Init();
            }

            $this->ajax = new Ajax();
        }

        /**
         * Allows adjusting the event data before it is saved.
         *
         * @param array $data The event data to be saved.
         * @param string $post_type The type of post that the event is associated with.
         * @param string $template The name of the template that the event is associated with.
         *
         */
        function adjust_event_data( $data, $meta ) {
            // We want to override event_type in certain scenarios.
            // e.g. if a button is clicked in a bar, then event_type will be 'conversion'

            // We want to check events to determine if a subtype is needed.
            // e.g. if the event_type is 'click' then set the subtype to 'engagement';
            // e.g. if the event_type is 'open' and the visitor manually opened it, then set the subtype to 'engagement';

            // We also want to determine sentiment for certain events.
            // e.g. if the event_type is 'close' then check how quickly it was closed to determine a negative sentiment;
            // e.g. if the event_type is 'open' and the visitor manually opened it, then set positive sentiment;

            if ( empty( $meta ) ) {
                $meta = [];
            }

            // Post type and template not used at the moment.
            //$post_type = isset( $meta['post_type'] ) ? $meta['post_type'] : null;
            //$template = isset( $meta['template'] ) ? $meta['template'] : null;

            $event_type = isset( $data['event_type'] ) ? $data['event_type'] : null;
            $extra_data = isset( $data['extra_data'] ) ? $data['extra_data'] : [];

            $conversion = false;

            // Check clicks
            switch ( $event_type ) {
                case FOOCONVERT_EVENT_TYPE_CLICK:

                    // Any click is considered a positive engagement.
                    $data['event_subtype'] = FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT;
                    $data['sentiment'] = 1; // positive sentiment.

                    // check for conversions.
                    $tag_name = isset( $extra_data['tagName'] ) ? $extra_data['tagName'] : null;
                    // button or link clicks are considered conversions.
                    if ( $tag_name === 'a' || $tag_name === 'button' ) {
                        $conversion = true;
                    }

                    break;
                case FOOCONVERT_EVENT_TYPE_OPEN:
                    $trigger = isset( $extra_data['trigger'] ) ? $extra_data['trigger'] : null;

                    // A manual open is considered a positive engagement.
                    if ( $trigger === 'open-button' ) {
                        $data['event_subtype'] = FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT;
                        $data['sentiment'] = 1; // positive sentiment.
                    }

                    break;
                case FOOCONVERT_EVENT_TYPE_CLOSE:
                    $data['event_subtype'] = FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT;

                    $duration = isset( $extra_data['duration'] ) ? intval( $extra_data['duration'] ) : 0;

                    // A close after 5 seconds is considered neutral.
                    if ( $duration > apply_filters( 'fooconvert_close_sentiment_positive', 5000 ) ) {
                        $data['sentiment'] = null; // neutral sentiment.
                    } else {
                        $data['sentiment'] = 0; // negative sentiment.
                    }

                    break;
            }

            if ( $conversion ) {
                $data['event_type'] = FOOCONVERT_EVENT_TYPE_CONVERSION;
            }

            return $data;
        }

        /**
         * Callback for the `wp_enqueue_scripts` action.
         *
         * This hook makes sure that the frontend CSS is enqueued when the frontend JS is enqueued.
         *
         * @since 1.0.0
         */
        function ensure_frontend_assets_enqueued() {
            $is_frontend_js_enqueued = wp_script_is( FOOCONVERT_FRONTEND_ASSET_HANDLE );
            $is_frontend_css_enqueued = wp_style_is( FOOCONVERT_FRONTEND_ASSET_HANDLE );
            if ( $is_frontend_js_enqueued && !$is_frontend_css_enqueued ) {
                wp_enqueue_style( FOOCONVERT_FRONTEND_ASSET_HANDLE );
            }
            if ( $is_frontend_js_enqueued ) {
                $data = array(
                    'endpoint' => $this->ajax->get_endpoint(),
                );
                wp_add_inline_script( FOOCONVERT_FRONTEND_ASSET_HANDLE, Utils::to_js_script( 'FOOCONVERT_CONFIG', $data ), 'before' );
            }
        }

        //region Properties

        /**
         * Utility classes and methods for custom element dynamic blocks.
         *
         * @var FooConvert_Components
         * @access public
         *
         * @since 1.0.0
         */
        public FooConvert_Components $components;

        public FooConvert_Compatibility $compatibility;

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

        public Ajax $ajax;

        //endregion

        //region KSES

        /**
         * A wrapper around the `wp_kses` method that extends both the allowed HTML elements and CSS properties
         * to include the plugin custom elements and SVG elements.
         *
         * @param string $content Text content to filter.
         * @return string Filtered content containing only the allowed HTML.
         */
        public function kses_post( string $content, bool $compatibility_mode = false ) : string {
            $allowed_html = wp_kses_allowed_html( 'post' );
            // merge the plugin elements into the allowed list
            $allowed_html = array_merge(
                $allowed_html,
                $this->blocks->get_kses_definitions(),
                $this->widgets->get_kses_definitions()
            );

            if ( $compatibility_mode ) {
                $allowed_html = array_merge(
                    $allowed_html,
                    $this->compatibility->get_kses_definitions()
                );
            }

            return $this->kses_with_svg( $content, $allowed_html );
        }

        /**
         * A wrapper around the `wp_kses` method that extends both the allowed HTML elements and CSS properties
         * to include SVG elements.
         *
         * @param string $content Text content to filter.
         * @return string Filtered content containing only the allowed HTML.
         */
        public function kses_svg( string $content ) : string {
            return $this->kses_with_svg( $content, array() );
        }

        /**
         * A wrapper around the `wp_kses` method that allows SPAN and SVG elements.
         *
         * @param string $content Text content to filter.
         * @return string Filtered content containing only the allowed HTML.
         */
        public function kses_icon( string $content ) : string {
            return $this->kses_with_svg( $content, array(
                'span' => array(
                    'class' => true,
                    'role' => true,
                    'aria-hidden' => true,
                )
            ) );
        }

        /**
         * A wrapper around the `wp_kses` method that extends both the allowed HTML elements and CSS properties
         * to include SVG elements and the custom element 'slot' and 'is' attributes.
         *
         * @param string $content Text content to filter.
         * @param array $allowed_html An array of allowed HTML elements and attributes.
         * @return string Filtered content containing only the allowed HTML.
         */
        private function kses_with_svg( string $content, array $allowed_html ) : string {
            // merge the SVG elements into the allowed list
            $allowed_html = $this->merge_allowed_svg_html( $allowed_html );
            // extend all elements with the 'slot' and 'is' global attributes for custom elements
            $allowed_html = $this->add_custom_element_attributes( $allowed_html );
            // hook into the safe_style_css filter, so we can include the SVG presentation attributes for only this call to wp_kses
            add_filter( 'safe_style_css', array( $this, 'safe_style_css_svg_presentation_attributes' ) );
            $result = wp_kses( $content, $allowed_html );
            remove_filter( 'safe_style_css', array( $this, 'safe_style_css_svg_presentation_attributes' ) );
            return $result;
        }

        /**
         * Iterates the supplied allowed HTML elements list and adds the 'slot' and 'is' attributes to each element.
         *
         * @param array $allowed_html An array of allowed HTML elements and attributes.
         * @return array An array of allowed HTML elements and attributes with the 'slot' and 'is' attributes.
         */
        private function add_custom_element_attributes( array $allowed_html ): array {
            foreach ( $allowed_html as $_ => &$attributes ) {
                if ( !isset( $attributes['slot'] ) ) {
                    $attributes['slot'] = true;
                }
                if ( !isset( $attributes['is'] ) ) {
                    $attributes['is'] = true;
                }
            }
            return $allowed_html;
        }

        /**
         * Merges the allowed SVG elements into the given allowed HTML elements array.
         *
         * Any pre-existing elements (<a/>) will have there attributes merged with those of the SVG specific element.
         *
         * @param array $allowed_html An array of allowed HTML elements and attributes.
         * @return array
         */
        private function merge_allowed_svg_html( array $allowed_html ) : array {
            if ( empty( $allowed_html ) ) {
                $allowed_html = FOOCONVERT_SVG_ALLOWED_HTML;
            } else {
                foreach ( FOOCONVERT_SVG_ALLOWED_HTML as $tag_name => $attributes ) {
                    if ( isset( $allowed_html[ $tag_name ] ) && is_array( $allowed_html[ $tag_name ] ) && is_array( FOOCONVERT_SVG_ALLOWED_HTML[ $tag_name ] ) ) {
                        // if the tag already exists and both it and the SVG values are an array, merge them
                        $allowed_html[ $tag_name ] = array_merge( $allowed_html[ $tag_name ], FOOCONVERT_SVG_ALLOWED_HTML[ $tag_name ] );
                    } else {
                        // otherwise simply set the tag
                        $allowed_html[ $tag_name ] = FOOCONVERT_SVG_ALLOWED_HTML[ $tag_name ];
                    }
                }
            }
            return $allowed_html;
        }

        /**
         * Callback for the `safe_style_css` filter.
         *
         * This callback extends the allowed CSS properties with the SVG presentation attributes.
         *
         * This filter is only hooked and then immediately unhooked when using the `FooConvert->kses_*()` functions.
         *
         * @param string[] $attr The allowed CSS attributes.
         * @return string[] The SVG extended CSS attributes.
         *
         * @see https://developer.wordpress.org/reference/hooks/safe_style_css/
         */
        public function safe_style_css_svg_presentation_attributes( array $attr ) : array {
            return array_merge( $attr, FOOCONVERT_SVG_SAFE_CSS );
        }

        //endregion

        //region Hooks

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
                    // load translations for the editor JS
                    wp_set_script_translations( FOOCONVERT_EDITOR_ASSET_HANDLE, 'fooconvert' );
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

        //endregion
    }
}