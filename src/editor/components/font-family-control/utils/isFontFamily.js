import { hasKeys, isString, isUndefined } from "@steveush/utils";

/**
 * @param {unknown} value
 * @returns {value is {fontFamily: string}}
 */
const isFontFamilyStyle = value => isUndefined( value ) || hasKeys( value, 'fontFamily' );

/**
 * @param {unknown} value
 * @returns {value is {key: string, name: string, style?: {fontFamily: string}}}
 */
const isFontFamily = ( value ) => hasKeys( value, { key: isString, name: isString, style: isFontFamilyStyle } );

export default isFontFamily;
