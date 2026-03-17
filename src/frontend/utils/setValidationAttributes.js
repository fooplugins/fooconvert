const BOOL_ATTR_TRUE = [ undefined, 1, '1', true, 'true' ];

/**
 *
 * @param {HTMLElement} element
 * @param {Array<{name:string,value?:(string|boolean|number)}>} attributes
 */
const setValidationAttributes = ( element, attributes ) => {
    attributes.forEach( ( { name, value } ) => {
        if ( name === 'required' ) {
            element.toggleAttribute( name, BOOL_ATTR_TRUE.includes( value ) );
        } else {
            element.setAttribute( name, `${ value }` );
        }
    } );
};

export default setValidationAttributes;