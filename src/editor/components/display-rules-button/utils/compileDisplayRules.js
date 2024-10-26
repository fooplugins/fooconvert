import { __ } from "@wordpress/i18n";

import isDisplayRulesMeta from "./isDisplayRulesMeta";
import isStringNotEmpty from "../../../utils/isStringNotEmpty";

/**
 *
 * @param {DisplayRulesMeta} rules
 * @returns {{reasons: *[], success: boolean, location: *[], exclude: *[], users: *[]}}
 */
const compileDisplayRules = ( rules ) => {
    const result = {
        success: false,
        reasons: [],
        location: [],
        exclude: [],
        users: [],
    };

    if ( isDisplayRulesMeta( rules ) ) {

        const isValidLocation = location => location.type.trim().length > 0 && ( location.type.startsWith( 'specific:' ) ? location.data.length > 0 : true );
        result.location = rules.location.filter( isValidLocation );
        result.exclude = rules.exclude.filter( isValidLocation );

        const entireSite = result.location.find( location => location.type === "general:entire_site" );
        if ( entireSite ) {
            result.location = [ entireSite ];
        } else {
            result.location = result.location.reduce( ( acc, location ) => {
                const excluded = result.exclude.find( e => e.type === location.type );
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
        }
        if ( result.location.length === 0 ) {
            result.reasons.push( { source: "location", message: __( 'No locations configured.', 'fooconvert' ) } );
        }

        result.users = rules.users.filter( isStringNotEmpty );
        const allUsers = result.users.find( role => role === "general:all_users" );
        if ( allUsers ) {
            result.users = [ allUsers ];
        }
        if ( result.users.length === 0 ) {
            result.reasons.push( { source: "users", message: __( 'No users configured.', 'fooconvert' ) } );
        }
    } else {
        result.reasons.push( { source: "rules", message: __( 'Invalid rules object.', 'fooconvert' ) } );
    }

    result.success = result.reasons.length === 0;

    return result;
};

export default compileDisplayRules;