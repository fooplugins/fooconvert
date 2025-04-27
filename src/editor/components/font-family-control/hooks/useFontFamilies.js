import { useSettings } from "@wordpress/block-editor";
import { useMemo } from "@wordpress/element";

/**
 * An object containing the `slug`, `name` and `style` for a font family.
 *
 * @typedef {{key:string,name:string,style?:{fontFamily:string}}} FontFamily
 */

/**
 * An object containing the `slug`, `name` and `fontFamily` for a typography font family.
 *
 * @typedef {{slug:string,name?:string,fontFamily:string}} TypographyFontFamily
 */

/**
 *
 * @returns {FontFamily[]}
 */
const useFontFamilies = () => {
    const [ settingsFontFamilies ] = useSettings( 'typography.fontFamilies' );
    return useMemo( () => {
        if ( !!settingsFontFamilies ) {
            // see: https://github.com/WordPress/gutenberg/blob/trunk/packages/block-editor/src/components/global-styles/typography-utils.js#L153 for where the default, theme and custom values come from
            const typographyFontFamilies =
                /** @type {TypographyFontFamily[]} */
                [ 'default', 'theme', 'custom' ].flatMap( ( key ) => settingsFontFamilies?.[ key ] ?? [] );

            /** @type {Map<string, FontFamily>} */
            const fontFamilyMap = new Map();
            // push the values into a map to remove possible duplicates
            return [ ...typographyFontFamilies.reduce( ( map, { slug, name, fontFamily } ) => {
                fontFamilyMap.set( slug, {
                    key: slug,
                    name: name || fontFamily,
                    style: { fontFamily }
                } );
                return map;
            }, fontFamilyMap ).values() ]
                .sort( ( a, b ) => a.name.localeCompare( b.name ) );
        }
        return [];
    }, [ settingsFontFamilies ] );
};

export default useFontFamilies;