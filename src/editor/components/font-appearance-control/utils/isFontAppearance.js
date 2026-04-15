import isStringNotEmpty from "../../../utils/isStringNotEmpty";
import { hasKeys, isNumber } from "@steveush/utils";

/**
 * @typedef {{fontStyle: string, fontWeight: number}} FontAppearance
 */

/**
 *
 * @type {Record<string, ((value: unknown) => boolean)>}
 */
const KEYS = {
    fontStyle: isStringNotEmpty,
    fontWeight: isNumber
};

/**
 *
 * @param {unknown} value
 * @returns {value is FontAppearance}
 */
const isFontAppearance = value => hasKeys( value, KEYS );

export default isFontAppearance;
