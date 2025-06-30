import { CustomSelectControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import classnames from "classnames";
import { isString } from "@steveush/utils";
import { useFontFamilies } from "./hooks";
import isFontFamily from "./utils/isFontFamily";

const CLASS_NAME = 'fc--font-family-control';

const FontFamilyControl = ( props ) => {

    const {
        value,
        onChange,
        size = "__unstable-large",
        className,
        label,
        ...restProps
    } = props;

    const fontFamilies = useFontFamilies();

    const options = [
        {
            key: '',
            name: __( 'Default' ),
        },
        ...fontFamilies,
    ];

    let found;
    if ( isString( value ) ) {
        found = options.find( option => option?.style?.fontFamily === value );
    } else if ( isFontFamily( value ) ) {
        found = options.find( option => option?.key === value?.key );
    }

    const selected = found ?? options.at( 0 );

    const setFontFamily = ( { selectedItem } ) => onChange( selectedItem );

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