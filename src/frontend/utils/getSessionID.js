import { isString } from "@steveush/utils";

import generateGUID from "./generateGUID";
import getSessionData from "./getSessionData";
import setSessionData from "./setSessionData";

/**
 * The key used to store the session ID for the current browser session.
 * @type {string}
 */
export const SESSION_ID_STORAGE_KEY = 'FOOCONVERT_SESSION_ID';

/**
 * Get a session ID for the current browser session.
 *
 * @returns {string}
 */
const getSessionID = () => {
    let sessionID = getSessionData( SESSION_ID_STORAGE_KEY );

    if ( !isString( sessionID, true ) ) {
        sessionID = generateGUID();
        setSessionData( SESSION_ID_STORAGE_KEY, sessionID );
    }

    return sessionID;
};

export default getSessionID;
