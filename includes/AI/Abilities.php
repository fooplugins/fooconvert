<?php

namespace FooPlugins\FooConvert\AI;

use WP_Error;

class Abilities {

    const CATEGORY = 'fooconvert-popup-builder';

    const ABILITY_LIST_TEMPLATES = 'fooconvert/list-popup-templates';

    const ABILITY_BLOCK_CATALOG = 'fooconvert/get-block-catalog';

    const ABILITY_CONVERSION_PLAYBOOK = 'fooconvert/get-conversion-playbook';

    const ABILITY_VALIDATE_POPUP_BLUEPRINT = 'fooconvert/validate-popup-blueprint';

    const ABILITY_LIST_POPUP_MEDIA = 'fooconvert/list-popup-media';

    const ABILITY_GENERATE_POPUP_IMAGE_PROMPT = 'fooconvert/generate-popup-image-prompt';

    const ABILITY_GENERATE_POPUP_IMAGE = 'fooconvert/generate-popup-image';

    const ABILITY_IMPORT_POPUP_IMAGE = 'fooconvert/import-popup-image';

    const ABILITY_CREATE_POPUP_IMAGE = 'fooconvert/create-popup-image';

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
            self::ABILITY_LIST_POPUP_MEDIA,
            self::ABILITY_GENERATE_POPUP_IMAGE_PROMPT,
            self::ABILITY_GENERATE_POPUP_IMAGE,
            self::ABILITY_IMPORT_POPUP_IMAGE,
            self::ABILITY_CREATE_POPUP_IMAGE,
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

