<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use WP_Post;
use WP_Post_Type;
use WP_Query;
use WP_Term;
use WP_User;

class DisplayRules extends BaseComponent {
    public function __construct() {
        parent::__construct();

        add_action( 'wp_after_insert_post', array( $this, 'after_insert_should_compile' ), 10, 4 );
        add_action( 'template_redirect', array( $this, 'enqueue_required' ), 5 );
        add_action( 'wp_footer', array( $this, 'render_enqueued' ), 5 );
    }

    //region Meta

    /**
     * Registers the display rules meta key for a specific post type.
     *
     * @param string $post_type The post type to register the meta key for.
     * @return bool True if the meta key was successfully registered in the global array, false if not.
     *
     * @since 1.0.0
     */
    public function register( string $post_type ) : bool {
        $this->register_column( $post_type );
        return register_meta( 'post', FOOCONVERT_META_KEY_DISPLAY_RULES, array(
            'object_subtype' => $post_type,
            'single' => true,
            'type' => 'object',
            'description' => __( 'Display rules for FooConvert.', 'fooconvert' ),
            'auth_callback' => array( $this, 'auth_callback' ),
            'default' => $this->defaults(),
            'show_in_rest' => array( 'schema' => $this->schema() )
        ) );
    }

    public function register_column( string $post_type ) : void {
        add_filter( "manage_{$post_type}_posts_columns", function( $columns ) use ( $post_type ) {
            return $this->create_column( $post_type, $columns );
        } );

        add_filter( "manage_edit-{$post_type}_sortable_columns", function( $columns ) use ( $post_type ) {
            return $this->sortable_column( $post_type, $columns );
        } );

        add_action( "manage_{$post_type}_posts_custom_column", function( $column_name, $post_id ) use ( $post_type ) {
            $this->create_column_content( $post_type, $column_name, $post_id );
        }, 10, 2 );

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        add_action( 'admin_enqueue_scripts', function( $hook_suffix ) use ( $post_type ) {
            if ( $hook_suffix === 'edit.php' && isset( $_GET['post_type'] ) ) {
                $current_post_type = sanitize_key( $_GET['post_type'] );
                if ( $current_post_type === $post_type ) {
                    wp_add_inline_style( 'common', '.column-fc-bar_display_rules { width: 10%; }' );
                }
            }
        } );
        // phpcs:enable
    }

    public function create_column( $post_type, $columns ) : array {
        // add the column after the default title column
        $updated = array();
        $inserted = false;
        foreach ( $columns as $column_name => $column_display_name ) {
            $updated[ $column_name ] = $column_display_name;
            if ( $column_name === 'title' ) {
                $updated["{$post_type}_display_rules"] = __( 'Display Rules', 'fooconvert' );
                $inserted = true;
            }
        }

        // if for some reason the column was not inserted, add it
        if ( !$inserted ) {
            $updated["{$post_type}_display_rules"] = __( 'Display Rules', 'fooconvert' );
        }
        return $updated;
    }

    public function sortable_column( $post_type, $columns ) {
        $columns["{$post_type}_display_rules"] = array(
            "{$post_type}_display_rules",
            false,
            __( 'Display Rules', 'fooconvert' ),
            __( 'Table ordered by display rules.', 'fooconvert' ),
        );
        return $columns;
    }

    /**
     * @param $post_type
     * @param $column_name
     * @param $post_id
     * @return void
     */
    public function create_column_content( $post_type, $column_name, $post_id ) : void {
        if ( $column_name === "{$post_type}_display_rules" ) {
            $display_rules = get_post_meta( $post_id, FOOCONVERT_META_KEY_DISPLAY_RULES, true );
            $is_set = !empty( $display_rules ) && !empty( $display_rules['location'] );

            if ( $is_set ) {
                esc_html_e( 'Set', 'fooconvert' );
            } else {
                esc_html_e( 'Not set!', 'fooconvert' );
            }
        }
    }

