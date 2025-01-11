/**
 * Generates a UUID (version 4) according to RFC 4122. In modern browsers,
 * this method uses the `crypto.randomUUID()` method, which is more secure.
 * In older browsers, a fallback method is used.
 *
 * @returns {string} A UUID (version 4) according to RFC 4122.
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Crypto/randomUUID
 * @see https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_(random)
 */
const generateGUID = () => {
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
};

export default generateGUID;