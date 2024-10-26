import isPossibleBorderValue from "./isPossibleBorderValue";
import isPossibleBorderBox from "./isPossibleBorderBox";

/**
 * A partial {@link FCBorderValue|border value} or {@link FCBorderBox|border box} object representing a border value.
 *
 * @typedef {(Partial<FCBorderValue>|Partial<FCBorderBox>)} FCBorder
 */

/**
 * Check if a value is a {@link FCBorderRadius|border radius}.
 *
 * @param {*} value - The value to check.
 * @returns {value is BorderRadiusValue} - `true` if the value is either a string or a partial {@link FCBorderRadiusBox|border radius box} object, otherwise `false`.
 */
/**
 *
 * @param value
 * @returns {value is Partial<FCBorderValue>|value is Partial<FCBorderBox>}
 */
const isBorder = value => isPossibleBorderValue( value ) || isPossibleBorderBox( value );

export default isBorder;