/**
 *
 * @param text
 * @returns {Promise<void>}
 */
const copyToClipboard = text => {
    if ( navigator?.clipboard?.writeText ) {
        return navigator.clipboard.writeText( text );
    } else if ( document?.queryCommandSupported( 'copy' ) ) {
        const textarea = document.createElement( "textarea" );
        textarea.textContent = text;
        textarea.style.position = "fixed";
        document.body.appendChild( textarea );
        const restore = document.activeElement;
        textarea.focus();
        textarea.select();
        try {
            const result = document.execCommand( "copy" );
            return result ? Promise.resolve() : Promise.reject( new Error( 'Browser does not support copying to clipboard.' ) );
        } catch ( error ) {
            return Promise.reject( error );
        } finally {
            document.body.removeChild( textarea );
            if ( restore ) {
                restore.focus();
            }
        }
    }
    return Promise.reject( new Error( 'Browser does not support the Clipboard API.' ) );
};

export default copyToClipboard;
