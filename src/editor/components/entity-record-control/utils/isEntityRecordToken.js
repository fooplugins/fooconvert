import { hasKeys, isNumber } from "@steveush/utils";
import isStringNotEmpty from "../../../utils/isStringNotEmpty";


/**
 * An interface representing a token created from an entity record.
 * @typedef {{id:number,label:string}} EntityRecordToken
 */

/**
 * Check if a value is a valid entity record token.
 *
 * @param {any} value - The value to check.
 * @returns {value is EntityRecordToken} - `true` if the value is an entity record token, otherwise `false`.
 */
const isEntityRecordToken = value => hasKeys( value, {
    id: isNumber,
    label: isStringNotEmpty
} );

export default isEntityRecordToken;