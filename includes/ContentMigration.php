<?php

namespace FooPlugins\FooConvert;

use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

if ( !class_exists( __NAMESPACE__ . '\ContentMigration' ) ) {

    class ContentMigration {

        private const MIGRATION_MOVE_PRO_MEDIA_URLS = 'move_pro_media_urls_to_free';

        private const OLD_MEDIA_PATH = 'plugins/fooconvert/pro/assets/media/';

        private const NEW_MEDIA_PATH = 'plugins/fooconvert/assets/media/';

        public function __construct() {
            add_action( 'init', array( $this, 'maybe_migrate_widget_content' ), 20 );

            foreach ( $this->get_widget_post_types() as $post_type ) {
                add_filter( 'rest_prepare_' . $post_type, array( $this, 'maybe_migrate_rest_post' ), 10, 3 );
            }
        }

        public function maybe_migrate_widget_content() {
            if ( $this->is_completed( self::MIGRATION_MOVE_PRO_MEDIA_URLS ) ) {
                return;
            }

            foreach ( $this->get_widget_ids() as $post_id ) {
                $this->get_post_content( $post_id );
            }

            $this->mark_completed( self::MIGRATION_MOVE_PRO_MEDIA_URLS );
        }

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

        private function maybe_migrate_post_content( int $post_id, string $content ): string {
            $migrated_content = $this->normalize_content( $content );
            if ( $migrated_content !== $content ) {
                $this->persist_migrated_content( $post_id, $migrated_content );
            }

            return $migrated_content;
        }

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

        private function get_widget_post_types(): array {
            return array(
                FOOCONVERT_CPT_BAR,
                FOOCONVERT_CPT_FLYOUT,
                FOOCONVERT_CPT_POPUP,
            );
        }

        private function get_completed(): array {
            $completed = get_option( FOOCONVERT_OPTION_CONTENT_MIGRATIONS, array() );

            return is_array( $completed ) ? $completed : array();
        }

        private function is_completed( string $migration ): bool {
            return in_array( $migration, $this->get_completed(), true );
        }

        private function is_media_url_migration_completed(): bool {
            return $this->is_completed( self::MIGRATION_MOVE_PRO_MEDIA_URLS );
        }

        private function mark_completed( string $migration ): void {
            $completed = $this->get_completed();
            if ( !in_array( $migration, $completed, true ) ) {
                $completed[] = $migration;
                update_option( FOOCONVERT_OPTION_CONTENT_MIGRATIONS, $completed, false );
            }
        }
    }
}
