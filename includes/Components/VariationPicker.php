<?php

namespace FooPlugins\FooConvert\Components;

/**
 * Normalizes template picker metadata for editor variations.
 */
class VariationPicker {

    /**
     * Returns the registered picker categories.
     *
     * @return array<string,array<string,string>>
     */
    public function get_categories(): array {
        $categories = array(
            'blank'        => array(
                'label' => __( 'Start from scratch', 'fooconvert' ),
            ),
            'commerce'     => array(
                'label' => __( 'Commerce', 'fooconvert' ),
            ),
            'lead-capture' => array(
                'label' => __( 'Lead capture', 'fooconvert' ),
            ),
            'video'        => array(
                'label' => __( 'Video', 'fooconvert' ),
            ),
            'compliance'   => array(
                'label' => __( 'Compliance', 'fooconvert' ),
            ),
            'promotion'    => array(
                'label' => __( 'Promotion', 'fooconvert' ),
            ),
        );

        return apply_filters( 'fooconvert_variation_picker_categories', $categories );
    }

    /**
     * Returns the registered picker tags.
     *
     * @return array<string,array<string,string>>
     */
    public function get_tags(): array {
        $tags = array(
            'blank'       => array(
                'label' => __( 'Blank', 'fooconvert' ),
            ),
            'countdown'   => array(
                'label' => __( 'Countdown', 'fooconvert' ),
            ),
            'cookie'      => array(
                'label' => __( 'Cookie', 'fooconvert' ),
            ),
            'download'    => array(
                'label' => __( 'Download', 'fooconvert' ),
            ),
            'email'       => array(
                'label' => __( 'Email', 'fooconvert' ),
            ),
            'ecommerce'   => array(
                'label' => __( 'Ecommerce', 'fooconvert' ),
            ),
            'exit-intent' => array(
                'label' => __( 'Exit intent', 'fooconvert' ),
            ),
            'newsletter'  => array(
                'label' => __( 'Newsletter', 'fooconvert' ),
            ),
            'offer'       => array(
                'label' => __( 'Offer', 'fooconvert' ),
            ),
            'seasonal'    => array(
                'label' => __( 'Seasonal', 'fooconvert' ),
            ),
            'video'       => array(
                'label' => __( 'Video', 'fooconvert' ),
            ),
        );

        return apply_filters( 'fooconvert_variation_picker_tags', $tags );
    }

    /**
     * Normalizes picker data across all variations.
     *
     * @param array<int,array<string,mixed>> $variations Existing block variations.
     * @return array<int,array<string,mixed>>
     */
    public function prepare_variations( array $variations ): array {
        return array_map( array( $this, 'prepare_variation' ), $variations );
    }

    /**
     * Normalizes picker data for a single variation.
     *
     * @param array<string,mixed> $variation Variation config.
     * @return array<string,mixed>
     */
    public function prepare_variation( array $variation ): array {
        if ( ! isset( $variation['picker'] ) || ! is_array( $variation['picker'] ) ) {
            return $variation;
        }

        $variation['picker'] = $this->normalize_picker( $variation['picker'] );

        return $variation;
    }

    /**
     * Normalizes the picker payload.
     *
     * @param array<string,mixed> $picker Raw picker payload.
     * @return array<string,mixed>
     */
    private function normalize_picker( array $picker ): array {
        $category_value = $this->normalize_value( $picker['category'] ?? '' );
        if ( '' !== $category_value ) {
            $picker['category'] = array(
                'value' => $category_value,
                'label' => $this->get_option_label( $this->get_categories(), $category_value ),
            );
        } else {
            unset( $picker['category'] );
        }

        $picker['tags'] = $this->normalize_tags( $picker['tags'] ?? array() );

        $availability = $this->normalize_value( $picker['availability'] ?? '' );
        if ( '' !== $availability ) {
            $picker['availability'] = $availability;
        } else {
            unset( $picker['availability'] );
        }

        $priority = $this->normalize_priority( $picker['priority'] ?? null );
        if ( null !== $priority ) {
            $picker['priority'] = $priority;
        } else {
            unset( $picker['priority'] );
        }

        return $picker;
    }

    /**
     * Normalizes the picker tag list.
     *
     * @param mixed $raw_tags Raw tag payload.
     * @return array<int,array<string,string>>
     */
    private function normalize_tags( $raw_tags ): array {
        if ( ! is_array( $raw_tags ) ) {
            return array();
        }

        $normalized = array();
        foreach ( $raw_tags as $raw_tag ) {
            $value = $this->normalize_value( $raw_tag );
            if ( '' === $value ) {
                continue;
            }

            $normalized[] = array(
                'value' => $value,
                'label' => $this->get_option_label( $this->get_tags(), $value ),
            );
        }

        return $normalized;
    }

    /**
     * Normalizes a picker value.
     *
     * @param mixed $raw_value Raw picker value.
     * @return string
     */
    private function normalize_value( $raw_value ): string {
        if ( is_array( $raw_value ) ) {
            $raw_value = $raw_value['value'] ?? '';
        }

        if ( ! is_string( $raw_value ) ) {
            return '';
        }

        return sanitize_key( $raw_value );
    }

    /**
     * Normalizes a picker priority.
     *
     * @param mixed $raw_priority Raw picker priority.
     * @return ?int
     */
    private function normalize_priority( $raw_priority ): ?int {
        if ( is_numeric( $raw_priority ) ) {
            return intval( $raw_priority );
        }

        return null;
    }

    /**
     * Resolves the label for a picker value.
     *
     * @param array<string,array<string,string>> $options Available picker options.
     * @param string                             $value Option value.
     * @return string
     */
    private function get_option_label( array $options, string $value ): string {
        if ( isset( $options[ $value ]['label'] ) && is_string( $options[ $value ]['label'] ) ) {
            return $options[ $value ]['label'];
        }

        return ucwords( str_replace( '-', ' ', $value ) );
    }
}
