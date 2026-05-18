<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\FooConvert;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
            add_action( 'admin_menu', array( $this, 'reorder_menu' ), 999 );
            add_action( 'in_admin_footer', array( $this, 'add_custom_footer' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueues' ) );

            new namespace\Stats();
            new namespace\Dashboard();
            new namespace\ContainerManager();
            new namespace\Settings();
            new namespace\LeadsMenu();
            new namespace\BrandContext();
            new \FooPlugins\FooConvert\AI\PopupBuilder\Admin();
            if ( fooconvert_fs()->is_not_paying() ) {
                new namespace\Promotions();
            }

            add_filter( 'block_editor_settings_all', function( $settings, $context ) {
                if ( FooConvert::plugin()->post_type->is_editor() ) {
                    $settings['localAutosaveInterval'] = 0;
                    $settings['autosaveInterval'] = 0;

                    if ( isset( $context->post ) && $context->post instanceof \WP_Post ) {
                        $block_name = fooconvert_get_popup_type_block_name( fooconvert_get_popup_type( $context->post ) );
                        if ( $block_name !== '' ) {
                            $settings['template'] = array(
                                array( $block_name ),
                            );
                            $settings['templateLock'] = 'all';
                        }
                    }
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
                'dashicons-format-chat',
                50
            );

            do_action( 'fooconvert_admin_menu_before_post_types' );

            $popup_post_type = get_post_type_object( FOOCONVERT_CPT_POPUP );
            if ( $popup_post_type ) {
                add_submenu_page(
                    FOOCONVERT_MENU_SLUG,
                    $popup_post_type->labels->add_new_item,
                    $popup_post_type->labels->add_new_item,
                    $popup_post_type->cap->create_posts,
                    'post-new.php?post_type=' . FOOCONVERT_CPT_POPUP,
                    null,
                    15
                );
            }

            add_submenu_page(
                null,
                __( 'Choose Popup Type', 'fooconvert' ),
                __( 'Choose Popup Type', 'fooconvert' ),
                'manage_options',
                FOOCONVERT_MENU_SLUG_POPUP_CHOOSER,
                array( $this, 'render_popup_type_chooser' )
            );

            do_action( 'fooconvert_admin_menu_after_post_types' );
        }

        /**
         * Ensures the submenu order is Popups, Add New Popup, then Dashboard.
         *
         * @return void
         */
        public function reorder_menu(): void {
            global $submenu;

            if ( !isset( $submenu[FOOCONVERT_MENU_SLUG] ) || !is_array( $submenu[FOOCONVERT_MENU_SLUG] ) ) {
                return;
            }

            $desired_slugs = apply_filters( 'fooconvert_admin_menu_desired_slugs', array(
                'edit.php?post_type=' . FOOCONVERT_CPT_POPUP,
                'post-new.php?post_type=' . FOOCONVERT_CPT_POPUP,
                FOOCONVERT_MENU_SLUG,
            ) );

            $ordered = array();
            $added = array();

            foreach ( $desired_slugs as $desired_slug ) {
                foreach ( $submenu[FOOCONVERT_MENU_SLUG] as $item ) {
                    if ( !isset( $item[2] ) || $item[2] !== $desired_slug ) {
                        continue;
                    }

                    $ordered[] = $item;
                    $added[] = $desired_slug;
                    break;
                }
            }

            foreach ( $submenu[FOOCONVERT_MENU_SLUG] as $item ) {
                $slug = isset( $item[2] ) ? $item[2] : '';
                if ( in_array( $slug, $added, true ) ) {
                    continue;
                }

                $ordered[] = $item;
            }

            $submenu[FOOCONVERT_MENU_SLUG] = array_values( $ordered );
        }

        /**
         * Renders the popup type chooser used before opening the editor.
         *
         * @return void
         */
        public function render_popup_type_chooser(): void {
            $cards = array(
                FOOCONVERT_POPUP_TYPE_BAR    => array(
                    'title'       => __( 'Bar', 'fooconvert' ),
                    'description' => __( 'Create a top or bottom bar with triggers, display rules, and templates.', 'fooconvert' ),
                ),
                FOOCONVERT_POPUP_TYPE_FLYOUT => array(
                    'title'       => __( 'Flyout', 'fooconvert' ),
                    'description' => __( 'Create a side flyout with template variations and the usual popup controls.', 'fooconvert' ),
                ),
                FOOCONVERT_POPUP_TYPE_OVERLAY => array(
                    'title'       => __( 'Overlay', 'fooconvert' ),
                    'description' => __( 'Create a centered overlay and continue into the normal template picker flow.', 'fooconvert' ),
                ),
            );
            ?>
            <div class="wrap fooconvert-popup-chooser">
                <div class="fooconvert-admin-page-heading">
                    <div class="fooconvert-admin-page-heading__copy">
                        <h1><?php esc_html_e( 'Choose a Popup Type', 'fooconvert' ); ?></h1>
                        <p><?php esc_html_e( 'Pick the kind of popup you want to create before choosing a template.', 'fooconvert' ); ?></p>
                    </div>
                    <a class="fooconvert-ai-popup-builder-button" href="<?php echo esc_url( fooconvert_admin_url_ai_popup_builder() ); ?>" target="_top">
                        <svg class="fooconvert-ai-popup-builder-button__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                            <path d="M12 2.75l1.9 5.12 5.35 1.98-5.35 1.98L12 17.25l-1.9-5.42-5.35-1.98 5.35-1.98L12 2.75z" />
                            <path d="m18.25 14.25.75 2.03 2 .72-2 .72-.75 2.03-.75-2.03-2-.72 2-.72.75-2.03z" />
                            <path d="m5.75 14.75.5 1.32 1.25.43-1.25.43-.5 1.32-.5-1.32L4 16.5l1.25-.43.5-1.32z" />
                        </svg>
                        <span><?php esc_html_e( 'AI Popup Builder', 'fooconvert' ); ?></span>
                    </a>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;max-width:920px;margin-top:24px;">
                    <?php foreach ( $cards as $popup_type => $card ) : ?>
                        <div class="card" style="margin:0;padding:24px;">
                            <h2 style="margin-top:0;"><?php echo esc_html( $card['title'] ); ?></h2>
                            <p><?php echo esc_html( $card['description'] ); ?></p>
                            <p style="margin-bottom:0;">
                                <a class="button button-primary" href="<?php echo esc_url( fooconvert_admin_url_popup_new( $popup_type ) ); ?>">
                                    <?php
                                    /* translators: %s is the popup type card title, for example "Bar" or "Overlay". */
                                    $create_label = sprintf( esc_html__( 'Create %s', 'fooconvert' ), $card['title'] );
                                    echo esc_html( $create_label );
                                    ?>
                                </a>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top:24px;">
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . FOOCONVERT_CPT_POPUP ) ); ?>">
                        <?php esc_html_e( 'Back to popups', 'fooconvert' ); ?>
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
                if ( $current_post_Type === FOOCONVERT_CPT_POPUP ) {
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
         * Adds minimal footer branding on plugin admin pages.
         */
        public function add_custom_footer() {
            if ( $this->is_valid_page() ) {
                ?>
                <div class="fooconvert-admin-footer">
                    <?php echo esc_html( sprintf( 'FooConvert v%s', FOOCONVERT_VERSION ) ); ?>
                </div>
                <?php
            }
        }
    }
}
