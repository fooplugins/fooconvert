<?php

namespace FooPlugins\FooConvert\Brand;

use DOMDocument;
use DOMXPath;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts brand details by fetching and analyzing a remote URL.
 */
class RemoteExtractor {

    use ExtractorHelpers;

    /**
     * Fetches a URL and extracts branding information from it.
     *
     * @param string $url Fully-qualified URL.
     * @return array<string,mixed>|WP_Error
     */
    public function extract( string $url ) {
        $response = wp_remote_get(
            $url,
            array(
                'timeout'    => 30,
                'user-agent' => 'Mozilla/5.0 (compatible; FooConvertBrandExtractor/1.0)',
                'sslverify'  => false,
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'fooconvert_brand_fetch_failed', sprintf( __( 'Could not fetch the URL: %s', 'fooconvert' ), $response->get_error_message() ) );
        }

        $status = wp_remote_retrieve_response_code( $response );
        if ( $status < 200 || $status >= 400 ) {
            return new WP_Error( 'fooconvert_brand_http_error', sprintf( __( 'The site responded with HTTP %d.', 'fooconvert' ), intval( $status ) ) );
        }

        $html     = wp_remote_retrieve_body( $response );
        $base_url = $this->get_base_url( $url );

        libxml_use_internal_errors( true );
        $dom = new DOMDocument();
        $dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING );
        libxml_clear_errors();

        $xpath    = new DOMXPath( $dom );
        $css_text = $this->collect_css( $xpath, $base_url );

        $colors   = $this->extract_colors( $html, $css_text );
        $fonts    = $this->extract_fonts( $css_text, $xpath );
        $logo     = $this->extract_logo( $xpath, $base_url );
        $favicon  = $this->extract_favicon( $xpath, $base_url );
        $og_image = $this->extract_og_image( $xpath, $base_url );

