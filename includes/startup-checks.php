<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Does some preliminary checks before the plugin is loaded
 */

if ( ! function_exists( 'fooconvert_min_php_admin_notice' ) ) {
    /**
     * Show an admin notice to administrators when the minimum PHP version could not be reached
     */
    function fooconvert_min_php_admin_notice() {
        //only show the admin message to users who can install plugins
        if ( ! current_user_can( 'install_plugins' ) ) {
            return;
        }

        $min_php_notice = sprintf(
        // translators: %1$s = Plugin name; %2$s = Minimum PHP version; %3$s = Current PHP version
            __( '%1$s could not be initialized because you need to be running at least PHP version %2$s, and you are running version %3$s', 'fooconvert' ),
            FOOCONVERT_NAME,
            FOOCONVERT_MIN_PHP,
            phpversion()
        );

        echo '<div class="notice notice-error"><p>' . esc_html( $min_php_notice ) . '</p></div>';
    }
}

if ( ! function_exists( 'fooconvert_min_wp_admin_notice' ) ) {
    /**
     * Show an admin notice to administrators when the minimum WP version could not be reached
     */
    function fooconvert_min_wp_admin_notice() {
        //only show the admin message to users who can install plugins
        if ( ! current_user_can( 'install_plugins' ) ) {
            return;
        }

        global $wp_version;

        $min_wp_notice = sprintf(
        // translators: %1$s = Plugin name; %2$s = Minimum WP version; %3$s = Current WP version
            __( '%1$s could not be initialized because you need WordPress to be at least version %2$s, and you are running version %3$s', 'fooconvert' ),
            FOOCONVERT_NAME,
            FOOCONVERT_MIN_WP,
            $wp_version
        );

        echo '<div class="notice notice-error"><p>' . esc_html( $min_wp_notice ) . '<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">' . esc_html__( 'Update WordPress now.', 'fooconvert' ) . '</a></p></div>';
    }
}

//check minimum PHP version
if ( version_compare( phpversion(), FOOCONVERT_MIN_PHP, "<" ) ) {
    add_action( 'admin_notices', 'fooconvert_min_php_admin_notice' );
    return false;
}

//check minimum WordPress version
global $wp_version;
if ( version_compare( $wp_version, FOOCONVERT_MIN_WP, '<' ) ) {
    add_action( 'admin_notices', 'fooconvert_min_wp_admin_notice' );
    return false;
}

//if we got here, then we passed all startup checks and the plugin can be loaded
return true;