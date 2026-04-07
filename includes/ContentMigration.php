<?php

namespace FooPlugins\FooConvert;

use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

if ( !class_exists( __NAMESPACE__ . '\ContentMigration' ) ) {

    /**
     * Migrates stored widget content to updated asset paths on read.
     */
    class ContentMigration {

        /**
         * Migration key used to mark media URL updates as completed.
         *
         * @var string
         */
        private const MIGRATION_MOVE_PRO_MEDIA_URLS = 'move_pro_media_urls_to_free';

        /**
         * Migration key used to mark the single popup CPT merge as completed.
         *
         * @var string
         */
        private const MIGRATION_MERGE_WIDGET_CPTS = 'merge_widget_cpts_into_popup';

        /**
         * Legacy media path stored in migrated widget content.
         *
         * @var string
         */
        private const OLD_MEDIA_PATH = 'plugins/fooconvert/pro/assets/media/';

        /**
         * Current media path used by the free plugin.
         *
         * @var string
         */
        private const NEW_MEDIA_PATH = 'plugins/fooconvert/assets/media/';

        /**
         * Hooks the content migration into widget reads and REST responses.
         *
         * @return void
         */
        public function __construct() {
            add_action( 'plugins_loaded', array( $this, 'maybe_migrate_widget_post_types' ), 20 );
            add_action( 'init', array( $this, 'maybe_migrate_widget_content' ), 20 );

            foreach ( $this->get_widget_post_types() as $post_type ) {
                add_filter( 'rest_prepare_' . $post_type, array( $this, 'maybe_migrate_rest_post' ), 10, 3 );
            }
        }

        /**
         * Migrates stored widget content once for all widget post types.
         *
         * @return void
         */
        public function maybe_migrate_widget_content() {
            if ( $this->is_completed( self::MIGRATION_MOVE_PRO_MEDIA_URLS ) ) {
                return;
            }

            foreach ( $this->get_widget_ids() as $post_id ) {
                $this->get_post_content( $post_id );
            }

            $this->mark_completed( self::MIGRATION_MOVE_PRO_MEDIA_URLS );
        }

        /**
         * Migrates legacy widget CPTs into the popup CPT and stores the logical popup type in post meta.
         *
         * @return void
         */
        public function maybe_migrate_widget_post_types(): void {
            if ( $this->is_completed( self::MIGRATION_MERGE_WIDGET_CPTS ) ) {
                return;
            }

            $updated_ids = array();
            foreach ( $this->get_legacy_post_type_migration_map() as $legacy_post_type => $popup_type ) {
                foreach ( $this->get_widget_ids_for_post_type( $legacy_post_type ) as $post_id ) {
                    if ( $this->update_widget_post_type( $post_id, FOOCONVERT_CPT_POPUP ) ) {
                        $updated_ids[] = $post_id;
                    }
                    update_post_meta( $post_id, FOOCONVERT_META_KEY_POPUP_TYPE, $popup_type );
                }
            }

            foreach ( $this->get_widget_ids_for_post_type( FOOCONVERT_CPT_POPUP ) as $post_id ) {
                $popup_type = fooconvert_normalize_popup_type( get_post_meta( $post_id, FOOCONVERT_META_KEY_POPUP_TYPE, true ) );
                if ( $popup_type === '' ) {
                    update_post_meta( $post_id, FOOCONVERT_META_KEY_POPUP_TYPE, FOOCONVERT_POPUP_TYPE_POPUP );
                }
            }

            foreach ( array_unique( $updated_ids ) as $post_id ) {
                clean_post_cache( $post_id );
            }

            $this->mark_completed( self::MIGRATION_MERGE_WIDGET_CPTS );
        }

        /**
         * Returns widget content after applying any pending path migrations.
         *
         * @param int $post_id Widget post ID.
         * @return string
         */
        public function get_post_content( int $post_id ): string {
            $content = get_post_field( 'post_content', $post_id );
            if ( !is_string( $content ) || $content === '' ) {
                return '';
            }

            if ( $this->is_media_url_migration_completed() ) {
                return $content;
            }

            return $this->maybe_migrate_post_content( $post_id, $content );
        }

        /**
         * Updates REST responses so editors receive migrated widget content.
         *
         * @param mixed           $response REST response object.
         * @param WP_Post         $post The prepared post.
         * @param WP_REST_Request $request The current REST request.
         * @return mixed
         */
        public function maybe_migrate_rest_post( $response, WP_Post $post, WP_REST_Request $request ) {
            if ( !$response instanceof WP_REST_Response || !in_array( $post->post_type, $this->get_widget_post_types(), true ) ) {
                return $response;
            }

            if ( $this->is_media_url_migration_completed() ) {
                return $response;
            }

            $content = is_string( $post->post_content ) ? $post->post_content : '';
            $migrated_content = $this->maybe_migrate_post_content( (int)$post->ID, $content );
            if ( $migrated_content === $content ) {
                return $response;
            }

            $data = $response->get_data();
            if ( isset( $data['content'] ) && is_array( $data['content'] ) ) {
                $data['content']['raw'] = $migrated_content;
                if ( array_key_exists( 'rendered', $data['content'] ) ) {
                    $data['content']['rendered'] = FooConvert::plugin()->do_content( $migrated_content );
                }
            }
            $response->set_data( $data );

            return $response;
        }

        /**
         * Rewrites legacy media paths in widget content.
         *
         * @param string $content Raw widget content.
         * @return string
         */
        public function normalize_content( string $content ): string {
            return str_replace(
                array(
                    self::OLD_MEDIA_PATH,
                    str_replace( '/', '\/', self::OLD_MEDIA_PATH )
                ),
                array(
                    self::NEW_MEDIA_PATH,
                    str_replace( '/', '\/', self::NEW_MEDIA_PATH )
                ),
                $content
            );
        }

        /**
         * Migrates content for a single widget post when changes are required.
         *
         * @param int    $post_id Widget post ID.
         * @param string $content Raw widget content.
         * @return string
         */
        private function maybe_migrate_post_content( int $post_id, string $content ): string {
            $migrated_content = $this->normalize_content( $content );
            if ( $migrated_content !== $content ) {
                $this->persist_migrated_content( $post_id, $migrated_content );
            }

            return $migrated_content;
        }

        /**
         * Persists migrated widget content back to the posts table.
         *
         * @param int    $post_id Widget post ID.
         * @param string $content Migrated content.
         * @return void
         */
        private function persist_migrated_content( int $post_id, string $content ): void {
            global $wpdb;

            $wpdb->update(
                $wpdb->posts,
                array( 'post_content' => $content ),
                array( 'ID' => $post_id ),
                array( '%s' ),
                array( '%d' )
            );

            clean_post_cache( $post_id );
        }

        /**
         * Returns the IDs of widget posts eligible for migration.
         *
         * @return int[]
         */
        private function get_widget_ids(): array {
            global $wpdb;

            $post_types = $this->get_widget_post_types();
            $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
            $query = $wpdb->prepare(
                "SELECT ID
                 FROM {$wpdb->posts}
                 WHERE post_type IN ($placeholders)
                 AND post_status NOT IN ('auto-draft', 'trash')",
                ...$post_types
            );

            $results = $wpdb->get_col( $query );

            return array_map( 'intval', is_array( $results ) ? $results : array() );
        }

        /**
         * Returns widget IDs for a specific post type that are eligible for migration.
         *
         * @param string $post_type Widget post type.
         * @return int[]
         */
        private function get_widget_ids_for_post_type( string $post_type ): array {
            global $wpdb;

            $query = $wpdb->prepare(
                "SELECT ID
                 FROM {$wpdb->posts}
                 WHERE post_type = %s
                 AND post_status NOT IN ('auto-draft', 'trash')",
                $post_type
            );

            $results = $wpdb->get_col( $query );

            return array_map( 'intval', is_array( $results ) ? $results : array() );
        }

        /**
         * Updates the database post type for a widget.
         *
         * @param int    $post_id Widget post ID.
         * @param string $post_type New post type.
         * @return bool
         */
        private function update_widget_post_type( int $post_id, string $post_type ): bool {
            global $wpdb;

            $result = $wpdb->update(
                $wpdb->posts,
                array( 'post_type' => $post_type ),
                array( 'ID' => $post_id ),
                array( '%s' ),
                array( '%d' )
            );

            return $result !== false;
        }

        /**
         * Returns the legacy widget post types that should be migrated to popups.
         *
         * @return array<string,string>
         */
        private function get_legacy_post_type_migration_map(): array {
            return array(
                FOOCONVERT_CPT_BAR    => FOOCONVERT_POPUP_TYPE_BAR,
                FOOCONVERT_CPT_FLYOUT => FOOCONVERT_POPUP_TYPE_FLYOUT,
            );
        }

        /**
         * Returns the widget post types tracked by this migration.
         *
         * @return string[]
         */
        private function get_widget_post_types(): array {
            return array(
                FOOCONVERT_CPT_BAR,
                FOOCONVERT_CPT_FLYOUT,
                FOOCONVERT_CPT_POPUP,
            );
        }

        /**
         * Returns the list of completed content migration keys.
         *
         * @return string[]
         */
        private function get_completed(): array {
            $completed = get_option( FOOCONVERT_OPTION_CONTENT_MIGRATIONS, array() );

            return is_array( $completed ) ? $completed : array();
        }

        /**
         * Checks whether a migration key has already been completed.
         *
         * @param string $migration Migration key.
         * @return bool
         */
        private function is_completed( string $migration ): bool {
            return in_array( $migration, $this->get_completed(), true );
        }

        /**
         * Checks whether the media URL migration has already run.
         *
         * @return bool
         */
        private function is_media_url_migration_completed(): bool {
            return $this->is_completed( self::MIGRATION_MOVE_PRO_MEDIA_URLS );
        }

        /**
         * Stores a migration key as completed.
         *
         * @param string $migration Migration key.
         * @return void
         */
        private function mark_completed( string $migration ): void {
            $completed = $this->get_completed();
            if ( !in_array( $migration, $completed, true ) ) {
                $completed[] = $migration;
                update_option( FOOCONVERT_OPTION_CONTENT_MIGRATIONS, $completed, false );
            }
        }
    }
}
