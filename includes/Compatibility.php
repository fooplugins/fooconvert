<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Components\Base\BaseComponent;

/**
 * Class Compatibility.
 */
class Compatibility extends BaseComponent {

    /**
     * Returns the component data name.
     */
    function get_component_data_name(): string {
        return 'FC_COMPATIBILITY';
    }

    /**
     * Returns the component data.
     */
    function get_component_data(): array {
        return array(
            'meta' => array(
                'key'      => FOOCONVERT_META_KEY_COMPATIBILITY,
                'defaults' => $this->defaults()
            ),
        );
    }

    /**
     * Handles defaults.
     */
    function defaults(): array {
        return array(
            'required' => false,
            'enabled'  => false,
        );
    }

    /**
     * Handles schema.
     */
    function schema(): array {
        return array(
            'type'       => 'object',
            'properties' => array(
                'required' => array( 'type' => 'boolean' ),
                'enabled'  => array( 'type' => 'boolean' )
            )
        );
    }

    /**
     * The auth callback for the compatibility meta key.
     *
     * @return bool True if the current user can edit the meta key, false if not.
     *
     * @since 1.0.0
     */
    public function auth_callback(): bool {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Handles register.
     */
    public function register(): bool {
        return register_meta( 'post', FOOCONVERT_META_KEY_COMPATIBILITY, array(
            'object_subtype' => FOOCONVERT_CPT_POPUP,
            'single'         => true,
            'type'           => 'object',
            'description'    => __( 'Compatibility settings for FooConvert.', 'fooconvert' ),
            'auth_callback'  => array( $this, 'auth_callback' ),
            'default'        => $this->defaults(),
            'show_in_rest'   => array( 'schema' => $this->schema() )
        ) );
    }

    /**
     * Returns the meta.
     */
    public function get_meta( int $post_id ): array {
        $meta = get_post_meta( $post_id, FOOCONVERT_META_KEY_COMPATIBILITY, true );
        if ( !Utils::is_array( $meta ) ) {
            $meta = array();
        }
        return array_merge( $this->defaults(), $meta );
    }

    /**
     * Sets the meta.
     */
    public function set_meta( int $post_id, array $value, bool $update = false ): bool {
        if ( $update === true ) {
            $meta = $this->get_meta( $post_id );
            $value = array_merge( $meta, $value );
        }
        $result = update_post_meta( $post_id, FOOCONVERT_META_KEY_COMPATIBILITY, $value );
        return !empty( $result );
    }

    /**
     * Determines whether enabled.
     */
    public function is_enabled( int $post_id ): bool {
        $meta = $this->get_meta( $post_id );
        return Utils::get_bool( $meta, 'enabled' );
    }

    /**
     * Checks content.
     */
    public function check_content( int $post_id, string $content ): bool {
        $required = preg_match( '/<script\b[^>]*>(.*?)<\/script>/is', $content ) === 1;
        return $this->set_meta( $post_id, array( 'required' => $required ), true );
    }
}
