import { isString } from "@steveush/utils";

/**
 *
 * @param {IconSet[]} iconSets
 * @param {string|undefined} slug
 * @returns {IconSetIcon|undefined}
 */
const getIconSetsIcon = ( iconSets, slug ) => {
    if ( isString( slug, true ) ) {
        for ( const iconSet of iconSets ) {
            const found = iconSet.icons.find( icon => icon.slug === slug );
            if ( found ) {
                return found;
            }
        }
    }
    return undefined;
};

export default getIconSetsIcon;