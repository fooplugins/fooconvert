<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

class OpenTriggerPanel extends BaseComponent {
    private const V2_VERSION = 2;

    private const LIFETIMES = array( 'page', 'session', 'visit' );

    private const FREQUENCY_MODES = array( 'once', 'repeat' );

    function get_component_data_name(): string {
        return 'FC_OPEN_TRIGGER';
    }

    function get_component_data(): array {
        return array(
            'triggers' => $this->get_trigger_definitions()
        );
    }

    function get_attributes( array $attr_value ): array {
        return array();
    }

    function get_data( array $attr_value ): array {
        $data = array();
        $trigger = $this->normalize_trigger( $attr_value );
        if ( !empty( $trigger ) ) {
            $data['triggerConfig'] = $trigger;
        }
        return $data;
    }

    private function normalize_trigger( array $attr_value ): array {
        $version = Utils::get_int( $attr_value, 'version' );
        if ( $version === self::V2_VERSION ) {
            return $this->normalize_v2_trigger( $attr_value );
        }
        return $this->normalize_legacy_trigger( $attr_value );
    }

    private function normalize_v2_trigger( array $trigger ): array {
        $steps = Utils::get_array( $trigger, 'steps' );
        if ( empty( $steps ) ) {
            return array();
        }

        $normalized_steps = array();
        foreach ( $steps as $step ) {
            if ( !is_array( $step ) ) {
                continue;
            }
            $normalized_step = $this->normalize_step( $step );
            if ( !empty( $normalized_step ) ) {
                $normalized_steps[] = $normalized_step;
            }
        }

        if ( count( $normalized_steps ) === 0 ) {
            return array();
        }

        $lifetime = Utils::get_string( $trigger, 'lifetime', 'page' );
        if ( !in_array( $lifetime, self::LIFETIMES, true ) ) {
            $lifetime = 'page';
        }

        $frequency = Utils::get_array( $trigger, 'frequency' );
        $frequency_mode = Utils::get_string( $frequency, 'mode', 'repeat' );
        if ( !in_array( $frequency_mode, self::FREQUENCY_MODES, true ) ) {
            $frequency_mode = 'repeat';
        }
        $cooldown_seconds = Utils::get_int( $frequency, 'cooldownSeconds' );
        if ( $cooldown_seconds < 0 ) {
            $cooldown_seconds = 0;
        }

        return array(
            'version'   => self::V2_VERSION,
            'lifetime'  => $lifetime,
            'frequency' => array(
                'mode'            => $frequency_mode,
                'cooldownSeconds' => $cooldown_seconds
            ),
            'steps'     => $normalized_steps
        );
    }

    private function normalize_legacy_trigger( array $trigger ): array {
        $trigger_type = Utils::get_string( $trigger, 'type' );
        $trigger_data = Utils::get_key( $trigger, 'data' );
        $has_once_key = array_key_exists( 'once', $trigger );
        $definition = $this->get_trigger_definition_by_legacy_type( $trigger_type );

        $trigger_once = $has_once_key
            ? Utils::get_bool( $trigger, 'once' )
            : !empty( $definition['defaultOnce'] );

        $step = null;

        switch ( $trigger_type ) {
            case 'immediate':
                $step = array(
                    'event' => 'fc.immediate',
                    'where' => array()
                );
                break;
            case 'anchor':
                if ( Utils::is_string( $trigger_data, true ) ) {
                    $step = array(
                        'event' => 'fc.anchor.click',
                        'where' => array(
                            'ids' => $this->normalize_string_array( $trigger_data )
                        )
                    );
                }
                break;
            case 'element':
                if ( Utils::is_string( $trigger_data, true ) ) {
                    $step = array(
                        'event' => 'fc.element.click',
                        'where' => array(
                            'selector' => $trigger_data
                        )
                    );
                }
                break;
            case 'visible':
                if ( Utils::is_string( $trigger_data, true ) ) {
                    $step = array(
                        'event' => 'fc.element.visible',
                        'where' => array(
                            'ids' => $this->normalize_string_array( $trigger_data )
                        )
                    );
                }
                break;
            case 'exit-intent':
                if ( Utils::is_int_within( $trigger_data, 0, 100 ) ) {
                    $step = array(
                        'event' => 'fc.exit_intent',
                        'where' => array(
                            'delaySeconds' => intval( $trigger_data )
                        )
                    );
                }
                break;
            case 'scroll':
                if ( Utils::is_int_within( $trigger_data, 1, 100 ) ) {
                    $step = array(
                        'event' => 'fc.scroll.percent',
                        'where' => array(
                            'percent' => intval( $trigger_data )
                        )
                    );
                }
                break;
            case 'timer':
                if ( Utils::is_int_within( $trigger_data, 0, 100 ) ) {
                    $step = array(
                        'event' => 'fc.timer.elapsed',
                        'where' => array(
                            'seconds' => intval( $trigger_data )
                        )
                    );
                }
                break;
        }

        if ( empty( $step ) ) {
            return array();
        }

        return array(
            'version'   => self::V2_VERSION,
            'lifetime'  => 'page',
            'frequency' => array(
                'mode'            => $trigger_once ? 'once' : 'repeat',
                'cooldownSeconds' => 0
            ),
            'steps'     => array( $step )
        );
    }

