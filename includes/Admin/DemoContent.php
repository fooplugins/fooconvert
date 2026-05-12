<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Event;
use FooPlugins\FooConvert\FooConvert;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FooConvert admin demo-content helper.
 */

if ( ! class_exists( 'FooPlugins\FooConvert\Admin\DemoContent' ) ) {

	/**
	 * Class DemoContent.
	 */
	class DemoContent {
		/**
		 * Init constructor.
		 */
		function __construct() {}

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

		/**
		 * Cleans up old demo content.
		 *
		 * This function will delete any posts of the given post types
		 * that have the meta key set to the given value.
		 *
		 * @param string $popup_post_type The post type to search for.
		 * @param string $meta_key The meta key to search for.
		 *
		 * @return void
		 */
		function cleanup_old_demo_content( $popup_post_type, $meta_key ) {
			$old_demo_content = get_posts(
				array(
					'meta_key'    => $meta_key,
					'meta_value'  => '1',
					'post_type'   => $popup_post_type,
					'post_status' => 'any',
					'numberposts' => -1,
				)
			);

			if ( ! empty( $old_demo_content ) ) {
				foreach ( $old_demo_content as $post ) {
					wp_delete_post( $post->ID, true );
				}
			}
		}

		/**
		 * Deletes all demo content.
		 *
		 * This function will delete all demo content created by the `create` method.
		 *
		 * @since 1.0.0
		 */
		function delete() {
			$this->cleanup_old_demo_content( $this->ensure_registered_popup_post_type(), FOOCONVERT_META_KEY_DEMO_CONTENT );
		}

		/**
		 * Create demo content for the plugin.
		 *
		 * This function will create demo content for the plugin, unless
		 * demo content already exists. If $force is set to true, it will
		 * delete any existing demo content, and then create the demo content.
		 *
		 * @param bool $force If set to true, will delete existing demo content.
		 * @return int The number of demo content created.
		 */
		function create( $force = false ) {
			$popup_post_type = $this->ensure_registered_popup_post_type();

			if ( true === $force ) {
				$this->cleanup_old_demo_content( $popup_post_type, FOOCONVERT_META_KEY_DEMO_CONTENT );
			}

			$existing_posts = get_posts(
				array(
					'meta_key'       => FOOCONVERT_META_KEY_DEMO_CONTENT,
					'post_type'      => $popup_post_type,
					'post_status'    => 'any',
					'meta_value'     => '1',
					'posts_per_page' => 1,
				)
			);

			if ( ! empty( $existing_posts ) ) {
				return 0;
			}

			$count = 0;

			foreach ( $this->get_demo_content() as $content ) {
				$content_for_insert = $content;
				$post_content       = isset( $content['post_content'] ) ? strval( $content['post_content'] ) : '';

				unset( $content_for_insert['post_content'] );

				if ( ! array_key_exists( 'meta_input', $content_for_insert ) || ! is_array( $content_for_insert['meta_input'] ) ) {
					$content_for_insert['meta_input'] = array();
				}

				$content_for_insert['meta_input'][ FOOCONVERT_META_KEY_DEMO_CONTENT ] = '1';
				$post_id = wp_insert_post( $content_for_insert );

				if ( is_wp_error( $post_id ) ) {
					continue;
				}

				$post_id      = intval( $post_id );
				$post_content = str_replace(
					array( '||POST_ID||', '||ASSETS_URL||' ),
					array( strval( $post_id ), FOOCONVERT_ASSETS_URL ),
					$post_content
					);

					if ( '' !== $post_content ) {
						$slashed_post_content = function_exists( 'wp_slash' )
							? wp_slash( $post_content )
							: addslashes( $post_content );

						wp_update_post(
							array(
								'ID'           => $post_id,
								'post_content' => $slashed_post_content,
							)
						);
					}

				$popup_type = isset( $content_for_insert['meta_input'][ FOOCONVERT_META_KEY_POPUP_TYPE ] )
					? strval( $content_for_insert['meta_input'][ FOOCONVERT_META_KEY_POPUP_TYPE ] )
					: FOOCONVERT_POPUP_TYPE_OVERLAY;

				$meta = array(
					'post_type' => $this->get_popup_post_type( $popup_type ),
					'template'  => isset( $content_for_insert['template'] ) ? strval( $content_for_insert['template'] ) : '',
					'demo'      => true,
				);

				$this->create_events( $post_id, $meta, wp_rand( 500, 1000 ) );

				$count++;
			}

			return $count;
		}

		// phpcs:enable

		/**
		 * Creates demo event data for the popup.
		 *
		 * @param int   $post_id Popup ID.
		 * @param array $meta Event metadata.
		 * @param int   $num_events Number of demo events to generate.
		 * @return void
		 */
		function create_events( $post_id, $meta, $num_events = 1000 ) {
			$event = new Event();
			$woocommerce_order_sources = $this->get_demo_woocommerce_order_sources();
			$woocommerce_order_source_index = 0;

			// Bias demo data toward positive engagement so the dashboard does not
			// look empty or artificially hostile on first load.
			$event_types = array(
				FOOCONVERT_EVENT_TYPE_OPEN  => 0.7, // 70% views
				FOOCONVERT_EVENT_TYPE_CLICK => 0.2, // 20% clicks
				FOOCONVERT_EVENT_TYPE_CLOSE => 0.1, // 10% dismissals
			);

			for ( $i = 0; $i < $num_events; $i++ ) {
				$event_type = $this->weighted_random_event( $event_types );

				$conversion = null;

				if ( FOOCONVERT_EVENT_TYPE_CLICK === $event_type ) {
					// Demo clicks should not all read as successful conversions.
					$conversion = wp_rand( 0, 1 );
				}

				$event_subtype = in_array( $event_type, array( FOOCONVERT_EVENT_TYPE_CLICK, FOOCONVERT_EVENT_TYPE_CLOSE ), true )
					? FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT
					: null;
				$sentiment = FOOCONVERT_EVENT_TYPE_CLICK === $event_type
					? 1
					: ( FOOCONVERT_EVENT_TYPE_CLOSE === $event_type ? 0 : null );

				$timestamp = gmdate( 'Y-m-d H:i:s', strtotime( '-' . wp_rand( 0, 30 ) . ' days -' . wp_rand( 0, 86400 ) . ' seconds' ) );

				if ( 1 === wp_rand( 0, 1 ) ) {
					$user_id             = wp_rand( 1, 10 );
					$anonymous_user_guid = null;
				} else {
					$user_id = 0;
					// Anonymous demo visitors still need a stable GUID shape.
					$anonymous_user_guid = bin2hex( random_bytes( 32 ) );
				}

				$device_types = array( 'desktop', 'mobile', 'tablet' );
				$device_type  = $device_types[ array_rand( $device_types ) ];

				$extra_data = array();
				if ( 1 === $conversion && isset( $woocommerce_order_sources[ $woocommerce_order_source_index ] ) ) {
					$order_source = $woocommerce_order_sources[ $woocommerce_order_source_index ];
					$woocommerce_order_source_index++;
					$extra_data = $this->build_demo_woocommerce_order_extra_data( $order_source );
				}

				$event->create(
					array(
						'post_id'             => $post_id,
						'event_type'          => $event_type,
						'event_subtype'       => $event_subtype,
						'conversion'          => $conversion,
						'sentiment'           => $sentiment,
						'page_url'            => home_url( '/page-' . wp_rand( 1, 10 ) ),
						'device_type'         => $device_type,
						'user_id'             => $user_id,
						'anonymous_user_guid' => $anonymous_user_guid,
						'extra_data'          => $extra_data,
						'timestamp'           => $timestamp,
					),
					$meta
				);
			}

			do_action( 'fooconvert_demo_content_after_create_events', $post_id, $meta, $num_events );
		}

		/**
		 * Returns real WooCommerce order/product sources for demo conversion metadata.
		 *
		 * @return array<int,array<string,mixed>>
		 */
		private function get_demo_woocommerce_order_sources() {
			if ( function_exists( 'fooconvert_is_woocommerce_active' ) && ! fooconvert_is_woocommerce_active() ) {
				return array();
			}

			if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_orders' ) || ! function_exists( 'wc_get_products' ) ) {
				return array();
			}

			$orders = $this->get_demo_woocommerce_orders( 10 );
			$products = $this->get_demo_simple_woocommerce_products( 10 );

			if ( empty( $orders ) || empty( $products ) ) {
				return array();
			}

			$sources = array();
			$product_index = 0;

			foreach ( $orders as $order ) {
				if ( ! $order instanceof \WC_Order ) {
					continue;
				}

				$product = $products[ $product_index % count( $products ) ];
				$product_index++;

				$sources[] = array(
					'order_id'       => method_exists( $order, 'get_id' ) ? intval( $order->get_id() ) : 0,
					'order_number'   => method_exists( $order, 'get_order_number' ) ? (string) $order->get_order_number() : '',
					'order_status'   => method_exists( $order, 'get_status' ) ? (string) $order->get_status() : '',
					'order_currency' => method_exists( $order, 'get_currency' ) ? (string) $order->get_currency() : get_option( 'woocommerce_currency', 'USD' ),
					'product_id'     => method_exists( $product, 'get_id' ) ? intval( $product->get_id() ) : 0,
					'product_name'   => method_exists( $product, 'get_name' ) ? (string) $product->get_name() : '',
					'product_type'   => method_exists( $product, 'get_type' ) ? (string) $product->get_type() : 'simple',
					'product_amount' => $this->get_demo_simple_product_amount( $product ),
				);
			}

			return $sources;
		}

		/**
		 * Returns recent WooCommerce orders for demo metadata.
		 *
		 * @param int $limit Maximum number of orders.
		 * @return array<int,\WC_Order>
		 */
		private function get_demo_woocommerce_orders( $limit ) {
			$args = array(
				'limit'   => max( 1, intval( $limit ) ),
				'orderby' => 'date',
				'order'   => 'DESC',
				'return'  => 'objects',
			);

			if ( function_exists( 'wc_get_order_statuses' ) ) {
				$args['status'] = array_keys( wc_get_order_statuses() );
			}

			try {
				$orders = wc_get_orders( $args );
			} catch ( \Throwable $exception ) {
				return array();
			}

			if ( ! is_array( $orders ) ) {
				return array();
			}

			$normalized = array();
			foreach ( $orders as $order ) {
				if ( ! $order instanceof \WC_Order && is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
					$order = wc_get_order( intval( $order ) );
				}

				if ( $order instanceof \WC_Order ) {
					$normalized[] = $order;
				}
			}

			return $normalized;
		}

		/**
		 * Returns simple, non-variable WooCommerce products with positive prices.
		 *
		 * @param int $limit Maximum number of products.
		 * @return array<int,\WC_Product>
		 */
		private function get_demo_simple_woocommerce_products( $limit ) {
			try {
				$products = wc_get_products(
					array(
						'limit'   => max( 1, intval( $limit ) ),
						'status'  => array( 'publish', 'private' ),
						'type'    => 'simple',
						'orderby' => 'date',
						'order'   => 'DESC',
						'return'  => 'objects',
					)
				);
			} catch ( \Throwable $exception ) {
				return array();
			}

			if ( ! is_array( $products ) ) {
				return array();
			}

			$simple_products = array();
			foreach ( $products as $product ) {
				if ( ! $product instanceof \WC_Product && is_numeric( $product ) && function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( intval( $product ) );
				}

				if ( $this->is_demo_simple_product( $product ) && $this->get_demo_simple_product_amount( $product ) > 0 ) {
					$simple_products[] = $product;
				}
			}

			return $simple_products;
		}

		/**
		 * Builds WooCommerce order conversion metadata.
		 *
		 * @param array<string,mixed> $order_source Order/product source.
		 * @return array<string,mixed>
		 */
		private function build_demo_woocommerce_order_extra_data( $order_source ) {
			return array(
				'conversion_type' => 'woocommerce_order',
				'order_id'        => intval( $order_source['order_id'] ),
				'order_number'    => (string) $order_source['order_number'],
				'order_status'    => (string) $order_source['order_status'],
				'order_currency'  => (string) $order_source['order_currency'],
				'order_value'     => $order_source['product_amount'],
				'product_id'      => intval( $order_source['product_id'] ),
				'product_name'    => (string) $order_source['product_name'],
				'product_type'    => (string) $order_source['product_type'],
				'product_amount'  => $order_source['product_amount'],
			);
		}

		/**
		 * Checks whether a product is a simple product.
		 *
		 * @param mixed $product Candidate product.
		 * @return bool
		 */
		private function is_demo_simple_product( $product ) {
			if ( ! $product instanceof \WC_Product ) {
				return false;
			}

			if ( method_exists( $product, 'is_type' ) ) {
				return $product->is_type( 'simple' );
			}

			return method_exists( $product, 'get_type' ) && 'simple' === $product->get_type();
		}

		/**
		 * Returns a simple product's current amount.
		 *
		 * @param \WC_Product $product Product.
		 * @return float
		 */
		private function get_demo_simple_product_amount( $product ) {
			if ( ! $this->is_demo_simple_product( $product ) ) {
				return 0.0;
			}

			$price = method_exists( $product, 'get_price' ) ? $product->get_price() : '';
			if ( '' === $price && method_exists( $product, 'get_regular_price' ) ) {
				$price = $product->get_regular_price();
			}

			if ( function_exists( 'wc_format_decimal' ) ) {
				$price = wc_format_decimal( $price );
			}

			return round( floatval( $price ), 2 );
		}

		/**
		 * Handles weighted random event.
		 *
		 * @param array<string,float> $weights Weighted event list.
		 * @return string
		 */
		private function weighted_random_event( $weights ) {
			$rand       = wp_rand() / mt_getrandmax();
			$cumulative = 0;

			foreach ( $weights as $event => $weight ) {
				$cumulative += $weight;
				if ( $rand < $cumulative ) {
					return $event;
				}
			}

			// Defensive fallback if the configured weights do not total 1.
			return FOOCONVERT_EVENT_TYPE_OPEN;
		}

			/**
			 * Returns the demo content.
			 *
			 * @return array<int,array<string,mixed>>
			 */
			function get_demo_content() {
				return array(
					// Demo Bars.
					array(
						'post_title'  => __( 'Digital Download Signup Bar [Demo]', 'fooconvert' ),
						'post_status' => 'draft',
						'post_type'   => FOOCONVERT_CPT_POPUP,
						'template'    => 'bar__digital_download_signup',
						'meta_input'  => array(
							FOOCONVERT_META_KEY_POPUP_TYPE    => FOOCONVERT_POPUP_TYPE_BAR,
							FOOCONVERT_META_KEY_DISPLAY_RULES => array(
								'location' => array(
									array(
										'type' => 'general:front_page',
										'data' => array(),
									),
								),
								'exclude'  => array(),
								'users'    => array( 'general:all_users' ),
							),
						),
						'post_content' => <<<'HTML'
<!-- wp:fc/bar {"viewState":"open","template":"bar__digital_download_signup","settings":{"transitions":true,"maxWidth":"800px","trigger":{"version":2,"lifetime":"page","frequency":{"mode":"once","cooldownSeconds":0},"steps":[{"event":"fc.scroll.percent","where":{"percent":50}}]}},"styles":{"dimensions":{"padding":"24px"}},"openButton":{"styles":{"border":{"shadow":"6px 6px 9px #00000000"},"color":{"background":"#4f4c4c","icon":"#ffffff"}},"settings":{"hidden":true}},"closeButton":{"styles":{"color":{"background":"#ffffff00","icon":"#8f8f8f"},"dimensions":{"margin":"15px","padding":"0px"},"border":{"radius":"50%"}}},"content":{"styles":{"color":{"text":"#ffffff"},"background":{"backgroundImage":{"url":"||ASSETS_URL||media/template__bar__digital.jpg"},"backgroundSize":"cover"},"border":{"shadow":"6px 6px 9px #00000000","color":"#FFFFFF","style":"solid","width":"5px"},"dimensions":{"gap":"32px","padding":"32px"}}},"variation":"","postId":||POST_ID||} --><!-- wp:fc/bar-open-button /-->

<!-- wp:fc/bar-container --><!-- wp:fc/bar-close-button /-->

<!-- wp:fc/bar-content --><!-- wp:group {"tagName":"div","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} --><div class="wp-block-group"><!-- wp:paragraph {"align":"center","fontFamily":"montserrat","style":{"typography":{"fontSize":"24px","textAlign":"center"},"color":{"text":"#ffffff"}}} -->
<p class="has-text-align-center has-text-color has-montserrat-font-family" style="color:#ffffff;font-size:24px">Grow Your Traffic in 7 Days – Download our FREE Guide!</p>
<!-- /wp:paragraph -->

<!-- wp:fc/sign-up {"settings":{"closeOnSuccess":true,"successMessage":"Thanks! Please check your inbox."},"inputs":{"settings":{"emailOnly":true,"stackLabels":false,"noLabels":true,"emailPlaceholder":"Enter your email..."},"styles":{"typography":{"fontSize":"1.125rem"},"border":{"radius":"30px","width":"0px","style":"none"},"dimensions":{"padding":{"top":"6px","right":"20px","bottom":"6px","left":"40px"},"margin":{"top":"10px","right":"-20px","bottom":"10px","left":"10px"}}}},"button":{"settings":{"layout":"text-only","text":"Download"},"styles":{"color":{"background":"#ef4136"},"typography":{"fontSize":"20px"},"border":{"radius":"30px"},"dimensions":{"padding":{"top":"4px","right":"22px","bottom":"4px","left":"20px"},"margin":{"top":"7px","right":"7px","bottom":"7px","left":"-20px"}}}},"styles":{"color":{"background":"#ffffff00"},"dimensions":{"gap":"0px"}}} /-->

<!-- wp:paragraph {"textColor":"base","fontSize":"medium","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}}}} -->
<p class="has-base-color has-text-color has-link-color has-medium-font-size">Proven tips used by 7,000+ brands to boost traffic!</p>
<!-- /wp:paragraph --></div><!-- /wp:group --><!-- /wp:fc/bar-content --><!-- /wp:fc/bar-container --><!-- /wp:fc/bar -->
HTML
					),
					array(
						'post_title'  => __( 'Special Offer Countdown Bar [Demo]', 'fooconvert' ),
						'post_status' => 'draft',
						'post_type'   => FOOCONVERT_CPT_POPUP,
						'template'    => 'bar__special_offer',
						'meta_input'  => array(
							FOOCONVERT_META_KEY_POPUP_TYPE    => FOOCONVERT_POPUP_TYPE_BAR,
							FOOCONVERT_META_KEY_DISPLAY_RULES => array(
								'location' => array(
									array(
										'type' => 'general:front_page',
										'data' => array(),
									),
								),
								'exclude'  => array(),
								'users'    => array( 'general:all_users' ),
							),
						),
						'post_content' => <<<'HTML'
<!-- wp:fc/bar {"template":"bar__special_offer","settings":{"transitions":true,"trigger":{"version":2,"lifetime":"page","frequency":{"mode":"once","cooldownSeconds":0},"steps":[{"event":"fc.timer.elapsed","where":{"seconds":3}}]},"closeAnchor":"special-offer"},"openButton":{"styles":{"border":{"color":"#0F6F00","style":"solid","width":"1px","shadow":"6px 6px 9px #00000000"},"color":{"background":"#0F6F00","icon":"#ffffff"}},"settings":{"hidden":true}},"closeButton":{"styles":{"color":{"icon":"#ffffff"}}},"content":{"styles":{"color":{"background":"radial-gradient(rgb(60,171,0) 2%,rgb(15,111,0) 100%)","text":"#ffffff"},"dimensions":{"gap":"32px","padding":"32px"},"border":{"radius":"0px","style":"none","width":"0px","shadow":"6px 6px 9px #00000000"}}},"postId":||POST_ID||} --><!-- wp:fc/bar-open-button /-->

<!-- wp:fc/bar-container --><!-- wp:fc/bar-close-button /-->

<!-- wp:fc/bar-content --><!-- wp:group {"tagName":"div","layout":{"type":"flex","flexWrap":"wrap"}} --><div class="wp-block-group"><!-- wp:paragraph {"fontFamily":"system-font","style":{"typography":{"textTransform":"uppercase","letterSpacing":"3px","fontSize":"24px","lineHeight":"1.2"},"elements":{"link":{"color":{"text":"#000000"}}},"color":{"text":"#000000"}}} -->
<p class="has-text-color has-link-color has-system-font-font-family" style="color:#000000;font-size:24px;letter-spacing:3px;line-height:1.2;text-transform:uppercase">Special Offer:</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"placeholder":"Add special offer...","fontFamily":"system-font","style":{"typography":{"textTransform":"uppercase","fontSize":"24px","lineHeight":"1.2","fontStyle":"normal","fontWeight":"600"},"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"0","bottom":"0"}}}} -->
<p class="has-system-font-font-family" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0;font-size:24px;font-style:normal;font-weight:600;line-height:1.2;text-transform:uppercase">ENDS SOON!</p>
<!-- /wp:paragraph --></div><!-- /wp:group -->

<!-- wp:fc/countdown {"uniqueId":"426766db-4456-472d-92e1-8aec0a2ed27d","styles":{"border":{"radius":"0px"},"dimensions":{"gap":"0px","margin":"-20px","padding":"0px"}},"segment":{"styles":{"color":{"background":"#00000030"},"typography":{"fontSize":"0.8rem"},"dimensions":{"gap":"0px","padding":"15px","margin":"5px"},"border":{"radius":"15px"}}}} /-->

<!-- wp:buttons {"layout":{"type":"flex"}} --><div class="wp-block-buttons"><!-- wp:button {"className":"is-style-fill","style":{"typography":{"fontSize":"20px"},"border":{"radius":"16px"},"elements":{"link":{"color":{"text":"#ffffff"}}},"spacing":{"padding":{"left":"18px","right":"18px","top":"8px","bottom":"8px"}},"color":{"background":"#000000","text":"#ffffff"},"shadow":"var:preset|shadow|natural"},"fontFamily":"system-font"} --><div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-text-color has-background has-link-color has-system-font-font-family has-custom-font-size wp-element-button" href="#special-offer" style="border-radius:16px;color:#ffffff;background-color:#000000;padding-top:8px;padding-right:18px;padding-bottom:8px;padding-left:18px;box-shadow:var(--wp--preset--shadow--natural);font-size:20px">Let's Go!</a></div><!-- /wp:button --></div><!-- /wp:buttons --><!-- /wp:fc/bar-content --><!-- /wp:fc/bar-container --><!-- /wp:fc/bar -->
HTML
					),

					// Demo Flyouts.
					array(
						'post_title'  => __( 'Digital Download Signup Flyout [Demo]', 'fooconvert' ),
						'post_status' => 'draft',
						'post_type'   => FOOCONVERT_CPT_POPUP,
						'template'    => 'flyout__digital_download_signup',
						'meta_input'  => array(
							FOOCONVERT_META_KEY_POPUP_TYPE    => FOOCONVERT_POPUP_TYPE_FLYOUT,
							FOOCONVERT_META_KEY_DISPLAY_RULES => array(
								'location' => array(
									array(
										'type' => 'general:entire_site',
										'data' => array(),
									),
								),
								'exclude'  => array(),
								'users'    => array( 'general:all_users' ),
							),
						),
						'post_content' => <<<'HTML'
<!-- wp:fc/flyout {"postId":||POST_ID||,"postType":"fc-flyout","template":"flyout__digital_download_signup","settings":{"transitions":true,"position":"right-bottom"},"openButton":{"styles":{"border":{"shadow":"6px 6px 9px #00000000"},"color":{"background":"#4f4c4c","icon":"#ffffff"}},"settings":{"hidden":true}},"closeButton":{"styles":{"color":{"background":"#ffffff00","icon":"#8f8f8f"},"dimensions":{"margin":"15px","padding":"0px"},"border":{"radius":"50%"}}},"content":{"styles":{"color":{"text":"#ffffff"},"background":{"backgroundImage":{"url":"||ASSETS_URL||media/template__flyout__digital.jpg"},"backgroundSize":"cover"},"border":{"shadow":"6px 6px 9px #00000000","color":"#FFFFFF","style":"solid","width":"5px"},"dimensions":{"padding":"32px"},"width":"fit-content"}}} -->
<!-- wp:fc/flyout-open-button /-->

<!-- wp:fc/flyout-container -->
<!-- wp:fc/flyout-close-button /-->

<!-- wp:fc/flyout-content -->
<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"24px","textAlign":"center"},"color":{"text":"#ffffff"}},"fontFamily":"montserrat"} -->
<p class="has-text-align-center has-text-color has-montserrat-font-family" style="color:#ffffff;font-size:24px">Grow Your Traffic in 7 Days </p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"24px","textAlign":"center"},"color":{"text":"#ffffff"}},"fontFamily":"montserrat"} -->
<p class="has-text-align-center has-text-color has-montserrat-font-family" style="color:#ffffff;font-size:24px">Download our FREE Guide!</p>
<!-- /wp:paragraph -->

<!-- wp:fc/sign-up {"styles":{"color":{"background":"#ffffff00"},"dimensions":{"gap":"0px"}},"settings":{"closeOnSuccess":true,"successMessage":"Thanks! Please check your inbox."},"inputs":{"settings":{"emailOnly":true,"stackLabels":false,"noLabels":true,"emailPlaceholder":"Enter your email..."},"styles":{"typography":{"fontSize":"1.125rem"},"border":{"radius":"30px","width":"0px","style":"none"},"dimensions":{"padding":{"top":"6px","right":"20px","bottom":"6px","left":"40px"},"margin":{"top":"10px","right":"-20px","bottom":"10px","left":"10px"}}}},"button":{"settings":{"layout":"text-only","text":"Download"},"styles":{"color":{"background":"#ef4136"},"typography":{"fontSize":"20px"},"border":{"radius":"30px"},"dimensions":{"padding":{"top":"4px","right":"22px","bottom":"4px","left":"20px"},"margin":{"top":"7px","right":"7px","bottom":"7px","left":"-20px"}}}}} /-->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}}},"textColor":"base","fontSize":"small"} -->
<p class="has-base-color has-text-color has-link-color has-small-font-size">Proven tips used by 7,000+ brands to boost traffic!</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- /wp:fc/flyout-content -->
<!-- /wp:fc/flyout-container -->
<!-- /wp:fc/flyout -->
HTML
					),
					array(
						'post_title'  => __( 'Newsletter Subscribe Flyout [Demo]', 'fooconvert' ),
						'post_status' => 'draft',
						'post_type'   => FOOCONVERT_CPT_POPUP,
						'template'    => 'flyout__newsletter_subscribe',
						'meta_input'  => array(
							FOOCONVERT_META_KEY_POPUP_TYPE    => FOOCONVERT_POPUP_TYPE_FLYOUT,
							FOOCONVERT_META_KEY_DISPLAY_RULES => array(
								'location' => array(
									array(
										'type' => 'general:entire_site',
										'data' => array(),
									),
								),
								'exclude'  => array(),
								'users'    => array( 'general:all_users' ),
							),
						),
						'post_content' => <<<'HTML'
<!-- wp:fc/flyout {"viewState":"open","template":"flyout__newsletter_subscribe","settings":{"transitions":true,"trigger":{"version":2,"lifetime":"page","frequency":{"mode":"once","cooldownSeconds":0},"steps":[{"event":"fc.scroll.percent","where":{"percent":50}}]}},"styles":{"dimensions":{"padding":"24px"}},"openButton":{"styles":{"border":{"shadow":"6px 6px 9px #00000000"},"color":{"background":"#F4F4F4","icon":"#F7941D"}},"settings":{"hidden":true}},"closeButton":{"styles":{"color":{"background":"#d6d6d600","icon":"#d9d9d9"},"dimensions":{"margin":"15px","padding":"0px"}},"settings":{"icon":{"size":"24px"}}},"content":{"styles":{"background":{"backgroundImage":{"url":"||ASSETS_URL||media/template__flyout__i-cafe.jpg"},"backgroundSize":"cover"},"border":{"radius":"10px","color":"#F7941D","style":"solid","width":"5px","shadow":"6px 6px 9px #00000000"},"dimensions":{"gap":"32px","padding":{"top":"60px","right":"32px","bottom":"60px","left":"32px"}},"width":"280px"}},"variation":"","postId":||POST_ID||} -->
<!-- wp:fc/flyout-open-button /-->

<!-- wp:fc/flyout-container -->
<!-- wp:fc/flyout-close-button /-->

<!-- wp:fc/flyout-content -->
<!-- wp:paragraph {"align":"left","fontFamily":"handlee","style":{"typography":{"fontSize":"30px","lineHeight":"1.2"}}} -->
<p class="has-handlee-font-family" style="font-size:30px;line-height:1.2">Get FREE Weekly Tips to help Grow Your business...</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","fontFamily":"system-font","style":{"typography":{"fontSize":"16px","fontStyle":"italic","fontWeight":"400","lineHeight":"1.3","textAlign":"center"},"color":{"text":"#7c7c7c"},"spacing":{"margin":{"top":"-2px","bottom":"0","right":"0px"}}}} -->
<p class="has-text-align-center has-text-color has-system-font-font-family" style="color:#7c7c7c;margin-top:-2px;margin-right:0px;margin-bottom:0;font-size:16px;font-style:italic;font-weight:400;line-height:1.3">Join 5,000+ readers getting exclusive content &amp; tools.</p>
<!-- /wp:paragraph -->

<!-- wp:fc/sign-up {"styles":{"typography":{"fontFamily":"Montserrat"},"dimensions":{"gap":"8px","margin":{"top":"30px","right":"4px","bottom":"4px","left":"4px"}}},"settings":{"layout":"stack","successMessage":"Thanks for joining!","closeOnSuccess":true},"inputs":{"settings":{"emailOnly":true,"noLabels":true,"emailPlaceholder":"Your email address","stackLabels":true},"styles":{"typography":{"fontSize":"1rem","lineHeight":"1.1"},"border":{"radius":"10px","width":"1px","style":"solid","color":"#949494"},"dimensions":{"padding":{"top":"15px","right":"3px","bottom":"15px","left":"23px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}},"button":{"settings":{"text":"Join The List","justify":"flex-end","layout":"icon-text","icon":{"slug":"default__send","size":"32px"},"width":"fit-content"},"styles":{"color":{"text":"#111111","background":"#f7941d","icon":"#FFFFFF"},"typography":{"fontSize":"1.2rem","fontStyle":"normal","fontWeight":700},"border":{"radius":"10px","color":"#f7941d","width":"2px","shadow":"6px 6px 9px #00000000"}}}} /-->
<!-- /wp:fc/flyout-content -->
<!-- /wp:fc/flyout-container -->
<!-- /wp:fc/flyout -->
HTML
					),
					array(
						'post_title'  => __( 'Smart Exit Offer Flyout [Demo]', 'fooconvert' ),
						'post_status' => 'draft',
						'post_type'   => FOOCONVERT_CPT_POPUP,
						'template'    => 'flyout__smart_exit_offer',
						'meta_input'  => array(
							FOOCONVERT_META_KEY_POPUP_TYPE    => FOOCONVERT_POPUP_TYPE_FLYOUT,
							FOOCONVERT_META_KEY_DISPLAY_RULES => array(
								'location' => array(
									array(
										'type' => 'general:entire_site',
										'data' => array(),
									),
								),
								'exclude'  => array(),
								'users'    => array( 'general:all_users' ),
							),
						),
						'post_content' => <<<'HTML'
<!-- wp:fc/flyout {"template":"flyout__smart_exit_offer","viewState":"open","styles":{"dimensions":{"padding":"24px"}},"settings":{"transitions":true,"trigger":{"version":2,"lifetime":"page","frequency":{"mode":"once","cooldownSeconds":0},"steps":[{"event":"fc.exit_intent","where":{"delaySeconds":5}}]},"closeAnchor":"claim"},"openButton":{"styles":{"border":{"shadow":"6px 6px 9px #00000000"},"color":{"background":"#7B1FBD","icon":"#ffffff"}},"settings":{"hidden":true}},"closeButton":{"styles":{"color":{"background":"#ffffff00","icon":"#a200ff"},"dimensions":{"margin":"15px","padding":"0px"},"border":{"radius":"50%"}}},"content":{"styles":{"color":{"background":"#7B1FBD","text":"#ffffff"},"background":{"backgroundImage":{"url":"||ASSETS_URL||media/template__flyout__purple_percent.jpg"},"backgroundSize":"cover"},"border":{"radius":"30px","shadow":"6px 6px 9px #00000000","color":"#FFFFFF","style":"solid","width":"5px"},"dimensions":{"gap":"32px","padding":{"top":"48px","right":"32px","bottom":"48px","left":"32px"}},"width":"280px"}},"variation":"","postId":||POST_ID||} --><!-- wp:fc/flyout-open-button /-->

<!-- wp:fc/flyout-container --><!-- wp:fc/flyout-close-button /-->

<!-- wp:fc/flyout-content --><!-- wp:group {"tagName":"div","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} --><div class="wp-block-group"><!-- wp:group {"tagName":"div","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} --><div class="wp-block-group"><!-- wp:paragraph {"align":"left","className":"fc-text-shadow-stroke","fontFamily":"montserrat","style":{"elements":{"link":{"color":{"text":"#f26522"}}},"color":{"text":"#f26522"},"typography":{"fontSize":"52px","fontStyle":"normal","fontWeight":"800","lineHeight":"1","textAlign":"left"}}} -->
<p class="has-text-align-left fc-text-shadow-stroke has-text-color has-link-color has-montserrat-font-family" style="color:#f26522;font-size:52px;font-style:normal;font-weight:800;line-height:1">HEY... Don’t Go Yet!</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","className":"fc-text-shadow","fontFamily":"montserrat","style":{"typography":{"fontSize":"26px","fontStyle":"normal","fontWeight":"600","lineHeight":"1.1","textAlign":"center"},"color":{"text":"#ffffff"}}} -->
<p class="has-text-align-center fc-text-shadow has-text-color has-montserrat-font-family" style="color:#ffffff;font-size:26px;font-style:normal;font-weight:600;line-height:1.1">Take 10% Off Your First Order – Our Treat!</p>
<!-- /wp:paragraph --></div><!-- /wp:group -->

<!-- wp:fc/coupon {"uniqueId":"||POST_ID||-smart-exit-flyout-coupon","styles":{"typography":{"fontSize":"1.125rem"},"background":[]},"settings":{"textAlign":"center","noLabel":true,"closeOnCopy":true},"code":{"settings":{"text":"SAVE10","textAlign":"center"},"styles":{"border":{"radius":"35px"},"typography":{"fontFamily":{"key":"fira-code","name":"Fira Code","style":{"fontFamily":"\"Fira Code\", monospace"}}},"innerPadding":{"top":"5px","right":"20px","bottom":"5px","left":"20px"},"dimensions":{"padding":"5px"}}},"button":{"settings":{"icon":{"slug":"default__copy","size":"24px"},"text":"Claim Now!"},"styles":{"border":{"radius":"35px"},"color":{"background":"#f26522"},"typography":{"fontFamily":{"key":"montserrat","name":"Montserrat","style":{"fontFamily":"Montserrat"}},"fontSize":"1.125rem","fontStyle":"normal","fontWeight":600}}}} /-->

<!-- wp:paragraph {"align":"center","fontSize":"small","style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center has-small-font-size">Only available while you’re still here...</p>
<!-- /wp:paragraph --></div><!-- /wp:group --><!-- /wp:fc/flyout-content --><!-- /wp:fc/flyout-container --><!-- /wp:fc/flyout -->
HTML
					),

					// Demo Overlays.
					array(
						'post_title'  => __( 'Smart Exit Offer Overlay [Demo]', 'fooconvert' ),
						'post_status' => 'draft',
						'post_type'   => FOOCONVERT_CPT_POPUP,
						'template'    => 'popup__smart_exit_offer',
						'meta_input'  => array(
							FOOCONVERT_META_KEY_POPUP_TYPE    => FOOCONVERT_POPUP_TYPE_OVERLAY,
							FOOCONVERT_META_KEY_DISPLAY_RULES => array(
								'location' => array(
									array(
										'type' => 'general:entire_site',
										'data' => array(),
									),
								),
								'exclude'  => array(),
								'users'    => array( 'general:all_users' ),
							),
						),
						'post_content' => <<<'HTML'
<!-- wp:fc/overlay {"postId":||POST_ID||,"postType":"fc-popup","template":"popup__smart_exit_offer","styles":{"dimensions":{"padding":"24px"},"color":{"backdrop":"#111111"}},"settings":{"transitions":true,"trigger":{"type":"exit-intent","data":5,"once":true},"closeAnchor":"claim","editorBackground":"white"},"closeButton":{"styles":{"color":{"background":"#ffffff00","icon":"#a200ff"},"dimensions":{"margin":"15px","padding":"0px"},"border":{"radius":"50%"}}},"content":{"styles":{"color":{"background":"#7B1FBD","text":"#ffffff"},"background":{"backgroundImage":{"url":"||ASSETS_URL||media/template__flyout__purple_percent.jpg"},"backgroundSize":"cover"},"border":{"radius":"30px","shadow":"6px 6px 9px #00000000","color":"#FFFFFF","style":"solid","width":"5px"},"dimensions":{"gap":"32px","padding":{"top":"48px","right":"32px","bottom":"48px","left":"32px"}},"width":"720px"}}} -->
<!-- wp:fc/overlay-container -->
<!-- wp:fc/overlay-close-button /-->

<!-- wp:fc/overlay-content -->
<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"fc-text-shadow-stroke has-text-color has-link-color has-montserrat-font-family","style":{"elements":{"link":{"color":{"text":"#f26522"}}},"color":{"text":"#f26522"},"typography":{"fontSize":"52px","fontStyle":"normal","fontWeight":"800","lineHeight":"1","textAlign":"center"},"layout":{"selfStretch":"fit","flexSize":null}}} -->
<p class="has-text-align-center fc-text-shadow-stroke has-text-color has-link-color has-montserrat-font-family" style="color:#f26522;font-size:52px;font-style:normal;font-weight:800;line-height:1">HEY... Don’t Go Yet!</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"fc-text-shadow has-text-color has-montserrat-font-family","style":{"typography":{"fontSize":"30px","fontStyle":"normal","fontWeight":"600","lineHeight":"1.1","textAlign":"center"},"color":{"text":"#ffffff"}}} -->
<p class="has-text-align-center fc-text-shadow has-text-color has-montserrat-font-family" style="color:#ffffff;font-size:30px;font-style:normal;font-weight:600;line-height:1.1">Take 10% Off Your First Order – Our Treat!</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:fc/coupon {"uniqueId":"||POST_ID||-smart-exit-overlay-coupon","styles":{"typography":{"fontSize":"1.125rem"},"background":[]},"settings":{"textAlign":"center","noLabel":true,"closeOnCopy":true},"code":{"settings":{"text":"SAVE10","textAlign":"center"},"styles":{"border":{"radius":"35px"},"typography":{"fontFamily":{"key":"fira-code","name":"Fira Code","style":{"fontFamily":"\"Fira Code\", monospace"}}},"innerPadding":{"top":"5px","right":"20px","bottom":"5px","left":"20px"},"dimensions":{"padding":"5px"}}},"button":{"settings":{"icon":{"slug":"default__copy","size":"24px"},"layout":"text-icon","text":"Claim Now!"},"styles":{"border":{"radius":"35px"},"color":{"background":"#f26522"},"typography":{"fontFamily":{"key":"montserrat","name":"Montserrat","style":{"fontFamily":"Montserrat"}},"fontSize":"1.125rem","fontStyle":"normal","fontWeight":600}}}} /-->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Only available while you’re still here...</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- /wp:fc/overlay-content -->
<!-- /wp:fc/overlay-container -->
<!-- /wp:fc/overlay -->
HTML
					),
					array(
						'post_title'  => __( 'Special Offer Countdown Overlay [Demo]', 'fooconvert' ),
						'post_status' => 'draft',
						'post_type'   => FOOCONVERT_CPT_POPUP,
						'template'    => 'popup__special_offer',
						'meta_input'  => array(
							FOOCONVERT_META_KEY_POPUP_TYPE    => FOOCONVERT_POPUP_TYPE_OVERLAY,
							FOOCONVERT_META_KEY_DISPLAY_RULES => array(
								'location' => array(
									array(
										'type' => 'general:entire_site',
										'data' => array(),
									),
								),
								'exclude'  => array(),
								'users'    => array( 'general:all_users' ),
							),
						),
						'post_content' => <<<'HTML'
<!-- wp:fc/overlay {"template":"popup__special_offer","content":{"styles":{"color":{"background":"radial-gradient(rgb(60,171,0) 2%,rgb(15,111,0) 100%)","text":"#ffffff"},"border":{"shadow":"6px 6px 9px #00000000","color":"#0F6F00","style":"solid","width":"0px","radius":"16px"},"dimensions":{"padding":"38px"},"width":"720px"}},"closeButton":{"styles":{"color":{"icon":"#ffffff"}}},"settings":{"transitions":true,"trigger":{"version":2,"lifetime":"page","frequency":{"mode":"once","cooldownSeconds":0},"steps":[{"event":"fc.timer.elapsed","where":{"seconds":3}}]},"closeAnchor":"special-offer"},"postId":||POST_ID||} --><!-- wp:fc/overlay-container --><!-- wp:fc/overlay-close-button /-->

<!-- wp:fc/overlay-content --><!-- wp:paragraph {"align":"center","fontFamily":"system-font","style":{"typography":{"fontSize":"24px","lineHeight":"1.2","letterSpacing":"3px","textTransform":"uppercase","textAlign":"center"},"elements":{"link":{"color":{"text":"#000000"}}},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"color":{"text":"#000000"}}} -->
<p class="has-text-align-center has-text-color has-link-color has-system-font-font-family" style="color:#000000;margin-top:32px;margin-right:32px;margin-bottom:32px;margin-left:32px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;font-size:24px;letter-spacing:3px;line-height:1.2;text-transform:uppercase">Special Offer:</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"placeholder":"Add special offer...","align":"center","style":{"typography":{"fontSize":"32px","lineHeight":"1.2","textTransform":"uppercase","fontStyle":"normal","fontWeight":"600","textAlign":"center"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"elements":{"link":{"color":{"text":"#ffffff"}}},"color":{"text":"#ffffff"}}} -->
<p class="has-text-align-center has-text-color has-link-color" style="color:#ffffff;margin-top:32px;margin-right:32px;margin-bottom:32px;margin-left:32px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;font-size:32px;font-style:normal;font-weight:600;line-height:1.2;text-transform:uppercase">OFFER ends soon! Act quickly</p>
<!-- /wp:paragraph -->

<!-- wp:fc/countdown {"styles":{"border":{"radius":"0px","color":"#111111","width":"11px"}},"settings":{"fomoValue":12,"closeOnExpire":true},"segment":{"settings":{"layout":"stack","padDigits":false},"styles":{"color":{"background":"#00000030"},"border":{"radius":"16px","style":"none","width":"0px","shadow":"6px 6px 9px #00000000"},"typography":{"fontSize":"1rem"},"dimensions":{"padding":"31px","margin":"27px","gap":"12px"}}}} /-->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"48px","bottom":"32px"}}},"layout":{"type":"flex","justifyContent":"center"}} --><div style="margin-top:48px;margin-bottom:32px" class="wp-block-buttons"><!-- wp:button {"className":"is-style-fill","style":{"typography":{"fontSize":"24px"},"border":{"radius":"16px"},"elements":{"link":{"color":{"text":"#ffffff"}}},"spacing":{"padding":{"left":"32px","right":"32px","top":"12px","bottom":"12px"}},"color":{"background":"#000000","text":"#ffffff"}},"fontFamily":"system-font"} --><div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-text-color has-background has-link-color has-system-font-font-family has-custom-font-size wp-element-button" href="#special-offer" style="border-radius:16px;color:#ffffff;background-color:#000000;padding-top:12px;padding-right:32px;padding-bottom:12px;padding-left:32px;font-size:24px">Let's Go!</a></div><!-- /wp:button --></div><!-- /wp:buttons --><!-- /wp:fc/overlay-content --><!-- /wp:fc/overlay-container --><!-- /wp:fc/overlay -->
HTML
					),
					);
			}

			/**
			 * Maps a logical popup type to its event post type.
			 *
			 * @param string $popup_type Popup type.
			 * @return string
			 */
		private function get_popup_post_type( string $popup_type ): string {
			switch ( $popup_type ) {
				case FOOCONVERT_POPUP_TYPE_BAR:
					return FOOCONVERT_CPT_BAR;

				case FOOCONVERT_POPUP_TYPE_FLYOUT:
					return FOOCONVERT_CPT_FLYOUT;
			}

			return FOOCONVERT_CPT_POPUP;
		}

		/**
		 * Ensures the popup CPT is registered before demo content queries run.
		 *
		 * @return string
		 */
		public function ensure_registered_popup_post_type() {
			$popup_post_type = FOOCONVERT_CPT_POPUP;
			if ( post_type_exists( $popup_post_type ) ) {
				return $popup_post_type;
			}

			FooConvert::plugin()->post_type->register();

			return $popup_post_type;
		}
	}
}
