<?php

namespace FooPlugins\FooConvert\Consent;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( __NAMESPACE__ . '\Consent' ) ) {

    /**
     * Class Consent.
     *
     * Domain model for writing authoritative proof-of-consent records to
     * the dedicated `fooconvert_consent_log` table. Kept deliberately small
     * and dependency-free: input validation / sanitisation / hashing lives
     * here, storage goes through `QueryConsent`, and wiring into the core
     * event pipeline is handled by `Init`.
     */
    class Consent {

        /**
         * FooFields settings container id. Produces the option name
         * `fooconvert-cookie-consent-settings` via the SettingsPage
         * convention (`{settings_id}-settings`).
         */
        const SETTINGS_ID = 'fooconvert-cookie-consent';

        /**
         * Default cookie name used to store the per-visitor consent state
         * on the client. The module is responsible for reading/writing
         * this cookie; core does not know about it.
         */
        const COOKIE_NAME = 'fc_consent';

        /**
         * Default retention window (days) for proof-of-consent records.
         * Longer than the core event retention because a consent record
         * is a legal artefact, not an analytics row.
         */
        const RETENTION_DEFAULT_DAYS = 365;

        /**
         * Event type identifiers used on the `fooconvert_log_event` hook.
         */
        const EVENT_TYPE_GRANT = 'consent_grant';
        const EVENT_TYPE_WITHDRAW = 'consent_withdraw';

        /**
         * Category keys we know about today. Extension is allowed via
         * `fooconvert_consent_categories`; this list only governs the
         * compact serialization used on the wire and in the DB.
         */
        const KNOWN_CATEGORIES = array( 'necessary', 'preferences', 'statistics', 'marketing' );

        /**
         * Returns the stored settings array for this module.
         *
         * @return array<string, mixed>
         */
        public static function get_settings(): array {
            $raw = get_option( self::SETTINGS_ID . '-settings' );

            return is_array( $raw ) ? $raw : array();
        }

        /**
         * Default category labels/descriptions, written to be usable
         * verbatim in the banner — a fresh install renders compliant
         * copy without any admin editing.
         *
         * Admin overrides from the Categories tab are applied on top of
         * these defaults via `get_category_copy()` below.
         *
         * @return array<string, array{label:string, description:string}>
         */
        public static function get_category_defaults(): array {
            return array(
                'necessary'   => array(
                    'label'       => __( 'Necessary', 'fooconvert' ),
                    'description' => __( 'Required for the site to work — these cookies do not track you for advertising or analytics, and cannot be disabled.', 'fooconvert' ),
                ),
                'preferences' => array(
                    'label'       => __( 'Preferences', 'fooconvert' ),
                    'description' => __( 'Remember choices you make (language, region, display options) so you don\'t have to set them again on every visit.', 'fooconvert' ),
                ),
                'statistics'  => array(
                    'label'       => __( 'Statistics', 'fooconvert' ),
                    'description' => __( 'Help us understand how visitors use the site — which pages are popular and where people get stuck — in aggregate.', 'fooconvert' ),
                ),
                'marketing'   => array(
                    'label'       => __( 'Marketing', 'fooconvert' ),
                    'description' => __( 'Used by us or our partners to show you relevant ads on this site and on other sites you visit.', 'fooconvert' ),
                ),
            );
        }

        /**
         * Returns the effective label + description for each known category:
         * the default, overridden by any admin customisation stored on the
         * Categories tab.
         *
         * @return array<string, array{label:string, description:string}>
         */
        public static function get_category_copy(): array {
            $defaults = self::get_category_defaults();
            $settings = self::get_settings();
            $out = array();

            foreach ( self::KNOWN_CATEGORIES as $key ) {
                $default = $defaults[ $key ] ?? array( 'label' => ucfirst( $key ), 'description' => '' );
                $label_override = $settings[ 'category_' . $key . '_label' ] ?? null;
                $desc_override = $settings[ 'category_' . $key . '_description' ] ?? null;

                $out[ $key ] = array(
                    'label'       => is_string( $label_override ) && $label_override !== '' ? $label_override : $default['label'],
                    'description' => is_string( $desc_override ) && $desc_override !== '' ? $desc_override : $default['description'],
                );
            }

            return $out;
        }

        /**
         * Returns a single setting value, or the supplied default when
         * the key is missing.
         *
         * @param string $key     Setting key.
         * @param mixed  $default Fallback when the key isn't stored yet.
         * @return mixed
         */
        public static function get_setting( string $key, $default = null ) {
            $settings = self::get_settings();

            return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
        }

        /**
         * Returns true when the module is enabled in settings.
         */
        public static function is_enabled(): bool {
            return (bool) self::get_setting( 'enabled', false );
        }