    private function normalize_step( array $step ): array {
        $event = Utils::get_string( $step, 'event' );
        $definition = $this->get_trigger_definition_by_event( $event );
        if ( empty( $definition ) ) {
            return array();
        }

        $where = Utils::get_array( $step, 'where' );
        $normalized = array(
            'event' => $event,
            'where' => $this->normalize_where( $definition, $where )
        );

        if ( array_key_exists( 'withinSeconds', $step ) ) {
            $within_seconds = intval( $step['withinSeconds'] );
            if ( $within_seconds >= 0 ) {
                $normalized['withinSeconds'] = $within_seconds;
            }
        }

        return $normalized;
    }

    private function normalize_where( array $definition, array $where ): array {
        $normalized = array();
        $fields = isset( $definition['fields'] ) && is_array( $definition['fields'] ) ? $definition['fields'] : array();

        foreach ( $fields as $field ) {
            $path = isset( $field['path'] ) ? strval( $field['path'] ) : '';
            if ( empty( $path ) ) {
                continue;
            }

            $value = $this->get_nested_value( $where, $path );
            $sanitized = $this->sanitize_field_value( $field, $value );
            $normalized = $this->set_nested_value( $normalized, $path, $sanitized );
        }

        return $normalized;
    }

    private function normalize_string_array( $value ): array {
        $values = is_array( $value ) ? $value : explode( ',', strval( $value ) );
        $result = array();
        foreach ( $values as $entry ) {
            $entry = trim( strval( $entry ) );
            if ( $entry !== '' ) {
                $result[] = $entry;
            }
        }
        return array_values( array_unique( $result ) );
    }

    private function normalize_int_array( $value ): array {
        $values = is_array( $value ) ? $value : explode( ',', strval( $value ) );
        $result = array();
        foreach ( $values as $entry ) {
            $int = intval( $entry );
            if ( $int > 0 ) {
                $result[] = $int;
            }
        }
        return array_values( array_unique( $result ) );
    }

    private function get_trigger_definitions(): array {
        $definitions = array(
            array(
                'event'        => 'fc.immediate',
                'label'        => __( 'On page load', 'fooconvert' ),
                'help'         => __( 'Open immediately on page load.', 'fooconvert' ),
                'supportsOnce' => true,
                'defaultOnce'  => true,
                'legacyType'   => 'immediate',
                'fields'       => array()
            ),
            array(
                'event'        => 'fc.anchor.click',
                'label'        => __( 'On anchor click', 'fooconvert' ),
                'help'         => __( 'Open when an anchor is clicked.', 'fooconvert' ),
                'supportsOnce' => false,
                'defaultOnce'  => false,
                'legacyType'   => 'anchor',
                'fields'       => array(
                    array(
                        'path'    => 'ids',
                        'type'    => 'string-list',
                        'label'   => __( 'Anchor', 'fooconvert' ),
                        'help'    => __( 'Comma-separated anchor IDs.', 'fooconvert' ),
                        'default' => array()
                    )
                )
            ),
            array(
                'event'        => 'fc.element.visible',
                'label'        => __( 'On anchor visible', 'fooconvert' ),
                'help'         => __( 'Open when an anchor becomes visible within the window.', 'fooconvert' ),
                'supportsOnce' => true,
                'defaultOnce'  => true,
                'legacyType'   => 'visible',
                'fields'       => array(
                    array(
                        'path'    => 'ids',
                        'type'    => 'string-list',
                        'label'   => __( 'Anchor', 'fooconvert' ),
                        'help'    => __( 'Comma-separated anchor IDs.', 'fooconvert' ),
                        'default' => array()
                    )
                )
            ),
            array(
                'event'        => 'fc.element.click',
                'label'        => __( 'On element click', 'fooconvert' ),
                'help'         => __( 'Open when an element is clicked.', 'fooconvert' ),
                'supportsOnce' => false,
                'defaultOnce'  => false,
                'legacyType'   => 'element',
                'fields'       => array(
                    array(
                        'path'    => 'selector',
                        'type'    => 'text',
                        'label'   => __( 'Selector', 'fooconvert' ),
                        'help'    => __( 'A CSS selector that specifies the element(s) to target.', 'fooconvert' ),
                        'default' => ''
                    )
                )
            ),
            array(
                'event'        => 'fc.exit_intent',
                'label'        => __( 'On exit intent', 'fooconvert' ),
                'help'         => __( 'Open when the mouse exits the top of the window.', 'fooconvert' ),
                'supportsOnce' => true,
                'defaultOnce'  => true,
                'legacyType'   => 'exit-intent',
                'fields'       => array(
                    array(
                        'path'    => 'delaySeconds',
                        'type'    => 'range',
                        'label'   => __( 'Wait time in seconds', 'fooconvert' ),
                        'help'    => __( 'Only detect after the user has been on the page for the specified time.', 'fooconvert' ),
                        'default' => 15,
                        'min'     => 0,
                        'max'     => 100
                    )
                )
            ),
            array(
                'event'        => 'fc.scroll.percent',
                'label'        => __( 'On page scroll', 'fooconvert' ),
                'help'         => __( 'Open after the page has been scrolled.', 'fooconvert' ),
                'supportsOnce' => true,
                'defaultOnce'  => true,
                'legacyType'   => 'scroll',
                'fields'       => array(
                    array(
                        'path'    => 'percent',
                        'type'    => 'range',
                        'label'   => __( 'Scroll percent', 'fooconvert' ),
                        'help'    => __( 'The percentage of the page to scroll before opening.', 'fooconvert' ),
                        'default' => 20,
                        'min'     => 1,
                        'max'     => 100
                    )
                )
            ),
            array(
                'event'        => 'fc.timer.elapsed',
                'label'        => __( 'On timer elapsed', 'fooconvert' ),
                'help'         => __( 'Open after a specified amount of time.', 'fooconvert' ),
                'supportsOnce' => true,
                'defaultOnce'  => true,
                'legacyType'   => 'timer',
                'fields'       => array(
                    array(
                        'path'    => 'seconds',
                        'type'    => 'range',
                        'label'   => __( 'Wait time in seconds', 'fooconvert' ),
                        'help'    => __( 'The amount of time to wait before opening.', 'fooconvert' ),
                        'default' => 15,
                        'min'     => 0,
                        'max'     => 100
                    )
                )
            )
        );

        $definitions = apply_filters( 'fooconvert_open_trigger_definitions', $definitions );
        return is_array( $definitions ) ? array_values( $definitions ) : array();
    }

