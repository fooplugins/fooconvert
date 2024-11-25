import { isNumber, isPlainObject, isString } from "@steveush/utils";

const makeToken = ( id, label ) => {
    if ( isNumber( id ) && isString( label, true ) ) {
        return { id, label };
    }
    return null;
};

const postTypeToken = ( record ) => {
    const { id, title: { raw: label } } = record;
    return makeToken( id, label );
};

const taxonomyToken = ( record ) => {
    const { id, name: label } = record;
    return makeToken( id, label );
};

/**
 * Create a token from a record of a specific kind and name.
 *
 * @param {string} kind - The kind of entity record. See `useEntityRecords` first parameter.
 * @param {string} name - The name of entity record. See `useEntityRecords` second parameter.
 * @param {object} record - The record to create a token from. See `useEntityRecords` return value.
 * @return {?EntityRecordToken} - A token object representing the record, otherwise `null`.
 */
const createEntityRecordToken = ( kind, name, record ) => {
    if ( isString( kind ) && isString( name ) && isPlainObject( record ) ) {
        switch ( kind ) {
            case "postType":
                return postTypeToken( record );
            case "taxonomy":
                return taxonomyToken( record );
        }
    }
    return null;
};

export default createEntityRecordToken;