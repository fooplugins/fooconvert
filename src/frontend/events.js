/**
 * Log an event for a given widget.
 *
 * @param {number} widgetId The ID of the widget to log the event for.
 * @param {string} event_type The type of event to log.
 */
export const logEvent = ( widgetId, event_type ) => {
    const data = {
        widgetId: widgetId,
        eventType: event_type,
        deviceType: getDeviceType(),
        pageURL: window.location.href,
        uniqueID: getUniqueID()
    };

    console.log('Sending event data to server...', data);
    //const ajaxurl = fooconvert_vars.ajaxurl; //TODO : get this from wp_localize_script.
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            action: 'fooconvert_log_event',
            // nonce: fooconvert_vars.nonce, // TODO : get this from wp_localize_script.
            data: data
        })
    })
        .then(response => response.json())
    // .then(data => {
    //     // console.log('Success:', data);
    // })
    // .catch((error) => {
    //     // console.error('Error:', error);
    // });
}


/**
 * Determine the device type (desktop, tablet, mobile) of the current client.
 *
 * @returns {string} The device type.
 */
const getDeviceType = () => {
    const userAgent = navigator.userAgent.toLowerCase();
    if (userAgent.match(/tablet|ipad|playbook|silk/)) {
        return 'tablet';
    } else if (userAgent.match(/mobile|iphone|ipod|android|blackberry|mini|windows\sce|palm/)) {
        return 'mobile';
    } else {
        return 'desktop';
    }
}

/**
 * Generates a UUID (version 4) according to RFC 4122. In modern browsers,
 * this method uses the `crypto.randomUUID()` method, which is more secure.
 * In older browsers, a fallback method is used.
 *
 * @returns {string} A UUID (version 4) according to RFC 4122.
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Crypto/randomUUID
 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_(random)
 */
const generateGuid = () => {
    // Check if the crypto object and randomUUID method are available
    if (window.crypto && crypto.randomUUID) {
        return crypto.randomUUID();
    } else {
        // Fallback method for non-secure contexts
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0,
                v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
}

/**
 * Sets a cookie with the given name, value and number of days until it expires.
 * If days is not given, the cookie will expire at the end of the session.
 *
 * @param {string} name The name of the cookie.
 * @param {*} value The value of the cookie.
 * @param {number} [days] The number of days until the cookie expires.
 */
const setCookie = (name, value, days) => {
    let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
}

/**
 * Retrieves a cookie by name.
 *
 * @param {string} name The name of the cookie to retrieve.
 * @returns {string|null} The value of the cookie, or null if the cookie does not exist.
 */
const getCookie = (name) => {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

const getUniqueID = () => {

    let uniqueID;

    try {
        uniqueID = localStorage.getItem('fc_unique_id');
    } catch (e) {
    }

    if (!uniqueID) {
        uniqueID = getCookie('fc_unique_id');
    }

    if (!uniqueID) {
        uniqueID = generateGuid();

        try {
            localStorage.setItem('fc_unique_id', uniqueID);
        } catch (e) {
        }

        setCookie('fc_unique_id', uniqueID, 365); // Setting the cookie to expire in 365 days
    }

    return uniqueID;
}

// document.addEventListener("DOMContentLoaded", function () {
//     //When a widget is displayed on the page, log a view event.
// });
