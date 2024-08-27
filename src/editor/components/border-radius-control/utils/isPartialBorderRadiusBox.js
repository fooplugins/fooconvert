import { someKeys } from "@steveush/utils";
import { BORDER_RADIUS_BOX_DEFINITION } from "./isBorderRadiusBox";

/**
 * Check if a value contains at least one {@link BorderRadiusBox|`BorderRadiusBox`} key.
 *
 * @param {any} value - The value to check.
 * @returns {value is Partial<BorderRadiusBox>} - `true` if the value contains at least one {@link BorderRadiusBox|`BorderRadiusBox`} key, otherwise `false`.
 */
const isPartialBorderRadiusBox = value => someKeys( value, BORDER_RADIUS_BOX_DEFINITION );

export default isPartialBorderRadiusBox;