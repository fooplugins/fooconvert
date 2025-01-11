/**
 * Determine the device type (desktop, tablet, mobile) of the current client.
 *
 * @returns {string} The device type.
 */
const getDeviceType = () => {
    const userAgent = globalThis?.navigator?.userAgent?.toLowerCase();
    if ( typeof userAgent === 'string' ) {
        if ( userAgent.match( /tablet|ipad|playbook|silk/ ) ) {
            return 'tablet';
        }
        if ( userAgent.match( /mobile|iphone|ipod|android|blackberry|mini|windows\sce|palm/ ) ) {
            return 'mobile';
        }
    }
    return 'desktop';
};

export default getDeviceType;