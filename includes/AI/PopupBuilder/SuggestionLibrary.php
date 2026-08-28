<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

defined( 'ABSPATH' ) || exit;

class SuggestionLibrary {

    /**
     * Returns prompt suggestions for the AI popup builder.
     *
     * @param string $edition Current builder edition.
     * @return array<int,array<string,mixed>>
     */
    public static function get( string $edition = 'free' ): array {
        /**
         * Filters the prompt suggestions shown in the AI popup builder.
         *
         * @param array<int,array<string,mixed>> $suggestions Suggestion definitions.
         * @param string                         $edition Current builder edition.
         */
        $suggestions = apply_filters( 'fooconvert_ai_popup_builder_suggestion_library', self::get_default(), $edition );

        return self::normalize( $suggestions );
    }

    /**
     * Returns the default prompt suggestion definitions for FooConvert Free.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function get_default(): array {
        $library = require __DIR__ . '/default-suggestion-library.php';

        return is_array( $library ) ? $library : array();
    }

    /**
     * Normalizes suggestion definitions before exposing them to JavaScript.
     *
     * @param mixed $suggestions Raw suggestion definitions.
     * @return array<int,array<string,mixed>>
     */
    public static function normalize( $suggestions ): array {
        if ( ! is_array( $suggestions ) ) {
            return array();
        }

        $normalized = array();
        foreach ( $suggestions as $suggestion ) {
            if ( is_scalar( $suggestion ) ) {
                $text = trim( (string) $suggestion );
                if ( '' !== $text ) {
                    $normalized[] = array(
                        'text'  => $text,
                        'tags'  => array( 'Create' ),
                        'phase' => 'initial',
                    );
                }

                continue;
            }

            if ( ! is_array( $suggestion ) || ! isset( $suggestion['text'] ) || ! is_scalar( $suggestion['text'] ) ) {
                continue;
            }

            $text = trim( (string) $suggestion['text'] );
            if ( '' === $text ) {
                continue;
            }

            $item = array(
                'text'  => $text,
                'tags'  => self::normalize_string_list( $suggestion['tags'] ?? array() ),
                'phase' => 'edit' === ( $suggestion['phase'] ?? '' ) ? 'edit' : 'initial',
            );

            foreach ( array( 'popupTypes', 'excludePopupTypes', 'requiredBlocks' ) as $list_key ) {
                $values = self::normalize_string_list( $suggestion[ $list_key ] ?? array() );
                if ( ! empty( $values ) ) {
                    $item[ $list_key ] = $values;
                }
            }

            if ( ! empty( $suggestion['requiresImageGeneration'] ) ) {
                $item['requiresImageGeneration'] = true;
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    /**
     * Normalizes a scalar array to unique, non-empty strings.
     *
     * @param mixed $values Raw values.
     * @return array<int,string>
     */
    public static function normalize_string_list( $values ): array {
        if ( ! is_array( $values ) ) {
            return array();
        }

        $normalized = array();
        foreach ( $values as $value ) {
            if ( ! is_scalar( $value ) ) {
                continue;
            }

            $text = trim( (string) $value );
            if ( '' !== $text ) {
                $normalized[] = $text;
            }
        }

        return array_values( array_unique( $normalized ) );
    }
}
