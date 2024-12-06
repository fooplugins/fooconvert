import { isString } from "@steveush/utils";

const isSelector = value => {
    if ( isString( value, true ) ) {
        try {
            globalThis.document.querySelector( value );
            return true;
        } catch ( err ) {
            /* Nom nom nom */
        }
    }
    return false;
};

export default isSelector;