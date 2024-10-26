/**
 * @typedef {Omit<IconSetIcon, "svg", "props">} SerializableIconSetIcon
 * @property {string} svg
 */

import { cloneElement, renderToString } from "@wordpress/element";
import isIconSetIcon from "./isIconSetIcon";

/**
 *
 * @param {IconSetIcon} icon
 * @returns {SerializableIconSetIcon|undefined}
 */
const makeSerializableIconSetIcon = icon => {
    if ( isIconSetIcon( icon ) ) {
        const { svg, props, ...restProps } = icon;
        return {
            ...restProps,
            svg: renderToString( cloneElement( svg, props ) )
        };
    }
    return undefined;
};

export default makeSerializableIconSetIcon;