        return $this->assemble_branding( $colors, $fonts, $logo, $favicon, $og_image, $css_text );
    }

    /**
     * Collects CSS from inline style tags and linked stylesheets.
     *
     * @param DOMXPath $xpath DOM XPath.
     * @param string   $base_url Base URL.
     * @return string
     */
    private function collect_css( DOMXPath $xpath, string $base_url ): string {
        $css = '';

        foreach ( $xpath->query( '//style' ) as $style ) {
            $css .= $style->textContent . "\n";
        }

        $urls = array();
        $elementor_urls = array();

        foreach ( $xpath->query( '//link[@rel="stylesheet"]' ) as $link ) {
            $href = $link->getAttribute( 'href' );
            if ( '' === $href ) {
                continue;
            }

            $resolved = $this->resolve_url( $href, $base_url );
            if ( '' === $resolved ) {
                continue;
            }

            if ( preg_match( '/elementor\/css\/post-/i', $resolved ) || preg_match( '/elementor.*global/i', $resolved ) ) {
                $elementor_urls[] = $resolved;
            } else {
                $urls[] = $resolved;
            }
        }

        foreach ( $elementor_urls as $url ) {
            $response = wp_remote_get(
                $url,
                array(
                    'timeout'    => 10,
                    'sslverify'  => false,
                    'user-agent' => 'Mozilla/5.0 (compatible; FooConvertBrandExtractor/1.0)',
                )
            );

            if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
                $css .= wp_remote_retrieve_body( $response ) . "\n";
            }
        }

        $count = 0;
        foreach ( $urls as $url ) {
            if ( $count >= 5 ) {
                break;
            }

            $response = wp_remote_get(
                $url,
                array(
                    'timeout'    => 10,
                    'sslverify'  => false,
                    'user-agent' => 'Mozilla/5.0 (compatible; FooConvertBrandExtractor/1.0)',
                )
            );

            if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
                $css .= wp_remote_retrieve_body( $response ) . "\n";
            }

            $count++;
        }

        return $css;
    }

    /**
     * Extracts fonts from CSS and Google Fonts links.
     *
     * @param string   $css_text CSS text.
     * @param DOMXPath $xpath DOM XPath.
     * @return string[]
     */
    private function extract_fonts( string $css_text, DOMXPath $xpath ): array {
        $fonts = array();

        foreach ( $xpath->query( '//link[contains(@href, "fonts.googleapis.com")]' ) as $link ) {
            $href = $link->getAttribute( 'href' );
            if ( preg_match_all( '/family=([^&]+)/', $href, $matches ) ) {
                foreach ( $matches[1] as $family_raw ) {
                    $parts = explode( '|', $family_raw );
                    foreach ( $parts as $part ) {
                        $name = str_replace( '+', ' ', urldecode( trim( $part ) ) );
                        $name = preg_replace( '/:.*$/', '', $name );
                        if ( is_string( $name ) && '' !== $name ) {
                            $fonts[] = $name;
                        }
                    }
                }
            }
        }

        if ( preg_match_all( '/@font-face\s*\{[^}]*font-family\s*:\s*["\']?([^;"\'}\)]+)/i', $css_text, $matches ) ) {
            foreach ( $matches[1] as $family ) {
                $fonts[] = trim( $family );
            }
        }

        if ( preg_match_all( '/font-family\s*:\s*([^;}{]+)/i', $css_text, $matches ) ) {
            foreach ( $matches[1] as $value ) {
                $families = $this->split_css_list( $value );
                $first    = trim( $families[0], " \t\n\r\0\x0B\"'" );

                if ( '' === $first || $this->is_generic_font( $first ) ) {
                    continue;
                }

                if ( preg_match( '/var\(\s*--([^,)]+)/i', $first, $var_matches ) ) {
                    $resolved = $this->resolve_css_var_font( $css_text, trim( $var_matches[1] ) );
                    if ( '' !== $resolved ) {
                        $fonts[] = $resolved;
                    }
                } else {
                    $fonts[] = $first;
                }
            }
        }

        $seen   = array();
        $unique = array();

        foreach ( $fonts as $font ) {
            $key = strtolower( $font );
            if ( isset( $seen[ $key ] ) || $this->is_generic_font( $font ) || preg_match( '/^var\s*\(/i', $font ) ) {
                continue;
            }

            $seen[ $key ] = true;
            $unique[]     = $font;
        }

        return ! empty( $unique ) ? $unique : array( 'sans-serif' );
    }

    /**
     * Extracts a likely logo URL.
     *
     * @param DOMXPath $xpath DOM XPath.
     * @param string   $base_url Base URL.
     * @return string
     */
    private function extract_logo( DOMXPath $xpath, string $base_url ): string {
        $queries = array(
            '//a[contains(@class,"custom-logo-link")]//img | //img[contains(@class,"custom-logo")]',
            '//header//img[contains(@class,"logo") or contains(@id,"logo") or contains(@alt,"logo")] | //nav//img[contains(@class,"logo") or contains(@id,"logo") or contains(@alt,"logo")] | //header//a[contains(@class,"logo") or contains(@id,"logo")]//img',
            '//*[contains(@class,"site-logo")]//img | //*[contains(@class,"elementor-site-logo")]//img',
            '//img[contains(@class,"logo") or contains(@id,"logo") or contains(@alt,"logo")]',
        );

        foreach ( $queries as $query ) {
            $nodes = $xpath->query( $query );
            if ( 0 === $nodes->length ) {
                continue;
            }

            $resolved = $this->resolve_url( $nodes->item( 0 )->getAttribute( 'src' ), $base_url );
            if ( '' !== $resolved ) {
                return $resolved;
            }
        }

        return '';
    }

    /**
     * Extracts a favicon URL.
     *
     * @param DOMXPath $xpath DOM XPath.
     * @param string   $base_url Base URL.
     * @return string
     */
    private function extract_favicon( DOMXPath $xpath, string $base_url ): string {
        foreach ( array( '//link[@rel="icon"]', '//link[@rel="shortcut icon"]', '//link[contains(@rel,"icon")]' ) as $query ) {
            $nodes = $xpath->query( $query );
            if ( 0 === $nodes->length ) {
                continue;
            }

            return $this->resolve_url( $nodes->item( 0 )->getAttribute( 'href' ), $base_url );
        }

        return '' !== $base_url ? $base_url . '/favicon.ico' : '';
    }

    /**
     * Extracts an OG/Twitter image URL.
     *
     * @param DOMXPath $xpath DOM XPath.
     * @param string   $base_url Base URL.
     * @return string
     */
    private function extract_og_image( DOMXPath $xpath, string $base_url ): string {
        $og = $xpath->query( '//meta[@property="og:image"]' );
        if ( $og->length > 0 ) {
            return $this->resolve_url( $og->item( 0 )->getAttribute( 'content' ), $base_url );
        }

        $twitter = $xpath->query( '//meta[@name="twitter:image"]' );
        if ( $twitter->length > 0 ) {
            return $this->resolve_url( $twitter->item( 0 )->getAttribute( 'content' ), $base_url );
        }

        return '';
    }
}
