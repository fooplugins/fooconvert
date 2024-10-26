import { isString } from "@steveush/utils";

/**
 * Return a non-empty `string` or `undefined`.
 *
 * @remarks
 * A `string` is considered empty if it:
 * - has zero length
 * - contains only whitespace
 *
 * @param {string|any} value - The value to check.
 * @returns {string|undefined} A non-empty `string`, otherwise `undefined`.
 * @see is_$string
 * @example
 * $string( false ); // => undefined
 * $string( '' ); // => undefined
 * $string( ' \n\t' ); // => undefined
 * $string( 'not-empty' ); // => 'not-empty'
 */
const $string = value => isString( value, true ) ? value : undefined;

export default $string;