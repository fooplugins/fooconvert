import { isString } from "@steveush/utils";

const isStringNotEmpty = value => isString( value, true );

export default isStringNotEmpty;