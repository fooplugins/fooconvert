<?php

namespace FooPlugins\FooConvert;

/**
 * Registers and renders FooConvert shortcodes.
 */
class Shortcodes {

    /**
     * Prevent duplicate shortcode registration per request.
     *
     * @var bool
     */
    private bool $registered = false;

    /**
     * Registers the popup shortcode.
     *
     * @return void
     */
    public function register(): void {
        if ( $this->registered ) {
            return;
        }

        add_shortcode( FOOCONVERT_CPT_POPUP, array( $this, 'render_shortcode' ) );
        $this->registered = true;
    }

    /**
     * Renders a popup shortcode instance.
     *
     * @param array       $attributes Shortcode attributes.
     * @param string|null $content Optional enclosed content.
     * @param string      $tag Shortcode tag name.
     * @return string|false
     */
    public function render_shortcode( array $attributes, ?string $content, string $tag ) {
        $attributes = shortcode_atts( array( 'id' => 0 ), $attributes, $tag );
        $post_id = (int) $attributes['id'];

        if ( !empty( $post_id ) && !FooConvert::plugin()->display_rules->is_enqueued( $post_id ) ) {
            $queueable = FooConvert::plugin()->display_rules->get_queueable( $post_id, 'shortcode' );
            if ( !empty( $queueable ) ) {
                do_action( 'fooconvert_enqueue_required_assets', array( $queueable ) );
                return FooConvert::plugin()->display_rules->render_queueable( $queueable );
            }
        }

        return false;
    }
}