    private function get_trigger_definition_by_event( string $event ): array {
        foreach ( $this->get_trigger_definitions() as $definition ) {
            if ( isset( $definition['event'] ) && $definition['event'] === $event ) {
                return $definition;
            }
        }
        return array();
    }

    private function get_trigger_definition_by_legacy_type( string $legacy_type ): array {
        foreach ( $this->get_trigger_definitions() as $definition ) {
            if ( isset( $definition['legacyType'] ) && $definition['legacyType'] === $legacy_type ) {
                return $definition;
            }
        }
        return array();
    }

    private function sanitize_field_value( array $field, $value ) {
        $type = isset( $field['type'] ) ? strval( $field['type'] ) : '';
        $default = $field['default'] ?? null;

        switch ( $type ) {
            case 'string-list':
                return $this->normalize_string_array( $value );
            case 'int-list':
                return $this->normalize_int_array( $value );
            case 'text':
                return Utils::is_string( $value ) ? strval( $value ) : strval( $default ?? '' );
            case 'select':
                $options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
                $allowed = array();
                foreach ( $options as $option ) {
                    if ( is_array( $option ) && isset( $option['value'] ) ) {
                        $allowed[] = strval( $option['value'] );
                    }
                }
                $normalized = strval( $value ?? '' );
                if ( in_array( $normalized, $allowed, true ) ) {
                    return $normalized;
                }
                return strval( $default ?? '' );
            case 'range':
                $min = isset( $field['min'] ) && is_numeric( $field['min'] ) ? floatval( $field['min'] ) : null;
                $max = isset( $field['max'] ) && is_numeric( $field['max'] ) ? floatval( $field['max'] ) : null;
                $fallback = is_numeric( $default ) ? floatval( $default ) : 0;
                $number = is_numeric( $value ) ? floatval( $value ) : $fallback;
                if ( !is_null( $min ) && $number < $min ) {
                    $number = $fallback;
                }
                if ( !is_null( $max ) && $number > $max ) {
                    $number = $fallback;
                }
                return floor( $number ) === $number ? intval( $number ) : $number;
        }

        return $default;
    }

    private function get_nested_value( array $array, string $path ) {
        $segments = explode( '.', $path );
        $value = $array;

        foreach ( $segments as $segment ) {
            if ( !is_array( $value ) || !array_key_exists( $segment, $value ) ) {
                return null;
            }
            $value = $value[ $segment ];
        }

        return $value;
    }

    private function set_nested_value( array $array, string $path, $value ): array {
        $segments = explode( '.', $path );
        $pointer = &$array;

        foreach ( $segments as $index => $segment ) {
            if ( $index === count( $segments ) - 1 ) {
                $pointer[ $segment ] = $value;
                break;
            }

            if ( !isset( $pointer[ $segment ] ) || !is_array( $pointer[ $segment ] ) ) {
                $pointer[ $segment ] = array();
            }

            $pointer = &$pointer[ $segment ];
        }

        return $array;
    }
}