        /**
         * Records a consent decision.
         *
         * Expected input shape (all optional unless marked required):
         *   consent_id  (string, required) — per-visitor UUID; echoed in the `fc_consent` cookie.
         *   event_type  (string, required) — 'consent_grant' | 'consent_withdraw'.
         *   categories  (array<string,bool>, required) — e.g. ['statistics' => true, 'marketing' => false].
         *   version     (int)                         — banner/config version at capture time.
         *   page_url    (string)                      — page the decision was made on.
         *   user_agent  (string)                      — raw UA; hashed before storage.
         *   ip          (string)                      — raw IP; truncated + hashed before storage.
         *   source      (string)                      — 'banner' | 'settings' | 'withdraw'.
         *
         * @param array<string, mixed> $data Input payload; see above.
         * @return int|\WP_Error Row id or WP_Error.
         */
        public function record( array $data ) {
            if ( empty( $data['consent_id'] ) || !is_string( $data['consent_id'] ) ) {
                return new \WP_Error( 'missing_required_fields', 'consent_id is required' );
            }

            $event_type = isset( $data['event_type'] ) ? (string) $data['event_type'] : '';
            if ( $event_type !== self::EVENT_TYPE_GRANT && $event_type !== self::EVENT_TYPE_WITHDRAW ) {
                return new \WP_Error( 'invalid_event_type', 'event_type must be consent_grant or consent_withdraw' );
            }

            if ( !isset( $data['categories'] ) || !is_array( $data['categories'] ) ) {
                return new \WP_Error( 'missing_required_fields', 'categories map is required' );
            }

            $consent_id = $this->sanitize_consent_id( $data['consent_id'] );
            if ( $consent_id === '' ) {
                return new \WP_Error( 'invalid_consent_id', 'consent_id is not a valid UUID' );
            }

            $categories = $this->serialize_categories( $data['categories'] );

            $row = array(
                'consent_id'      => $consent_id,
                'event_type'      => $event_type,
                'version'         => isset( $data['version'] ) ? max( 1, (int) $data['version'] ) : 1,
                'categories'      => $categories,
                'page_url'        => !empty( $data['page_url'] ) ? $this->clean_page_url( (string) $data['page_url'] ) : null,
                'user_agent_hash' => !empty( $data['user_agent'] ) ? $this->hash_user_agent( (string) $data['user_agent'] ) : null,
                'ip_hash'         => !empty( $data['ip'] ) ? $this->hash_ip( (string) $data['ip'] ) : null,
                'user_id'         => is_user_logged_in() ? get_current_user_id() : null,
                'source'          => !empty( $data['source'] ) ? $this->sanitize_source( (string) $data['source'] ) : null,
            );

            /**
             * Filter the consent row just before insert. Use this to add a
             * custom field or to redact more aggressively than the default.
             *
             * @param array<string, mixed> $row   The row about to be inserted.
             * @param array<string, mixed> $input The raw input payload.
             */
            $row = apply_filters( 'fooconvert_consent_data', $row, $data );

            if ( !is_array( $row ) || empty( $row ) ) {
                return 0;
            }

            $id = QueryConsent::insert_consent_data( $row );

            if ( is_int( $id ) && $id > 0 ) {
                $row['id'] = $id;

                /**
                 * Fires after a proof-of-consent row has been written.
                 *
                 * @param array<string, mixed> $row The stored row, including `id`.
                 */
                do_action( 'fooconvert_consent_recorded', $row );

                return $id;
            }

            return $id; // WP_Error from QueryConsent passes through unchanged.
        }

        /**
         * Returns the latest stored consent state for a given consent_id.
         *
         * @param string $consent_id
         * @return array<string, mixed>|null
         */
        public function get_latest( string $consent_id ) {
            $consent_id = $this->sanitize_consent_id( $consent_id );
            if ( $consent_id === '' ) {
                return null;
            }

            return QueryConsent::get_latest_for_consent_id( $consent_id );
        }

        /**
         * Returns the full consent history for a given consent_id, newest first.
         *
         * @param string $consent_id
         * @return array<int, array<string, mixed>>
         */
        public function get_history( string $consent_id ): array {
            $consent_id = $this->sanitize_consent_id( $consent_id );
            if ( $consent_id === '' ) {
                return array();
            }

            return QueryConsent::get_history_for_consent_id( $consent_id );
        }

        /**
         * Returns the consent log table stats.
         *
         * @return array<string, mixed>
         */
        public function get_consent_log_table_stats(): array {
            return QueryConsent::get_consent_log_table_stats();
        }

        /**
         * Deletes all consent records. Destructive.
         *
         * @return int|false
         */
        public function delete_all() {
            return QueryConsent::delete_all_consent_records();
        }

