import { isNumber, isPlainObject, isString } from "@steveush/utils";

import getDeviceType from "./getDeviceType";
import getSessionID from "./getSessionID";
import getUniqueID from "./getUniqueID";
import cfg from "../config";

const { url, nonce } = cfg.endpoint;

/**
 *
 * @type {{DISMISS: string, CONVERSION: string, CLICK: string, CLOSE: string, VIEW: string, OPEN: string}}
 */
export const LOG_EVENT_TYPES = {
    VIEW: 'view',
    DISMISS: 'dismiss',
    CLICK: 'click',
    CONVERSION: 'conversion',
    OPEN: 'open',
    CLOSE: 'close'
};

/**
 * @typedef {{success:boolean,data?:string}} LogEventResult
 */

/**
 * Log an event for a given popup.
 *
 * @param {number} postId The ID of the popup to log the event for.
 * @param {string} postType The post type of the popup.
 * @param {string} template The template used within the popup.
 * @param {string} eventType The type of event to log.
 * @param {object} [extraData] An optional object containing any extra info for the event.
 * @returns {Promise<LogEventResult>}
 */
const logEvent = ( postId, postType, template, eventType, extraData ) => {
    if ( isNumber( postId ) && isString( postType ) && isString( template ) && isString( eventType ) ) {
        const deviceType = getDeviceType();
        const sessionID = getSessionID();
        const uniqueID = getUniqueID();
        const pageURL = globalThis?.window?.location?.href;
        if ( isString( uniqueID ) && isString( sessionID ) && isString( pageURL ) ) {
            const data = {
                postId,
                postType,
                template,
                eventType,
                deviceType,
                pageURL,
                sessionID,
                uniqueID
            };

            if ( isPlainObject( extraData ) ) {
                data.extraData = extraData;
            }

            // console.log( 'Sending event data to server...', data );

            return fetch( url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams( {
                    action: 'fooconvert_log_event',
                    nonce: nonce,
                    data: JSON.stringify( data )
                } )
            } )
                .then( response => response.json() )
                .catch( err => {
                    console.error( 'FooConvertLogEventError', err );
                    return { success: false, data: 'Unexpected Error' }
                } );
        }
    }
    return Promise.resolve( { success: false, data: 'Invalid parameters' } );
};

export default logEvent;
