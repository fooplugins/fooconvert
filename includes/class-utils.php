<?php

namespace FooPlugins\FooConvert;

use WP_Block_Type;
use WP_Post;

if ( ! class_exists( __NAMESPACE__ . '\Utils' ) ) {

    class Utils {
        //region Mixed - functions for dealing with mixed values

        /**
         * Check if a key exists in the given array or object.
         *
         * @param array|object $array_or_object The array or object to check.
         * @param string|int $key The array key or object property to check.
         *
         * @return bool True if the key exists, otherwise false.
         *
         * @since 1.0.0
         */
        public static function has_key( $array_or_object, $key ) : bool {
            return ( ( is_string( $key ) || is_int( $key ) ) && is_array( $array_or_object ) && array_key_exists( $key, $array_or_object ) )
                || ( is_string( $key ) && is_object( $array_or_object ) && property_exists( $array_or_object, $key ) );
        }

        /**
         * Get the value of a key from the given array or object.
         *
         * @param array|object $array_or_object The array or object to interrogate.
         * @param string|int $key The array key or object property to fetch.
         * @param mixed $default Optional. The value to return if the key does not exist. Default `null`.
         *
         * @return mixed The value for the key, otherwise the default value.
         *
         * @since 1.0.0
         */
        public static function get_key( $array_or_object, $key, $default = null ) {
            if ( ( is_string( $key ) || is_int( $key ) ) && is_array( $array_or_object ) && array_key_exists( $key, $array_or_object ) ) {
                return $array_or_object[ $key ];
            } else if ( is_string( $key ) && is_object( $array_or_object ) && property_exists( $array_or_object, $key ) ) {
                return $array_or_object->{$key};
            }
            return $default;
        }

        /**
         * Set the value of a key for the given array or object.
         *
         * @param array|object $array_or_object The array or object to modify.
         * @param string|int $key The array key or object property to set.
         * @param mixed $value The new value for the key.
         *
         * @return bool True if the key was successfully set, otherwise false.
         *
         * @since 1.0.0
         */
        public static function set_key( &$array_or_object, $key, $value ) : bool {
            if ( ( is_string( $key ) || is_int( $key ) ) && is_array( $array_or_object ) ) {
                $array_or_object[ $key ] = $value;
                return true;
            } else if ( is_string( $key ) && is_object( $array_or_object ) ) {
                $array_or_object->{$key} = $value;
                return true;
            }
            return false;
        }

        /**
         * Check an array or object contains all the given keys.
         *
         * If a callback is supplied it is called for each entry in the array or object and passed three parameters that can be used to validate the entry:
         *
         *  - `$value`             - The current value.
         *  - `$key`               - The current key.
         *  - `$array_or_object`   - The array or object being iterated.
         *
         * @param array|object $array_or_object The array or object to interrogate.
         * @param (string|int)[] $keys The array of array keys or object properties to check.
         * @param ?callable $callback Optional. If supplied and a key exists this callback can be used to validate the value. Default `null`.
         *
         * @return bool True if all the keys exist, otherwise false.
         *
         * @since 1.0.0
         */
        public static function has_keys( $array_or_object, array $keys, ?callable $callback = null ) : bool {
            $has_callback = is_callable( $callback );
            return self::array_every( $keys, function ( $key ) use ( $array_or_object, $has_callback, $callback ) {
                $key_exists = self::has_key( $array_or_object, $key );
                if ( $key_exists && $has_callback ) {
                    $value = self::get_key( $array_or_object, $key );
                    return call_user_func( $callback, $value, $key, $array_or_object );
                }
                return $key_exists;
            } );
        }

        /**
         * Check an array or object contains some of the given keys.
         *
         * If a callback is supplied it is called for each entry in the array or object and passed three parameters that can be used to validate the entry:
         *
         *  - `$value` - The current value.
         *  - `$key` - The current key.
         *  - `$array_or_object` - The array or object being checked.
         *
         * @param array|object $array_or_object The array or object to interrogate.
         * @param (string|int)[] $keys The array of array keys or object properties to check.
         * @param ?callable $callback Optional. If supplied and a key exists this callback can be used to validate the value. Default `null`.
         *
         * @return bool True if any of the keys exist, otherwise false.
         *
         * @since 1.0.0
         */
        public static function some_keys( $array_or_object, array $keys, ?callable $callback = null ) : bool {
            $has_callback = is_callable( $callback );
            return self::array_some( $keys, function ( $key ) use ( $array_or_object, $has_callback, $callback ) {
                $key_exists = self::has_key( $array_or_object, $key );
                if ( $key_exists && $has_callback ) {
                    $value = self::get_key( $array_or_object, $key );
                    return call_user_func( $callback, $value, $key, $array_or_object );
                }
                return $key_exists;
            } );
        }

        //endregion

        //region Array - functions for dealing with array values

        /**
         * Check if at least one element in an array satisfies the given callback.
         *
         * The callback is supplied three parameters:
         *
         *  - `$value` - The iterated value.
         *  - `$key`   - The iterated key.
         *  - `$array` - The array being iterated.
         *
         * @param array $array The array to check.
         * @param callable $callback The callback function to apply to each element.
         *
         * @return bool True if at least one element satisfies the callback, otherwise false.
         *
         * @since 1.0.0
         */
        static function array_some( array $array, callable $callback ) : bool {
            foreach ( $array as $key => $value ) {
                if ( call_user_func( $callback, $value, $key, $array ) ) {
                    return true;
                }
            }
            return false;
        }

        /**
         * Check if every element in an array satisfies the given callback.
         *
         * The callback is supplied three parameters:
         *
         *  - `$value` - The iterated value.
         *  - `$key`   - The iterated key.
         *  - `$array` - The array being iterated.
         *
         * @param array $array The array to check.
         * @param callable $callback The callback function to apply to each element.
         *
         * @return bool True if every element satisfies the callback function, otherwise false.
         *
         * @since 1.0.0
         */
        static function array_every( array $array, callable $callback ) : bool {
            foreach ( $array as $key => $value ) {
                if ( ! call_user_func( $callback, $value, $key, $array ) ) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Find the first element in an array that satisfies the given callback.
         *
         * The callback is supplied three parameters:
         *
         *  - `$value` - The iterated value.
         *  - `$key`   - The iterated key.
         *  - `$array` - The array being iterated.
         *
         * @param array $array The array to search.
         * @param callable $callback The callback function to apply to each element.
         * @param mixed $default Optional. The value to return if no element was found. Default `null`.
         *
         * @return mixed The first element that satisfies the callback function, otherwise the default value.
         *
         * @since 1.0.0
         */
        static function array_find( array $array, callable $callback, $default = null ) {
            foreach ( $array as $key => $value ) {
                if ( call_user_func( $callback, $value, $key, $array ) ) {
                    return $value;
                }
            }
            return $default;
        }

        /**
         * Find the key of the first element in an array that satisfies the given callback.
         *
         * The callback is supplied three parameters:
         *
         *  - `$value` - The iterated value.
         *  - `$key`   - The iterated key.
         *  - `$array` - The array being iterated.
         *
         * @param array $array The array to search.
         * @param callable $callback The callback function to apply to each element.
         * @param mixed $default Optional. The value to return if no key was found. Default `null`.
         *
         * @return mixed The key of the first element that satisfies the callback function, otherwise the default value.
         *
         * @since 1.0.0
         */
        static function array_find_key( array $array, callable $callback, $default = null ) {
            foreach ( $array as $key => $value ) {
                if ( call_user_func( $callback, $value, $key, $array ) ) {
                    return $key;
                }
            }
            return $default;
        }

        /**
         * Map each element of an array to the return value of the given callback.
         *
         * The callback is supplied three parameters:
         *
         *  - `$value` - The iterated value.
         *  - `$key`   - The iterated key.
         *  - `$array` - The array being iterated.
         *
         * @param array $array The array to map.
         * @param callable $callback The callback function to apply to each element.
         *
         * @return array An array containing the mapped elements.
         *
         * @since 1.0.0
         */
        static function array_map( array $array, callable $callback ) : array {
            $result = [];
            foreach ( $array as $key => $value ) {
                $result[ $key ] = call_user_func( $callback, $value, $key, $array );
            }
            return $result;
        }

        /**
         * Check if a value is an array.
         *
         * Optionally check if the array is not empty and/or each element satisfies a provided callback.
         *
         * The callback is supplied three parameters:
         *
         *  - `$value` - The iterated value.
         *  - `$key`   - The iterated key.
         *  - `$array` - The array being iterated.
         *
         * @param mixed $value The value to check.
         * @param bool $not_empty Optional. Whether to check if the value is not empty. Default `false`.
         *
         * @return bool True if the value is an array and satisfied any optional checks, otherwise false.
         *
         * @since 1.0.0
         */
        public static function is_array( $value, bool $not_empty = false ) : bool {
            if ( is_array( $value ) ) {
                return ! ( $not_empty && empty( $value ) );
            }
            return false;
        }

        /**
         * Check if a key exists in the given array or object, and that it's value is an array.
         *
         * Optionally check if the array is not empty and/or each element satisfies a provided callback.
         *
         * The callback is supplied three parameters:
         *
         *  - `$value` - The iterated value.
         *  - `$key`   - The iterated key.
         *  - `$array` - The array being iterated.
         *
         * @param array|object $array_or_object The array or object to interrogate.
         * @param string|int $key The array key or object property to check.
         * @param bool $not_empty Optional. Whether to check if the value is not empty. Default `false`.
         *
         * @return bool True if the key exists, is an array and satisfied any optional checks, otherwise false.
         *
         * @since 1.0.0
         */
        public static function is_key_array( $array_or_object, $key, bool $not_empty = false ) : bool {
            return self::is_array( self::get_key( $array_or_object, $key ), $not_empty );
        }

        /**
         * Get the value of a key from the given array or object and ensure it is an array.
         *
         * Optionally check if the array is not empty and/or each element satisfies a provided callback.
         *
         * The callback is supplied three parameters:
         *
         *  - `$value` - The iterated value.
         *  - `$key`   - The iterated key.
         *  - `$array` - The array being iterated.
         *
         * @param array|object $array_or_object The array or object to interrogate.
         * @param string|int $key The array key or object property to fetch.
         * @param array $default Optional. The value to return if the key does not exist or is not an array. Default `array()`.
         *
         * @return array The array value for the key if all checks were satisfied, otherwise the default value.
         *
         * @since 1.0.0
         */
        public static function get_array( $array_or_object, $key, array $default = array() ) : array {
            $value = self::get_key( $array_or_object, $key );
            return is_array( $value ) ? $value : $default;
        }

        //endregion

        //region Bool - functions for dealing with boolean values

        /**
         * Check if a key exists in the given array or object, and that it's value is a bool.
         *
         * @param array|object $array_or_object The array or object to interrogate.
         * @param string|int $key The array key or object property to check.
         *
         * @return bool True if the key exists and is a bool, otherwise false.
         *
         * @since 1.0.0
         */
        public static function is_key_bool( $array_or_object, $key ) : bool {
            return is_bool( self::get_key( $array_or_object, $key ) );
        }

        /**
         * Get the value of a key from the given array or object and ensure it is a bool.
         *
         * @param array|object $array_or_object The array or object to interrogate.
         * @param string|int $key The array key or object property to fetch.
         * @param bool $default Optional. The value to return if the key does not exist or is not a bool. Default `false`.
         *
         * @return bool The bool value for the key, otherwise the default value.
         *
         * @since 1.0.0
         */
        public static function get_bool( $array_or_object, $key, bool $default = false ) : bool {
            $value = self::get_key( $array_or_object, $key );
            return is_bool( $value ) ? $value : $default;
        }

        //endregion

        //region Int - functions for dealing with int values

        /**
         * @param mixed $value The value to check.
         * @param ?int $min Optional. The minimum allowed value. Default `null`.
         * @param ?int $max Optional. The maximum allowed value. Default `null`.
         * @return bool True if the value is greater than or equal to min and/or is smaller than or equal to max, otherwise false.
         */
        public static function is_int_within( $value, ?int $min = null, ?int $max = null ) : bool {
            if ( is_int( $value ) ) {
                return ! ( ( is_int( $min ) && $value < $min ) || ( is_int( $max ) && $value > $max ) );
            }
            return false;
        }

        /**
         * Check if a value is an int.
         *
         * Optionally check if the value satisfies the minimum and maximum constraints.
         *
         * @param mixed $value The value to check.
         * @param bool $not_empty Optional. Whether to check if the value is not empty. Default `false`.
         *
         * @return bool True if the value is an int and satisfied any optional checks, otherwise false.
         *
         * @since 1.0.0
         */
        public static function is_int( $value, bool $not_empty = false ) : bool {
            if ( is_int( $value ) ) {
                return ! ( $not_empty && empty( $value ) );
            }
            return false;
        }

        /**
         * Check if a key exists in the given array or object, and that it's value is an int.
         *
         * Optionally check if the value satisfies the minimum and maximum constraints.
         *
         * @param array|object $array_or_object The array or object to interrogate.
         * @param string|int $key The array key or object property to check.
         * @param bool $not_empty Optional. Whether to check if the value is not empty. Default `false`.
         *
         * @return bool True if the key exists, is an int and satisfied any optional checks, otherwise false.
         *
         * @since 1.0.0
         */
        public static function is_key_int( $array_or_object, $key, bool $not_empty = false ) : bool {
            return self::is_int( self::get_key( $array_or_object, $key ), $not_empty );
        }

        /**
         * Get the value of a key from the supplied array or object and ensure it is an int.
         *
         * Optionally check if the value satisfies the minimum and maximum constraints.
         *
         * @param array|object $array_or_object The array or object to interrogate.
         * @param string|int $key The array key or object property to fetch.
         * @param int $default Optional. The value to return if the key does not exist, is not an int, or does not satisfy the minimum and/or maximum values. Default `0`.
         *
         * @return int The int value for the key if all checks were satisfied, otherwise the default value.
         *
         * @since 1.0.0
         */
        public static function get_int( $array_or_object, $key, int $default = 0 ) : int {
            $value = self::get_key( $array_or_object, $key );
            return is_int( $value ) ? $value : $default;
        }

        //endregion

        //region String - functions for dealing with string values

        /**
         * Check if a value is a string.
         *
         * Optionally check if the string is not empty and/or the value exists within an allowed list of possible values.
         *
         * @param mixed $value The value to check.
         * @param bool $not_empty Optional. Whether to check if the value is not empty. Default `false`.
         *
         * @return bool True if the value is a string and satisfied any optional checks, otherwise false.
         *
         * @since 1.0.0
         */
        public static function is_string( $value, bool $not_empty = false ) : bool {
            if ( is_string( $value ) ) {
                return ! ( $not_empty && empty( $value ) );
            }
            return false;
        }

        /**
         * Check if a key exists in the given array or object, and that it's value is a string.
         *
         * Optionally check if the string is not empty and/or the value exists within an allowed list of possible values.
         *
         * @param array|object $array_or_object The array or object to interrogate.
         * @param string|int $key The array key or object property to check.
         * @param bool $not_empty Optional. Whether to check if the value is not empty. Default `false`.
         *
         * @return bool True if the key exists, is a string, and satisfied any optional checks, otherwise false.
         *
         * @since 1.0.0
         */
        public static function is_key_string( $array_or_object, $key, bool $not_empty = false ) : bool {
            return self::is_string( self::get_key( $array_or_object, $key ), $not_empty );
        }


        /**
         * Get the value of a key from the given array or object and ensure it is a string.
         *
         * @param array|object $array_or_object The array or object to interrogate.
         * @param string|int $key The array key or object property to check.
         * @param string $default Optional. The value to return if the key does not exist, is not a string, is empty or does not exist within the allowed list. Default `''`.
         *
         * @return string The string value for the key if all checks were satisfied, otherwise the default value.
         *
         * @since 1.0.0
         */
        public static function get_string( $array_or_object, $key, string $default = '' ) : string {
            $value = self::get_key( $array_or_object, $key );
            return is_string( $value ) ? $value : $default;
        }

        //endregion

        //region Post Type - functions to help with various post type related things :P

        /**
         * Check if the current page is the editor for the specified post type(s).
         *
         * @param string|string[] $post_type The post type(s) name.
         *
         * @return bool True if the current page is the editor for the specified post type(s), otherwise false.
         *
         * @since 1.0.0
         */
        static function is_post_type_editor( $post_type ) : bool {
            if ( is_admin() ) {
                $current_post_type = null;
                global $pagenow;
                if ( $pagenow == "post-new.php" && isset( $_GET['post_type'] ) ) {
                    $current_post_type = $_GET['post_type'];
                }
                if ( $pagenow == "post.php" ) {
                    $post_id = null;
                    if ( isset( $_GET['post'] ) ) $post_id = (int) $_GET['post'];
                    elseif ( isset( $_GET['post_ID'] ) ) $post_id = (int) $_GET['post_ID'];
                    if ( is_int( $post_id ) ) {
                        $post_obj = get_post( $post_id );
                        if ( $post_obj instanceof WP_Post ) {
                            $current_post_type = $post_obj->post_type;
                        }
                    }
                }
                if ( is_string( $current_post_type ) ) {
                    if ( is_string( $post_type ) ) {
                        return $current_post_type === $post_type;
                    }
                    if ( is_array( $post_type ) ) {
                        return in_array( $current_post_type, $post_type );
                    }
                }
            }
            return false;
        }

        /**
         * Register a block from the metadata stored in the `block.json` file for a specific post type(s).
         *
         * This function registers the block for all pages/posts but when in the admin it only registers the block for the specific post type editor page.
         *
         * This effectively allows the blocks to appear on any page or post on the frontend, but it will only appear in the specific post type editor page.
         *
         * @param string|string[] $post_type The post type(s) to register the block for.
         * @param string $file_or_folder Path to the JSON file with metadata definition for the block or path to the folder where the `block.json`
         *                                        file is located. If providing the path to a JSON file, the filename must end with `block.json`.
         * @param array $args Optional. Array of block type arguments. Accepts any public property of {@link WP_Block_Type}.
         *                                        See `WP_Block_Type::__construct()` for information on accepted arguments.
         *                                        Default `array()`.
         *
         * @return WP_Block_Type|false The registered block type on success, or false on failure.
         *
         * @since 1.0.0
         */
        static function register_post_type_block( $post_type, string $file_or_folder, array $args = array() ) {
            if ( is_admin() && ! self::is_post_type_editor( $post_type ) ) return false;
            return register_block_type_from_metadata( $file_or_folder, $args );
        }

        /**
         * @param string|string[] $post_type
         * @param array{file_or_folder:string,args:array}[] $blocks
         * @return false|WP_Block_Type[]
         */
        static function register_post_type_blocks( $post_type, array $blocks ) {
            $block_types = [];
            foreach ( $blocks as $block ) {
                $file_or_folder = self::get_string( $block, 'file_or_folder' );
                if ( ! empty( $file_or_folder ) ) {
                    $result = self::register_post_type_block( $post_type, $file_or_folder, self::get_array( $block, 'args' ) );
                    if ( $result instanceof WP_Block_Type ) {
                        $block_types[] = $result;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }
            return ! empty( $block_types ) ? $block_types : false;
        }

        //endregion

        /**
         * Prepare an array of values for use within a SQL queries IN statement.
         *
         * @param (string|float|int)[] $array
         * @return string|null
         * @example
         * $ids = [ 1, 2, 3 ];
         * $prepared_ids = Utils::wpdb_prepare_in( $ids ); // => '(%d,%d,%d)'
         * $query = $wpdb->prepare( "SELECT * FROM table WHERE id IN {$prepared_ids}", $ids );
         */
        static function wpdb_prepare_in( array $array ) : ?string {
            if ( ! empty( $array ) ) {
                $placeholders = array_map( function ( $item ) {
                    return is_string( $item ) ? '%s' : ( is_float( $item ) ? '%f' : ( is_int( $item ) ? '%d' : '' ) );
                }, $array );
                return '(' . join( ',', $placeholders ) . ')';
            }
            return null;
        }

        /**
         * Convert an associative array of CSS styles, where the keys are CSS selectors and the values are
         * arrays of CSS property/value pairs, to a string.
         *
         * @param array $styles The array of styles to convert.
         * @param bool $prettify Optional. Whether to insert indentation and new lines into the generated output.
         *                        Default `false`.
         *
         * @return false|string The string representation of the given CSS styles, otherwise false.
         *
         * @example Input/Output
         * $input = array(
         *     '.my-class' => array(
         *         'color' => '#F00',
         *         'background-color' => '#000'
         *     )
         * );
         * $output = Utils::to_css_text( $styles );
         * // $output => '.my-class { color: #F00; background-color: #000; }'
         *
         * @since 1.0.0
         */
        static function to_css_text( array $styles, bool $prettify = false ) {
            if ( ! empty( $styles ) ) {
                $indent = $prettify ? "\t" : '';
                $new_line = $prettify ? "\n" : '';
                $css_text = '';
                foreach ( $styles as $selector => $props ) {
                    if ( self::is_string( $selector, true ) && self::is_array( $props, true ) ) {
                        $css_text .= $selector . ' { ' . $new_line;
                        foreach ( $props as $name => $value ) {
                            if ( self::is_string( $name, true ) && ( self::is_string( $value, true ) || is_int( $value ) ) ) {
                                $css_text .= $indent . $name . ': ' . $value . '; ' . $new_line;
                            }
                        }
                        $css_text .= '}' . $new_line;
                    }
                }
                return $css_text !== '' ? $css_text : false;
            }
            return false;
        }

        static function make_identifier( string $name ): string {
            $name = preg_replace( '/\W/', '_', $name );
            $name = preg_replace( '/^(\d)/', '$$1', $name );
            return strtoupper( $name );
        }

        /**
         * Convert the given object name and array of data to an HTML decoded, JSON encoded, JavaScript script.
         *
         * @param string|string[] $object_name The name of the object to output. If supplied an array of strings, no `var` or `const` is
         *                                     output as the target is assumed to be a pre-existing global object. You are responsible
         *                                     for ensuring the global object exists, a JavaScript error will be thrown if it does not.
         * @param array $data An array of data to be converted to HTML friendly JSON.
         * @param bool $prefer_const Optional. If supplied this will use `const` instead of `var` when generating the script. Default `false`.
         *
         * @return false|string The string representation of the given object name and data, otherwise false.
         *
         * @since 1.0.0
         */
        static function to_js_script( $object_name, array $data, bool $prefer_const = false ) {
            if ( ! empty( $object_name ) && ! empty( $data ) ) {
                if ( is_string( $object_name ) ) {
                    $object_name = array( $object_name );
                }
                foreach ( $object_name as $index => $name_part ) {
                    if ( $index === 0 ) {
                        // Make sure the first name part is an acceptable JavaScript identifier.
                        if ( ! preg_match( '/[a-zA-Z0-9_]+/', $name_part ) ) {
                            return false;
                        }
                    } else {
                        // For additional parts we only care that the name is a non-empty string as the value is set
                        // using JavaScript bracket notation: i.e. someObject[ 'name_path' ][ 'name' ] = data.
                        // When setting properties this way JavaScript pretty much allows any character in the name.
                        if ( ! self::is_string( $name_part, true ) ) {
                            return false;
                        }
                    }
                }

                // Prepare the data for JSON encoding, this copies the logic from wp_localize_script
                foreach ( $data as $key => $value ) {
                    if ( ! is_string( $value ) ) {
                        continue;
                    }
                    $data[ $key ] = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );
                }
                $json = wp_json_encode( $data );
                if ( empty( $json ) ) {
                    return false;
                }

                // Handle a name that contains multiple parts i.e. the value is being set on a pre-existing global object.
                if ( count( $object_name ) > 1 ) {
                    $name = array_shift( $object_name );
                    $path = implode( '"]["', $object_name );
                    return "{$name}[\"$path\"] = $json;";
                }
                // Create a new global variable for the data
                $name = implode( '.', $object_name );
                if ( $prefer_const ) {
                    return "const $name = $json;";
                } else {
                    return "var $name = $json;";
                }
            }
            return false;
        }

//        static function get_kses_post_with_custom_element_attributes() : array {
//            $kses_defaults = wp_kses_allowed_html( 'post' );
//
//            foreach ( $kses_defaults as $tag => $attr ) {
//                $kses_defaults[$tag]['is'] = true;
//                $kses_defaults[$tag]['slot'] = true;
//            }
//
//            return $kses_defaults;
//        }
//
//        static function kses_svg() {
//            $definition = array(
//
//            );
//        }
    }

}