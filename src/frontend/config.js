import { hasKeys, isArray, isPlainObject, isString } from "@steveush/utils";

const objectName = 'FOOCONVERT_CONFIG';
/**
 * @typedef FooConvertConfiguration
 * @property {{ url: string, nonce: string }} endpoint
 * @property {string[]} widgets
 */

/**
 * @type {FooConvertConfiguration}
 */
const configuration = globalThis[ objectName ];
if ( !isPlainObject( configuration ) ) {
    throw new Error( `FOOCONVERT_ERROR: The global "${ objectName }" variable is not an object.` );
}
if ( !hasKeys( configuration?.endpoint, { url: isString, nonce: isString } ) ) {
    throw new Error( `FOOCONVERT_ERROR: The global "${ objectName }" object is missing the required "endpoint" object property.` );
}
if ( !isArray( configuration?.widgets, true, value => isString( value ) ) ) {
    throw new Error( `FOOCONVERT_ERROR: The global "${ objectName }" object is missing the required "widgets" string[] property.` );
}

export default configuration;