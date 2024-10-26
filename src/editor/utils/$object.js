import { cleanObject } from "@steveush/utils";

/**
 * Apply updates to an object where both the value or updates could be undefined.
 *
 * @param {object|undefined} value
 * @param {object|undefined} updates
 *
 * @returns {object|undefined}
 */
const $object = ( value, updates ) => {
    return typeof updates === 'object' ? cleanObject( {
        ...( typeof value === 'object' ? value : {} ),
        ...updates
    } ) : undefined;
};

export default $object;