        /**
         * Prunes consent records older than the configured retention window.
         *
         * @return int|false
         */
        public function delete_old() {
            $days = apply_filters( 'fooconvert_consent_log_retention_days', self::RETENTION_DEFAULT_DAYS );

            return QueryConsent::delete_old_consent_records( (int) $days );
        }

        /**
         * Compact serializer for the categories map. Produces e.g.
         * "necessary=1,preferences=0,statistics=1,marketing=0" so the payload
         * is small, greppable, and trivial to parse in either direction.
         *
         * Unknown keys are preserved (extensibility) but normalised to bool.
         *
         * @param array<string, mixed> $categories
         * @return string
         */
        public function serialize_categories( array $categories ): string {
            $known = apply_filters( 'fooconvert_consent_categories', self::KNOWN_CATEGORIES );
            $parts = array();

            // Necessary is always granted; callers can't opt out.
            $categories['necessary'] = true;

            foreach ( $known as $key ) {
                $key = sanitize_key( (string) $key );
                if ( $key === '' ) continue;
                $parts[ $key ] = array_key_exists( $key, $categories ) && !empty( $categories[ $key ] ) ? 1 : 0;
            }

            // Preserve any caller-supplied extras we didn't know about.
            foreach ( $categories as $key => $value ) {
                $key = sanitize_key( (string) $key );
                if ( $key === '' || isset( $parts[ $key ] ) ) continue;
                $parts[ $key ] = !empty( $value ) ? 1 : 0;
            }

            $pairs = array();
            foreach ( $parts as $key => $value ) {
                $pairs[] = $key . '=' . $value;
            }

            return implode( ',', $pairs );
        }

        /**
         * Parses the compact serialized categories string into a map.
         *
         * @param string $serialized
         * @return array<string, bool>
         */
        public function parse_categories( string $serialized ): array {
            $out = array();
            foreach ( explode( ',', $serialized ) as $pair ) {
                $pair = trim( $pair );
                if ( $pair === '' ) continue;

                $eq = strpos( $pair, '=' );
                if ( $eq === false ) continue;

                $key = sanitize_key( substr( $pair, 0, $eq ) );
                if ( $key === '' ) continue;

                $out[ $key ] = substr( $pair, $eq + 1 ) === '1';
            }

            return $out;
        }

        /**
         * Accepts either a canonical UUID or any 16–64 char hex/dash string
         * and returns a safe lowercase representation. Returns '' if the
         * input is unusable.
         */
        private function sanitize_consent_id( string $raw ): string {
            $raw = strtolower( trim( $raw ) );

            if ( preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $raw ) ) {
                return $raw;
            }

            if ( preg_match( '/^[0-9a-f-]{16,36}$/', $raw ) ) {
                return $raw;
            }

            return '';
        }

        private function sanitize_source( string $raw ): ?string {
            $raw = sanitize_key( $raw );
            $allowed = array( 'banner', 'settings', 'withdraw' );

            return in_array( $raw, $allowed, true ) ? $raw : null;
        }

        /**
         * Hashes a user agent with the site auth salt. Deterministic so we
         * can recognise "same client" across records, salted so a dump of
         * the table on its own doesn't identify anyone.
         */
        private function hash_user_agent( string $user_agent ): string {
            return hash( 'sha256', $user_agent . '|' . wp_salt( 'auth' ) );
        }

        /**
         * Truncates the IP to a /24 (IPv4) or /64 (IPv6) before hashing,
         * so the record is proof-of-consent-grade without being a raw
         * identifier. Raw IPs never hit the DB.
         */
        private function hash_ip( string $ip ): string {
            $truncated = $this->truncate_ip( $ip );

            return hash( 'sha256', $truncated . '|' . wp_salt( 'auth' ) );
        }

        private function truncate_ip( string $ip ): string {
            $ip = trim( $ip );

            if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
                $parts = explode( '.', $ip );
                $parts[3] = '0';
                return implode( '.', $parts );
            }

            if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
                $packed = @inet_pton( $ip );
                if ( $packed !== false && strlen( $packed ) === 16 ) {
                    $masked = substr( $packed, 0, 8 ) . str_repeat( "\0", 8 );
                    $out = @inet_ntop( $masked );
                    if ( is_string( $out ) ) {
                        return $out;
                    }
                }
            }

            return $ip;
        }

        /**
         * Strips the site origin from the URL the same way `Event` and `Lead` do.
         */
        private function clean_page_url( string $page_url ): string {
            $home_url = home_url();

            if ( strpos( $page_url, $home_url ) === 0 ) {
                return '/' . ltrim( substr( $page_url, strlen( $home_url ) ), '/' );
            }

            return $page_url;
        }
    }
}
