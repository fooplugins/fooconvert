import { useMemo } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import * as exports from "@wordpress/icons";
import { capitalize } from "@steveush/utils";

const { Icon, ...icons } = exports;
const ICONS = Object.entries( icons );

const makeLabel = name => {
    const parts = name.split( /(?<![A-Z])(?=[A-Z])/ );
    return capitalize( parts.join( ' ' ).toLowerCase() );
};

/**
 *
 * @returns {IconSet[]}
 */
const useIconSets = () => {
    return useMemo( () => {
        return [ {
            name: __( 'WordPress', 'fooconvert' ),
            icons: ICONS.map( ( [ name, icon ] ) => {
                return {
                    slug: `wordpress-${ name }`,
                    name: makeLabel( name ),
                    svg: icon
                };
            } )
        } ];
    }, [ ICONS ] );
};

export default useIconSets;