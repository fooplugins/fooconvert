<?php

namespace FooPlugins\FooConvert;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( __NAMESPACE__ . '\Consent' ) ) {

    /**
     * Class Consent.
     *
     * Writes authoritative proof-of-consent records to the dedicated
     * `fooconvert_consent_log` table. Follows the same shape as `Lead`:
     * the frontend fires a `fooconvert_log_event` AJAX call with
     * `eventType` set to `consent_grant` or `consent_withdraw`, and
     * `Ajax::handle_log_event` routes the payload here in addition to
     * writing the banner's engagement row to `fooconvert_events`.
     */
    class Consent {

        /**
         * Category keys we know about today. Extension is allowed via
         * `fooconvert_consent_categories`; this list only governs the
         * compact serialization used on the wire and in the DB.
         */
        const KNOWN_CATEGORIES = array( 'necessary', 'preferences', 'statistics', 'marketing' );

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
            if ( $event_type !== FOOCONVERT_EVENT_TYPE_CONSENT_GRANT && $event_type !== FOOCONVERT_EVENT_TYPE_CONSENT_WITHDRAW ) {
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

            $id = Data\QueryConsent::insert_consent_data( $row );

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

            return Data\QueryConsent::get_latest_for_consent_id( $consent_id );
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

            return Data\QueryConsent::get_history_for_consent_id( $consent_id );
        }

        /**
         * Returns the consent log table stats.
         *
         * @return array<string, mixed>
         */
        public function get_consent_log_table_stats(): array {
            return Data\QueryConsent::get_consent_log_table_stats();
        }

        /**
         * Deletes all consent records. Destructive.
         *
         * @return int|false
         */
        public function delete_all() {
            return Data\QueryConsent::delete_all_consent_records();
        }

        /**
         * Prunes consent records older than the configured retention window.
         *
         * @return int|false
         */
        public function delete_old() {
            $days = apply_filters( 'fooconvert_consent_log_retention_days', FOOCONVERT_CONSENT_LOG_RETENTION_DEFAULT );

            return Data\QueryConsent::delete_old_consent_records( (int) $days );
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
         * Accepts either a canonical UUID or any 16–64 char slug-ish string
         * and returns a safe lowercase representation. Returns '' if the
         * input is unusable.
         */
        private function sanitize_consent_id( string $raw ): string {
            $raw = strtolower( trim( $raw ) );

            // Canonical UUID v4-ish.
            if ( preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $raw ) ) {
                return $raw;
            }

            // Allow a relaxed hex/dash id so the frontend can use any RFC-4122 variant.
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
                    // Zero the lower 64 bits.
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
