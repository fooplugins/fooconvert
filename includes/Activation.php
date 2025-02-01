<?php
namespace FooPlugins\FooConvert;

/**
 * FooConvert Init Class
 * Runs all classes that need to run at startup
 */

if ( !class_exists( __NAMESPACE__ . '\Activation' ) ) {

    class Activation {
        /**
         * Callback for the `register_activation_hook` method.
         *
         * @param bool $network_wide True if WPMU superadmin uses "Network Activate" action, otherwise false if WPMU is
         * disabled or plugin is activated on an individual blog.
         */
        public static function activated( bool $network_wide ) {
            $plugin_data = get_site_option( FOOCONVERT_OPTION_VERSION );
            $save_data = false;
            if ( false === $plugin_data ) {
                $plugin_data = array(
                    'version'       => FOOCONVERT_VERSION,
                    'first_version' => FOOCONVERT_VERSION,
                    'first_install' => time()
                );
                $save_data = true;
            } else {
                $version = $plugin_data['version'];

                if ( $version !== FOOCONVERT_VERSION ) {
                    //the version has been updated

                    $plugin_data['version'] = FOOCONVERT_VERSION;
                    $save_data = true;
                }
            }

            if ( $save_data ) {
                update_site_option( FOOCONVERT_OPTION_VERSION, $plugin_data );
            }

            // Make sure the database tables are created.
            $schema = new Data\Schema();
            $schema->create_event_table_if_needed();
        }
    }
}