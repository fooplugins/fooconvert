const RETURN_FALSE = () => false;
const MATCH_REGEX = regex => value => regex.test( value );
const MATCH_STRING = search => value => value.startsWith( search );

/**
 * Gets all context values that exist within the specified namespace.
 * @param {object} context
 * @param {string|RegExp} namespace
 * @return {object}
 */
const contextNS = ( context, namespace ) => {
    let matches = RETURN_FALSE;
    if ( typeof namespace === "string" ) {
        if ( !namespace.endsWith( "/" ) ) namespace += "/";
        matches = MATCH_STRING( namespace );
    }
    if ( namespace instanceof RegExp ) {
        matches = MATCH_REGEX( namespace );
    }
    if ( matches === RETURN_FALSE ) return {};
    return Object.entries( context ).reduce( ( ctx, [ key, value ] ) => {
        if ( matches( key ) ){
            const name = key.split( "/" ).at( 1 );
            if ( typeof name === "string" ) {
                ctx[ name ] = value;
            }
        }
        return ctx;
    }, {} );
};

export default contextNS;