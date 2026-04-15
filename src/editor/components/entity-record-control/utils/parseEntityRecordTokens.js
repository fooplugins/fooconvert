import { isString } from "@steveush/utils";
import isEntityRecordToken from "./isEntityRecordToken";

/**
 * @typedef {{id: number, label: string}} EntityRecordToken
 */

/**
 * Parse a JSON string representing an array of tokens into valid objects.
 *
 * @param {string} json
 * @returns {EntityRecordToken[]}
 */
const parseEntityRecordTokens = json => {
    if ( isString( json, true ) && /^\[.*?]$/.test( json ) ) {
        const tokens = JSON.parse( json );
        return tokens.filter( isEntityRecordToken );
    }
    return [];
};

export default parseEntityRecordTokens;
