<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\FooConvert;

if ( !class_exists( 'FooPlugins\FooConvert\Admin\Init' ) ) {

    /**
     * Class Init.
     */
    class Init {
        /**
         * Initializes the Init.
         */
        function __construct() {
            add_action( 'admin_menu', array( $this, 'register_menu' ) );
            add_action( 'in_admin_header', array( $this, 'add_custom_header' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueues' ) );
            add_action( 'load-post-new.php', array( $this, 'maybe_redirect_widget_creation' ) );

            new namespace\Stats();
            new namespace\Dashboard();
            new namespace\ContainerManager();
            new namespace\Settings();
            new namespace\LeadsMenu();
            if ( fooconvert_fs()->is_not_paying() ) {
                new namespace\Promotions();
            }

            add_filter( 'block_editor_settings_all', function( $settings, $context ) {
                if ( FooConvert::plugin()->widgets->is_editor() ) {
                    $settings['localAutosaveInterval'] = 0;
                    $settings['autosaveInterval'] = 0;
                }

                return $settings;
            }, 10, 2 );
        }

        /**
         * Registers menu.
         */
        public function register_menu() {
            add_menu_page(
                __( 'FooConvert', 'fooconvert' ),
                __( 'FooConvert', 'fooconvert' ),
                'manage_options',
                FOOCONVERT_MENU_SLUG,
                '__return_null',
                'dashicons-chart-bar',
                50
            );

            do_action( 'fooconvert_admin_menu_before_post_types' );

            add_submenu_page(
                null,
                __( 'Choose Widget Type', 'fooconvert' ),
                __( 'Choose Widget Type', 'fooconvert' ),
                'manage_options',
                FOOCONVERT_MENU_SLUG_WIDGET_CHOOSER,
                array( $this, 'render_widget_type_chooser' )
            );

            do_action( 'fooconvert_admin_menu_after_post_types' );
        }

        /**
         * Redirects generic popup creation requests to the widget type chooser.
         *
         * @return void
         */
        public function maybe_redirect_widget_creation(): void {
            $post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post';
            if ( $post_type !== FOOCONVERT_CPT_POPUP ) {
                return;
            }

            if ( fooconvert_get_requested_popup_type() !== '' ) {
                return;
            }

            wp_safe_redirect( fooconvert_admin_url_widget_type_chooser() );
            exit;
        }

        /**
         * Renders the widget type chooser used before opening the editor.
         *
         * @return void
         */
        public function render_widget_type_chooser(): void {
            $cards = array(
                FOOCONVERT_POPUP_TYPE_BAR    => array(
                    'title'       => __( 'Bar', 'fooconvert' ),
                    'description' => __( 'Create a top or bottom bar with triggers, display rules, and templates.', 'fooconvert' ),
                ),
                FOOCONVERT_POPUP_TYPE_FLYOUT => array(
                    'title'       => __( 'Flyout', 'fooconvert' ),
                    'description' => __( 'Create a side flyout with template variations and the usual FooConvert controls.', 'fooconvert' ),
                ),
                FOOCONVERT_POPUP_TYPE_POPUP  => array(
                    'title'       => __( 'Popup', 'fooconvert' ),
                    'description' => __( 'Create a centered popup and continue into the normal template picker flow.', 'fooconvert' ),
                ),
            );
            ?>
            <div class="wrap fooconvert-widget-chooser">
                <h1><?php esc_html_e( 'Choose a Widget Type', 'fooconvert' ); ?></h1>
                <p><?php esc_html_e( 'Pick the kind of widget you want to create before choosing a template.', 'fooconvert' ); ?></p>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;max-width:920px;margin-top:24px;">
                    <?php foreach ( $cards as $popup_type => $card ) : ?>
                        <div class="card" style="margin:0;padding:24px;">
                            <h2 style="margin-top:0;"><?php echo esc_html( $card['title'] ); ?></h2>
                            <p><?php echo esc_html( $card['description'] ); ?></p>
                            <p style="margin-bottom:0;">
                                <a class="button button-primary" href="<?php echo esc_url( fooconvert_admin_url_widget_new( $popup_type ) ); ?>">
                                    <?php
                                    printf(
                                        esc_html__( 'Create %s', 'fooconvert' ),
                                        esc_html( $card['title'] )
                                    );
                                    ?>
                                </a>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top:24px;">
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . FOOCONVERT_CPT_POPUP ) ); ?>">
                        <?php esc_html_e( 'Back to widgets', 'fooconvert' ); ?>
                    </a>
                </p>
            </div>
            <?php
        }

        /**
         * Determines whether valid page.
         */
        private function is_valid_page() {
            if ( function_exists( 'get_current_screen' ) ) {
                $current_screen = get_current_screen();

                if ( fooconvert_str_contains( $current_screen->id, 'fooconvert-pricing' ) ) {
                    return false;
                } else if ( fooconvert_str_contains( $current_screen->id, FOOCONVERT_MENU_SLUG ) ) {
                    return true;
                }

                $current_post_Type = $current_screen->post_type;
                if ( $current_post_Type === FooConvert::plugin()->widgets->get_registered_post_type() ) {
                    return $current_screen->base !== 'post';
                }
            }

            return false;
        }

        /**
         * Handles admin enqueues.
         */
        public function admin_enqueues() {
            if ( $this->is_valid_page() ) {
                wp_enqueue_style(
                    'fooconvert-admin',
                    FOOCONVERT_ASSETS_URL . 'admin/css/admin.css',
                    null,
                    FOOCONVERT_VERSION
                );
            }
        }

        /**
         * Adds custom header.
         */
        public function add_custom_header() {
            if ( $this->is_valid_page() ) {
                $current_screen = get_current_screen();

                $drop = '';
                $pages = array( 'contact', 'pricing' );
                foreach ( $pages as $page ) {
                    $check = FOOCONVERT_MENU_SLUG . '-' . $page;
                    if ( fooconvert_str_contains( $current_screen->id, $check ) ) {
                        $drop = 'drop';
                        break;
                    }
                }
                ?>
                <div class="fooconvert-admin-header <?php echo esc_attr( $drop ); ?>">
                    <div class="fooconvert-title">
                        <img class="fooconvert-logo" src="<?php echo esc_url( FOOCONVERT_ASSETS_URL . 'admin/img/horizontal-logo-50.png' ); ?>" alt="FooConvert Logo">
                        <p>Version <?php echo esc_html( FOOCONVERT_VERSION ); ?></p>
                    </div>
                </div>
                <?php
            }
        }
    }
}
