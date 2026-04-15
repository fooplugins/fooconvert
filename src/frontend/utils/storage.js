/**
 * Get a browser storage object by name, or `null` if it is unavailable.
 *
 * @param {string} storageName
 * @returns {Storage|null}
 */
const getBrowserStorage = storageName => {
    try {
        return globalThis?.[ storageName ] ?? null;
    } catch {
        return null;
    }
};

/**
 * Read an item from browser storage.
 *
 * @param {string} storageName
 * @param {string} key
 * @returns {string|null}
 */
export const readStorageItem = ( storageName, key ) => {
    const storage = getBrowserStorage( storageName );

    if ( !storage ) {
        return null;
    }

    try {
        return storage.getItem( key );
    } catch {
        return null;
    }
};

/**
 * Write an item to browser storage.
 *
 * @param {string} storageName
 * @param {string} key
 * @param {string} value
 * @returns {boolean}
 */
export const writeStorageItem = ( storageName, key, value ) => {
    const storage = getBrowserStorage( storageName );

    if ( !storage ) {
        return false;
    }

    try {
        storage.setItem( key, value );
        return true;
    } catch {
        return false;
    }
};

/**
 * Remove an item from browser storage.
 *
 * @param {string} storageName
 * @param {string} key
 * @returns {boolean}
 */
export const removeStorageItem = ( storageName, key ) => {
    const storage = getBrowserStorage( storageName );

    if ( !storage ) {
        return false;
    }

    try {
        storage.removeItem( key );
        return true;
    } catch {
        return false;
    }
};

/**
 * List the keys in browser storage.
 *
 * @param {string} storageName
 * @returns {string[]}
 */
export const listStorageKeys = storageName => {
    const storage = getBrowserStorage( storageName );

    if ( !storage ) {
        return [];
    }

    try {
        return Object.keys( storage );
    } catch {
        return [];
    }
};

/**
 * Parse a JSON string from browser storage.
 *
 * @param {string|null} value
 * @returns {unknown}
 */
export const parseStorageJSON = value => {
    if ( typeof value !== 'string' || value.length === 0 ) {
        return undefined;
    }

    try {
        return JSON.parse( value );
    } catch {
        return undefined;
    }
};
