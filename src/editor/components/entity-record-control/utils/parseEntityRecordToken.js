import { isString } from "@steveush/utils";
import isEntityRecordToken from "./isEntityRecordToken";

/**
 * Parse a JSON string representing a token into a valid object.
 *
 * @param {string} json - The JSON string to parse.
 * @return {?EntityRecordToken} - The token object, otherwise `null`.
 */
const parseEntityRecordToken = json => {
    if ( isString( json, true ) && /^\{[^}]*?}$/.test( json ) ) {
        try {
            const parsed = JSON.parse( json );
            if ( isEntityRecordToken( parsed ) ) {
                return parsed;
            }
        } catch ( err ) {
            console.error( 'parseEntityRecordToken:error', err, json );
        }
    }
    return null;
};

export default parseEntityRecordToken;