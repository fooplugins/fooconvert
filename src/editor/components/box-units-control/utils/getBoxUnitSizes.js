import makeBoxUnit from "./makeBoxUnit";

/**
 * An object representing a value which contains a top, right, bottom and left string properties.
 *
 * @typedef {{top: string, right: string, bottom: string, left: string}} FCBoxUnitSizes
 */

/**
 *
 * @param {*} value
 * @param {string|Partial<FCBoxUnitSizes>} [defaults]
 * @returns {FCBoxUnitSizes}
 */
const getBoxUnitSizes = ( value, defaults ) => {
    const d = Object.assign( {
        top: "0px",
        right: "0px",
        bottom: "0px",
        left: "0px"
    }, makeBoxUnit( defaults ) );
    const sizes = makeBoxUnit( value );
    if ( sizes !== null && typeof sizes === "object" ) {
        const { top = d.top, right = d.right, bottom = d.bottom, left = d.left } = sizes;
        return { top, right, bottom, left };
    }
    return d;
};

export default getBoxUnitSizes;