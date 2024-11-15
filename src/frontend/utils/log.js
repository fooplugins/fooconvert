import { isNumber, isPlainObject, isString } from "@steveush/utils";

import getDeviceType from "./getDeviceType";
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
 * Log an event for a given widget.
 *
 * @param {number} widgetId The ID of the widget to log the event for.
 * @param {string} eventType The type of event to log.
 * @param {object} [extraData] An optional object containing any extra info for the event.
 */
const log = ( widgetId, eventType, extraData ) => {
    if ( isNumber( widgetId ) && isString( eventType ) ) {
        const deviceType = getDeviceType();
        const uniqueID = getUniqueID();
        const pageURL = globalThis?.window?.location?.href;
        if ( isString( uniqueID ) && isString( pageURL ) ) {
            const data = {
                widgetId,
                eventType,
                deviceType,
                pageURL,
                uniqueID
            };

            if ( isPlainObject( extraData ) ) {
                data.extraData = extraData;
            }

            console.log( 'Sending event data to server...', data );

            fetch( url, {
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
                .then( responseJSON => {
                    if ( responseJSON?.success === false ) {
                        const err = new Error( responseJSON?.data?.message ?? 'Unknown error' );
                        err.name = 'FooConvertLogEventError';
                        throw err;
                    }
                } )
                .catch( error => console.error( error ) );
        }
    }
};

export default log;