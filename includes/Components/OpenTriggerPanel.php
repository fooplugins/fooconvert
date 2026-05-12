<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes popup open-trigger settings for editor and frontend consumers.
 */
class OpenTriggerPanel extends BaseComponent {
    /**
     * Schema version used for normalized trigger payloads.
     *
     * @var int
     */
    private const V2_VERSION = 2;

    /**
     * Supported trigger lifetime modes.
     *
     * @var string[]
     */
    private const LIFETIMES = array( 'page', 'session', 'visit' );

    /**
     * Supported trigger frequency modes.
     *
     * @var string[]
     */
    private const FREQUENCY_MODES = array( 'once', 'repeat' );

    /**
     * Returns the JavaScript settings key for this component.
     *
     * @return string
     */
    function get_component_data_name(): string {
        return 'FC_OPEN_TRIGGER';
    }

    /**
     * Returns the editor data required to configure trigger controls.
     *
     * @return array<string,mixed>
     */
    function get_component_data(): array {
        return array(
            'triggers' => $this->get_trigger_definitions()
        );
    }

    /**
     * Returns HTML attributes for the trigger panel component.
     *
     * @param array $attr_value Component attributes.
     * @return array<string,mixed>
     */
    function get_attributes( array $attr_value ): array {
        return array();
    }

    /**
     * Returns normalized frontend data for the trigger configuration.
     *
     * @param array $attr_value Component attributes.
     * @return array<string,mixed>
     */
    function get_data( array $attr_value ): array {
        $data = array();
        $trigger = $this->normalize_trigger( $attr_value );
        if ( !empty( $trigger ) ) {
            $data['triggerConfig'] = $trigger;
        }
        return $data;
    }

    /**
     * Normalizes a trigger payload from either legacy or V2 formats.
     *
     * @param array $attr_value Raw trigger configuration.
     * @return array<string,mixed>
     */
    private function normalize_trigger( array $attr_value ): array {
        $version = Utils::get_int( $attr_value, 'version' );
        if ( $version === self::V2_VERSION ) {
            return $this->normalize_v2_trigger( $attr_value );
        }
        return $this->normalize_legacy_trigger( $attr_value );
    }

    /**
     * Normalizes a V2 trigger configuration payload.
     *
     * @param array $trigger Raw trigger configuration.
     * @return array<string,mixed>
     */
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

        $first_event = isset( $normalized_steps[0]['event'] ) ? strval( $normalized_steps[0]['event'] ) : '';
        $definition = !empty( $first_event ) ? $this->get_trigger_definition_by_event( $first_event ) : array();

        $lifetime = Utils::get_string( $trigger, 'lifetime', 'page' );
        if ( !in_array( $lifetime, self::LIFETIMES, true ) ) {
            $lifetime = 'page';
        }

