import stringOrUndefined from "./stringOrUndefined";
import isClickableData from "./isClickableData";
import { cleanObject } from "@steveush/utils";

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
        data = cleanObject( event.detail.data )
        if ( isClickableData( data ) ) {
            // noinspection JSValidateTypes
            return data;
        }
    }
    return undefined;
};

export default getClickableData;