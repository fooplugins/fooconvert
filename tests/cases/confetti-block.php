<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Blocks\Base {
    use WP_Block;

    abstract class BaseBlock {
        public function render( array $attributes, string $content, WP_Block $block ) {
            return $content;
        }
    }
}

namespace FooPlugins\FooConvert {
    class Utils {
        public static function register_popup_blocks( array $blocks ) {
            return $blocks;
        }
    }

    class Popups {
        public function get_registered_post_type(): string {
            return 'fc-popup';
        }
    }

    class FooConvert {
        /** @var ?FooConvert */
        private static $instance = null;

        /** @var Popups */
        public $popups;

        public function __construct() {
            $this->popups = new Popups();
        }

        public static function plugin(): FooConvert {
            if ( self::$instance === null ) {
                self::$instance = new FooConvert();
            }

            return self::$instance;
        }
    }
}

namespace {
    use FooPlugins\FooConvert\Pro\Blocks\Confetti;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    class WP_Block {}

    if ( !defined( 'FOOCONVERT_PRO_ASSETS_PATH' ) ) {
        define( 'FOOCONVERT_PRO_ASSETS_PATH', dirname( __DIR__, 2 ) . '/pro/assets/' );
    }
    if ( !defined( 'FOOCONVERT_CPT_POPUP' ) ) {
        define( 'FOOCONVERT_CPT_POPUP', 'fc-popup' );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Blocks/Confetti.php';

    $block = new Confetti();

    Assertions::same(
        'fc/confetti',
        $block->get_block_name(),
        'Confetti should register the expected block name.'
    );

    Assertions::same(
        'fc-confetti',
        $block->get_tag_name(),
        'Confetti should render the expected custom element tag.'
    );

    Assertions::same(
        array(
            'hidden'      => 'hidden',
            'aria-hidden' => 'true',
        ),
        $block->get_frontend_attributes( 'fc-confetti-test', array(), new WP_Block() ),
        'Confetti should render as an invisible frontend support block.'
    );

    $registered = $block->register_blocks();

    Assertions::same(
        'fc/confetti',
        $block->get_block_name(),
        'Confetti block registration should not alter the block name.'
    );

    Assertions::same(
        'fc-confetti',
        $block->get_tag_name(),
        'Confetti block registration should not alter the tag name.'
    );

    Assertions::true(
        is_array( $registered ) && isset( $registered[0]['file_or_folder'] ) && false !== strpos( $registered[0]['file_or_folder'], 'blocks/confetti/block.json' ),
        'Confetti should register its block metadata from the PRO block.json file.'
    );

    echo "confetti-block: ok\n";
}
