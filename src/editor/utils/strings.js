import { isString, pluck } from "@steveush/utils";

/**
 * Plucks all string properties from an object.
 *
 * @param {Record<string, any>} obj - The object to pluck values from.
 * @returns {Record<string, string>} - An object containing all found string properties.
 */
const strings = ( obj ) => {
    return pluck( obj, value => isString( value, true ) );
};

export default strings;