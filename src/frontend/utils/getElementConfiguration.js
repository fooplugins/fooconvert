import { isString } from "@steveush/utils";

/**
 * Get the element configuration data for the given id.
 *
 * @param {string} id - The id of the element to retrieve the data for.
 * @returns {undefined|*} The element configuration data, otherwise `undefined`.
 */
const getElementConfiguration = id => {
    if ( isString( id ) ) {
        const identifier = id.replaceAll( /\W/g, '_' ).replace( /^(\d)/, '$$1' ).toUpperCase();
        if ( Object.hasOwn( globalThis, identifier ) ) {
            return globalThis[ identifier ];
        }
    }
    return undefined;
};

export default getElementConfiguration;