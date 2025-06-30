import { hasKeys, isString, isUndefined } from "@steveush/utils";

const isFontFamilyStyle = value => isUndefined( value ) || hasKeys( value, 'fontFamily' );

const isFontFamily = ( value ) => hasKeys( value, { key: isString, name: isString, style: isFontFamilyStyle } );

export default isFontFamily;