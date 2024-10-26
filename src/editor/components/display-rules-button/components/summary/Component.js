import { isString } from "@steveush/utils";
import classnames from "classnames";

import "./Component.scss";
import getGroupedSelectOption from "../../../grouped-select-control/utils/getGroupedSelectOption";
import editorData from "../../editorData";

const rootClass = 'fc-display-rules-summary';

const summarize = rules => {
    const result = {
        valid: true,
        success: 'Rules are configured.',
        reasons: [],
        include: [],
        exclude: [],
        roles: []
    };

    const isValidLocation = location => isString( location.type, true ) && ( location.type.startsWith( 'specific:' ) ? location.data.length > 0 : true );
    let include = rules.location.filter( isValidLocation );
    let exclude = rules.exclude.filter( isValidLocation );

    const entireSite = include.find( location => location.type === "general:entire_site" );
    if ( entireSite ) {
        result.include.push( entireSite );
    } else {
        include = include.reduce( ( acc, location ) => {
            const excluded = exclude.find( e => e.type === location.type );
            if ( excluded ) {
                if ( location.type.startsWith( 'specific:' ) ) {
                    const data = location.data.filter( l => !excluded.data.some( e => e.id === l.id ) );
                    if ( data.length > 0 ) {
                        acc.push( { type: location.type, data } );
                    }
                }
            } else {
                acc.push( location );
            }
            return acc;
        }, [] );
        result.include.push( ...include );
    }
    if ( result.include.length === 0 ) {
        result.valid = false;
        result.reasons.push( 'No valid locations.' );
    }
    result.exclude.push( ...exclude );

    const allUsers = rules.users.find( role => role === "general:all_users" );
    if ( allUsers ) {
        result.roles.push( allUsers );
    } else {
        result.roles.push( ...rules.users.filter( role => role !== '' ) );
    }
    if ( result.roles.length === 0 ) {
        result.valid = false;
        result.reasons.push( 'No valid user roles.' );
    }

    return result;
};

const DisplayRulesSummary = ( {
                                  rules,
                                  className
                              } ) => {

    const result = summarize( rules );

    const renderSuccess = () => <li key={ 0 }>{ result.success }</li>;
    const renderReasons = () => result.reasons.map( ( reason, i ) => <li key={ i }>{ reason }</li> );

    return (
        <ul className={ classnames( rootClass, className, { 'has-reasons': !result.valid } ) }>
            { result.valid ? renderSuccess() : renderReasons() }
        </ul>
    );
};


/**
 *
 * @param {EntityRecordToken[]} entities
 * @returns {JSX.Element}
 */
const renderEntities = ( entities ) => {
    if ( entities.length > 0 ) {
        return (
            <ul className={ `${ rootClass }__location-entities` }>
                { entities.map( ( entity, index ) => <li
                    key={ index }
                    className={ `${ rootClass }__location-entity` }
                >
                    <span className={ `${ rootClass }__location-entity-label` }>{ entity.label }</span>
                </li> ) }
            </ul>
        );
    }
    return null;
};

/**
 *
 * @param {DisplayRulesLocation} location
 * @param {number} index
 * @param {GroupedSelectOptions} options
 * @returns {JSX.Element}
 */
const renderLocation = ( location, index, options ) => {
    const option = getGroupedSelectOption( options, location.type );
    return (
        <li key={ index } className={ `${ rootClass }__location` }>
            <span className={ `${ rootClass }__location-label` }>{ option?.label ?? 'ERR_NOT_FOUND' }</span>
            { renderEntities( location.data ) }
        </li>
    );
};

/**
 *
 * @param {string} label
 * @param {DisplayRulesLocation[]} locations
 * @param {GroupedSelectOptions} options
 * @return {JSX.Element}
 */
const renderLocations = ( label, locations, options ) => {
    if ( locations.length > 0 ) {
        return (
            <div className={ `${ rootClass }__locations` }>
                <label className={ `${ rootClass }__locations-label` }>{ label }</label>
                <ul className={ `${ rootClass }__locations-list` }>
                    { locations.map( ( location, i ) => renderLocation( location, i, options ) ) }
                </ul>
            </div>
        );
    }
    return null;
};

const renderRole = ( role, index, options ) => {
    const option = getGroupedSelectOption( options, role );
    return (
        <li key={ index } className={ `${ rootClass }__role` }>
            <span className={ `${ rootClass }__role-label` }>{ option?.label ?? 'ERR_NOT_FOUND' }</span>
        </li>
    );
};

const renderRoles = ( label, roles, options ) => {
    if ( roles.length > 0 ) {
        return (
            <div className={ `${ rootClass }__roles` }>
                <label className={ `${ rootClass }__roles-label` }>{ label }</label>
                <ul className={ `${ rootClass }__roles-list` }>
                    { roles.map( ( role, i ) => renderRole( role, i, options ) ) }
                </ul>
            </div>
        );
    }
    return null;
};

const renderSummary = ( summary ) => {
    if ( summary.valid ) {
        return (
            <div>
                { renderLocations( 'Visible on', summary.include, editorData.location ) }
                { renderLocations( 'Excluded from', summary.exclude, editorData.exclude ) }
                { renderRoles( 'For', summary.roles, editorData.users ) }
            </div>
        );
    } else {
        return (
            <div>
                <label>{ 'Not visible' }</label>
                <ul>
                    { summary.reasons.map( ( reason, i ) => <li key={ i }>{ reason }</li> ) }
                </ul>
            </div>
        );
    }
};

export default DisplayRulesSummary;