/**
 *
 * @return {HTMLElement}
 */
// const getEditorDocument = () => {
//     const iframeCanvas = document.querySelector( "iframe[name=editor-canvas]" );
//     return iframeCanvas instanceof HTMLIFrameElement
//         ? iframeCanvas.contentDocument.documentElement
//         : document.documentElement;
// };
/**
 *
 * @return {Promise<HTMLElement>}
 */
const getEditorDocument = async() => {
    return new Promise( ( resolve, reject ) => {
        const iframeCanvas = document.querySelector( "iframe[name=editor-canvas]" );
        if ( iframeCanvas instanceof HTMLIFrameElement ) {
            const interval = setInterval( () => {
                if ( !!iframeCanvas.contentDocument.documentElement ) {
                    clearInterval( interval );
                    clearTimeout( timeout );
                    resolve( iframeCanvas.contentDocument.documentElement );
                }
            }, 10 );
            const timeout = setTimeout( () => {
                clearInterval( interval );
                reject( new Error( `Could not load editor document` ) );
            }, 10000 );
        } else {
            resolve( document.documentElement );
        }
    } );
};

export default getEditorDocument;