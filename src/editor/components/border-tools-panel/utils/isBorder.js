import isPossibleBorderValue from "./isPossibleBorderValue";
import isPossibleBorderBox from "./isPossibleBorderBox";

/**
 * A partial {@link FCBorderValue|border value} or {@link FCBorderBox|border box} object representing a border value.
 *
 * @typedef {(Partial<FCBorderValue>|Partial<FCBorderBox>)} FCBorder
 */

/**
 * Check if a value is a border value or border box.
 *
 * @param {unknown} value - The value to check.
 * @returns {value is FCBorder} - `true` if the value is either a border value or border box, otherwise `false`.
 */
const isBorder = value => isPossibleBorderValue( value ) || isPossibleBorderBox( value );

export default isBorder;
