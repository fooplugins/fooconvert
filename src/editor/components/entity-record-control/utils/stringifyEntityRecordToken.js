import isEntityRecordToken from "./isEntityRecordToken";

/**
 * Stringify a token ensuring the order and quantity of properties.
 *
 * @param {EntityRecordToken} token - The token to stringify. If any of the properties are invalid `null` is returned.
 * @return {?string} - The JSON string representation of the token, otherwise `null`.
 */
const stringifyEntityRecordToken = token => isEntityRecordToken( token ) ? JSON.stringify( token, [ 'id', 'label' ] ) : null;

export default stringifyEntityRecordToken;