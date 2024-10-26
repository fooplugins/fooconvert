import { isString } from "@steveush/utils";
import isEntityRecordToken from "./isEntityRecordToken";

const parseEntityRecordTokens = json => {
    if ( isString( json, true ) && /^\[.*?]$/.test( json ) ) {
        const tokens = JSON.parse( json );
        return tokens.filter( isEntityRecordToken );
    }
    return [];
};

export default parseEntityRecordTokens;