        $frequency = Utils::get_array( $trigger, 'frequency' );
        $frequency_mode = Utils::get_string( $frequency, 'mode', $this->get_default_frequency_mode( $definition ) );
        if ( !in_array( $frequency_mode, self::FREQUENCY_MODES, true ) ) {
            $frequency_mode = $this->get_default_frequency_mode( $definition );
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

    /**
     * Normalizes a legacy trigger payload into the V2 structure.
     *
     * @param array $trigger Raw legacy trigger configuration.
     * @return array<string,mixed>
     */
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

    /**
     * Returns the default frequency mode for a trigger definition.
     *
     * @param array $definition Trigger definition metadata.
     * @return string
     */
    private function get_default_frequency_mode( array $definition ): string {
        return !empty( $definition['defaultOnce'] ) ? 'once' : 'repeat';
    }

    /**
     * Normalizes a single trigger step.
     *
     * @param array $step Raw step definition.
     * @return array<string,mixed>
     */
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

    /**
     * Normalizes the `where` clause for a trigger step.
     *
     * @param array $definition Trigger definition metadata.
     * @param array $where Raw `where` values.
     * @return array<string,mixed>
     */
    private function normalize_where( array $definition, array $where ): array {
        $normalized = array();
        $fields = $this->get_leaf_fields(
            isset( $definition['fields'] ) && is_array( $definition['fields'] ) ? $definition['fields'] : array()
        );

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

    /**
     * Flattens nested section fields into a list of input fields.
     *
     * @param array $fields Trigger field definitions.
     * @return array<int,array<string,mixed>>
     */
    private function get_leaf_fields( array $fields ): array {
        $result = array();

        foreach ( $fields as $field ) {
            if ( !is_array( $field ) ) {
                continue;
            }

            $type = isset( $field['type'] ) ? strval( $field['type'] ) : '';
            if ( $type === 'section' ) {
                $children = isset( $field['fields'] ) && is_array( $field['fields'] ) ? $field['fields'] : array();
                $result = array_merge( $result, $this->get_leaf_fields( $children ) );
                continue;
            }
            if ( $type === 'rules' ) {
                $groups = isset( $field['groups'] ) && is_array( $field['groups'] ) ? $field['groups'] : array();
                foreach ( $groups as $group ) {
                    if ( is_array( $group ) ) {
                        $children = isset( $group['fields'] ) && is_array( $group['fields'] ) ? $group['fields'] : array();
                        $result   = array_merge( $result, $this->get_leaf_fields( $children ) );
                    }
                }
                continue;
            }

            $result[] = $field;
        }

        return $result;
    }

    /**
     * Converts a mixed value into a unique list of strings.
     *
     * @param mixed $value Raw string or list value.
     * @return string[]
     */
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

    /**
     * Converts a mixed value into a unique list of positive integers.
     *
     * @param mixed $value Raw numeric or list value.
     * @return int[]
     */
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

    /**
     * Returns the trigger definitions available to the editor.
     *
     * @return array<int,array<string,mixed>>
     */
    private function get_trigger_definitions(): array {
        $definitions = array(
            array(
                'group'        => __( 'General', 'fooconvert' ),
                'event'        => 'fc.immediate',
                'label'        => __( 'On page load', 'fooconvert' ),
                'help'         => __( 'Open immediately on page load.', 'fooconvert' ),
                'supportsOnce' => true,
                'defaultOnce'  => true,
                'legacyType'   => 'immediate',
                'fields'       => array()
            ),
            array(
                'group'        => __( 'Elements', 'fooconvert' ),
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
                'group'        => __( 'Elements', 'fooconvert' ),
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
                'group'        => __( 'Elements', 'fooconvert' ),
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
                'group'        => __( 'General', 'fooconvert' ),
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
                'group'        => __( 'General', 'fooconvert' ),
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
                'group'        => __( 'General', 'fooconvert' ),
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

    /**
     * Looks up a trigger definition by event name.
     *
     * @param string $event Event identifier.
     * @return array<string,mixed>
     */
    private function get_trigger_definition_by_event( string $event ): array {
        foreach ( $this->get_trigger_definitions() as $definition ) {
            if ( isset( $definition['event'] ) && $definition['event'] === $event ) {
                return $definition;
            }
        }
        return array();
    }

    /**
     * Looks up a trigger definition by legacy trigger type.
     *
     * @param string $legacy_type Legacy trigger type.
     * @return array<string,mixed>
     */
    private function get_trigger_definition_by_legacy_type( string $legacy_type ): array {
        foreach ( $this->get_trigger_definitions() as $definition ) {
            if ( isset( $definition['legacyType'] ) && $definition['legacyType'] === $legacy_type ) {
                return $definition;
            }
        }
        return array();
    }

    /**
     * Sanitizes a trigger field value according to its field definition.
     *
     * @param array $field Field definition metadata.
     * @param mixed $value Raw field value.
     * @return mixed
     */
    private function sanitize_field_value( array $field, $value ) {
        $type = isset( $field['type'] ) ? strval( $field['type'] ) : '';
        $default = $field['default'] ?? null;

        switch ( $type ) {
            case 'string-list':
                return $this->normalize_string_array( $value );
            case 'int-list':
                return $this->normalize_int_array( $value );
            case 'entity-record-list':
                return $this->normalize_entity_record_ids( $value );
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

    /**
     * Converts an array of entity-record tokens or IDs into unique positive integers.
     *
     * @param mixed $value Raw entity token or ID list.
     * @return int[]
     */
    private function normalize_entity_record_ids( $value ): array {
        if ( !is_array( $value ) ) {
            return $this->normalize_int_array( $value );
        }

        $result = array();
        foreach ( $value as $entry ) {
            if ( is_array( $entry ) && isset( $entry['id'] ) ) {
                $int = intval( $entry['id'] );
            } else {
                $int = intval( $entry );
            }

            if ( $int > 0 ) {
                $result[] = $int;
            }
        }

        return array_values( array_unique( $result ) );
    }

    /**
     * Reads a nested value from an array using dot notation.
     *
     * @param array  $array Source array.
     * @param string $path Dot-notated path.
     * @return mixed
     */
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

    /**
     * Writes a nested value into an array using dot notation.
     *
     * @param array  $array Source array.
     * @param string $path Dot-notated path.
     * @param mixed  $value Value to assign.
     * @return array
     */
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
