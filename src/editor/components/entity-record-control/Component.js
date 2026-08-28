import { FormTokenField } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useEntityRecords } from "@wordpress/core-data";
import { useState } from "@wordpress/element";
import { isString } from "@steveush/utils";

import "./Component.scss";
import {
    createEntityRecordToken,
    getRelativeSlug,
    makeSearchArgs,
    makeSelectedRecordArgs,
    makeSlugSearchArgs,
    parseEntityRecordToken,
    stringifyEntityRecordSuggestion,
    stringifyEntityRecordToken
} from "./utils";
import classnames from "classnames";
import { Icon, border } from "@wordpress/icons";
import useDebounce from "../../hooks/useDebounce";

/**
 *
 * @param {EntityRecordToken[]} tokens
 * @param {string} json
 * @returns {EntityRecordToken[]}
 */
const jsonToTokenReducer = ( tokens, json ) => {
    if ( typeof json === 'object' ) {
        json = json?.value;
    }
    const token = parseEntityRecordToken( json );
    if ( token !== null ) {
        tokens.push( { id: token.id, label: token.label } );
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

const recordToSuggestionReducer = ( strings, record, kind, name, search ) => {
    const token = createEntityRecordToken( kind, name, record );
    const suggestion = stringifyEntityRecordSuggestion( token, search );
    if ( suggestion !== null ) {
        strings.push( suggestion );
    }
    return strings;
};

const mergeRecords = ( ...recordSets ) => {
    const records = new Map();
    recordSets.forEach( recordSet => {
        if ( Array.isArray( recordSet ) ) {
            recordSet.forEach( record => {
                if ( record?.id !== undefined && !records.has( record.id ) ) {
                    records.set( record.id, record );
                }
            } );
        }
    } );
    return Array.from( records.values() );
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

const renderItem = ( { item } ) => {
    const token = parseEntityRecordToken( item );
    const label = token?.label ?? item;
    return (
        <span className={ `${ rootClass }__item` } title={ label }>{ label }</span>
    );
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
 * @param {boolean} [searchBySlug] - Optional. Also query the entity's exact slug. Defaults to `false`.
 * @param {boolean} [showRelativeSlug] - Optional. Show the relative permalink below selected record titles. Defaults to `false`.
 * @param {string} [emptyResult]
 * @param {boolean} [__next40pxDefaultSize]
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
                                  searchBySlug = false,
                                  showRelativeSlug = false,
                                  emptyResult = __( 'No results found', 'fooconvert' ),
                                  __next40pxDefaultSize = true,
                                  className
                              } ) => {

    const [ search, setSearch ] = useState( '' );

    const serializedValue = tokens.reduce( tokenToJsonReducer, [] );
    const selectedIds = tokens.map( token => token?.id ).filter( id => id !== undefined );
    const selectedRecordArgs = makeSelectedRecordArgs( queryArgs, selectedIds );
    const selectedRecordQuery = useEntityRecords( kind, name, selectedRecordArgs, {
        enabled: showRelativeSlug === true && selectedIds.length > 0
    } );
    const relativeSlugs = new Map();
    if ( Array.isArray( selectedRecordQuery.records ) ) {
        selectedRecordQuery.records.forEach( record => {
            const relativeSlug = getRelativeSlug( record );
            if ( relativeSlug !== '' ) {
                relativeSlugs.set( record.id, relativeSlug );
            }
        } );
    }
    const value = serializedValue.map( json => {
        const token = parseEntityRecordToken( json );
        const relativeSlug = relativeSlugs.get( token?.id );
        return relativeSlug ? { value: json, title: relativeSlug } : json;
    } );

    const tokensChanged = tokens => {
        onChange( tokens.reduce( jsonToTokenReducer, [] ) );
        setSearch( '' );
    };
    const searchChanged = value => {
        value = isString( value ) && value.length >= minSearchChars ? value : '';
        setSearch( value );
    };

    const debouncedSearch = useDebounce( searchChanged, 300 );

    let suggestions = [];
    const searchArgs = makeSearchArgs( queryArgs, search, minSearchChars, maxSuggestions );
    const searchOptions = { enabled: isString( searchArgs?.search ) };
    const searchQuery = useEntityRecords( kind, name, searchArgs, searchOptions );

    const slugSearchArgs = makeSlugSearchArgs( queryArgs, search, minSearchChars, maxSuggestions );
    const slugSearchOptions = {
        enabled: searchBySlug === true && Array.isArray( slugSearchArgs?.slug )
    };
    const slugSearchQuery = useEntityRecords( kind, name, slugSearchArgs, slugSearchOptions );

    const hasResolved = searchQuery.hasResolved
        && ( !slugSearchOptions.enabled || slugSearchQuery.hasResolved );
    if ( hasResolved ) {
        const records = mergeRecords( slugSearchQuery.records, searchQuery.records );
        suggestions = records.reduce(
            ( acc, record ) => recordToSuggestionReducer( acc, record, kind, name, search ),
            []
        );
    }
    const isResolving = searchQuery.isResolving
        || ( slugSearchOptions.enabled && slugSearchQuery.isResolving );
    const noResults = searchOptions.enabled && hasResolved && suggestions.length === 0;

    return (
        <div className={ classnames( rootClass, className, {
            'is-next-40px-default-size': __next40pxDefaultSize,
            'is-resolving': isResolving,
            'no-results': noResults
        } ) }>
            <FormTokenField
                hideLabelFromVision
                placeholder={ placeholder }
                suggestions={ suggestions }
                maxSuggestions={ maxSuggestions }
                value={ value }
                displayTransform={ displayTransform }
                onChange={ tokensChanged }
                onInputChange={ debouncedSearch }
                __experimentalRenderItem={ renderItem }
                __experimentalShowHowTo={ false }
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />
            { ( isResolving || noResults ) && (
                <div className={ `${ rootClass }__popup` }>
                    { isResolving && (
                        <div className={ `${ rootClass }__is-resolving` }>
                            <Icon icon={ border } className={ `${ rootClass }__icon` }/>
                        </div>
                    ) }
                    { noResults && (
                        <div className={ `${ rootClass }__no-results` }>
                            { emptyResult }
                        </div>
                    ) }
                </div>
            ) }
        </div>
    );
};

export default EntityRecordControl;
