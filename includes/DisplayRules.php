<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use WP_Post;
use WP_Post_Type;
use WP_Query;
use WP_Term;
use WP_User;

/**
 * Class DisplayRules.
 */
class DisplayRules extends BaseComponent {
    /**
     * Prevent duplicate meta registration work per request.
     *
     * @var bool
     */
    private bool $registered = false;

    /**
     * Prevent duplicate list table hook registration per request.
     *
     * @var bool
     */
    private bool $column_registered = false;

    /**
     * Initializes the DisplayRules.
     */
    public function __construct() {
        parent::__construct();

        add_action( 'wp_after_insert_post', array( $this, 'after_insert_should_compile' ), 10, 4 );
        add_action( 'template_redirect', array( $this, 'enqueue_required' ), 5 );
        add_action( 'wp_footer', array( $this, 'render_enqueued' ), 5 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    //region Meta

    /**
     * Registers the display rules meta key for the popup post type.
     *
     * @return bool True if the meta key was successfully registered in the global array, false if not.
     *
     * @since 1.0.0
     */
    public function register(): bool {
        if ( $this->registered ) {
            return true;
        }

        $this->register_column();
        $this->registered = register_meta( 'post', FOOCONVERT_META_KEY_DISPLAY_RULES, array(
            'object_subtype' => FOOCONVERT_CPT_POPUP,
            'single'         => true,
            'type'           => 'object',
            'description'    => __( 'Display rules for FooConvert.', 'fooconvert' ),
            'auth_callback'  => array( $this, 'auth_callback' ),
            'default'        => $this->defaults(),
            'show_in_rest'   => array( 'schema' => $this->schema() )
        ) );

        return $this->registered;
    }

    /**
     * Returns the popup list table column name.
     *
     * @return string
     */
    private function get_column_name(): string {
        return FOOCONVERT_CPT_POPUP . '_display_rules';
    }

    /**
     * Registers the popup list table column.
     *
     * @return void
     */
    public function register_column(): void {
        if ( $this->column_registered ) {
            return;
        }

        add_filter( "manage_".FOOCONVERT_CPT_POPUP."_posts_columns", function ( $columns ) {
            return $this->create_column( $columns );
        } );

        add_filter( "manage_edit-".FOOCONVERT_CPT_POPUP."_sortable_columns", function ( $columns ) {
            return $this->sortable_column( $columns );
        } );

        add_action( "manage_".FOOCONVERT_CPT_POPUP."_posts_custom_column", function ( $column_name, $post_id ) {
            $this->create_column_content( $column_name, $post_id );
        }, 10, 2 );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_column_assets' ) );

        $this->column_registered = true;
    }

    /**
     * Enqueue the popup list table column assets.
     *
     * @param string $hook_suffix The current admin hook suffix.
     * @return void
     */
    public function enqueue_column_assets( string $hook_suffix ): void {
        if ( !$this->is_popup_list_table_screen( $hook_suffix ) ) {
            return;
        }

        $asset_path = FOOCONVERT_ASSETS_PATH . 'admin/display-rules-list/index.asset.php';
        if ( !file_exists( $asset_path ) ) {
            return;
        }

        $asset = include $asset_path;
        if ( !Utils::has_keys( $asset, array( 'dependencies', 'version' ) ) ) {
            return;
        }

        $handle = 'fc-admin-display-rules-list';

        wp_enqueue_style( 'wp-components' );
        wp_enqueue_style(
            $handle,
            FOOCONVERT_ASSETS_URL . 'admin/display-rules-list/index.css',
            array( 'wp-components' ),
            $asset['version']
        );
        wp_enqueue_script(
            $handle,
            FOOCONVERT_ASSETS_URL . 'admin/display-rules-list/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
        wp_set_script_translations( $handle, 'fooconvert' );
        $this->enqueue_component_data( $handle );
        wp_add_inline_style(
            $handle,
            ".column-" . $this->get_column_name() . " { width: 24%; min-width: 280px; }"
        );
    }

    /**
     * Determine whether the current request is the popup list table screen.
     *
     * @param string $hook_suffix The current admin hook suffix.
     * @return bool
     */
    private function is_popup_list_table_screen( string $hook_suffix ): bool {
        if ( $hook_suffix !== 'edit.php' ) {
            return false;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $post_type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : '';
        // phpcs:enable

        return $post_type === FOOCONVERT_CPT_POPUP;
    }

    /**
     * Creates column.
     */
    public function create_column( $columns ): array {
        // add the column after the default title column
        $updated = array();
        $inserted = false;
        $display_rules_column_name = $this->get_column_name();

        foreach ( $columns as $column_name => $column_display_name ) {
            $updated[ $column_name ] = $column_display_name;
            if ( $column_name === 'title' ) {
                $updated[ $display_rules_column_name ] = __( 'Display Rules', 'fooconvert' );
                $inserted = true;
            }
        }

        // if for some reason the column was not inserted, add it
        if ( !$inserted ) {
            $updated[ $display_rules_column_name ] = __( 'Display Rules', 'fooconvert' );
        }
        return $updated;
    }

    /**
     * Handles sortable column.
     */
    public function sortable_column( $columns ) {
        $column_name = $this->get_column_name();
        $columns[ $column_name ] = array(
            $column_name,
            false,
            __( 'Display Rules', 'fooconvert' ),
            __( 'Table ordered by display rules.', 'fooconvert' ),
        );
        return $columns;
    }

    /**
     * @param string $column_name The current list table column name.
     * @param int    $post_id The current post ID.
     * @return void
     */
    public function create_column_content( $column_name, $post_id ): void {
        if ( $column_name === $this->get_column_name() ) {
            $rules = $this->prepare_column_rules( get_post_meta( $post_id, FOOCONVERT_META_KEY_DISPLAY_RULES, true ) );
            $admin_state = $this->get_admin_state( (int) $post_id, $rules );

            $config = array(
                'postId'        => (int) $post_id,
                'postTitle'     => get_the_title( $post_id ),
                'rules'         => $rules,
                'summary'       => $this->get_column_summary( $rules ),
                'canEdit'       => $admin_state['canEdit'],
                'showSummary'   => $admin_state['showSummary'],
                'lockedMessage' => $admin_state['lockedMessage'],
            );
            $json = wp_json_encode( $config );

            if ( !is_string( $json ) ) {
                esc_html_e( 'Display rules unavailable.', 'fooconvert' );
                return;
            }

            echo '<div class="fc-display-rules-list__app" data-config="' . esc_attr( $json ) . '">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->render_column_summary_markup(
                $config['summary'],
                $config['lockedMessage'],
                $config['showSummary']
            );
            echo '</div>';
        }
    }

    /**
     * Build the admin list state for a popup display-rules row.
     *
     * @param int   $post_id Popup post ID.
     * @param array $rules Normalized display rules.
     * @return array{canEdit:bool, showSummary:bool, lockedMessage:string}
     */
    private function get_admin_state( int $post_id, array $rules ): array {
        $can_edit_post = current_user_can( 'edit_post', $post_id );
        $fallback_state = array(
            'canEdit'       => $can_edit_post,
            'showSummary'   => true,
            'lockedMessage' => $can_edit_post
                ? ''
                : __( 'You do not have permission to edit these display rules.', 'fooconvert' ),
        );

        /**
         * Filters the display-rules admin state for a popup list-table row.
         *
         * @param array{canEdit:bool, showSummary:bool, lockedMessage:string} $state The current list row state.
         * @param int                                                         $post_id Popup post ID.
         * @param array                                                       $rules Normalized display rules.
         * @param DisplayRules                                                $display_rules The display rules component instance.
         */
        $state = apply_filters( 'fooconvert_display_rules_admin_state', $fallback_state, $post_id, $rules, $this );

        if ( !is_array( $state ) ) {
            return $fallback_state;
        }

        return array(
            'canEdit'       => !empty( $state['canEdit'] ),
            'showSummary'   => !array_key_exists( 'showSummary', $state ) || !empty( $state['showSummary'] ),
            'lockedMessage' => isset( $state['lockedMessage'] ) ? (string) $state['lockedMessage'] : '',
        );
    }

    /**
     * Normalize the display rules value for list table rendering.
     *
     * @param mixed $display_rules The stored display rules value.
     * @return array
     */
    private function prepare_column_rules( $display_rules ): array {
        $defaults = $this->defaults();
        $display_rules = is_array( $display_rules ) ? $display_rules : array();

        return array(
            'location' => isset( $display_rules['location'] ) && is_array( $display_rules['location'] )
                ? array_values( $display_rules['location'] )
                : $defaults['location'],
            // false positive - this array is not used to query posts
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
            'exclude'  => isset( $display_rules['exclude'] ) && is_array( $display_rules['exclude'] )
                ? array_values( $display_rules['exclude'] )
                : $defaults['exclude'],
            'users'    => isset( $display_rules['users'] ) && is_array( $display_rules['users'] )
                ? array_values( $display_rules['users'] )
                : $defaults['users'],
        );
    }

    /**
     * Build the fallback summary shown in the popup list table.
     *
     * @param array $rules The display rules.
     * @return array{location:string, exclude:string, users:string, showExclude:bool, showUsers:bool}
     */
    private function get_column_summary( array $rules ): array {
        $location = $this->get_location_summary_text(
            Utils::get_array( $rules, 'location' ),
            'default',
            __( 'Not set', 'fooconvert' )
        );
        $exclude = $this->get_location_summary_text(
            Utils::get_array( $rules, 'exclude' ),
            'exclude',
            __( 'None', 'fooconvert' )
        );
        $users = $this->get_user_summary_text(
            Utils::get_array( $rules, 'users' ),
            __( 'Not set', 'fooconvert' )
        );
        $compiled_users = array_values( array_filter(
            Utils::get_array( $rules, 'users' ),
            function ( $user ) {
                return is_string( $user ) && $user !== '';
            }
        ) );
        $has_all_users = in_array( 'general:all_users', $compiled_users, true );

        return array(
            'location'    => $location,
            'exclude'     => $exclude,
            'users'       => $users,
            'showExclude' => $exclude !== __( 'None', 'fooconvert' ),
            'showUsers'   => !$has_all_users,
        );
    }

    /**
     * Render the fallback column summary markup.
     *
     * @param array{location:string, exclude:string, users:string, showExclude:bool, showUsers:bool} $summary The current summary strings.
     * @param string                                                                    $locked_message Optional. A lock message to display beneath the summary.
     * @param bool                                                                      $show_summary Whether the summary rows should be rendered.
     * @return string
     */
    private function render_column_summary_markup( array $summary, string $locked_message = '', bool $show_summary = true ): string {
        ob_start();
        ?>
        <?php if ( $show_summary ) : ?>
            <div class="fc-display-rules-list__summary">
                <div class="fc-display-rules-list__summary-row">
                    <span class="fc-display-rules-list__summary-label"><?php esc_html_e( 'Show on', 'fooconvert' ); ?></span>
                    <span class="fc-display-rules-list__summary-value"><?php echo esc_html( Utils::get_string( $summary, 'location' ) ); ?></span>
                </div>
                <?php if ( Utils::get_bool( $summary, 'showExclude' ) ) : ?>
                    <div class="fc-display-rules-list__summary-row">
                        <span class="fc-display-rules-list__summary-label"><?php esc_html_e( 'Hide from', 'fooconvert' ); ?></span>
                        <span class="fc-display-rules-list__summary-value"><?php echo esc_html( Utils::get_string( $summary, 'exclude' ) ); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ( Utils::get_bool( $summary, 'showUsers' ) ) : ?>
                    <div class="fc-display-rules-list__summary-row">
                        <span class="fc-display-rules-list__summary-label"><?php esc_html_e( 'Users', 'fooconvert' ); ?></span>
                        <span class="fc-display-rules-list__summary-value"><?php echo esc_html( Utils::get_string( $summary, 'users' ) ); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ( $locked_message !== '' ) : ?>
            <p class="<?php echo esc_attr( $show_summary ? 'description' : 'fc-display-rules-list__locked-message' ); ?>"><?php echo esc_html( $locked_message ); ?></p>
        <?php endif; ?>
        <?php
        return trim( (string) ob_get_clean() );
    }

    /**
     * Create a summary string for the location-style rules.
     *
     * @param array  $locations The configured locations.
     * @param string $context The location context.
     * @param string $empty_label The fallback label.
     * @return string
     */
    private function get_location_summary_text( array $locations, string $context, string $empty_label ): string {
        $labels = array();
        $option_map = $this->get_option_map( $this->get_component_locations( $context ) );

        foreach ( $locations as $location ) {
            if ( !is_array( $location ) ) {
                continue;
            }

            $type = Utils::get_string( $location, 'type' );
            if ( $type === '' ) {
                continue;
            }

            $option = isset( $option_map[ $type ] ) && is_array( $option_map[ $type ] )
                ? $option_map[ $type ]
                : array();
            $label = Utils::get_string( $option, 'label' );
            $label = $label !== '' ? $label : $type;

            if ( fooconvert_str_starts_with( $type, 'specific:' ) ) {
                $data_labels = array();
                foreach ( Utils::get_array( $location, 'data' ) as $data_item ) {
                    $data_label = Utils::get_string( $data_item, 'label' );
                    if ( $data_label !== '' ) {
                        $data_labels[] = $data_label;
                    }
                }

                $data_summary = $this->get_summary_text_from_labels( $data_labels, '' );
                if ( $data_summary !== '' ) {
                    $label .= ': ' . $data_summary;
                }
            }

            $labels[] = $label;
        }

        return $this->get_summary_text_from_labels( $labels, $empty_label );
    }

    /**
     * Create a summary string for the user rules.
     *
     * @param array  $users The configured user rules.
     * @param string $empty_label The fallback label.
     * @return string
     */
    private function get_user_summary_text( array $users, string $empty_label ): string {
        $labels = array();
        $option_map = $this->get_option_map( $this->get_component_users() );

        foreach ( $users as $user ) {
            $user = is_string( $user ) ? $user : '';
            if ( $user === '' ) {
                continue;
            }

            $option = isset( $option_map[ $user ] ) && is_array( $option_map[ $user ] )
                ? $option_map[ $user ]
                : array();
            $label = Utils::get_string( $option, 'label' );
            $labels[] = $label !== '' ? $label : $user;
        }

        return $this->get_summary_text_from_labels( $labels, $empty_label );
    }

    /**
     * Flatten grouped select options into a lookup map.
     *
     * @param array $groups The grouped options.
     * @return array<string, array>
     */
    private function get_option_map( array $groups ): array {
        $option_map = array();

        foreach ( $groups as $group ) {
            foreach ( Utils::get_array( $group, 'options' ) as $option ) {
                if ( !is_array( $option ) ) {
                    continue;
                }

                $value = Utils::get_string( $option, 'value' );
                if ( $value !== '' ) {
                    $option_map[ $value ] = $option;
                }
            }
        }

        return $option_map;
    }

    /**
     * Reduce a label list to a short summary for the list table.
     *
     * @param array  $labels The labels to summarize.
     * @param string $empty_label The fallback label.
     * @return string
     */
    private function get_summary_text_from_labels( array $labels, string $empty_label ): string {
        $labels = array_values( array_filter( array_map( 'strval', $labels ), 'strlen' ) );
        $labels = array_values( array_unique( $labels ) );

        if ( empty( $labels ) ) {
            return $empty_label;
        }

        $count = count( $labels );
        $visible = array_slice( $labels, 0, 2 );
        $summary = implode( ', ', $visible );

        if ( $count > 2 ) {
            $summary .= sprintf( __( ' +%d more', 'fooconvert' ), $count - 2 );
        }

        return $summary;
    }

    /**
     * The auth callback for the display rules meta key.
     *
     * @return bool True if the current user can edit the meta key, false if not.
     *
     * @since 1.0.0
     */
    public function auth_callback(): bool {
        if ( !current_user_can( 'edit_posts' ) ) {
            return false;
        }

        $args = func_get_args();
        $post_id = isset( $args[2] ) ? absint( $args[2] ) : 0;

        /**
         * Filters whether display rules can be edited for a specific popup.
         *
         * @param bool         $can_edit Whether display rules are editable. Defaults to `true` once capability checks pass.
         * @param int          $post_id Popup post ID.
         * @param string       $context Current edit context. `meta` when validating the meta auth callback.
         * @param DisplayRules $display_rules The display rules component instance.
         */
        return (bool) apply_filters( 'fooconvert_display_rules_can_edit', true, $post_id, 'meta', $this );
    }

    /**
     * Get the default metadata for the display rules.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function defaults(): array {
        return array(
            'location' => array(),
            // false positive - this array is not used to query posts
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
            'exclude'  => array(),
            'users'    => array( 'general:all_users' )
        );
    }

    /**
     * Get the schema for the meta key value.
     *
     * @return array
     *
     * @since 1.0.0
     * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
     */
    public function schema(): array {
        return array(
            'type'       => 'object',
            'properties' => array(
                'location' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'type' => array( 'type' => 'string', 'required' => true ),
                            'data' => array(
                                'type'  => 'array',
                                'items' => array(
                                    'type'       => 'object',
                                    'properties' => array(
                                        'id'    => array( 'type' => 'integer', 'required' => true ),
                                        'label' => array( 'type' => 'string', 'required' => true )
                                    )
                                )
                            )
                        )
                    )
                ),
                // false positive - this array is not used to query posts
                // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
                'exclude'  => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'type' => array( 'type' => 'string', 'required' => true ),
                            'data' => array(
                                'type'  => 'array',
                                'items' => array(
                                    'type'       => 'object',
                                    'properties' => array(
                                        'id'    => array( 'type' => 'integer', 'required' => true ),
                                        'label' => array( 'type' => 'string', 'required' => true )
                                    )
                                )
                            )
                        )
                    )
                ),
                'users'    => array(
                    'type'  => 'array',
                    'items' => array(
                        'type' => 'string'
                    )
                )
            )
        );
    }

    //endregion

    //region Component - Create the data for the display rules component

    /**
     * Returns the component data name.
     */
    function get_component_data_name(): string {
        return 'FC_DISPLAY_RULES';
    }

    /**
     * Returns the component data.
     */
    function get_component_data(): array {
        return array(
            'meta'     => array(
                'key'      => FOOCONVERT_META_KEY_DISPLAY_RULES,
                'defaults' => $this->defaults()
            ),
            'postType' => FOOCONVERT_CPT_POPUP,
            'location' => $this->get_component_locations(),
            // false positive - this array is not used to query posts
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
            'exclude'  => $this->get_component_locations( 'exclude' ),
            'users'    => $this->get_component_users()
        );
    }

    /**
     * Returns the component locations.
     */
    function get_component_locations( string $context = 'default' ): array {
        $locations = array(
            array(
                'group'   => 'general',
                'label'   => __( 'General', 'fooconvert' ),
                'options' => array(
                    array(
                        'value' => 'general:entire_site',
                        'label' => __( 'Entire Site', 'fooconvert' )
                    ),
                    array(
                        'value' => 'general:front_page',
                        'label' => __( 'Front Page', 'fooconvert' )
                    ),
                    array(
                        'value' => 'general:blog',
                        'label' => __( 'Blog', 'fooconvert' )
                    ),
                    array(
                        'value' => 'general:search_results',
                        'label' => __( 'Search Results', 'fooconvert' )
                    ),
                    array(
                        'value' => 'general:no_search_results',
                        'label' => __( 'No Search Results', 'fooconvert' )
                    ),
                    array(
                        'value' => 'general:404',
                        'label' => __( '404 Template', 'fooconvert' )
                    )
                )
            )
        );

        // First, load all public post types
        $public_post_types = get_post_types( array( 'public' => true ), 'objects' );
        $post_type_locations = [];
        foreach ( $public_post_types as $post_type ) {
            if ( $post_type instanceof WP_Post_Type ) {
                $post_type_locations[] = array(
                    'value' => 'specific:' . $post_type->name,
                    'label' => $post_type->label,
                    'data'  => array(
                        'kind'        => 'postType',
                        'name'        => $post_type->name,
                        // Translators: %s refers to the taxonomy that is being searched for. e.g. "Category".
                        'placeholder' => sprintf( __( 'Type to choose %s...', 'fooconvert' ), strtolower( $post_type->label ) )
                    )
                );
            }
        }

        if ( !empty( $post_type_locations ) ) {
            $locations[] = array(
                'group'   => 'specific_posts',
                'label'   => __( 'Specific Posts', 'fooconvert' ),
                'options' => $post_type_locations
            );
        }

        // Next, load all public taxonomies
        $taxonomy_locations = [];
        $public_taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
        foreach ( $public_taxonomies as $taxonomy ) {
            if ( $taxonomy instanceof \WP_Taxonomy ) {
                $taxonomy_locations[] = array(
                    'value' => 'specific:' . $taxonomy->name,
                    'label' => $taxonomy->label,
                    'data'  => array(
                        'kind'        => 'taxonomy',
                        'name'        => $taxonomy->name,
                        // Translators: %s refers to the taxonomy that is being searched for. e.g. "Category".
                        'placeholder' => sprintf( __( 'Type to choose %s...', 'fooconvert' ), strtolower( $taxonomy->label ) )
                    )
                );
            }
        }

        if ( !empty( $taxonomy_locations ) ) {
            $locations[] = array(
                'group'   => 'specific_taxonomies',
                'label'   => __( 'Specific Taxonomies', 'fooconvert' ),
                'options' => $taxonomy_locations
            );
        }

        // Next, load all post types that have an archive.
        $archive_locations = [];
        foreach ( $public_post_types as $post_type ) {
            if ( $post_type instanceof WP_Post_Type && $post_type->has_archive !== false ) {
                $archive_locations[] = array(
                    'value' => 'archive:' . $post_type->name,
                    'label' => $post_type->label,
                );
            }
        }

        if ( !empty( $archive_locations ) ) {
            $locations[] = array(
                'group'   => 'archive',
                'label'   => __( 'Post Archives', 'fooconvert' ),
                'options' => $archive_locations
            );
        }

        if ( $context === 'exclude' ) {
            // remove 'general:entire_site' from the exclude locations
            array_shift( $locations[0]['options'] );
        }

        return apply_filters( 'fooconvert_display_rules_locations', $locations, $context );
    }

    /**
     * Returns the component users.
     */
    function get_component_users(): array {
        $roles = Utils::array_map( wp_roles()->get_names(), function ( $value, $key ) {
            return [ 'value' => "role:$key", 'label' => $value ];
        } );
        usort( $roles, function ( $a, $b ) {
            return strcmp( $a['label'], $b['label'] );
        } );

        return array(
            array(
                'group'   => 'general',
                'label'   => __( 'General', 'fooconvert' ),
                'options' => array(
                    array(
                        'value' => 'general:all_users',
                        'label' => __( 'All Users', 'fooconvert' )
                    ),
                    array(
                        'value' => 'general:logged_in',
                        'label' => __( 'Logged In', 'fooconvert' )
                    ),
                    array(
                        'value' => 'general:logged_out',
                        'label' => __( 'Logged Out', 'fooconvert' )
                    )
                )
            ),
            array(
                'group'   => 'roles',
                'label'   => __( 'Roles', 'fooconvert' ),
                'options' => $roles
            )
        );
    }

    //endregion

    //region Compilation - Manages the 'fooconvert_display_rules' option

    /**
     * Callback for the `wp_after_insert_post` action.
     *
     * This callback performs some checks on the incoming post and if it is a widget post that supports display
     * rules triggers a recompile of the `fooconvert_display_rules` option.
     *
     * @remarks
     * The `wp_after_insert_post` action is used instead of `save_post` as metadata is saved by the time its
     * callback is invoked.
     *
     * @param int $post_id The post id that was inserted.
     * @param WP_Post $post The post object for the post.
     * @param bool $updated Whether the post was updated or created.
     * @param WP_Post|null $post_before If updated, this is the previous post value, otherwise `null`.
     *
     * @since 1.0.0
     */
    public function after_insert_should_compile( int $post_id, WP_Post $post, bool $updated, ?WP_Post $post_before ) {
        // bail out if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( $post->post_type !== FOOCONVERT_CPT_POPUP ) {
            return;
        }

        // otherwise if this is OR was a published widget post
        $was_published = $post_before instanceof WP_Post && $post_before->post_status === 'publish';
        $is_published = $post->post_status === 'publish';
        if ( $was_published || $is_published ) {
            $this->compile( $post_id, $is_published );
        }
    }

    /**
     * Compile the display rules for the given post id and status and update the `fooconvert_display_rules` option.
     *
     * @param int $post_id The post id.
     * @param bool $is_published Whether the post is currently published.
     *
     * @since 1.0.0
     */
    public function compile( int $post_id, bool $is_published ) {
        $widgets = [];

        $cached = get_option( FOOCONVERT_OPTION_DISPLAY_RULES, [] );

        // First, make sure all the widgets exist and are published.
        foreach ( $cached as $widget ) {
            $id = Utils::get_int( $widget, 'post_id' );

            // Do not add the current post to the list, so that it is compiled later.
            if ( $id === $post_id ) {
                continue;
            }

            $post = get_post( $id );

            if ( $post && $post->post_status === 'publish' ) {
                // The widget exists and is published.
                $widgets[] = $widget;
            }
        }

        // then go about compiling the rules again if currently published
        if ( $is_published ) {
            $compiled = $this->get_compiled( $post_id );
            if ( !empty( $compiled ) ) {
                $widgets[] = $compiled;
            }
        }

        do_action( 'fooconvert_display_rules_compiled', $widgets );

        update_option( FOOCONVERT_OPTION_DISPLAY_RULES, $widgets, true );
    }

    /**
     * Get the compiled display rules for the given post in a format better suited for location matching.
     *
     * @param int $post_id The post id of the display rules to compile.
     * @return array An array containing only the relevant data to perform location matching.
     *
     * @since 1.0.0
     */
    public function get_compiled( int $post_id ): array {
        $rules = get_post_meta( $post_id, FOOCONVERT_META_KEY_DISPLAY_RULES, true );
        if ( !empty( $rules ) ) {
            // make sure at a minimum the rules are the expected types and that at least 1 location and user
            // have been set before compiling
            $should_compile = Utils::has_keys( $rules, array_keys( $this->defaults() ), function ( $value, $key ) {
                if ( is_array( $value ) ) {
                    if ( $key === 'location' || $key === 'users' ) {
                        return !empty( $value );
                    }
                    return true;
                }
                return false;
            } );
            if ( $should_compile ) {
                $include = $this->compile_locations( $rules['location'] );
                if ( !empty( $include ) ) {
                    return array(
                        'post_id'            => $post_id,
                        'include'            => $include,
                        // false positive - this array is not used to query posts
                        // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
                        'exclude'            => $this->compile_locations( $rules['exclude'] ),
                        'users'              => $rules['users'],
                        'compatibility_mode' => FooConvert::plugin()->compatibility->is_enabled( $post_id )
                    );
                }
            }
        }
        return array();
    }

    /**
     * Compile an array of metadata locations into a flattened format better suited for location matching.
     *
     * @param array $locations An array of locations from the metadata.
     *
     * @return array A flattened array containing only the relevant data to perform location matching.
     *
     * @example
     * $input = array(
     *      array(
     *          'type' => 'general:*',
     *          'data' => null
     *      ),
     *      array(
     *          'type' => 'archive:*',
     *          'data' => null
     *      ),
     *      array(
     *          'type' => 'specific:*',
     *          'data' => array(
     *              array( 'id' => 1, 'label' => 'Abc' ),
     *              array( 'id' => 2, 'label' => 'Xyz' )
     *          )
     *      )
     * );
     * $output = compile_locations( $input );
     * $output = array(
     *      'general:*' => true,
     *      'archive:*' => true,
     *      'specific:*' => array( 1, 2 )
     * );
     *
     * @since 1.0.0
     */
    private function compile_locations( array $locations ): array {
        $entire_site = Utils::array_find( $locations, function ( $location ) {
            return Utils::get_string( $location, 'type' ) === 'general:entire_site';
        }, false );
        if ( $entire_site ) {
            // if the entire site value is set, we can ignore all others
            return array( 'general:entire_site' => true );
        } else {
            // otherwise go through the locations and 'flatten' them
            return array_reduce( $locations, function ( $result, $location ) {
                $type = Utils::get_string( $location, 'type' );
                if ( fooconvert_str_starts_with( $type, 'specific:' ) ) {
                    // specific locations require post_ids that must be matched, so extract them out
                    $data = Utils::get_array( $location, 'data' );
                    $post_ids = array_reduce( $data, function ( $result, $data_value ) {
                        $post_id = Utils::get_int( $data_value, 'id' );
                        if ( !empty( $post_id ) ) {
                            $result[] = $post_id;
                        }
                        return $result;
                    }, array() );
                    if ( !empty( $post_ids ) ) {
                        $result[$type] = $post_ids;
                    }
                } elseif ( $type !== '' ) {
                    // all non-specific locations are stored as static flags and can be matched by extensions.
                    $result[$type] = true;
                }
                return $result;
            }, array() );
        }
    }

    //endregion

    //region Matching

    /**
     * Stores the current location.
     *
     * @access private
     * @var array{type:string,data:int|null}
     * @since 1.0.0
     */
    private array $current_location;

    /**
     * Get the current location.
     *
     * @access public
     * @returns array{type:string,data:int|null} An array containing the current location information.
     *
     * @global $wp_query WP_Query
     * @since 1.0.0
     */
    public function get_current_location(): array {
        if ( !empty( $this->current_location ) ) {
            return $this->current_location;
        }

        global $wp_query;
        $result = array();

        // visibility => General
        if ( is_front_page() ) {
            $result = array( 'type' => 'general:front_page', 'data' => null );
        } elseif ( is_home() ) {
            $result = array( 'type' => 'general:blog', 'data' => null );
        } elseif ( is_search() ) {
            if ( $wp_query->found_posts === 0 ) {
                $result = array( 'type' => 'general:no_search_results', 'data' => null );
            } else {
                $result = array( 'type' => 'general:search_results', 'data' => null );
            }
        } elseif ( is_404() ) {
            $result = array( 'type' => 'general:404', 'data' => null );
        } elseif ( is_post_type_archive() ) {
            $result = array( 'type' => 'archive:' . $wp_query->get( 'post_type' ), 'data' => null );
        }

        // visibility => Specific
        if ( empty( $result ) ) {
            $queried_object = $wp_query->get_queried_object();
            if ( $queried_object !== null ) {
                if ( is_singular() && $queried_object instanceof WP_Post ) {
                    // specific:page / specific:post
                    $result = array( 'type' => 'specific:' . $queried_object->post_type, 'data' => $queried_object->ID );
                } elseif ( ( is_tax() || is_category() || is_tag() ) && $queried_object instanceof WP_Term ) {
                    // specific:category / specific:tag
                    $result = array( 'type' => 'specific:' . $queried_object->taxonomy, 'data' => $queried_object->term_id );
                }
            }
        }

        return $this->current_location = $result;
    }

    /**
     * Stores the current user roles.
     *
     * @access private
     * @var string[]
     *
     * @since 1.0.0
     */
    private array $current_user_roles;

    /**
     * Get the roles associated to the current user.
     *
     * @access public
     * @return string[] A string array of display rule roles for the current user.
     *
     * @since 1.0.0
     */
    public function get_current_user_roles(): array {
        if ( !empty( $this->current_user_roles ) ) {
            return $this->current_user_roles;
        }

        if ( is_user_logged_in() ) {
            $result[] = 'general:logged_in';
        } else {
            $result[] = 'general:logged_out';
        }

        $user = wp_get_current_user();
        if ( $user instanceof WP_User ) {
            foreach ( $user->roles as $role ) {
                $result[] = 'role:' . $role;
            }
        }
        return $this->current_user_roles = $result;
    }

    /**
     * Stores any enqueued widgets whose display rules matched the current request.
     *
     * @access private
     * @var array
     *
     * @since 1.0.0
     */
    private array $enqueued = array();

    /**
     * Check if a post id is enqueued.
     *
     * @access public
     * @param int $post_id The post id to check.
     * @return bool True if the post is enqueued, otherwise false.
     *
     * @since 1.0.0
     */
    public function is_enqueued( int $post_id ): bool {
        foreach ( $this->enqueued as $widget ) {
            if ( is_array( $widget ) ) {
                $source_post_id = isset( $widget['source_post_id'] ) ? absint( $widget['source_post_id'] ) : 0;
                $resolved_post_id = isset( $widget['post_id'] ) ? absint( $widget['post_id'] ) : 0;
                if ( $source_post_id === $post_id || $resolved_post_id === $post_id ) {
                    return true;
                }
            } elseif ( absint( $widget ) === $post_id ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Callback for the `wp_footer` action.
     *
     * This callback outputs any enqueued widget posts into the footer of the page.
     *
     * @remarks
     * The `do_blocks` call is executed within the `template_redirect` callback as some core blocks will not
     * properly register there styles if called after the `wp_head` action.
     *
     * See https://github.com/WordPress/gutenberg/issues/40018
     *
     * @access public
     *
     * @since 1.0.0
     */
    public function render_enqueued() {
        foreach ( $this->enqueued as $widget ) {
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo $this->render_queueable( $widget );
        }
    }

    /**
     * Sanitizes queued widget content before it is printed in the footer.
     *
     * @param array $widget Queueable widget payload.
     * @return string
     */
    public function render_queueable( array $widget ): string {
        if ( empty( $widget['content'] ) ) {
            return '';
        }

        $compatibility_mode = Utils::get_bool( $widget, 'compatibility_mode' );

        return FooConvert::plugin()->kses_post( $widget['content'], $compatibility_mode );
    }

    /**
     * Triggers the `fooconvert_enqueue_assets` action.
     *
     * This function allows other developers to enqueue additional assets
     * for the enqueued widgets by hooking into the `fooconvert_enqueue_assets`
     * action.
     */
    public function enqueue_assets() {
        do_action( 'fooconvert_enqueue_assets', $this->enqueued );
    }

    /**
     * Callback for the `template_redirect` action.
     *
     * This callback checks if any widgets with display rules match the current request and if any do, enqueues there
     * content for rendering into the page footer.
     *
     * @remarks
     * The `do_blocks` call is executed within this callback as some core blocks will not properly register
     * there styles if called after the `wp_head` action.
     *
     * See https://github.com/WordPress/gutenberg/issues/40018
     *
     * @access public
     *
     * @since 1.0.0
     */
    public function enqueue_required() {
        if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || wp_doing_cron() ) {
            return; // Exit if not needed!
        }

        $this->enqueued = apply_filters( 'fooconvert_enqueue_required', array() );

        // get the cached display rules
        $display_rules = get_option( FOOCONVERT_OPTION_DISPLAY_RULES, array() );
        if ( !empty( $display_rules ) ) {
            $current_location = $this->get_current_location();
            if ( !empty( $current_location ) ) {
                $current_user_roles = $this->get_current_user_roles();
                foreach ( $display_rules as $compiled ) {
                    if ( $this->match_compiled( $compiled, $current_location, $current_user_roles ) ) {
                        $matched_id = $compiled['post_id'];
                        $queueable = $this->get_queueable( $matched_id, 'display_rules' );
                        if ( !empty( $queueable ) && !$this->is_enqueued( absint( $queueable['source_post_id'] ?? $matched_id ) ) ) {
                            $this->enqueued[] = $queueable;
                        }
                    }
                }
            }
        }

        do_action( 'fooconvert_enqueue_required_assets', $this->enqueued );
    }

    /**
     * Adds a widget to the queue for processing.
     *
     * This function retrieves the queueable data for the given post ID
     * and appends it to the list of enqueued widgets for further processing.
     *
     * @param int $post_id The post ID of the widget to enqueue.
     *
     * @since 1.0.0
     */
    public function add_to_queue( int $post_id, string $context = 'manual' ) {
        $queueable = $this->get_queueable( $post_id, $context );
        if ( !empty( $queueable ) && !$this->is_enqueued( absint( $queueable['source_post_id'] ?? $post_id ) ) ) {
            $this->enqueued[] = $queueable;
        }
    }

    /**
     * Builds a queueable widget payload for rendering and asset enqueueing.
     *
     * @param int    $post_id Widget post ID.
     * @param string $context Context describing why the widget is being queued.
     * @return array<string,mixed>
     */
    public function get_queueable( int $post_id, string $context = 'display_rules' ): array {
        $source_post_id = $post_id;
        $resolved_post_id = apply_filters( 'fooconvert_resolve_widget_post_id', $post_id, array(
            'context'        => $context,
            'source_post_id' => $source_post_id
        ) );
        $resolved_post_id = intval( $resolved_post_id );
        if ( $resolved_post_id <= 0 ) {
            $resolved_post_id = $source_post_id;
        }

        $content = FooConvert::plugin()->content_migration->get_post_content( $resolved_post_id );
        if ( !empty( $content ) ) {
            $compatibility_mode = FooConvert::plugin()->compatibility->is_enabled( $resolved_post_id );
            $queueable = array(
                'source_post_id'     => $source_post_id,
                'post_id'            => $resolved_post_id,
                'content'            => do_blocks( $content ),
                'compatibility_mode' => $compatibility_mode,
            );
            /**
             * Allows extensions to attach additional request-time data to queueable widgets.
             *
             * @param array<string,mixed> $queueable Queueable widget payload.
             * @param int $resolved_post_id Widget post ID after any resolver filters are applied.
             * @param array<string,mixed> $context_data Context describing why the widget was queued.
             */
            return apply_filters( 'fooconvert_queueable_widget', $queueable, $resolved_post_id, array(
                'context'        => $context,
                'source_post_id' => $source_post_id,
                'resolved_post_id' => $resolved_post_id,
            ) );
        }
        return array();
    }

    /**
     * Check if the given compiled display rules match the current location and user roles.
     *
     * @access public
     * @param array $rules The {@link FooConvert_Display_Rules::get_compiled compiled} display rules to match.
     * @param array $current_location The current location.
     * @param array $current_user_roles The current users roles.
     *
     * @return bool True if the rules match the current location and users roles, otherwise false.
     *
     * @since 1.0.0
     */
    public function match_compiled( array $rules, array $current_location, array $current_user_roles ): bool {
        $match = false;
        if ( $this->match_compiled_locations( $rules['include'], $current_location ) ) {
            $match = true;
        }
        if ( $match && $this->match_compiled_locations( $rules['exclude'], $current_location ) ) {
            $match = false;
        }
        if ( $match && !$this->match_compiled_user_roles( $rules['users'], $current_user_roles ) ) {
            $match = false;
        }
        return $match;
    }

    /**
     * Match a compiled set of include or exclude locations against the current request.
     *
     * Built-in general, archive, and specific locations are matched directly. Any custom
     * static location flags that need runtime evaluation can be handled by extensions via
     * the `fooconvert_display_rules_match_locations` filter.
     *
     * @param array $compiled_locations The flattened compiled locations for the include or exclude branch.
     * @param array{type:string,data:int|null} $current_location The current request location.
     * @return bool
     */
    public function match_compiled_locations( array $compiled_locations, array $current_location ): bool {
        if ( array_key_exists( 'general:entire_site', $compiled_locations ) ) {
            return true;
        }
        list( 'type' => $type, 'data' => $data ) = $current_location;
        $matched = array_key_exists( $type, $compiled_locations ) && ( !is_int( $data ) || in_array( $data, $compiled_locations[$type], true ) );
        if ( $matched ) {
            return true;
        }

        /**
         * Allows extensions to evaluate custom compiled location types against the current request.
         *
         * @param bool $matched Defaults to `false` when no built-in location matched.
         * @param array $compiled_locations The flattened compiled locations for the include or exclude branch.
         * @param array{type:string,data:int|null} $current_location The current request location.
         * @param DisplayRules $display_rules The display rules component instance.
         */
        return (bool) apply_filters( 'fooconvert_display_rules_match_locations', false, $compiled_locations, $current_location, $this );
    }

    /**
     * Checks whether the current user roles satisfy the compiled user rules.
     *
     * @param array $compiled_user_roles Allowed compiled user role keys.
     * @param array $current_user_roles The current user's role keys.
     * @return bool
     */
    public function match_compiled_user_roles( array $compiled_user_roles, array $current_user_roles ): bool {
        if ( in_array( 'general:all_users', $compiled_user_roles ) ) {
            return true;
        }
        return count( array_intersect( $compiled_user_roles, $current_user_roles ) ) > 0;
    }

    //endregion
}
