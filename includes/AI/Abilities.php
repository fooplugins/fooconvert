<?php

namespace FooPlugins\FooConvert\AI;

use WP_Error;

class Abilities {

    const CATEGORY = 'fooconvert-popup-builder';

    const ABILITY_LIST_TEMPLATES = 'fooconvert/list-popup-templates';

    const ABILITY_BLOCK_CATALOG = 'fooconvert/get-block-catalog';

    const ABILITY_CONVERSION_PLAYBOOK = 'fooconvert/get-conversion-playbook';

    const ABILITY_VALIDATE_POPUP_BLUEPRINT = 'fooconvert/validate-popup-blueprint';

    /**
     * Hooks the abilities into WordPress when available.
     */
    public function __construct() {
        if ( ! self::wp_api_available() ) {
            return;
        }

        add_action( 'wp_abilities_api_categories_init', array( $this, 'register_main_category' ) );
        add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
    }

    /**
     * Returns the ability names allowed for the AI popup builder.
     *
     * @return array<int,string>
     */
    public static function get_allowed_abilities(): array {
        return array(
            self::ABILITY_LIST_TEMPLATES,
            self::ABILITY_BLOCK_CATALOG,
            self::ABILITY_CONVERSION_PLAYBOOK,
            self::ABILITY_VALIDATE_POPUP_BLUEPRINT,
        );
    }

    /**
     * Determines whether the WordPress abilities API is available.
     *
     * @return bool
     */
    public static function wp_api_available(): bool {
        return class_exists( 'WP_Ability' ) &&
            function_exists( 'wp_register_ability' ) &&
            function_exists( 'wp_register_ability_category' );
    }

    /**
     * Registers the Fooconvert popup builder ability category.
     *
     * @return void
     */
    public function register_main_category(): void {
        wp_register_ability_category(
            self::CATEGORY,
            array(
                'label'       => __( 'FooConvert Popup Builder', 'fooconvert' ),
                'description' => __( 'Popup strategy, template context, and validation tools for AI-assisted popup creation.', 'fooconvert' ),
            )
        );
    }

