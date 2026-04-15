import { cleanObject } from "@steveush/utils";

/**
 * Apply updates to an object where both the value or updates could be undefined.
 *
 * @template {Record<string, unknown>} T
 * @param {T|undefined} value
 * @param {T|undefined} updates
 *
 * @returns {T|undefined}
 */
const $object = ( value, updates ) => {
    return typeof updates === 'object' ? cleanObject( {
        ...( typeof value === 'object' ? value : {} ),
        ...updates
    } ) : undefined;
};

export default $object;
