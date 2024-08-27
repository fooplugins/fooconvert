/**
 * @type {?boolean}
 */
let result = null;

/**
 *
 * @returns {Promise<boolean>}
 */
const hasAdBlock = async () => {
    if ( result === null ) {
        result = false;
        try {
            await global
                .fetch( `https://ads.google.com?=${ ( new Date ).getTime() }`, { mode: "no-cors", method: "HEAD" } )
                .catch( _ => {
                result = true;
            } );
        } catch {
            result = true;
        }
    }
    return result;
};

export default hasAdBlock;