    /**
     * Registers all popup builder abilities.
     *
     * @return void
     */
    public function register_abilities(): void {
        wp_register_ability(
            self::ABILITY_LIST_TEMPLATES,
            array(
                'category'            => self::CATEGORY,
                'label'               => __( 'List Popup Templates', 'fooconvert' ),
                'description'         => __( 'List bundled FooConvert popup, flyout, and bar templates. Use `template_slug` to inspect one template in detail, or `popup_type` to narrow the list.', 'fooconvert' ),
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'popup_type'            => array(
                            'type' => 'string',
                            'enum' => fooconvert_get_popup_types(),
                        ),
                        'template_slug'         => array(
                            'type' => 'string',
                        ),
                        'include_content_blocks' => array(
                            'type'    => 'boolean',
                            'default' => false,
                        ),
                        'limit'                 => array(
                            'type'    => 'integer',
                            'default' => 10,
                        ),
                    ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'templates' => array(
                            'type'  => 'array',
                            'items' => array(
                                'type' => 'object',
                            ),
                        ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_list_templates' ),
                'permission_callback' => array( $this, 'can_manage_popups' ),
                'meta'                => array(
                    'annotations' => array(
                        'readonly'    => true,
                        'destructive' => false,
                        'idempotent'  => true,
                    ),
                    'show_in_rest' => true,
                ),
            )
        );

        wp_register_ability(
            self::ABILITY_BLOCK_CATALOG,
            array(
                'category'            => self::CATEGORY,
                'label'               => __( 'Get Block Catalog', 'fooconvert' ),
                'description'         => __( 'Return the supported content blocks and nesting rules for AI-generated popup content.', 'fooconvert' ),
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'block_name' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'blocks' => array(
                            'type'  => 'array',
                            'items' => array(
                                'type' => 'object',
                            ),
                        ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_get_block_catalog' ),
                'permission_callback' => array( $this, 'can_manage_popups' ),
                'meta'                => array(
                    'annotations' => array(
                        'readonly'    => true,
                        'destructive' => false,
                        'idempotent'  => true,
                    ),
                    'show_in_rest' => true,
                ),
            )
        );

        wp_register_ability(
            self::ABILITY_CONVERSION_PLAYBOOK,
            array(
                'category'            => self::CATEGORY,
                'label'               => __( 'Get Conversion Playbook', 'fooconvert' ),
                'description'         => __( 'Return Fooconvert popup conversion best practices, popup type guidance, and copywriting tactics.', 'fooconvert' ),
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'playbook' => array(
                            'type' => 'object',
                        ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_get_conversion_playbook' ),
                'permission_callback' => array( $this, 'can_manage_popups' ),
                'meta'                => array(
                    'annotations' => array(
                        'readonly'    => true,
                        'destructive' => false,
                        'idempotent'  => true,
                    ),
                    'show_in_rest' => true,
                ),
            )
        );

        wp_register_ability(
            self::ABILITY_VALIDATE_POPUP_BLUEPRINT,
            array(
                'category'            => self::CATEGORY,
                'label'               => __( 'Validate Popup Blueprint', 'fooconvert' ),
                'description'         => __( 'Evaluate a structured popup draft for conversion fundamentals, CTA focus, and popup-type fit.', 'fooconvert' ),
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'popup_draft' => PopupBlueprint::get_popup_draft_schema(),
                    ),
                    'required'   => array( 'popup_draft' ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'validation' => array(
                            'type' => 'object',
                        ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_validate_popup_blueprint' ),
                'permission_callback' => array( $this, 'can_manage_popups' ),
                'meta'                => array(
                    'annotations' => array(
                        'readonly'    => true,
                        'destructive' => false,
                        'idempotent'  => true,
                    ),
                    'show_in_rest' => true,
                ),
            )
        );
    }

    /**
     * Checks whether the current user can use the popup builder.
     *
     * @return bool|WP_Error
     */
    public function can_manage_popups() {
        $post_type_object = get_post_type_object( FOOCONVERT_CPT_POPUP );
        $capability       = $post_type_object && isset( $post_type_object->cap->create_posts )
            ? $post_type_object->cap->create_posts
            : 'manage_options';

        return current_user_can( $capability );
    }

    /**
     * Lists the Fooconvert popup templates.
     *
     * @param mixed $input Ability input.
     * @return array<string,mixed>
     */
    public function execute_list_templates( $input ): array {
        $input                 = self::normalize_input( $input );
        $popup_type            = fooconvert_normalize_popup_type( $input['popup_type'] ?? '' );
        $template_slug         = self::normalize_template_slug( $input['template_slug'] ?? '' );
        $include_content_blocks = ! empty( $input['include_content_blocks'] );
        $limit                 = max( 1, min( 20, absint( $input['limit'] ?? 10 ) ) );
        $templates             = PopupBlueprint::get_template_library();
        $results               = array();

        foreach ( $templates as $template ) {
            if ( '' !== $popup_type && $template['popup_type'] !== $popup_type ) {
                continue;
            }

            if ( '' !== $template_slug && $template['slug'] !== $template_slug ) {
                continue;
            }

            $entry = array(
                'slug'               => $template['slug'],
                'popup_type'         => $template['popup_type'],
                'title'              => $template['title'],
                'description'        => $template['description'],
                'attributes'         => $template['attributes'],
                'sample_block_names' => $template['sample_block_names'],
                'example_markup'     => $template['example_markup'],
            );

            if ( $include_content_blocks || '' !== $template_slug ) {
                $entry['content_blocks'] = $template['content_blocks'];
            }

            $results[] = $entry;
        }

        return array(
            'templates' => array_slice( $results, 0, $limit ),
        );
    }

    /**
     * Returns the supported block catalog.
     *
     * @param mixed $input Ability input.
     * @return array<string,mixed>
     */
    public function execute_get_block_catalog( $input ): array {
        $input      = self::normalize_input( $input );
        $block_name = isset( $input['block_name'] ) ? trim( (string) $input['block_name'] ) : '';
        $blocks     = PopupBlueprint::get_block_catalog();

        if ( '' !== $block_name ) {
            $blocks = array_values(
                array_filter(
                    $blocks,
                    static function( array $block ) use ( $block_name ): bool {
                        return $block['name'] === $block_name;
                    }
                )
            );
        }

        return array(
            'blocks' => $blocks,
        );
    }

    /**
     * Returns the popup conversion playbook.
     *
     * @param mixed $input Ability input.
     * @return array<string,mixed>
     */
    public function execute_get_conversion_playbook( $input ): array {
        return array(
            'playbook' => PopupBlueprint::get_conversion_playbook(),
        );
    }

    /**
     * Validates a popup blueprint.
     *
     * @param mixed $input Ability input.
     * @return array<string,mixed>|WP_Error
     */
    public function execute_validate_popup_blueprint( $input ) {
        $input = self::normalize_input( $input );

        if ( ! is_array( $input['popup_draft'] ?? null ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_blueprint_missing',
                __( 'A popup_draft object is required for validation.', 'fooconvert' )
            );
        }

        return array(
            'validation' => PopupBlueprint::evaluate_popup_draft( $input['popup_draft'] ),
        );
    }

    /**
     * Normalizes generic ability input to an array.
     *
     * @param mixed $input Ability input.
     * @return array<string,mixed>
     */
    private static function normalize_input( $input ): array {
        return is_array( $input ) ? $input : array();
    }

    /**
     * Normalizes a template slug.
     *
     * @param mixed $slug Template slug.
     * @return string
     */
    private static function normalize_template_slug( $slug ): string {
        if ( ! is_string( $slug ) ) {
            return '';
        }

        return preg_replace( '/[^a-z0-9_\\-]/', '', strtolower( $slug ) ) ?: '';
    }
}
