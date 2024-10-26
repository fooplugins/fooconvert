<?php

namespace FooPlugins\FooConvert\Blocks\Base;

use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Utils;
use WP_Block;
use WP_Block_Type;

/**
 * A base class for a dynamic block.
 */
abstract class Base_Block {
    /**
     * The block types registered by the call to the `register_blocks` method.
     *
     * @var WP_Block_Type[]
     */
    public array $block_types = array();

    /**
     * @var string[]
     */
    public array $supported = array();

    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'fooconvert_enqueued_editor_assets', array( $this, 'enqueue_editor_settings' ) );
    }

    /**
     * Callback for the `'init'` action.
     *
     * This method calls `register_blocks` and stores the result in the `block_types` property
     * so other methods such as `get_asset_handle` can look up values later on.
     */
    function init() {
        $block_types = $this->register_blocks();
        if ( ! empty( $block_types ) ) {
            $this->block_types = $block_types;
            foreach ( $this->block_types as $block_type ) {
                foreach ( $block_type->editor_script_handles as $editor_script_handle ) {
                    wp_set_script_translations( $editor_script_handle, 'fooconvert' );
                }
            }
        }
    }

    /**
     * Check if features are supported by this block.
     *
     * @param string|string[] $value A string or string array of features to check for.
     * @return bool True if all given features are supported, otherwise false.
     */
    function supports( $value ) : bool {
        if ( is_string( $value ) ) {
            return in_array( $value, $this->supported );
        }
        if ( is_array( $value ) ) {
            return count( array_intersect( $value, $this->supported ) ) === count( $value );
        }
        return false;
    }

    //region ICustomBlock implementation

    abstract function kses_definition(): array;

    /**
     * Get the block name.
     *
     * @return string
     *
     * @since 1.0.0
     */
    abstract function get_block_name() : string;

    /**
     * Get the element tag name.
     *
     * @return string
     *
     * @since 1.0.0
     */
    abstract function get_tag_name() : string;

    /**
     * Register the block.
     *
     * @return false|WP_Block_Type[]
     *
     * @since 1.0.0
     */
    abstract function register_blocks();

    /**
     * Get a specific asset handle from the registered block types.
     *
     * If this method is called prior to `register_blocks` or the specified handle could not be retrieved, `false` is returned.
     *
     * @param string $name One of the public 'handle' properties available on a `WP_Block_type` object.
     * @param int $asset_index Optional. The index of the asset if multiple items were passed. Default `0`.
     * @param int $block_index Optional. The index of the block if multiple blocks were registered. Default `0`.
     *
     * @return false|string The asset handle, otherwise false.
     */
    function get_asset_handle( string $name, int $asset_index = 0, int $block_index = 0 ) {
        if ( ! empty( $this->block_types ) && $block_index < count( $this->block_types ) ) {
            $block_type = $this->block_types[ $block_index ];
            $handles = Utils::get_array( $block_type, $name );
            if ( $asset_index < count( $handles ) ) {
                return $handles[ $asset_index ];
            }
        }
        return false;
    }

    /**
     * Get the settings for the block when displayed in the editor.
     *
     * This method creates the array that is output as a blocks editor settings and pulls in the values of various
     * other methods, primarily `get_variations` and `get_editor_data`.
     *
     * See the editor JavaScripts `getEditorSettings()` utility method for accessing these settings within JS scripts.
     *
     * @return array An array containing any settings to be output just before the editor scripts for the block.
     *
     * @since 1.0.0
     */
    function get_editor_settings() : array {
        $settings = array();
        $variations = $this->get_editor_variations();
        if ( ! empty( $variations ) ) {
            $settings['variations'] = $variations;
        }
        $data = $this->get_editor_data();
        if ( ! empty( $data ) ) {
            $settings['data'] = $data;
        }
        return $settings;
    }

    /**
     * Get the variable name for the blocks editor settings.
     *
     * This value is passed as the `$object_name` parameter to the {@link Utils::to_js_script} method when enqueueing
     * the script, so it's restrictions apply, mainly that the first string be a valid JavaScript name.
     *
     * @return string|string[] A string or string[] representing the blocks editor settings variable name.
     *
     * @example
     * 'MyObject' => 'var MyObject = ...'
     * [ 'MyObject', 'some', 'path' ] => 'MyObject["some"]["path"] = ...'
     *
     * @since 1.0.0
     */
    function get_editor_settings_name() : string {
        return Utils::make_identifier( $this->get_block_name() );
    }

    /**
     * Get the variable name for the blocks frontend data.
     *
     * This value is passed as the `$object_name` parameter to the {@link Utils::to_js_script} method when enqueueing
     * the script, so it's restrictions apply, mainly that the first string be a valid JavaScript name.
     *
     * @param string $instance_id The instance identifier for the block.
     *
     * @return string|string[] A string or string[] representing the blocks frontend data variable name.
     *
     * @example
     * 'MyObject' => 'var MyObject = ...'
     * [ 'MyObject', 'some', 'path' ] => 'MyObject["some"]["path"] = ...'
     *
     * @since 1.0.0
     */
    function get_frontend_data_name( string $instance_id ) : string {
        return Utils::make_identifier( $instance_id );
    }

    /**
     * Get the variations for the block.
     *
     * @return array
     *
     * @since 1.0.0
     */
    function get_editor_variations() : array {
        return array();
    }

    /**
     * Get data for the block when displayed in the editor.
     *
     * This data is included as part of the array returned by `get_editor_settings`.
     *
     * @return array An array containing any editor data for the block.
     *
     * @since 1.0.0
     */
    function get_editor_data() : array {
        return array();
    }

    /**
     * Create a unique instance identifier for the block.
     *
     * This value is used as the 'id' attribute for the block which is then used to apply custom styles
     * and supply data for the block when displayed on the frontend.
     *
     * @return string A unique instance identifier for the block.
     *
     * @since 1.0.0
     */
    function create_instance_id() : string {
        return wp_unique_prefixed_id( $this->get_tag_name() . '-' );
    }

    /**
     * Get the custom styles for the block when displayed on the frontend.
     *
     * These styles will be converted to a string and enqueued using {@link wp_add_inline_style}.
     *
     * @param string $instance_id The instance identifier for the block.
     * @param array $attributes The current block attributes.
     * @param WP_Block $block The `WP_Block` instance for the current block.
     *
     * @return array An associative array where the keys are CSS selectors and the values are arrays of CSS property/value pairs.
     *
     * @example Example - Expected Output
     * $styles = array(
     *     '.my-class' => array(
     *         'color' => '#F00',
     *         'background-color' => '#000'
     *     )
     * );
     *
     * @since 1.0.0
     */
    function get_frontend_styles( string $instance_id, array $attributes, WP_Block $block ) : array {
        return array();
    }

    /**
     * Get the data for the block when displayed in the frontend.
     *
     * This data is enqueued using the {@link wp_add_inline_script} method, and is converted to a string using
     * the {@link Utils::to_js_script} method.
     *
     * See the frontend JavaScripts `getBlockData()` utility method for accessing this data within frontend scripts.
     *
     * @param string $instance_id The instance identifier for the block.
     * @param array $attributes The current block attributes.
     * @param WP_Block $block The `WP_Block` instance for the current block.
     *
     * @return array An array containing any data to be output just before the frontend scripts for the block.
     *
     * @since 1.0.0
     */
    function get_frontend_data( string $instance_id, array $attributes, WP_Block $block ) : array {
        return array();
    }

    /**
     * Get the extra attributes for the block when displayed in the frontend.
     *
     * These attributes are supplied as part of the render process to the {@link get_block_wrapper_attributes} method.
     *
     * @param string $instance_id The instance identifier for the block.
     * @param array $attributes The current block attributes.
     * @param WP_Block $block The `WP_Block` instance for the current block.
     *
     * @return array An associative array containing any extra attributes to be output on the block wrapper element.
     *
     * @example Example - Expected Output
     * $attributes = array(
     *     'data-custom' => 'value'
     * );
     *
     * @since 1.0.0
     */
    function get_frontend_attributes( string $instance_id, array $attributes, WP_Block $block ) : array {
        return array();
    }

    /**
     * Get custom SVG icons for the block when displayed in the frontend.
     *
     * These icons are output as part of the render process as direct children of the block wrapper element
     * and must have a `slot` attribute defined.
     *
     * @param string $instance_id The instance identifier for the block.
     * @param array $attributes The current block attributes.
     * @param WP_Block $block The `WP_Block` instance for the current block.
     *
     * @return array An array containing any SVG icons to be output within the block.
     *
     * @example Example - Expected Output
     * $icons = array(
     *     '<svg slot="custom-element-slot" ...other>...</svg>'
     * );
     *
     * @since 1.0.0
     */
    function get_frontend_icons( string $instance_id, array $attributes, WP_Block $block ) : array {
        return array();
    }

    /**
     * The `render_callback` for the block.
     *
     * @param array $attributes
     * @param string $content
     * @param WP_Block $block
     *
     * @return string|false
     */
    function render( array $attributes, string $content, WP_Block $block ) {
        if ( ! empty( $content ) ) {
            $tag_name = $this->get_tag_name();
            $instance_id = $this->create_instance_id();
            $frontend_attributes = $this->get_frontend_attributes( $instance_id, $attributes, $block );
            $frontend_styles = $this->get_frontend_styles( $instance_id, $attributes, $block );
            $frontend_data = $this->get_frontend_data( $instance_id, $attributes, $block );
            $frontend_icons = $this->get_frontend_icons( $instance_id, $attributes, $block );

            $this->enqueue_frontend_styles( $instance_id, $frontend_styles );
            $this->enqueue_frontend_data( $instance_id, $frontend_data );

            ob_start();
            // @formatter:off
?>
<<?php echo esc_html( $tag_name ); ?> id="<?php echo esc_attr( $instance_id ) ?>" <?php echo wp_kses_data( get_block_wrapper_attributes( $frontend_attributes ) ); ?>>
    <?php $this->render_frontend_icons( $instance_id, $frontend_icons ); ?>
    <?php
            // Reviewers:
            // The do_blocks() output is passed through wp_kses with an extended post allowed HTML list that includes
            // the custom elements for the plugin.
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo FooConvert::plugin()->kses_post( do_blocks( $content ) );
    ?>
</<?php echo esc_html( $tag_name ); ?>>
<?php
            // @formatter:on
            return ob_get_clean();
        }
        return false;
    }

    function render_empty() : string {
        return '';
    }

    function render_content( array $attributes, string $content ) {
        ob_start();
        // Reviewers:
        // The do_blocks() output is passed through wp_kses with an extended post allowed HTML list that includes
        // the custom elements for the plugin.
        // phpcs:ignore WordPress.Security.EscapeOutput
        echo FooConvert::plugin()->kses_post( do_blocks( $content ) );
        return ob_get_clean();
    }

    //endregion

    function enqueue_editor_settings() : bool {
        $js_script = Utils::to_js_script( $this->get_editor_settings_name(), $this->get_editor_settings() );
        if ( ! empty( $js_script ) ) {
            $handle = $this->get_asset_handle( 'editor_script_handles' );
            if ( ! empty( $handle ) ) {
                // Reviewers:
                // The $js_script is built up from settings required by our plugins' blocks and is both
                // HTML decoded and JSON encoded by the to_js_script method.
                // phpcs:ignore WordPress.Security.EscapeOutput
                return wp_add_inline_script( $handle, $js_script, 'before' );
            }
        }
        return false;
    }

    function render_frontend_icons( string $instance_id, array $icons ) {
        if ( ! empty( $icons ) ) {
            foreach ( $icons as $icon ) {
                if ( Utils::is_string( $icon, true ) && false !== strpos( $icon, 'slot=' ) ) {
                    // Reviewers:
                    // This is the rendered output of an SVG from '@wordpress/icons' and is passed to
                    // wp_kses with an allowed HTML list that includes SVG elements.
                    // phpcs:ignore WordPress.Security.EscapeOutput
                    echo FooConvert::plugin()->kses_svg( $icon );
                }
            }
        }
    }

    function enqueue_frontend_styles( string $instance_id, array $styles ) : bool {
        $css_text = Utils::to_css_text( $styles, true );
        if ( ! empty( $css_text ) ) {
            $handle = $this->get_asset_handle( 'view_style_handles' );
            if ( ! empty( $handle ) ) {
                return wp_add_inline_style( $handle, esc_html( $css_text ) );
            }
        }
        return false;
    }

    function enqueue_frontend_data( string $instance_id, array $data ) : bool {
        $js_script = Utils::to_js_script( $this->get_frontend_data_name( $instance_id ), $data );
        if ( ! empty( $js_script ) ) {
            $handle = $this->get_asset_handle( 'view_script_handles' );
            if ( ! empty( $handle ) ) {
                // Reviewers:
                // The $js_script is built up from the $data array created by our plugins' blocks and is both
                // HTML decoded and JSON encoded by the to_js_script method.
                // phpcs:ignore WordPress.Security.EscapeOutput
                return wp_add_inline_script( $handle, $js_script, 'before' );
            }
        }
        return false;
    }
}