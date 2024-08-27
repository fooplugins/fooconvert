import { FormTokenField } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useEntityRecords } from "@wordpress/core-data";
import { useState } from "@wordpress/element";
import { isNumber, isPlainObject, isString } from "@steveush/utils";

import "./Component.scss";
import { createEntityRecordToken, parseEntityRecordToken, stringifyEntityRecordToken } from "./utils";
import classnames from "classnames";

/**
 *
 * @param {EntityRecordToken[]} tokens
 * @param {string} json
 * @returns {EntityRecordToken[]}
 */
const jsonToTokenReducer = ( tokens, json ) => {
    const token = parseEntityRecordToken( json );
    if ( token !== null ) {
        tokens.push( token );
    }
    return tokens;
};

/**
 *
 * @param {string[]} strings
 * @param {EntityRecordToken} token
 * @returns {string[]}
 */
const tokenToJsonReducer = ( strings, token ) => {
    const json = stringifyEntityRecordToken( token );
    if ( json !== null ) {
        strings.push( json );
    }
    return strings;
};

/**
 *
 * @param {string} data
 * @returns {string}
 */
const displayTransform = data => {
    const token = parseEntityRecordToken( data );
    if ( token !== null ) {
        return token.label;
    }
    return data;
};

const makeSearchArgs = ( queryArgs, search, minChars, perPage ) => {
    const args = isPlainObject( queryArgs ) ? { ...queryArgs } : {};
    if ( isString( search ) && search.length >= minChars ) {
        args.search = search;
    }
    if ( isNumber( perPage ) ) {
        args.per_page = perPage;
    }
    return args;
};

const rootClass = 'fc--entity-record-control';

/**
 *
 * @param {string} kind - The entity kind.
 * @param {string} name - The entity name.
 * @param {EntityRecordToken[]} tokens - An array of entity record tokens. If not supplied or `undefined`, defaults to an empty array.
 * @param {( value: EntityRecordToken[] )=>void} onChange - Callback for when the entity record tokens change.
 * @param {{}} [queryArgs] - Optional. Any additional args to include for each query. Defaults to an empty object.
 * @param {string} [placeholder] - Optional. The placeholder text for the component. Defaults to an empty string.
 * @param {number} [minSearchChars] - Optional. The minimum number of characters to be entered before a search query is performed. Defaults to `2`.
 * @param {number} [maxSuggestions] - Optional. The maximum number of suggestions to return per query. Defaults to `5`.
 * @param {string} [className] - Optional. A space delimited string of class names to add to the component.
 * @returns {JSX.Element} The rendered component.
 */
const EntityRecordControl = ( {
                                  kind,
                                  name,
                                  queryArgs = {},
                                  tokens = [],
                                  placeholder = '',
                                  onChange,
                                  minSearchChars = 2,
                                  maxSuggestions = 5,
                                  className
                              } ) => {

    const [ search, setSearch ] = useState( '' );

    const value = tokens.reduce( tokenToJsonReducer, [] );

    const tokensChanged = tokens => {
        onChange( tokens.reduce( jsonToTokenReducer, [] ) );
        setSearch( '' );
    };
    const searchChanged = value => {
        value = isString( value ) && value.length >= minSearchChars ? value : '';
        setSearch( value );
    };

    let suggestions = [];
    const searchArgs = makeSearchArgs( queryArgs, search, minSearchChars, maxSuggestions );
    const query = useEntityRecords( kind, name, searchArgs );
    if ( query.hasResolved ) {
        suggestions = query.records.reduce( ( acc, record ) => {
            const token = createEntityRecordToken( kind, name, record );
            return tokenToJsonReducer( acc, token );
        }, [] );
    }

    return (
        <div className={ classnames( rootClass, className ) }>
            <FormTokenField
                hideLabelFromVision
                placeholder={ placeholder }
                suggestions={ suggestions }
                maxSuggestions={ maxSuggestions }
                value={ value }
                displayTransform={ displayTransform }
                onChange={ tokensChanged }
                onInputChange={ searchChanged }
                __experimentalShowHowTo={ false }
                __nextHasNoMarginBottom
            />
        </div>
    );
};

export default EntityRecordControl;