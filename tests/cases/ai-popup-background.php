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

            return $GLOBALS['fc_background_prompt_result'] ?? 'Brand-aligned popup background prompt';
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
        'Keep the center calm.'
    );

    Assertions::true(
        0 === strpos( $prompt, 'Create a polished 3:4 vertical flyout background-only image' ),
        'Generating a popup background prompt should use the deterministic background prompt builder.'
    );

    Assertions::true(
        false !== stripos( $prompt, 'Rules you MUST follow:' )
            && false !== stripos( $prompt, 'No text, no numbers, no coupon codes, no logos, no UI elements, no buttons, no forms, no devices, no people, no faces, no hands, no watermarks, no embedded typography, no cluttered center, no dark center, no harsh shadows, no distracting product labels, no busy patterns, no overly saturated colors.' )
            && false === stripos( $prompt, '<popup-background-rules>' ),
        'Generating a popup background prompt should include the strict popup background rules heading.'
    );

    Assertions::false(
        false !== strpos( $prompt, '{aspect_ratio}' )
            || false !== strpos( $prompt, '{popup_type}' )
            || false !== strpos( $prompt, '{color_scheme_line}' )
            || false !== strpos( $prompt, '{palette_line}' )
            || false !== strpos( $prompt, '{composition}' )
            || false !== strpos( $prompt, '{additional_visual_direction_line}' ),
        'Generating a popup background prompt should substitute every prompt template placeholder.'
    );

    Assertions::true(
        false !== stripos( $prompt, '#FF7A00' ) && false !== stripos( $prompt, '#1A4D3E' ),
        'The popup background prompt should include brand palette cues.'
    );

    Assertions::true(
        false !== stripos( $prompt, '3:4 vertical flyout' ),
        'The popup background prompt should include popup-format aspect ratio guidance.'
    );

    Assertions::true(
        false !== stripos( $prompt, 'Keep the center calm.' ),
        'The popup background prompt should append explicit visual direction.'
    );

    Assertions::false(
        false !== stripos( $prompt, 'Premium outdoor skincare brand with a calm, modern editorial feel.' )
            || false !== stripos( $prompt, 'Fraunces' )
            || false !== stripos( $prompt, 'Inter' )
            || false !== stripos( $prompt, 'Primary CTA Style' )
            || false !== stripos( $prompt, '999px' )
            || false !== stripos( $prompt, 'Grow the email list' )
            || false !== stripos( $prompt, 'First-time shoppers' )
            || false !== stripos( $prompt, '10% off the first order' )
            || false !== stripos( $prompt, 'Join for 10% off' )
            || false !== stripos( $prompt, 'Get My Welcome Offer' )
            || false !== stripos( $prompt, 'headline' )
            || false !== stripos( $prompt, 'form controls' )
            || false !== stripos( $prompt, 'CTA treatment' )
            || false !== stripos( $prompt, 'Visual style:' ),
        'The popup background prompt should not include campaign context, brand prose, typography, CTA styling, popup copy, or leading visual-style language.'
    );

    Assertions::same(
        0,
        count( $GLOBALS['fc_background_prompt_calls'] ?? array() ),
        'Generating a popup background prompt should not call a text model.'
    );

    $GLOBALS['fc_background_prompt_result'] = 'Below is a ready-to-use newsletter signup flyout design matching your brand. ```html <div class="newsletter-flyout"><button>Send me the guide</button></div> ``` ```css .newsletter-flyout { position: fixed; font-family: Inter; border-radius: 18px; } ``` Recommended popup copy used: CTA: Send me the guide';
    $deterministic_prompt = PopupMedia::generate_prompt_for_background(
        array(
            'title'       => 'Newsletter Guide',
            'popup_type'  => FOOCONVERT_POPUP_TYPE_FLYOUT,
            'goal'        => 'Grow the email list',
            'audience'    => 'Website readers',
            'offer'       => 'Practical website guide',
            'content_blocks' => array(
                array(
                    'name'       => 'core/button',
                    'attributes' => array(
                        'text' => 'Send me the guide',
                    ),
                ),
            ),
        ),
        array(
            'brandOverview' => 'Clean software education brand.',
            'colorScheme'   => 'light',
            'colors'        => array(
                'primary'    => '#7D4EFF',
                'background' => '#FFFFFF',
            ),
        ),
        ''
    );
    unset( $GLOBALS['fc_background_prompt_result'] );

    Assertions::true(
        false !== stripos( $deterministic_prompt, 'background-only image' )
            && false !== stripos( $deterministic_prompt, '3:4 vertical flyout' ),
        'Background prompts should always be deterministic instead of using generated prompt text or fallback repair.'
    );

    Assertions::false(
        false !== stripos( $deterministic_prompt, 'newsletter-flyout' )
            || false !== stripos( $deterministic_prompt, '```' )
            || 1 === preg_match( '/\bposition\s*:/i', $deterministic_prompt )
            || false !== stripos( $deterministic_prompt, 'Send me the guide' )
            || false !== stripos( $deterministic_prompt, 'Grow the email list' )
            || false !== stripos( $deterministic_prompt, 'Website readers' )
            || false !== stripos( $deterministic_prompt, 'Practical website guide' )
            || false !== stripos( $deterministic_prompt, 'Clean software education brand' ),
        'Deterministic background prompts should not retain generated HTML, CSS, class names, CTA copy, campaign details, or brand overview text.'
    );

    Assertions::true(
        strlen( $deterministic_prompt ) < 900,
        'Deterministic background prompts should stay short enough to avoid over-specifying popup context.'
    );

    $description_method = new \ReflectionMethod( PopupMedia::class, 'build_attachment_description' );
    $description_method->setAccessible( true );
    $description = $description_method->invoke(
        null,
        'Below is a ready-to-use popup design with HTML and CSS.',
        array(
            'provider_metadata' => array( 'name' => 'OpenAI' ),
            'model_metadata'    => array( 'name' => 'gpt-image-1' ),
        )
    );

    Assertions::same(
        'Generated by OpenAI using gpt-image-1',
        $description,
        'Generated image attachment descriptions should summarize provider metadata without embedding the prompt.'
    );

    Assertions::false(
        false !== stripos( $description, 'Prompt:' )
            || false !== stripos( $description, 'Below is' ),
        'Generated image attachment descriptions should not include raw prompt text.'
    );

    echo "ai-popup-background: ok\n";
}
