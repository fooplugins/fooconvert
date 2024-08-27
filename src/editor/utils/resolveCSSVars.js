const CSS_VARS = /var\((--.*?)\)/g;
/**
 * Resolve CSS variables in a given value to their computed value.
 *
 * @param {string} value - The value to search.
 * @param {CSSStyleDeclaration} [computedStyles] - Optional. The computed styles to use to resolve any variables found
 * in the value. Defaults to the documentElement's computed styles.
 * @return {string} The original value with all resolved CSS variables replaced with their computed values.
 * If a CSS variable could not be resolved it is left in place.
 */
const resolveCSSVars = ( value, computedStyles ) => {
    if ( !computedStyles || !computedStyles?.getPropertyValue ) {
        computedStyles = globalThis.getComputedStyle( globalThis.document.documentElement );
    }
    return value.replaceAll( CSS_VARS, ( matchedVar, propertyName ) => {
        const computed = computedStyles.getPropertyValue( propertyName );
        return computed !== '' ? computed : matchedVar;
    } );
};

export default resolveCSSVars;