    /**
     * The auth callback for the display rules meta key.
     *
     * @return bool True if the current user can edit the meta key, false if not.
     *
     * @since 1.0.0
     */
    public function auth_callback() : bool {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Get the default metadata for the display rules.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function defaults() : array {
        return array(
            'location' => array(),
            // false positive - this array is not used to query posts
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
            'exclude' => array(),
            'users' => array( 'general:all_users' )
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
    public function schema() : array {
        return array(
            'type' => 'object',
            'properties' => array(
                'location' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'type' => array( 'type' => 'string', 'required' => true ),
                            'data' => array(
                                'type' => 'array',
                                'items' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'id' => array( 'type' => 'integer', 'required' => true ),
                                        'label' => array( 'type' => 'string', 'required' => true )
                                    )
                                )
                            )
                        )
                    )
                ),
                // false positive - this array is not used to query posts
                // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
                'exclude' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'type' => array( 'type' => 'string', 'required' => true ),
                            'data' => array(
                                'type' => 'array',
                                'items' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'id' => array( 'type' => 'integer', 'required' => true ),
                                        'label' => array( 'type' => 'string', 'required' => true )
                                    )
                                )
                            )
                        )
                    )
                ),
                'users' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'string'
                    )
                )
            )
        );
    }

    //endregion

    //region Component - Create the data for the display rules component

    function get_component_data_name() : string {
        return 'FC_DISPLAY_RULES';
    }

    function get_component_data() : array {
        return array(
            'meta' => array(
                'key' => FOOCONVERT_META_KEY_DISPLAY_RULES,
                'defaults' => $this->defaults()
            ),
            'location' => $this->get_component_locations(),
            // false positive - this array is not used to query posts
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
            'exclude' => $this->get_component_locations( 'exclude' ),
            'users' => $this->get_component_users()
        );
    }

    function get_component_locations( string $context = 'default' ) : array {
        $locations = array(
            array(
                'group' => 'general',
                'label' => __( 'General', 'fooconvert' ),
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
                    'data' => array(
                        'kind' => 'postType',
                        'name' => $post_type->name,
                        // Translators: %s refers to the taxonomy that is being searched for. e.g. "Category".
                        'placeholder' => sprintf( __( 'Type to choose %s...', 'fooconvert' ), strtolower( $post_type->label ) )
                    )
                );
            }
        }

        if ( ! empty( $post_type_locations ) ) {
            $locations[] = array(
                'group' => 'specific_posts',
                'label' => __( 'Specific Posts', 'fooconvert' ),
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
                    'data' => array(
                        'kind' => 'taxonomy',
                        'name' => $taxonomy->name,
                        // Translators: %s refers to the taxonomy that is being searched for. e.g. "Category".
                        'placeholder' => sprintf( __( 'Type to choose %s...', 'fooconvert' ), strtolower( $taxonomy->label ) )
                    )
                );
            }
        }

        if ( ! empty( $taxonomy_locations ) ) {
            $locations[] = array(
                'group' => 'specific_taxonomies',
                'label' => __( 'Specific Taxonomies', 'fooconvert' ),
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

        if ( ! empty( $archive_locations ) ) {
            $locations[] = array(
                'group' => 'archive',
                'label' => __( 'Post Archives', 'fooconvert' ),
                'options' => $archive_locations
            );
        }

        if ( $context === 'exclude' ) {
            // remove 'general:entire_site' from the exclude locations
            array_shift( $locations[0]['options'] );
        }

        return apply_filters( 'fooconvert_display_rules_locations', $locations, $context );
    }

    function get_component_users() : array {
        $roles = Utils::array_map( wp_roles()->get_names(), function ( $value, $key ) {
            return [ 'value' => "role:$key", 'label' => $value ];
        } );
        usort( $roles, function ( $a, $b ) {
            return strcmp( $a['label'], $b['label'] );
        } );

        return array(
            array(
                'group' => 'general',
                'label' => __( 'General', 'fooconvert' ),
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
                'group' => 'roles',
                'label' => __( 'Roles', 'fooconvert' ),
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

        // otherwise if this is OR was a published widget post
        $was_published = $post_before instanceof WP_Post && $post_before->post_status === 'publish';
        $is_published = $post->post_status === 'publish';
        if ( $was_published || $is_published ) {
            $widget = FooConvert::plugin()->widgets->get_instance( $post->post_type );
            // and this is a widget that supports display rules
            if ( $widget && $widget->supports( 'display-rules' ) ) {
                // compile them!
                $this->compile( $post_id, $is_published );
            }
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
        $cached = get_option( 'fooconvert_display_rules', array() );
        // first remove any previous value if it exists
        $updated = array_filter( $cached, function ( $value ) use ( $post_id ) {
            return Utils::get_int( $value, 'post_id' ) !== $post_id;
        } );
        // then go about compiling the rules again if currently published
        if ( $is_published ) {
            $compiled = $this->get_compiled( $post_id );
            if ( ! empty( $compiled ) ) {
                $updated[] = $compiled;
            }
        }
        update_option( 'fooconvert_display_rules', $updated, true );
    }

    /**
     * Get the compiled display rules for the given post in a format better suited for location matching.
     *
     * @param int $post_id The post id of the display rules to compile.
     * @return array An array containing only the relevant data to perform location matching.
     *
     * @since 1.0.0
     */
    public function get_compiled( int $post_id ) : array {
        $rules = get_post_meta( $post_id, FOOCONVERT_META_KEY_DISPLAY_RULES, true );
        if ( ! empty( $rules ) ) {
            // make sure at a minimum the rules are the expected types and that at least 1 location and user
            // have been set before compiling
            $should_compile = Utils::has_keys( $rules, array_keys( $this->defaults() ), function ( $value, $key ) {
                if ( is_array( $value ) ) {
                    if ( $key === 'location' || $key === 'users' ) {
                        return ! empty( $value );
                    }
                    return true;
                }
                return false;
            } );
            if ( $should_compile ) {
                $include = $this->compile_locations( $rules['location'] );
                if ( ! empty( $include ) ) {
                    return array(
                        'post_id' => $post_id,
                        'include' => $include,
                        // false positive - this array is not used to query posts
                        // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
                        'exclude' => $this->compile_locations( $rules['exclude'] ),
                        'users' => $rules['users'],
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
    private function compile_locations( array $locations ) : array {
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
                if ( str_starts_with( $type, 'general:' ) || str_starts_with( $type, 'archive:' ) ) {
                    // general & archive locations have no additional checks, they are static
                    $result[ $type ] = true;
                } elseif ( str_starts_with( $type, 'specific:' ) ) {
                    // specific locations require post_ids that must be matched, so extract them out
                    $data = Utils::get_array( $location, 'data' );
                    $post_ids = array_reduce( $data, function ( $result, $data_value ) {
                        $post_id = Utils::get_int( $data_value, 'id' );
                        if ( ! empty( $post_id ) ) {
                            $result[] = $post_id;
                        }
                        return $result;
                    }, array() );
                    if ( ! empty( $post_ids ) ) {
                        $result[ $type ] = $post_ids;
                    }
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
    public function get_current_location() : array {
        if ( ! empty( $this->current_location ) ) {
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
    public function get_current_user_roles() : array {
        if ( ! empty( $this->current_user_roles ) ) {
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
    public function is_enqueued( int $post_id ) : bool {
        return in_array( $post_id, $this->enqueued );
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
            // Reviewers:
            // The content is passed through wp_kses with an extended post allowed HTML list that includes
            // the custom elements for the plugin.
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo FooConvert::plugin()->kses_post( $widget['content'], $widget['compatibility_mode'] );
        }
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
        $this->enqueued = apply_filters( 'fooconvert_enqueue_required', array() );

        //todo: add to these exclusions to limit the overhead on the server
        if ( empty( $this->enqueued ) && ( is_admin() || wp_is_json_request() ) ) {
            return;
        }

        // get the cached display rules
        $display_rules = get_option( 'fooconvert_display_rules', array() );
        if ( ! empty( $display_rules ) ) {
            $current_location = $this->get_current_location();
            if ( ! empty( $current_location ) ) {
                $current_user_roles = $this->get_current_user_roles();
                foreach ( $display_rules as $compiled ) {
                    if ( $this->match_compiled( $compiled, $current_location, $current_user_roles ) ) {
                        $matched_id = $compiled['post_id'];
                        $matched_content = get_post_field( 'post_content', $matched_id );
                        $matched_compatibility_mode = Utils::get_bool( $compiled, 'compatibility_mode' );
                        if ( Utils::is_string( $matched_content, true ) ) {
                            $this->enqueued[] = array(
                                'post_id' => $matched_id,
                                'content' => do_blocks( $matched_content ),
                                'compatibility_mode' => $matched_compatibility_mode,
                            );
                        }
                    }
                }
            }
        }
    }

    public function get_queueable( int $post_id ) : array {
        $content = get_post_field( 'post_content', $post_id );
        if ( ! empty( $content ) ) {
            $compatibility_mode = FooConvert::plugin()->compatibility->is_enabled( $post_id );
            return array(
                'post_id' => $post_id,
                'content' => do_blocks( $content ),
                'compatibility_mode' => $compatibility_mode,
            );
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
    public function match_compiled( array $rules, array $current_location, array $current_user_roles ) : bool {
        $match = false;
        if ( $this->match_compiled_locations( $rules['include'], $current_location ) ) {
            $match = true;
        }
        if ( $match && $this->match_compiled_locations( $rules['exclude'], $current_location ) ) {
            $match = false;
        }
        if ( $match && ! $this->match_compiled_user_roles( $rules['users'], $current_user_roles ) ) {
            $match = false;
        }
        return $match;
    }

    public function match_compiled_locations( array $compiled_locations, array $current_location ) : bool {
        if ( array_key_exists( 'general:entire_site', $compiled_locations ) ) {
            return true;
        }
        list( 'type' => $type, 'data' => $data ) = $current_location;
        return array_key_exists( $type, $compiled_locations ) && ( ! is_int( $data ) || in_array( $data, $compiled_locations[ $type ], true ) );
    }

    public function match_compiled_user_roles( array $compiled_user_roles, array $current_user_roles ) : bool {
        if ( in_array( 'general:all_users', $compiled_user_roles ) ) {
            return true;
        }
        return count( array_intersect( $compiled_user_roles, $current_user_roles ) ) > 0;
    }

    //endregion
}