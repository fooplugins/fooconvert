import getIconSetsIcon from "./getIconSetsIcon";

/**
 *
 * @param {IconSet[]} iconSets
 * @param {...string} slugs
 * @returns {IconSetIcon[]}
 */
const getIconSetsIcons = ( iconSets, ...slugs ) => {
    const result = [];
    for ( const slug of slugs ) {
        const found = getIconSetsIcon( iconSets, slug );
        if ( found ) {
            result.push( found );
            break;
        }
    }
    return result;
};

export default getIconSetsIcons;