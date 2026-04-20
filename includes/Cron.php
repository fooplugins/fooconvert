<?php

namespace FooPlugins\FooConvert;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FooConvert Cron Class
 */

if ( !class_exists( 'FooPlugins\FooConvert\Cron' ) ) {

    /**
     * Class Cron.
     */
    class Cron {
        /**
         * Init constructor.
         */
        function __construct() {
            add_action( 'init', [ $this, 'init' ] );
        }

        /**
         * Initiate the cron job to update popup stats hourly.
         *
         * Schedule the 'calculate_popup_stats' event to run hourly if it hasn't already been scheduled.
         * Hook into the scheduled event to call the 'update_popup_stats' method.
         */
        public function init() {
            // Schedule cron jobs if not already scheduled
            if ( !wp_next_scheduled( FOOCONVERT_CRON_CALC_STATS ) ) {
                wp_schedule_event( time(), 'hourly', FOOCONVERT_CRON_CALC_STATS );
            }

            if ( !wp_next_scheduled( FOOCONVERT_CRON_DELETE_EVENTS ) ) {
                wp_schedule_event( time(), 'daily', FOOCONVERT_CRON_DELETE_EVENTS );
            }

            // Hook into the scheduled events
            add_action( FOOCONVERT_CRON_CALC_STATS, [ $this, 'update_popup_stats' ] );
            add_action( FOOCONVERT_CRON_DELETE_EVENTS, [ $this, 'delete_old_events' ] );
        }

        /**
         * Updates the popup stats for all popups with events.
         *
         * Calls the `get_popup_metrics` method of the to retrieve and store the metrics in the post meta.
         */
        public function update_popup_stats() {
            $stats = new Stats();
            $stats->update();
        }

        /**
         * Deletes old events.
         *
         * This method instantiates an Event object and calls its `delete_old_events` method
         * to remove events that are no longer needed.
         */
        public function delete_old_events() {
            $event = new Event();
            return $event->delete_old_events();
        }
    }
}