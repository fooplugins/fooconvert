import { isString } from "@steveush/utils";

/**
 * Block settings exposed through the global block registration namespace.
 *
 * @typedef {{[key: string]: unknown; variations?: WPBlockVariation[]; data?: unknown}} BlockSettings
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
