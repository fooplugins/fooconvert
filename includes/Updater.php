<?php

namespace FooPlugins\FooConvert;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FooConvert Updater Class
 * Handles runtime schema upgrade checks for plugin updates.
 */

if ( !class_exists( __NAMESPACE__ . '\Updater' ) ) {

    /**
     * Class Updater.
     */
    class Updater {

        /**
         * Register the hooks that keep the database schema in sync with plugin updates.
         *
         * @return void
         */
        public function __construct() {
            add_action( 'plugins_loaded', array( $this, 'check_database' ) );
            add_action( 'upgrader_process_complete', array( $this, 'handle_upgrader_process_complete' ), 10, 2 );
        }

        /**
         * Run the schema upgrade if the stored database version is behind the target version.
         *
         * @param string|null $target_version Optional plugin version to check against.
         * @return array<array-key, mixed>|false Table creation results when an upgrade runs, otherwise false.
         */
        public function check_database( ?string $target_version = null ) {
            $target_version = $this->normalize_target_version( $target_version );
            if ( empty( $target_version ) || !$this->database_needs_upgrade( $target_version ) ) {
                return false;
            }

            $schema = new Data\Schema();
            return $schema->create_event_table_if_needed( $target_version );
        }

        /**
         * Run the schema upgrade immediately after this plugin has been updated via the upgrader.
         *
         * @param mixed $upgrader   The upgrader instance passed by WordPress.
         * @param mixed $hook_extra Context about the completed upgrade operation.
         * @return void
         */
        public function handle_upgrader_process_complete( $upgrader, $hook_extra ) {
            if ( !$this->is_our_plugin_update( $hook_extra ) ) {
                return;
            }

            $updated_version = $this->get_plugin_file_version();
            if ( empty( $updated_version ) ) {
                return;
            }

            $this->check_database( $updated_version );
        }

        /**
         * Determine whether the stored schema version needs to be upgraded.
         *
         * @param string $target_version The plugin version that should be reflected in the database.
         * @return bool True when the database is behind the target version.
         */
        private function database_needs_upgrade( string $target_version ): bool {
            $version_create_table = (string) get_option( FOOCONVERT_OPTION_VERSION_CREATE_TABLE, '0.0.0' );

            return $version_create_table !== $target_version
                && version_compare( $target_version, $version_create_table, '>' );
        }

        /**
         * Normalize the requested target version, falling back to the loaded plugin version.
         *
         * @param string|null $target_version Optional version passed in by the caller.
         * @return string|null A trimmed version string, or null when no valid version is available.
         */
        private function normalize_target_version( ?string $target_version = null ): ?string {
            if ( is_string( $target_version ) && '' !== trim( $target_version ) ) {
                return trim( $target_version );
            }

            if ( defined( 'FOOCONVERT_VERSION' ) && is_string( FOOCONVERT_VERSION ) && '' !== trim( FOOCONVERT_VERSION ) ) {
                return trim( FOOCONVERT_VERSION );
            }

            return null;
        }

        /**
         * Check whether the upgrader callback is reporting an update for this plugin.
         *
         * @param mixed $hook_extra Upgrade context provided by WordPress.
         * @return bool True when the current upgrade operation targets FooConvert.
         */
        private function is_our_plugin_update( $hook_extra ): bool {
            if ( !is_array( $hook_extra ) ) {
                return false;
            }

            if ( ( $hook_extra['action'] ?? '' ) !== 'update' || ( $hook_extra['type'] ?? '' ) !== 'plugin' ) {
                return false;
            }

            $plugin = plugin_basename( FOOCONVERT_FILE );

            if ( isset( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
                return in_array( $plugin, $hook_extra['plugins'], true );
            }

            return isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $plugin;
        }

        /**
         * Read the plugin version directly from the plugin file on disk.
         *
         * @return string|null The plugin version from the file header, or null when unavailable.
         */
        private function get_plugin_file_version(): ?string {
            $plugin_data = get_file_data(
                FOOCONVERT_FILE,
                array(
                    'Version' => 'Version',
                ),
                'plugin'
            );

            if ( !is_array( $plugin_data ) || empty( $plugin_data['Version'] ) || !is_string( $plugin_data['Version'] ) ) {
                return null;
            }

            return trim( $plugin_data['Version'] );
        }
    }
}
