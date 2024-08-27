const BORDER_STYLES = [ "none", "hidden", "dotted", "dashed", "solid", "double", "groove", "ridge", "inset", "outset" ];

/**
 * Checks if the `value` is a valid CSS `border-style` value.
 *
 * @param {*} value - The value to check.
 * @return {boolean} `true` if the `value` is a CSS `border-style` value, otherwise `false`.
 */
const isCSSBorderStyle = value => {
    if ( typeof value === "string" ) {
        const trimmed = value.trim();
        if ( trimmed !== "" ) {
            const parts = trimmed.split( " " );
            if ( parts.length > 0 && parts.length <= 4 ) {
                return parts.every( part => BORDER_STYLES.includes( part ) );
            }
        }
    }
    return false;
};

export default isCSSBorderStyle;