export const KNOWN_VALIDATION_ATTR = [ 'required', 'minlength', 'maxlength', 'min', 'max', 'step', 'type', 'pattern' ];

/**
 * Validation attributes extracted from an input element.
 *
 * @typedef {{name: string, value: string}} ValidationAttribute
 */

/**
 *
 * @param {HTMLElement} element
 * @returns {ValidationAttribute[]}
 */
const getValidationAttributes = element => {
    return Array.from( element.attributes ).reduce( ( acc, attr ) => {
        if ( KNOWN_VALIDATION_ATTR.includes( attr.name ) ) {
            const { name, value } = attr;
            acc.push( { name, value } );
        }
        return acc;
    }, [] );
};

export default getValidationAttributes;
