/**
 *
 * @param text
 * @returns {Promise<void>}
 */
const copyToClipboard = text => {
    if ( navigator?.clipboard?.writeText ) {
        return navigator.clipboard.writeText( text );
    } else if ( document?.queryCommandSupported( 'copy' ) ) {
        // this fallback should only be triggered by very old browsers, or if the current page is not running in a secure context (i.e., HTTP not HTTPS)
        const textarea = document.createElement( "textarea" );
        textarea.textContent = text;
        textarea.style.position = "fixed";
        document.body.appendChild( textarea );
        const restore = document.activeElement;
        textarea.focus();
        textarea.select();
        try {
            const result = document.execCommand( "copy" );
            return result ? Promise.resolve() : Promise.reject( 'Browser does not support copying to clipboard.' );
        } catch ( ex ) {
            console.error( "Copy to clipboard failed.", ex );
        } finally {
            document.body.removeChild( textarea );
            if ( restore ) {
                restore.focus();
            }
        }
    }
    return Promise.reject( 'Browser does not support the Clipboard API.' );
};

export default copyToClipboard;