import { CustomSelectControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import classnames from "classnames";
import { isString } from "@steveush/utils";

export const FONT_FAMILY_OPTIONS_DEFAULTS = [
    {
        key: 'default',
        name: __( 'Default', 'fooconvert' )
    },
    {
        key: 'system-font',
        name: __( 'System Font', 'fooconvert' ),
        style: { fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif' }
    },
    {
        key: 'source-serif-pro',
        name: __( 'Source Serif Pro', 'fooconvert' ),
        style: { fontFamily: '"Source Serif Pro", serif' }
    }
];

const CLASS_NAME = 'fc--font-family-control';

const FontFamilyControl = ( props ) => {

    const {
        value,
        onChange,
        options = FONT_FAMILY_OPTIONS_DEFAULTS,
        size = "__unstable-large",
        className,
        label,
        ...restProps
    } = props;

    const found = isString( value, true ) ? options.find( option => option?.style?.fontFamily === value ) : undefined;
    const selected = found ?? options.at( 0 );

    const setFontFamily = ( { selectedItem } ) => onChange( selectedItem?.style?.fontFamily );

    return (
        <CustomSelectControl
            label={ label ?? __( 'Font', 'fooconvert' ) }
            className={ classnames( CLASS_NAME, className ) }
            value={ selected }
            options={ options }
            onChange={ setFontFamily }
            size={ size }
            { ...restProps }
        />
    );
};

export default FontFamilyControl;