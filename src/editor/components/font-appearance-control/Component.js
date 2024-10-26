import { __ } from "@wordpress/i18n";
import { CustomSelectControl } from "@wordpress/components";
import classnames from "classnames";
import { isFontAppearance } from "./utils";

const FONT_APPEARANCE_OPTIONS = [
    {
        key: 'default',
        name: __( 'Default', 'fooconvert' )
    }, {
        key: 'thin',
        name: __( 'Thin', 'fooconvert' ),
        style: { fontStyle: 'normal', fontWeight: 100 }
    }, {
        key: 'extra-light',
        name: __( 'Extra Light', 'fooconvert' ),
        style: { fontStyle: 'normal', fontWeight: 200 }
    }, {
        key: 'light',
        name: __( 'Light', 'fooconvert' ),
        style: { fontStyle: 'normal', fontWeight: 300 }
    }, {
        key: 'regular',
        name: __( 'Regular', 'fooconvert' ),
        style: { fontStyle: 'normal', fontWeight: 400 }
    }, {
        key: 'medium',
        name: __( 'Medium', 'fooconvert' ),
        style: { fontStyle: 'normal', fontWeight: 500 }
    }, {
        key: 'semi-bold',
        name: __( 'Semi Bold', 'fooconvert' ),
        style: { fontStyle: 'normal', fontWeight: 600 }
    }, {
        key: 'bold',
        name: __( 'Bold', 'fooconvert' ),
        style: { fontStyle: 'normal', fontWeight: 700 }
    }, {
        key: 'extra-bold',
        name: __( 'Extra Bold', 'fooconvert' ),
        style: { fontStyle: 'normal', fontWeight: 800 }
    }, {
        key: 'black',
        name: __( 'Black', 'fooconvert' ),
        style: { fontStyle: 'normal', fontWeight: 900 }
    }, {
        key: 'thin-italic',
        name: __( 'Thin Italic', 'fooconvert' ),
        style: { fontStyle: 'italic', fontWeight: 100 }
    }, {
        key: 'extra-light-italic',
        name: __( 'Extra Light Italic', 'fooconvert' ),
        style: { fontStyle: 'italic', fontWeight: 200 }
    }, {
        key: 'light-italic',
        name: __( 'Light Italic', 'fooconvert' ),
        style: { fontStyle: 'italic', fontWeight: 300 }
    }, {
        key: 'regular-italic',
        name: __( 'Regular Italic', 'fooconvert' ),
        style: { fontStyle: 'italic', fontWeight: 400 }
    }, {
        key: 'medium-italic',
        name: __( 'Medium Italic', 'fooconvert' ),
        style: { fontStyle: 'italic', fontWeight: 500 }
    }, {
        key: 'semi-bold-italic',
        name: __( 'Semi Bold Italic', 'fooconvert' ),
        style: { fontStyle: 'italic', fontWeight: 600 }
    }, {
        key: 'bold-italic',
        name: __( 'Bold Italic', 'fooconvert' ),
        style: { fontStyle: 'italic', fontWeight: 700 }
    }, {
        key: 'extra-bold-italic',
        name: __( 'Extra Bold Italic', 'fooconvert' ),
        style: { fontStyle: 'italic', fontWeight: 800 }
    }, {
        key: 'black-italic',
        name: __( 'Black Italic', 'fooconvert' ),
        style: { fontStyle: 'italic', fontWeight: 900 }
    }
];

const CLASS_NAME = 'fc--font-appearance-control';

const FontAppearanceControl = ( props ) => {

    const {
        value,
        onChange,
        options = FONT_APPEARANCE_OPTIONS,
        className,
        label,
        ...restProps
    } = props;

    const hasValue = isFontAppearance( value );
    const found = hasValue ? options.find( option => option?.style?.fontStyle === value.fontStyle && option?.style?.fontWeight === value.fontWeight ) : undefined;
    const selected = found ?? options.at( 0 );

    const setFontAppearance = ( { selectedItem } ) => onChange( selectedItem?.style );

    return (
        <CustomSelectControl
            label={ label ?? __( 'Appearance', 'fooconvert' ) }
            className={ classnames( CLASS_NAME, className ) }
            value={ selected }
            options={ options }
            onChange={ setFontAppearance }
            { ...restProps }
        />
    );
};

export default FontAppearanceControl;