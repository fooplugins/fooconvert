<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Admin {
    class DemoContent {
        public function get_demo_content(): array {
            return array();
        }
    }
}

namespace WordPress\AI {
    function get_preferred_models_for_text_generation(): array {
        return array( 'stub-text-model' );
    }
}

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\Media\Attachments as PopupMedia;
    use FooPlugins\FooConvert\AI\PopupBuilder\Settings;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    class WP_Error {
        private string $code;
        private string $message;

        public function __construct( string $code, string $message ) {
            $this->code = $code;
            $this->message = $message;
        }

        public function get_error_code(): string {
            return $this->code;
        }

        public function get_error_message(): string {
            return $this->message;
        }
    }

    class PopupBackgroundPromptBuilderStub {
        private int $index;

        public function __construct( string $content ) {
            $GLOBALS['fc_background_prompt_content'] = $content;
            $this->index = count( $GLOBALS['fc_background_prompt_calls'] ?? array() );
            $GLOBALS['fc_background_prompt_calls'][ $this->index ] = array(
                'content'     => $content,
                'temperature' => false,
                'model'       => '',
            );
        }

        public function using_system_instruction( string $instruction ): self {
            $GLOBALS['fc_background_prompt_system_instruction'] = $instruction;
            return $this;
        }

        public function using_temperature( float $temperature ): self {
            $GLOBALS['fc_background_prompt_temperature'] = $temperature;
            $GLOBALS['fc_background_prompt_calls'][ $this->index ]['temperature'] = $temperature;
            return $this;
        }

        public function using_model_preference( string ...$models ): self {
            $GLOBALS['fc_background_prompt_models'] = $models;
            $GLOBALS['fc_background_prompt_calls'][ $this->index ]['model'] = $models[0] ?? '';
            return $this;
        }

        public function generate_text() {
            $call_count = (int) ( $GLOBALS['fc_background_prompt_generate_count'] ?? 0 );
            $GLOBALS['fc_background_prompt_generate_count'] = $call_count + 1;

            if (
                ! empty( $GLOBALS['fc_background_prompt_fail_temperature_once'] )
                && 0 === $call_count
                && ! empty( $GLOBALS['fc_background_prompt_calls'][ $this->index ]['temperature'] )
            ) {
                return new WP_Error(
                    'unsupported_parameter',
                    "Unsupported parameter: 'temperature' is not supported with this model."
                );
            }

            return 'Brand-aligned popup background prompt';
        }
    }

    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    function sanitize_text_field( $value ): string {
        return trim( strip_tags( (string) $value ) );
    }

    function sanitize_textarea_field( $value ): string {
        return trim( strip_tags( (string) $value ) );
    }

    function sanitize_hex_color( $value ): string {
        $value = trim( (string) $value );
        return preg_match( '/^#[a-f0-9]{3,8}$/i', $value ) === 1 ? strtoupper( $value ) : '';
    }

    function wp_kses_post( $value ): string {
        return (string) $value;
    }

    function esc_url_raw( $value ): string {
        return trim( (string) $value );
    }

    function wp_strip_all_tags( $value ): string {
        return strip_tags( (string) $value );
    }

    function trailingslashit( string $value ): string {
        return rtrim( $value, '/\\' ) . '/';
    }

    function absint( $value ): int {
        return abs( (int) $value );
    }

    function did_action( string $hook ): int {
        return 1;
    }

    function doing_action( string $hook = '' ): bool {
        return false;
    }

    function get_option( string $option, $default = null ) {
        return $GLOBALS['fc_background_prompt_options'][ $option ] ?? $default;
    }

    function update_option( string $option, $value ): bool {
        $GLOBALS['fc_background_prompt_options'][ $option ] = $value;
        return true;
    }

    function get_bloginfo( string $show = '' ): string {
        return '';
    }

    function wp_ai_client_prompt( string $content = '' ): PopupBackgroundPromptBuilderStub {
        return new PopupBackgroundPromptBuilderStub( $content );
    }

    function current_user_can( string $capability ): bool {
        if ( 'manage_options' === $capability ) {
            return (bool) ( $GLOBALS['fc_background_prompt_can_manage_options'] ?? true );
        }

        return true;
    }

    function is_wp_error( $thing ): bool {
        return $thing instanceof WP_Error;
    }

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ );
    }

    if ( ! defined( 'FOOCONVERT_INCLUDES_PATH' ) ) {
        define( 'FOOCONVERT_INCLUDES_PATH', dirname( __DIR__, 2 ) . '/includes/' );
    }

    if ( ! defined( 'FOOCONVERT_ASSETS_URL' ) ) {
        define( 'FOOCONVERT_ASSETS_URL', 'https://example.test/wp-content/plugins/fooconvert/assets/' );
    }

    if ( ! defined( 'DAY_IN_SECONDS' ) ) {
        define( 'DAY_IN_SECONDS', 86400 );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Brand/Manager.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Settings.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Blueprint/DraftNormalizer.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Media/Attachments.php';

    $prompt = PopupMedia::generate_prompt_for_background(
        array(
            'title'       => 'Spring Welcome Offer',
            'popup_type'  => FOOCONVERT_POPUP_TYPE_FLYOUT,
            'goal'        => 'Grow the email list',
            'audience'    => 'First-time shoppers',
            'offer'       => '10% off the first order',
            'content_blocks' => array(
                array(
                    'name'       => 'core/heading',
                    'attributes' => array(
                        'content' => 'Join for 10% off',
                        'level'   => 2,
                    ),
                ),
                array(
                    'name'       => 'core/button',
                    'attributes' => array(
                        'text' => 'Get My Welcome Offer',
                    ),
                ),
            ),
        ),
        array(
            'brandOverview' => 'Premium outdoor skincare brand with a calm, modern editorial feel.',
            'colorScheme'   => 'light',
            'colors'        => array(
                'primary'       => '#1A4D3E',
                'secondary'     => '#DDEFE8',
                'accent'        => '#FF7A00',
                'background'    => '#F8F5EE',
                'textPrimary'   => '#17221E',
                'textSecondary' => '#4A5A53',
            ),
            'typography'    => array(
                'fontFamilies' => array(
                    'primary' => 'Inter',
                    'heading' => 'Fraunces',
                ),
                'fontSizes'   => array(
                    'h1'   => array( 'value' => '48px' ),
                    'body' => array( 'value' => '16px' ),
                ),
            ),
            'spacing'       => array(
                'baseUnit'     => 8,
                'borderRadius' => '20px',
            ),
            'components'    => array(
                'buttonPrimary' => array(
                    'background'   => '#FF7A00',
                    'textColor'    => '#FFFFFF',
                    'borderRadius' => '999px',
                ),
            ),
        ),
        'Keep the center calm for the offer copy.'
    );

    Assertions::same(
        'Brand-aligned popup background prompt',
        $prompt,
        'Generating a popup background prompt should return the AI-generated prompt text.'
    );

    $captured_content = (string) ( $GLOBALS['fc_background_prompt_content'] ?? '' );
    $captured_system_instruction = (string) ( $GLOBALS['fc_background_prompt_system_instruction'] ?? '' );

    Assertions::true(
        false !== stripos( $captured_content, 'Premium outdoor skincare brand with a calm, modern editorial feel.' ),
        'The popup background prompt context should include the brand overview.'
    );

    Assertions::true(
        false !== stripos( $captured_content, '#FF7A00' ) && false !== stripos( $captured_content, '#1A4D3E' ),
        'The popup background prompt context should include the brand palette.'
    );

    Assertions::true(
        false !== stripos( $captured_content, 'Fraunces' ) && false !== stripos( $captured_content, 'Inter' ),
        'The popup background prompt context should include typography direction from the brand.'
    );

    Assertions::true(
        false !== stripos( $captured_content, 'Primary CTA Style' ) && false !== stripos( $captured_content, '999px' ),
        'The popup background prompt context should include CTA styling so the background stays supportive.'
    );

    Assertions::true(
        false !== stripos( $captured_content, '3:4 vertical flyout' ),
        'The popup background prompt context should include popup-format aspect ratio guidance.'
    );

    Assertions::true(
        false !== stripos( $captured_content, 'Keep the center calm for the offer copy.' ),
        'The popup background prompt context should append additional direction.'
    );

    Assertions::true(
        false !== stripos( $captured_system_instruction, 'background behind popup copy and CTA' ),
        'The popup background system instruction should emphasize text-safe background generation.'
    );

    Assertions::same(
        0.85,
        $GLOBALS['fc_background_prompt_calls'][0]['temperature'] ?? null,
        'The popup background prompt should use its default temperature when the parameter has not been disabled.'
    );

    $GLOBALS['fc_background_prompt_calls'] = array();
    $GLOBALS['fc_background_prompt_generate_count'] = 0;
    $GLOBALS['fc_background_prompt_fail_temperature_once'] = true;
    $GLOBALS['fc_background_prompt_options'] = array();

    $retry_prompt = PopupMedia::generate_prompt_for_background(
        array(
            'title'      => 'Launch Offer',
            'popup_type' => FOOCONVERT_POPUP_TYPE_POPUP,
            'goal'       => 'Drive launch sales',
            'audience'   => 'Returning customers',
            'offer'      => '15% off',
        ),
        array(),
        ''
    );

    Assertions::same(
        'Brand-aligned popup background prompt',
        $retry_prompt,
        'Background prompt generation should retry after disabling a rejected optional temperature parameter.'
    );

    Assertions::same(
        2,
        (int) ( $GLOBALS['fc_background_prompt_generate_count'] ?? 0 ),
        'The unsupported temperature response should trigger one retry.'
    );

    Assertions::same(
        0.85,
        $GLOBALS['fc_background_prompt_calls'][0]['temperature'] ?? null,
        'The initial background prompt attempt should include temperature.'
    );

    Assertions::false(
        ! empty( $GLOBALS['fc_background_prompt_calls'][1]['temperature'] ),
        'The retried background prompt should omit the auto-disabled temperature parameter.'
    );

    $settings = Settings::get();
    Assertions::same(
        array( 'temperature' ),
        $settings['disabled_params'] ?? array(),
        'The unsupported background prompt parameter should be persisted to disabled AI params.'
    );

    echo "ai-popup-background: ok\n";
}
