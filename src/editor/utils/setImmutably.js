/**
 * Immutably sets a value inside an object. Like `lodash#set`, but returning a
 * new object. Treats nullish initial values as empty objects. Clones any
 * nested objects. Supports arrays, too.
 *
 * @param {Object}              object Object to set a value in.
 * @param {number|string|Array} path   Path in the object to modify.
 * @param {*}                   value  New value to set.
 * @return {Object} Cloned object with the new value set.
 */
const setImmutably = ( object, path, value ) => {
    // Normalize path
    path = Array.isArray( path ) ? [ ...path ] : [ path ];

    // Shallowly clone the base of the object
    object = Array.isArray( object ) ? [ ...object ] : { ...object };

    const leaf = path.pop();

    // Traverse object from root to leaf, shallowly cloning at each level
    let prev = object;
    for ( const key of path ) {
        const lvl = prev[ key ];
        prev = prev[ key ] = Array.isArray( lvl ) ? [ ...lvl ] : { ...lvl };
    }

    prev[ leaf ] = value;

    return object;
};

export default setImmutably;