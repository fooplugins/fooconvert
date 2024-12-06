import { hasKeys, isArray } from "@steveush/utils";
import isStringNotEmpty from "./isStringNotEmpty";
import isStringOrUndefined from "./isStringOrUndefined";

/**
 * The data sent to the server for logged click events.
 * @typedef ClickableData
 * @property {string} tagName - The lowercase tagName for the element.
 * @property {string[]} classList - A string array of CSS classes for the element.
 * @property {string} [id] - The id for the element, if one exists.
 * @property {string} [name] - The name for the element, if one exists.
 * @property {string} [href] - The href for the element, if one exists.
 * @property {string} [target] - The target for the element, if one exists.
 * @property {string} [rel] - The rel for the element, if one exists.
 */

/**
 *
 * @param {*} data
 * @returns {data is ClickableData}
 */
const isClickableData = data => hasKeys( data, {
    tagName: isStringNotEmpty,
    classList: value => isArray( value, false, isStringNotEmpty ),
    id: isStringOrUndefined,
    name: isStringOrUndefined,
    href: isStringOrUndefined,
    target: isStringOrUndefined,
    rel: isStringOrUndefined
} );

export default isClickableData;