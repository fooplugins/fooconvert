<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Components\Base\BaseComponent;

class Compatibility extends BaseComponent {

    function get_component_data_name(): string {
        return 'FC_COMPATIBILITY';
    }

    function get_component_data(): array {
        return array(
            'meta' => array(
                'key'      => FOOCONVERT_META_KEY_COMPATIBILITY,
                'defaults' => $this->defaults()
            ),
        );
    }

    function defaults(): array {
        return array(
            'required' => false,
            'enabled'  => false,
        );
    }

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

    public function register( string $post_type ): bool {
        return register_meta( 'post', FOOCONVERT_META_KEY_COMPATIBILITY, array(
            'object_subtype' => $post_type,
            'single'         => true,
            'type'           => 'object',
            'description'    => __( 'Compatibility settings for FooConvert.', 'fooconvert' ),
            'auth_callback'  => array( $this, 'auth_callback' ),
            'default'        => $this->defaults(),
            'show_in_rest'   => array( 'schema' => $this->schema() )
        ) );
    }

    public function get_meta( int $post_id ): array {
        $meta = get_post_meta( $post_id, FOOCONVERT_META_KEY_COMPATIBILITY, true );
        if ( !Utils::is_array( $meta ) ) {
            $meta = array();
        }
        return array_merge( $this->defaults(), $meta );
    }

    public function set_meta( int $post_id, array $value, bool $update = false ): bool {
        if ( $update === true ) {
            $meta = $this->get_meta( $post_id );
            $value = array_merge( $meta, $value );
        }
        $result = update_post_meta( $post_id, FOOCONVERT_META_KEY_COMPATIBILITY, $value );
        return !empty( $result );
    }

    public function is_enabled( int $post_id ): bool {
        $meta = $this->get_meta( $post_id );
        return Utils::get_bool( $meta, 'enabled' );
    }

    public function check_content( int $post_id, string $content ): bool {
        $required = preg_match( '/<script\b[^>]*>(.*?)<\/script>/is', $content ) === 1;
        return $this->set_meta( $post_id, array( 'required' => $required ), true );
    }
}