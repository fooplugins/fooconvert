import { isString } from "@steveush/utils";

const GRADIENT_REGEX = /^(?:repeating-)?(linear|radial|conic)-gradient\(/;

/**
 * Check if a value is a possible CSS gradient.
 *
 * @remarks
 * This method does not validate the value, it only tests that the value is a string, and that it starts with one of the CSS gradient function names.
 *
 * @param {any} value - The value to check.
 * @returns {boolean} - `true` if the value could be a CSS gradient, otherwise `false`.
 * @see https://developer.mozilla.org/en-US/docs/Web/CSS/gradient
 */
const isPossibleGradient = value => isString( value, true ) && GRADIENT_REGEX.test( value );

export default isPossibleGradient;