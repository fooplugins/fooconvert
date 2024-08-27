import { isString } from "@steveush/utils";
import isPartialBorderRadiusBox from "./isPartialBorderRadiusBox";

/**
 * A `string` or partial {@link BorderRadiusBox|`BorderRadiusBox`} object representing a {@link BorderRadiusControl} value.
 *
 * @typedef {(string|Partial<BorderRadiusBox>)} BorderRadiusValue
 */

/**
 * Check if a value is a {@link BorderRadiusValue|`BorderRadiusValue`}.
 *
 * @param {*} value - The value to check.
 * @returns {value is BorderRadiusValue} - `true` if the value is a {@link BorderRadiusValue|`BorderRadiusValue`}, otherwise `false`.
 */
const isBorderRadius = value => isString( value, true ) || isPartialBorderRadiusBox( value );

export default isBorderRadius;