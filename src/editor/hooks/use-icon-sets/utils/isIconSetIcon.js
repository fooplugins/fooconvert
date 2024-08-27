import { hasKeys } from "@steveush/utils";
import { isStringNotEmpty } from "../../../utils";
import { isValidElement } from "@wordpress/element";

/**
 * @typedef {{ slug: string, name: string, svg: import('react').ReactSVGElement, props?: {[key: string]: any} }} IconSetIcon
 */

/**
 *
 * @type {Record<string, ( ( value: any ) => boolean )>}
 */
export const ICON_SET_ICON_DEFINITION = {
    slug: isStringNotEmpty,
    name: isStringNotEmpty,
    svg: isValidElement
};

/**
 *
 * @param {any} value
 * @returns {value is IconSetIcon}
 */
const isIconSetIcon = value => hasKeys( value, ICON_SET_ICON_DEFINITION );

export default isIconSetIcon;