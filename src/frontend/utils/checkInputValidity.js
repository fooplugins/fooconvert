import getValidationAttributes from "./getValidationAttributes";
import { isArray } from "@steveush/utils";
import setValidationAttributes from "./setValidationAttributes";

/**
 *
 * @param {HTMLInputElement} input
 * @param {Array<{name:string,value:(string|boolean|number)}>} [attributes]
 * @returns {boolean}
 */
const checkInputValidity = ( input, attributes = [] ) => {
    if ( !isArray( attributes, true ) ) {
        attributes = getValidationAttributes( input );
    }
    const _input = input.ownerDocument.createElement( 'input' );
    _input.type = input.type;
    _input.value = input.value;
    setValidationAttributes( _input, attributes );
    return _input.checkValidity();
};

export default checkInputValidity;