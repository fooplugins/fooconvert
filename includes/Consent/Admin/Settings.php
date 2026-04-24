<?php

namespace FooPlugins\FooConvert\Consent\Admin;

use FooPlugins\FooConvert\Admin\FooFields\SettingsPage;
use FooPlugins\FooConvert\Consent\Consent;
use FooPlugins\FooConvert\Consent\QueryConsent;
use FooPlugins\FooConvert\Consent\Schema;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( __NAMESPACE__ . '\Settings' ) ) {

    /**
     * Class Settings.
     *
     * Cookie Consent settings page. Lives under FooConvert → Cookie Consent.
     *
     * Uses the same FooFields `SettingsPage` base class as core
     * `Admin\Settings`, so the module doesn't have to reimplement the
     * tabs / save / sanitize machinery. Registers its own submenu via
     * the base class's `admin_menu` hook — core admin files are not
     * modified.
     */
    class Settings extends SettingsPage {

        public function __construct() {
            parent::__construct( array(
                'manager'          => FOOCONVERT_SLUG,
                'settings_id'      => Consent::SETTINGS_ID,
                'menu_parent_slug' => FOOCONVERT_MENU_SLUG,
                'layout'           => 'foofields-tabs-horizontal',
            ) );
        }

        public function get_page_title() {
            return __( 'Cookie Consent', 'fooconvert' );
        }

        public function get_menu_title() {
            return __( 'Cookie Consent', 'fooconvert' );
        }

        /**
         * Builds the FooFields tab descriptors.
         *
         * @return array<string, array<string, mixed>>
         */
        public function get_tabs() {
            return apply_filters( 'fooconvert_consent_admin_tabs', array(
                'general'    => $this->build_general_tab(),
                'categories' => $this->build_categories_tab(),
                'log'        => $this->build_log_tab(),
            ) );
        }

        private function build_general_tab(): array {
            return array(
                'id'     => 'general',
                'label'  => __( 'General', 'fooconvert' ),
                'icon'   => 'dashicons-admin-settings',
                'order'  => 10,
                'fields' => array(
                    array(
                        'id'    => 'general_heading',
                        'order' => 5,
                        'type'  => 'heading',
                        'label' => __( 'Banner', 'fooconvert' ),
                        'desc'  => __( 'Controls whether the cookie consent banner is shown to visitors, and how it behaves.', 'fooconvert' ),
                    ),
                    array(
                        'id'      => 'enabled',
                        'order'   => 10,
                        'type'    => 'checkbox',
                        'label'   => __( 'Enable Cookie Consent', 'fooconvert' ),
                        'default' => false,
                        'desc'    => __( 'When enabled, the banner is shown to visitors who have not yet recorded a consent decision.', 'fooconvert' ),
                    ),
                    array(
                        'id'      => 'form_factor',
                        'order'   => 20,
                        'type'    => 'select',
                        'label'   => __( 'Banner Style', 'fooconvert' ),
                        'default' => 'bar',
                        'choices' => array(
                            'bar'     => __( 'Bar (recommended — least intrusive)', 'fooconvert' ),
                            'flyout'  => __( 'Flyout (corner card)', 'fooconvert' ),
                            'overlay' => __( 'Overlay (modal — use sparingly)', 'fooconvert' ),
                        ),
                        'desc'    => __( 'Which popup form factor to use for the banner. A full-screen overlay reads as a dark pattern to many regulators; prefer bar or flyout unless you have a specific reason.', 'fooconvert' ),
                    ),
                    array(
                        'id'      => 'geo_scope',
                        'order'   => 30,
                        'type'    => 'select',
                        'label'   => __( 'Apply To', 'fooconvert' ),
                        'default' => 'worldwide',
                        'choices' => array(
                            'worldwide' => __( 'All visitors (recommended)', 'fooconvert' ),
                            'eu_only'   => __( 'EU/EEA & UK visitors only', 'fooconvert' ),
                        ),
                        'desc'    => __( 'Showing the banner worldwide is the safer default and gives consistent analytics. EU/EEA & UK only requires a working IP-to-country lookup at the server or CDN layer.', 'fooconvert' ),
                    ),
                    array(
                        'id'      => 'expiry_days',
                        'order'   => 40,
                        'type'    => 'number',
                        'label'   => __( 'Consent Expiry (days)', 'fooconvert' ),
                        'default' => 180,
                        'desc'    => __( 'How long a visitor\'s consent decision remains valid before they are re-prompted. EU guidance suggests no longer than 12 months (365 days).', 'fooconvert' ),
                    ),
                    array(
                        'id'      => 'version',
                        'order'   => 50,
                        'type'    => 'number',
                        'label'   => __( 'Banner Version', 'fooconvert' ),
                        'default' => 1,
                        'desc'    => __( 'Increase this number any time you add or materially change a tracking category to force all visitors to re-confirm their consent. The stored proof-of-consent records include this version.', 'fooconvert' ),
                    ),

                    array(
                        'id'    => 'links_heading',
                        'order' => 90,
                        'type'  => 'heading',
                        'label' => __( 'Policy Links', 'fooconvert' ),
                        'desc'  => __( 'Linked from the banner so visitors can read the detail before deciding.', 'fooconvert' ),
                    ),
                    array(
                        'id'      => 'policy_url',
                        'order'   => 100,
                        'type'    => 'text',
                        'label'   => __( 'Cookie Policy URL', 'fooconvert' ),
                        'default' => '',
                        'desc'    => __( 'Link to the page that describes the cookies your site sets, with purpose and retention for each one.', 'fooconvert' ),
                    ),
                    array(
                        'id'      => 'privacy_url',
                        'order'   => 110,
                        'type'    => 'text',
                        'label'   => __( 'Privacy Policy URL', 'fooconvert' ),
                        'default' => '',
                        'desc'    => __( 'Link to the page that describes how you handle personal data more broadly.', 'fooconvert' ),
                    ),

                    array(
                        'id'    => 'behaviour_heading',
                        'order' => 190,
                        'type'  => 'heading',
                        'label' => __( 'Behaviour', 'fooconvert' ),
                        'desc'  => __( 'These defaults are chosen to match EU/UK regulator guidance; changing them may affect whether your configuration is compliant.', 'fooconvert' ),
                    ),
                    array(
                        'id'      => 'reject_on_dismiss',
                        'order'   => 200,
                        'type'    => 'checkbox',
                        'label'   => __( 'Treat Dismiss (✕) as Reject', 'fooconvert' ),
                        'default' => true,
                        'desc'    => __( 'Strongly recommended. Treating the close button as implicit acceptance is not compliant under French CNIL and Italian Garante guidance.', 'fooconvert' ),
                    ),
                    array(
                        'id'      => 'show_floating_button',
                        'order'   => 210,
                        'type'    => 'checkbox',
                        'label'   => __( 'Show Floating "Cookie Settings" Button', 'fooconvert' ),
                        'default' => true,
                        'desc'    => __( 'Lets visitors re-open the banner at any time to change or withdraw their consent — a GDPR requirement.', 'fooconvert' ),
                    ),
                    array(
                        'id'      => 'consent_mode_v2',
                        'order'   => 220,
                        'type'    => 'checkbox',
                        'label'   => __( 'Emit Google Consent Mode v2 Signals', 'fooconvert' ),
                        'default' => true,
                        'desc'    => __( 'If you use Google Analytics 4 or Google Ads, this keeps your analytics measurable (via modelling) for visitors who reject cookies. Has no effect if no Google tags are on the site.', 'fooconvert' ),
                    ),
                ),
            );
        }

        private function build_categories_tab(): array {
            $fields = array(
                array(
                    'id'    => 'categories_heading',
                    'order' => 5,
                    'type'  => 'heading',
                    'label' => __( 'Consent Categories', 'fooconvert' ),
                    'desc'  => __( 'Text shown in the banner\'s "Customize" view. Keep descriptions short and plain-language — regulators look for descriptions a non-technical visitor can understand. The "Necessary" category is always granted and cannot be disabled.', 'fooconvert' ),
                ),
            );

            $defaults = Consent::get_category_defaults();
            $order = 10;

            foreach ( Consent::KNOWN_CATEGORIES as $key ) {
                $is_necessary = ( $key === 'necessary' );
                $default = $defaults[ $key ] ?? array( 'label' => ucfirst( $key ), 'description' => '' );

                $fields[] = array(
                    'id'    => 'category_' . $key . '_heading',
                    'order' => $order,
                    'type'  => 'heading',
                    'label' => $default['label'] . ( $is_necessary ? ' · ' . __( 'Always On', 'fooconvert' ) : '' ),
                );
                $order += 1;

                $fields[] = array(
                    'id'       => 'category_' . $key . '_label',
                    'order'    => $order,
                    'type'     => $is_necessary ? 'readonly' : 'text',
                    'label'    => __( 'Label', 'fooconvert' ),
                    'default'  => $default['label'],
                );
                $order += 1;

                $fields[] = array(
                    'id'       => 'category_' . $key . '_description',
                    'order'    => $order,
                    'type'     => 'textarea',
                    'label'    => __( 'Description', 'fooconvert' ),
                    'default'  => $default['description'],
                    'desc'     => $is_necessary
                        ? __( 'Shown in the banner for the Necessary category, which is always granted.', 'fooconvert' )
                        : '',
                );
                $order += 10;
            }

            return array(
                'id'     => 'categories',
                'label'  => __( 'Categories', 'fooconvert' ),
                'icon'   => 'dashicons-category',
                'order'  => 20,
                'fields' => $fields,
            );
        }

        private function build_log_tab(): array {
            $fields = array(
                array(
                    'id'    => 'log_heading',
                    'order' => 5,
                    'type'  => 'heading',
                    'label' => __( 'Proof of Consent', 'fooconvert' ),
                    'desc'  => __( 'Every recorded grant or withdrawal. Stored with a salted hash of the visitor\'s IP and user-agent (never the raw values) so the record can rebut a "no consent was given" claim without being a raw identity log.', 'fooconvert' ),
                ),
            );

            if ( !Schema::does_table_exist() ) {
                $fields[] = array(
                    'id'    => 'log_missing_table',
                    'order' => 10,
                    'type'  => 'html',
                    'label' => __( 'Consent log table', 'fooconvert' ),
                    'html'  => '<p style="color:#b32d2e">'
                        . esc_html__( 'The consent log table does not exist yet. Deactivate and reactivate the plugin to trigger table creation.', 'fooconvert' )
                        . '</p>',
                );

                return array(
                    'id'     => 'log',
                    'label'  => __( 'Consent Log', 'fooconvert' ),
                    'icon'   => 'dashicons-list-view',
                    'order'  => 30,
                    'fields' => $fields,
                );
            }

            $stats = QueryConsent::get_consent_log_table_stats();

            $fields[] = array(
                'id'    => 'log_stats',
                'order' => 10,
                'type'  => 'html',
                'label' => __( 'Stats', 'fooconvert' ),
                'html'  => $this->render_stats_html( $stats ),
            );

            $fields[] = array(
                'id'    => 'log_recent',
                'order' => 20,
                'type'  => 'html',
                'label' => __( 'Recent Records', 'fooconvert' ),
                'html'  => $this->render_recent_html( QueryConsent::get_recent( 50 ) ),
            );

            $fields[] = array(
                'id'                   => 'log_delete_all',
                'order'                => 100,
                'type'                 => 'ajaxbutton',
                'callback'             => array( $this, 'delete_all_consent_records' ),
                'button'               => __( 'Delete All Consent Records', 'fooconvert' ),
                'confirmation_message' => __( 'Are you sure you want to permanently delete every proof-of-consent record? This cannot be undone, and is destructive to your compliance evidence.', 'fooconvert' ),
                'desc'                 => __( 'Destructive. Use only if you are resetting the site; deleting records means you can no longer prove that a visitor gave consent on a given date.', 'fooconvert' ),
            );

            return array(
                'id'     => 'log',
                'label'  => __( 'Consent Log', 'fooconvert' ),
                'icon'   => 'dashicons-list-view',
                'order'  => 30,
                'fields' => $fields,
            );
        }

        /**
         * AJAX callback for the "Delete All Consent Records" button.
         */
        public function delete_all_consent_records(): void {
            if ( !current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array( 'message' => __( 'Not allowed.', 'fooconvert' ) ), 403 );
            }

            $result = ( new Consent() )->delete_all();

            wp_send_json_success( array(
                'message' => is_int( $result )
                    // translators: %d refers to the number of proof-of-consent records deleted.
                    ? sprintf( __( 'Successfully deleted %d consent records.', 'fooconvert' ), $result )
                    : __( 'Consent records deleted.', 'fooconvert' ),
            ) );
        }

        private function render_stats_html( array $stats ): string {
            $rows = intval( $stats['Number_of_Rows'] ?? 0 );
            $visitors = intval( $stats['Unique_Visitors'] ?? 0 );
            $size = (float) ( $stats['Size_in_MB'] ?? 0 );

            $html = '<table style="max-width:420px">';
            $html .= '<tr><td>' . esc_html__( 'Total Records', 'fooconvert' ) . '</td>';
            $html .= '<td><pre style="margin:0">' . esc_html( (string) $rows ) . '</pre></td></tr>';
            $html .= '<tr><td>' . esc_html__( 'Unique Visitors', 'fooconvert' ) . '</td>';
            $html .= '<td><pre style="margin:0">' . esc_html( (string) $visitors ) . '</pre></td></tr>';
            $html .= '<tr><td>' . esc_html__( 'Table Size (MB)', 'fooconvert' ) . '</td>';
            $html .= '<td><pre style="margin:0">' . esc_html( number_format( $size, 2 ) ) . '</pre></td></tr>';
            $html .= '</table>';

            return $html;
        }

        private function render_recent_html( array $rows ): string {
            if ( empty( $rows ) ) {
                return '<p>' . esc_html__( 'No consent records yet.', 'fooconvert' ) . '</p>';
            }

            $html  = '<table class="widefat striped" style="max-width:960px">';
            $html .= '<thead><tr>';
            $html .= '<th>' . esc_html__( 'When', 'fooconvert' ) . '</th>';
            $html .= '<th>' . esc_html__( 'Event', 'fooconvert' ) . '</th>';
            $html .= '<th>' . esc_html__( 'Categories', 'fooconvert' ) . '</th>';
            $html .= '<th>' . esc_html__( 'Source', 'fooconvert' ) . '</th>';
            $html .= '<th>' . esc_html__( 'Version', 'fooconvert' ) . '</th>';
            $html .= '<th>' . esc_html__( 'Consent ID', 'fooconvert' ) . '</th>';
            $html .= '</tr></thead><tbody>';

            foreach ( $rows as $row ) {
                $categories = $this->format_categories( (string) ( $row['categories'] ?? '' ) );
                $consent_id_short = esc_html( substr( (string) ( $row['consent_id'] ?? '' ), 0, 8 ) );

                $html .= '<tr>';
                $html .= '<td>' . esc_html( (string) ( $row['timestamp'] ?? '' ) ) . '</td>';
                $html .= '<td>' . esc_html( (string) ( $row['event_type'] ?? '' ) ) . '</td>';
                $html .= '<td>' . esc_html( $categories ) . '</td>';
                $html .= '<td>' . esc_html( (string) ( $row['source'] ?? '' ) ) . '</td>';
                $html .= '<td>' . esc_html( (string) ( $row['version'] ?? '' ) ) . '</td>';
                $html .= '<td><code>' . $consent_id_short . '…</code></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';

            return $html;
        }

        private function format_categories( string $serialized ): string {
            if ( $serialized === '' ) return '';

            $parts = array();
            foreach ( ( new Consent() )->parse_categories( $serialized ) as $key => $on ) {
                $parts[] = ( $on ? '✓ ' : '✗ ' ) . $key;
            }

            return implode( ', ', $parts );
        }

    }
}
