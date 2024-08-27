import { hasKeys } from "@steveush/utils";
import { is_$string } from "../../../utils";

/**
 * An object containing the `topLeft`, `topRight`, `bottomRight` and `bottomLeft` border radius values.
 *
 * @typedef {{topLeft: string | undefined, topRight: string | undefined, bottomRight: string | undefined, bottomLeft: string | undefined}} BorderRadiusBox
 */

/**
 * A mapping of key to type checks that define a {@link BorderRadiusBox|`BorderRadiusBox`} object.
 *
 * @type {Record<string, ((value: any) => boolean)>}
 */
export const BORDER_RADIUS_BOX_DEFINITION = {
    topLeft: is_$string,
    topRight: is_$string,
    bottomRight: is_$string,
    bottomLeft: is_$string
};

/**
 * The default values for a {@link BorderRadiusBox|`BorderRadiusBox`} object.
 *
 * @type {BorderRadiusBox}
 */
export const BORDER_RADIUS_BOX_DEFAULTS = {
    topLeft: undefined,
    topRight: undefined,
    bottomRight: undefined,
    bottomLeft: undefined
};

/**
 * Check if a value is a {@link BorderRadiusBox|`BorderRadiusBox`} object.
 *
 * @param {any} value - The value to check.
 * @returns {value is BorderRadiusBox} - `true` if the value is a {@link BorderRadiusBox|`BorderRadiusBox`} object, otherwise `false`.
 */
const isBorderRadiusBox = value => hasKeys( value, BORDER_RADIUS_BOX_DEFINITION );

export default isBorderRadiusBox;