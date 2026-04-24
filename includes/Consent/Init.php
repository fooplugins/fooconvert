<?php

namespace FooPlugins\FooConvert\Consent;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( __NAMESPACE__ . '\Init' ) ) {

    /**
     * Class Init.
     *
     * Boots the Cookie Consent module. The module lives entirely inside this
     * directory: it has its own database table, its own query layer, its own
     * domain model, and it interacts with the core FooConvert plugin only
     * through documented WordPress hooks.
     *
     * Hooks subscribed to (provided by core):
     *  - `fooconvert_create_tables`  → creates the consent log table.
     *  - `fooconvert_log_event`      → records proof-of-consent rows when the
     *                                  core AJAX endpoint dispatches a
     *                                  consent event.
     *
     * Hooks provided to extensions (fired from this module):
     *  - `fooconvert_consent_data`          filter, row just before insert.
     *  - `fooconvert_consent_categories`    filter, known category keys.
     *  - `fooconvert_consent_recorded`      action, after a successful insert.
     *  - `fooconvert_consent_log_retention_days` filter, retention window.
     */
    class Init {
        public function __construct() {
            add_action( 'fooconvert_create_tables', array( $this, 'on_create_tables' ), 10, 1 );
            add_action( 'fooconvert_log_event', array( $this, 'on_log_event' ), 10, 3 );
        }

        /**
         * Create or upgrade the consent log table alongside the core tables.
         *
         * @param string $current_version Target plugin version from core.
         * @return void
         */
        public function on_create_tables( $current_version ): void {
            Schema::create_consent_log_table_and_indexes();
        }

        /**
         * React to a logged frontend event. If it's a consent decision,
         * persist the authoritative proof-of-consent row.
         *
         * @param string $event_type Normalised event type (e.g. `consent_grant`).
         * @param array  $data       Event data as passed to `Event::create()`.
         * @param array  $meta       Event meta.
         * @return void
         */
        public function on_log_event( $event_type, $data, $meta ): void {
            if ( $event_type !== Consent::EVENT_TYPE_GRANT && $event_type !== Consent::EVENT_TYPE_WITHDRAW ) {
                return;
            }

            $extra_data = isset( $data['extra_data'] ) && is_array( $data['extra_data'] ) ? $data['extra_data'] : array();

            if ( empty( $extra_data['consentId'] ) || !isset( $extra_data['categories'] ) || !is_array( $extra_data['categories'] ) ) {
                return;
            }

            $consent = new Consent();

            $consent->record( array(
                'consent_id' => (string) $extra_data['consentId'],
                'event_type' => $event_type,
                'categories' => $extra_data['categories'],
                'version'    => isset( $extra_data['version'] ) ? (int) $extra_data['version'] : 1,
                'page_url'   => isset( $data['page_url'] ) ? (string) $data['page_url'] : null,
                'source'     => isset( $extra_data['source'] ) ? (string) $extra_data['source'] : 'banner',
                'ip'         => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
            ) );
        }
    }
}
