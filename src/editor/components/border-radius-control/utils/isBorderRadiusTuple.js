import { isArray } from "@steveush/utils";
import { is_$string } from "../../../utils";

/**
 * A tuple containing the `topLeft`, `topRight`, `bottomRight` and `bottomLeft` border radius values., in that order.
 *
 * @typedef {[ topLeft: string | undefined, topRight: string | undefined, bottomRight: string | undefined, bottomLeft: string | undefined ]} BorderRadiusTuple
 */

/**
 *
 * @type {[ (( value: any ) => boolean), (( value: any ) => boolean), (( value: any ) => boolean), (( value: any ) => boolean) ]}
 */
export const BORDER_RADIUS_TUPLE_DEFINITION = [ is_$string, is_$string, is_$string, is_$string ];

/**
 *
 * @type {BorderRadiusTuple}
 */
export const BORDER_RADIUS_TUPLE_DEFAULTS = [ undefined, undefined, undefined, undefined ];

/**
 * Check if a value is a {@link BorderRadiusTuple|`BorderRadiusTuple`}.
 *
 * @param {any} value - The value to check.
 * @returns {value is BorderRadiusTuple} - `true` if the value is a {@link BorderRadiusTuple|`BorderRadiusTuple`}, otherwise `false`.
 */
const isBorderRadiusTuple = value => isArray( value, true, ( value, i ) => {
    return i < BORDER_RADIUS_TUPLE_DEFINITION.length && BORDER_RADIUS_TUPLE_DEFINITION[ i ]( value );
} ) && value.length === BORDER_RADIUS_TUPLE_DEFINITION.length;

export default isBorderRadiusTuple;