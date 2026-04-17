<?php

namespace FooPlugins\FooConvert;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( __NAMESPACE__ . '\ContentMigration' ) ) {

    /**
     * Migrates stored popup content to updated asset paths on read.
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
        private const MIGRATION_MERGE_POPUP_CPTS = 'merge_popup_cpts_into_popup';

        /**
         * Migration key used to mark the popup-to-overlay rename as completed.
         *
         * @var string
         */
        private const MIGRATION_RENAME_POPUP_TYPE_TO_OVERLAY = 'rename_popup_type_to_overlay';

        /**
         * Legacy media path stored in migrated popup content.
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
         * Hooks the content migration into popup reads and REST responses.
         *
         * @return void
         */
        public function __construct() {
            add_action( 'plugins_loaded', array( $this, 'maybe_migrate_popup_post_types' ), 20 );
            add_action( 'init', array( $this, 'maybe_migrate_popup_content' ), 20 );
        }

        /**
         * Migrates stored popup content once for all popup post types.
         *
         * @return void
         */
        public function maybe_migrate_popup_content() {
            $has_media_migration = !$this->is_completed( self::MIGRATION_MOVE_PRO_MEDIA_URLS );
            $has_overlay_migration = !$this->is_completed( self::MIGRATION_RENAME_POPUP_TYPE_TO_OVERLAY );
            if ( !$has_media_migration && !$has_overlay_migration ) {
                return;
            }

            foreach ( $this->get_post_ids() as $post_id ) {
                if ( $has_overlay_migration ) {
                    $this->maybe_migrate_popup_popup_type( $post_id );
                }
                $this->get_post_content( $post_id );
            }

            if ( $has_media_migration ) {
                $this->mark_completed( self::MIGRATION_MOVE_PRO_MEDIA_URLS );
            }

            if ( $has_overlay_migration ) {
                $this->mark_completed( self::MIGRATION_RENAME_POPUP_TYPE_TO_OVERLAY );
            }
        }

        /**
         * Migrates legacy popup CPTs into the popup CPT and stores the logical popup type in post meta.
         *
         * @return void
         */
        public function maybe_migrate_popup_post_types(): void {
            if ( $this->is_completed( self::MIGRATION_MERGE_POPUP_CPTS ) ) {
                return;
            }

            $updated_ids = array();
            foreach ( $this->get_legacy_post_type_migration_map() as $legacy_post_type => $popup_type ) {
                foreach ( $this->get_post_ids_for_post_type( $legacy_post_type ) as $post_id ) {
                    if ( $this->update_popup_post_type( $post_id, FOOCONVERT_CPT_POPUP ) ) {
                        $updated_ids[] = $post_id;
                    }
                    update_post_meta( $post_id, FOOCONVERT_META_KEY_POPUP_TYPE, $popup_type );
                }
            }

            foreach ( $this->get_post_ids_for_post_type( FOOCONVERT_CPT_POPUP ) as $post_id ) {
                $popup_type = fooconvert_normalize_popup_type( get_post_meta( $post_id, FOOCONVERT_META_KEY_POPUP_TYPE, true ) );
                if ( $popup_type === '' ) {
                    update_post_meta( $post_id, FOOCONVERT_META_KEY_POPUP_TYPE, FOOCONVERT_POPUP_TYPE_OVERLAY );
                }
            }

            foreach ( array_unique( $updated_ids ) as $post_id ) {
                clean_post_cache( $post_id );
            }

            $this->mark_completed( self::MIGRATION_MERGE_POPUP_CPTS );
        }

        /**
         * Returns popup content after applying any pending path migrations.
         *
         * @param int $post_id Popup post ID.
         * @return string
         */
        public function get_post_content( int $post_id ): string {
            $content = get_post_field( 'post_content', $post_id );
            if ( !is_string( $content ) || $content === '' ) {
                return '';
            }

            if ( !$this->has_pending_content_migrations() ) {
                return $content;
            }

            return $this->maybe_migrate_post_content( $post_id, $content );
        }

        /**
         * Rewrites legacy media paths in popup content.
         *
         * @param string $content Raw popup content.
         * @return string
         */
        public function normalize_content( string $content ): string {
            if ( !$this->is_media_url_migration_completed() ) {
                $content = str_replace(
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

            if ( !$this->is_popup_overlay_migration_completed() ) {
                $content = str_replace(
                    array(
                        'wp:fc/popup-container',
                        'wp:fc/popup-close-button',
                        'wp:fc/popup-content',
                        'wp:fc/popup',
                        '<fc-popup',
                        '</fc-popup',
                        '<\\/fc-popup',
                    ),
                    array(
                        'wp:fc/overlay-container',
                        'wp:fc/overlay-close-button',
                        'wp:fc/overlay-content',
                        'wp:fc/overlay',
                        '<fc-overlay',
                        '</fc-overlay',
                        '<\\/fc-overlay',
                    ),
                    $content
                );
            }

            return $content;
        }

        /**
         * Migrates content for a single popup post when changes are required.
         *
         * @param int    $post_id Popup post ID.
         * @param string $content Raw popup content.
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
         * Persists migrated popup content back to the posts table.
         *
         * @param int    $post_id Popup post ID.
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
         * Returns the IDs of popup posts eligible for migration.
         *
         * @return int[]
         */
        private function get_post_ids(): array {
            global $wpdb;

            $query = $wpdb->prepare(
                "SELECT ID
                 FROM {$wpdb->posts}
                 WHERE post_type = %s
                 AND post_status NOT IN ('auto-draft', 'trash')",
                $this->get_registered_popup_post_type()
            );

            $results = $wpdb->get_col( $query );

            return array_map( 'intval', is_array( $results ) ? $results : array() );
        }

        /**
         * Returns popup IDs for a specific post type that are eligible for migration.
         *
         * @param string $post_type Popup post type.
         * @return int[]
         */
        private function get_post_ids_for_post_type( string $post_type ): array {
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
         * Updates the database post type for a popup.
         *
         * @param int    $post_id Popup post ID.
         * @param string $post_type New post type.
         * @return bool
         */
        private function update_popup_post_type( int $post_id, string $post_type ): bool {
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
         * Returns the legacy popup post types that should be migrated to popups.
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
         * Returns the single registered popup post type tracked by content migrations.
         *
         * @return string
         */
        private function get_registered_popup_post_type(): string {
            return FOOCONVERT_CPT_POPUP;
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
         * Checks whether the popup-to-overlay migration has already run.
         *
         * @return bool
         */
        private function is_popup_overlay_migration_completed(): bool {
            return $this->is_completed( self::MIGRATION_RENAME_POPUP_TYPE_TO_OVERLAY );
        }

        /**
         * Checks whether any content migrations remain pending.
         *
         * @return bool
         */
        private function has_pending_content_migrations(): bool {
            return !$this->is_media_url_migration_completed() || !$this->is_popup_overlay_migration_completed();
        }

        /**
         * Normalizes the logical popup type stored for a popup post.
         *
         * @param int $post_id Popup post ID.
         * @return void
         */
        private function maybe_migrate_popup_popup_type( int $post_id ): void {
            $stored_popup_type = get_post_meta( $post_id, FOOCONVERT_META_KEY_POPUP_TYPE, true );
            $canonical_popup_type = fooconvert_get_popup_type( $post_id );
            if ( $canonical_popup_type === '' ) {
                $canonical_popup_type = FOOCONVERT_POPUP_TYPE_OVERLAY;
            }

            if ( $stored_popup_type !== $canonical_popup_type ) {
                update_post_meta( $post_id, FOOCONVERT_META_KEY_POPUP_TYPE, $canonical_popup_type );
            }
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
