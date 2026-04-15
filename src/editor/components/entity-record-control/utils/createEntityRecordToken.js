import { isNumber, isPlainObject, isString } from "@steveush/utils";

/**
 * @typedef {{id: number, label: string}} EntityRecordToken
 * @typedef {{id: number, title?: {raw?: string}}} PostTypeEntityRecord
 * @typedef {{id: number, name?: string}} TaxonomyEntityRecord
 */

const makeToken = ( id, label ) => {
    if ( isNumber( id ) && isString( label, true ) ) {
        return { id, label };
    }
    return null;
};

const postTypeToken = ( record ) => makeToken( record?.id, record?.title?.raw );

const taxonomyToken = ( record ) => makeToken( record?.id, record?.name );

/**
 * Create a token from a record of a specific kind and name.
 *
 * @param {string} kind - The kind of entity record. See `useEntityRecords` first parameter.
 * @param {string} name - The name of entity record. See `useEntityRecords` second parameter.
 * @param {PostTypeEntityRecord|TaxonomyEntityRecord|Record<string, unknown>} record - The record to create a token from. See `useEntityRecords` return value.
 * @return {EntityRecordToken|null} - A token object representing the record, otherwise `null`.
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
