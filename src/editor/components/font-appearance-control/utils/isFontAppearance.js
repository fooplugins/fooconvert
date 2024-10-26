import isStringNotEmpty from "../../../utils/isStringNotEmpty";
import { hasKeys, isNumber } from "@steveush/utils";

/**
 * @typedef {{fontStyle: string, fontWeight: number}} FontAppearance
 */

/**
 *
 * @type {Record<string, ((value: any) => boolean)>}
 */
const KEYS = {
    fontStyle: isStringNotEmpty,
    fontWeight: isNumber
};

/**
 *
 * @param value
 * @returns {value is FontAppearance}
 */
const isFontAppearance = value => hasKeys( value, KEYS );

export default isFontAppearance;