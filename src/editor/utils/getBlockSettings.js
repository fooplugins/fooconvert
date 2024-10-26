import { isString } from "@steveush/utils";

/**
 * @typedef {{[key: string]: any; variations?: WPBlockVariation[]; data?: any}} BlockSettings
 */

/**
 * Get the settings for the given dynamic block name.
 *
 * @param {string} blockName - The name of the block to retrieve the data for.
 * @returns {BlockSettings} The block settings.
 */
const getBlockSettings = blockName => {
    if ( isString( blockName ) ) {
        const identifier = blockName.replaceAll( /\W/g, '_' ).toUpperCase();
        if ( Object.hasOwn( globalThis, identifier ) ) {
            return globalThis[ identifier ];
        }
    }
    return {};
};

export default getBlockSettings;