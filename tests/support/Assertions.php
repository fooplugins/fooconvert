<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Tests\Support;

use RuntimeException;

spl_autoload_register(
    static function( string $class ): void {
        $root       = dirname( __DIR__, 2 );
        $pro_prefix = 'FooPlugins\\FooConvert\\Pro\\';

        if ( 0 === strpos( $class, $pro_prefix ) ) {
            $relative = substr( $class, strlen( $pro_prefix ) );
            if ( ! is_string( $relative ) || '' === $relative ) {
                return;
            }

            $file = $root . '/pro/includes/' . str_replace( '\\', '/', $relative ) . '.php';
            if ( file_exists( $file ) ) {
                require_once $file;
            }

            return;
        }

        $prefix = 'FooPlugins\\FooConvert\\';

        if ( 0 !== strpos( $class, $prefix ) ) {
            return;
        }

        $relative = substr( $class, strlen( $prefix ) );
        if ( ! is_string( $relative ) || '' === $relative ) {
            return;
        }

        $file = $root . '/includes/' . str_replace( '\\', '/', $relative ) . '.php';
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
);

/**
 * Lightweight assertion helpers for the dependency-free PHP smoke tests.
 */
final class Assertions {
    /**
     * Assert that two values are strictly identical.
     *
     * @param mixed $expected The expected value.
     * @param mixed $actual The actual value.
     * @param string $message The failure message to display if the assertion fails.
     * @return void
     */
    public static function same( $expected, $actual, string $message ): void {
        if ( $expected !== $actual ) {
            throw new RuntimeException(
                $message . PHP_EOL
                . 'Expected: ' . var_export( $expected, true ) . PHP_EOL
                . 'Actual: ' . var_export( $actual, true )
            );
        }
    }

    /**
     * Assert that the provided condition is true.
     *
     * @param bool $condition The condition being asserted.
     * @param string $message The failure message to display if the assertion fails.
     * @return void
     */
    public static function true( bool $condition, string $message ): void {
        if ( !$condition ) {
            throw new RuntimeException( $message );
        }
    }

    /**
     * Assert that the provided condition is false.
     *
     * @param bool $condition The condition being asserted.
     * @param string $message The failure message to display if the assertion fails.
     * @return void
     */
    public static function false( bool $condition, string $message ): void {
        if ( $condition ) {
            throw new RuntimeException( $message );
        }
    }
}
