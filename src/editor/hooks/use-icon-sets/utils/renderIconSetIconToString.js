import { cloneElement, renderToString } from "@wordpress/element";
import isIconSetIcon from "./isIconSetIcon";

/**
 *
 * @param {IconSetIcon} icon
 * @param {string|undefined} [size]
 * @param {{[key: string]: any;}} [props]
 * @returns {string|undefined}
 */
const renderIconSetIconToString = ( icon, size = '24px', props = {} ) => {
    if ( isIconSetIcon( icon ) ) {
        return renderToString( cloneElement( icon.svg, {
            ...props,
            width: size,
            height: size
        } ) );
    }
    return undefined;
};

export default renderIconSetIconToString;