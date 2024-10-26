import { hasKeys, isArray } from "@steveush/utils";
import { isStringNotEmpty } from "../../../utils";
import isIconSetIcon from "./isIconSetIcon";

/**
 * @typedef {{ name: string, icons: IconSetIcon[] }} IconSet
 */

/**
 *
 * @type {Record<string, ((value: any)=>boolean)>}
 */
export const ICON_SET_DEFINITION = {
    name: isStringNotEmpty,
    icons: value => isArray( value, false, isIconSetIcon )
};

/**
 *
 * @param {any} value
 * @returns {value is IconSetIcon}
 */
const isIconSet = value => hasKeys( value, ICON_SET_DEFINITION );

export default isIconSet;