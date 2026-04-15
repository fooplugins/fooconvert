import stringOrUndefined from "./stringOrUndefined";
import { cleanObject } from "@steveush/utils";

/**
 * The data sent to the server for logged click events.
 *
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
 * @param {HTMLElement} element
 * @returns {ClickableData|undefined}
 */
const getClickableData = element => {
    if ( element instanceof HTMLElement ) {
        const { tagName, classList, id, name, href, target, rel } = element;
        let data = {
            tagName: tagName.toLowerCase(),
            classList: [ ...classList ],
            id: stringOrUndefined( id ),
            name: stringOrUndefined( name ),
            href: stringOrUndefined( href ),
            target: stringOrUndefined( target ),
            rel: stringOrUndefined( rel ),
        };
        const event = new CustomEvent( 'fooconvert.clickable-data', {
            detail: { data },
            bubbles: true,
            composed: true
        } );
        element.dispatchEvent( event );
        data = cleanObject( event.detail.data );
        if ( data ) {
            return data;
        }
    }
    return undefined;
};

export default getClickableData;
