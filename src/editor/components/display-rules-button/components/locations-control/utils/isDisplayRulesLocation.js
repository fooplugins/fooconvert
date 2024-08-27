import { hasKeys, isArray, isString } from "@steveush/utils";
import { isEntityRecordToken } from "../../../../../components";

/**
 * An object used to store a display rules location.
 *
 * @typedef DisplayRulesLocation
 * @property {string} type - The type identifier for the location.
 * @property {EntityRecordToken[]} data - An array of entity record tokens associated to the location.
 */

/**
 * Check if a value is a `DisplayRulesLocation` object.
 *
 * @param {any} value - The value to check.
 * @return {value is DisplayRulesLocation} `true` if the value is a `DisplayRulesLocation` object, otherwise `false`.
 */
const isDisplayRulesLocation = value => hasKeys( value, {
    type: isString,
    data: val => isArray( val, false, isEntityRecordToken )
} );

export default isDisplayRulesLocation;