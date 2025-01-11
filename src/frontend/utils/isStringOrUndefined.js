import { isString, isUndefined } from "@steveush/utils";

const isStringOrUndefined = value => isString( value, true ) || isUndefined( value );

export default isStringOrUndefined;