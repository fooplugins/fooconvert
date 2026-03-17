export const KNOWN_VALIDATION_ATTR = [ 'required', 'minlength', 'maxlength', 'min', 'max', 'step', 'type', 'pattern' ];

/**
 *
 * @param {HTMLElement} element
 * @returns {Array<{name:string,value:string}>}
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