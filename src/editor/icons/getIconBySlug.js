import { isString, isArray } from "@steveush/utils";

import ICON_SETS from "./ICON_SETS.js";

/**
 *
 * @param {string|undefined} slug - The slug for the icon. The slug is composed of the set name and icon name separated by double underscores.
 * @returns {{name: string, label: string, value: import("react").ReactNode}|undefined}
 */
const getIconBySlug = slug => {
    if ( isString( slug ) ) {
        const [ setName = '', iconName = '' ] = slug.split( '__' );
        if ( setName !== '' && iconName !== '' ) {
            const iconSet = ICON_SETS.find( set => set?.name === setName );
            if ( !!iconSet && isArray( iconSet?.icons ) ) {
                return iconSet.icons.find( icon => icon?.name === iconName );
            }
        }
    }
    return undefined;
};

export default getIconBySlug;