        wp_register_ability(
            self::ABILITY_LIST_POPUP_MEDIA,
            array(
                'category'            => self::CATEGORY,
                'label'               => __( 'List Popup Media', 'fooconvert' ),
                'description'         => __( 'Return recently generated popup images for the current builder user.', 'fooconvert' ),
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'limit' => array(
                            'type'    => 'integer',
                            'default' => 12,
                        ),
                    ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'media_items' => array(
                            'type'  => 'array',
                            'items' => PopupMedia::get_attachment_schema(),
                        ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_list_popup_media' ),
                'permission_callback' => array( $this, 'can_manage_popup_media' ),
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
            self::ABILITY_GENERATE_POPUP_IMAGE_PROMPT,
            array(
                'category'            => self::CATEGORY,
                'label'               => __( 'Generate Popup Image Prompt', 'fooconvert' ),
                'description'         => __( 'Create an image-generation prompt tailored to the current Fooconvert popup draft and offer.', 'fooconvert' ),
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'popup_draft'    => PopupBlueprint::get_popup_draft_schema(),
                        'instructions'   => array(
                            'type' => 'string',
                        ),
                    ),
                    'required'   => array( 'popup_draft' ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'prompt' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_generate_popup_image_prompt' ),
                'permission_callback' => array( $this, 'can_manage_popup_media' ),
                'meta'                => array(
                    'annotations' => array(
                        'readonly'    => false,
                        'destructive' => false,
                        'idempotent'  => false,
                    ),
                    'show_in_rest' => true,
                ),
            )
        );

        wp_register_ability(
            self::ABILITY_GENERATE_POPUP_IMAGE,
            array(
                'category'            => self::CATEGORY,
                'label'               => __( 'Generate Popup Image', 'fooconvert' ),
                'description'         => __( 'Generate raw popup image data from an image prompt.', 'fooconvert' ),
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'prompt'    => array(
                            'type' => 'string',
                        ),
                        'reference' => array(
                            'type' => 'string',
                        ),
                    ),
                    'required'   => array( 'prompt' ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'image' => PopupMedia::get_generated_image_schema(),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_generate_popup_image' ),
                'permission_callback' => array( $this, 'can_manage_popup_media' ),
                'meta'                => array(
                    'annotations' => array(
                        'readonly'    => false,
                        'destructive' => false,
                        'idempotent'  => false,
                    ),
                    'show_in_rest' => true,
                ),
            )
        );

        wp_register_ability(
            self::ABILITY_IMPORT_POPUP_IMAGE,
            array(
                'category'            => self::CATEGORY,
                'label'               => __( 'Import Popup Image', 'fooconvert' ),
                'description'         => __( 'Import generated popup image data into the media library and return a reusable attachment payload.', 'fooconvert' ),
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'data'        => array(
                            'type' => 'string',
                        ),
                        'filename'    => array(
                            'type' => 'string',
                        ),
                        'title'       => array(
                            'type' => 'string',
                        ),
                        'description' => array(
                            'type' => 'string',
                        ),
                        'alt_text'    => array(
                            'type' => 'string',
                        ),
                        'mime_type'   => array(
                            'type' => 'string',
                        ),
                        'prompt'      => array(
                            'type' => 'string',
                        ),
                        'popup_type'  => array(
                            'type' => 'string',
                            'enum' => fooconvert_get_popup_types(),
                        ),
                    ),
                    'required'   => array( 'data' ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'image' => PopupMedia::get_attachment_schema(),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_import_popup_image' ),
                'permission_callback' => array( $this, 'can_manage_popup_media' ),
                'meta'                => array(
                    'annotations' => array(
                        'readonly'    => false,
                        'destructive' => true,
                        'idempotent'  => false,
                    ),
                    'show_in_rest' => true,
                ),
            )
        );

        wp_register_ability(
            self::ABILITY_CREATE_POPUP_IMAGE,
            array(
                'category'            => self::CATEGORY,
                'label'               => __( 'Create Popup Image', 'fooconvert' ),
                'description'         => __( 'Generate and import a conversion-oriented popup image in one step. Prefer this when the popup needs a ready-to-use visual asset.', 'fooconvert' ),
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'popup_draft'   => PopupBlueprint::get_popup_draft_schema(),
                        'instructions'  => array(
                            'type' => 'string',
                        ),
                    ),
                    'required'   => array( 'popup_draft' ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'prompt' => array(
                            'type' => 'string',
                        ),
                        'image'  => PopupMedia::get_attachment_schema(),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_create_popup_image' ),
                'permission_callback' => array( $this, 'can_manage_popup_media' ),
                'meta'                => array(
                    'annotations' => array(
                        'readonly'    => false,
                        'destructive' => true,
                        'idempotent'  => false,
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
     * Checks whether the current user can generate popup media.
     *
     * @return bool|WP_Error
     */
    public function can_manage_popup_media() {
        if ( ! $this->can_manage_popups() ) {
            return false;
        }

        if ( ! PopupMedia::can_manage_media() ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_forbidden',
                __( 'You do not have permission to generate popup images.', 'fooconvert' )
            );
        }

        return true;
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
     * Lists generated popup media items.
     *
     * @param mixed $input Ability input.
     * @return array<string,mixed>
     */
    public function execute_list_popup_media( $input ): array {
        $input = self::normalize_input( $input );

        return array(
            'media_items' => PopupMedia::list_generated_images( max( 1, min( 24, absint( $input['limit'] ?? 12 ) ) ) ),
        );
    }

    /**
     * Generates a popup image prompt.
     *
     * @param mixed $input Ability input.
     * @return array<string,mixed>|WP_Error
     */
    public function execute_generate_popup_image_prompt( $input ) {
        $input = self::normalize_input( $input );

        if ( ! is_array( $input['popup_draft'] ?? null ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_blueprint_missing',
                __( 'A popup_draft object is required to generate a popup image prompt.', 'fooconvert' )
            );
        }

        $prompt = PopupMedia::generate_prompt_for_popup(
            PopupBlueprint::sanitize_popup_draft( $input['popup_draft'] ),
            isset( $input['instructions'] ) ? sanitize_text_field( (string) $input['instructions'] ) : ''
        );

        if ( is_wp_error( $prompt ) ) {
            return $prompt;
        }

        return array(
            'prompt' => $prompt,
        );
    }

    /**
     * Generates raw popup image data.
     *
     * @param mixed $input Ability input.
     * @return array<string,mixed>|WP_Error
     */
    public function execute_generate_popup_image( $input ) {
        $input = self::normalize_input( $input );
        $prompt = isset( $input['prompt'] ) ? sanitize_text_field( (string) $input['prompt'] ) : '';

        if ( '' === $prompt ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_missing_prompt',
                __( 'An image prompt is required to generate popup media.', 'fooconvert' )
            );
        }

        $image = PopupMedia::generate_image_from_prompt(
            $prompt,
            isset( $input['reference'] ) && is_string( $input['reference'] ) ? $input['reference'] : null
        );

        if ( is_wp_error( $image ) ) {
            return $image;
        }

        return array(
            'image' => $image,
        );
    }

    /**
     * Imports popup image data into the media library.
     *
     * @param mixed $input Ability input.
     * @return array<string,mixed>|WP_Error
     */
    public function execute_import_popup_image( $input ) {
        $input = self::normalize_input( $input );
        $data  = isset( $input['data'] ) ? (string) $input['data'] : '';

        if ( '' === $data ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_missing_data',
                __( 'Base64 popup image data is required for import.', 'fooconvert' )
            );
        }

        $image = PopupMedia::import_base64_image(
            $data,
            array(
                'filename'    => sanitize_file_name( (string) ( $input['filename'] ?? 'fooconvert-popup-image' ) ),
                'title'       => sanitize_text_field( (string) ( $input['title'] ?? '' ) ),
                'description' => sanitize_text_field( (string) ( $input['description'] ?? '' ) ),
                'alt_text'    => sanitize_text_field( (string) ( $input['alt_text'] ?? '' ) ),
                'mime_type'   => sanitize_text_field( (string) ( $input['mime_type'] ?? '' ) ),
                'prompt'      => sanitize_text_field( (string) ( $input['prompt'] ?? '' ) ),
                'popup_type'  => fooconvert_normalize_popup_type( $input['popup_type'] ?? '' ),
            )
        );

        if ( is_wp_error( $image ) ) {
            return $image;
        }

        return array(
            'image' => $image,
        );
    }

    /**
     * Generates and imports a popup image in one step.
     *
     * @param mixed $input Ability input.
     * @return array<string,mixed>|WP_Error
     */
    public function execute_create_popup_image( $input ) {
        $input = self::normalize_input( $input );

        if ( ! is_array( $input['popup_draft'] ?? null ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_blueprint_missing',
                __( 'A popup_draft object is required to create a popup image.', 'fooconvert' )
            );
        }

        $result = PopupMedia::generate_popup_media(
            PopupBlueprint::sanitize_popup_draft( $input['popup_draft'] ),
            isset( $input['instructions'] ) ? sanitize_text_field( (string) $input['instructions'] ) : ''
        );

        return is_wp_error( $result ) ? $result : $result;
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
