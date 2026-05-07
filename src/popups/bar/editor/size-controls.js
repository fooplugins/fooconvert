import { __ } from "@wordpress/i18n";
import { SizeControl, ToggleSelectControl } from "#editor";

export const BAR_WIDTH_MODE_FULL = "full";
export const BAR_WIDTH_MODE_CONTENT = "content";
export const BAR_WIDTH_DEFAULT = "fit-content";

export const BAR_WIDTH_MODE_OPTIONS = [
    {
        value: BAR_WIDTH_MODE_FULL,
        label: __( "Full width", "fooconvert" ),
    },
    {
        value: BAR_WIDTH_MODE_CONTENT,
        label: __( "Content width", "fooconvert" ),
    },
];

export const BAR_WIDTH_SIZES = [
    {
        value: BAR_WIDTH_DEFAULT,
        abbr: __( "Fit", "fooconvert" ),
        label: __( "Fit Content", "fooconvert" ),
    },
    {
        value: "280px",
        abbr: __( "S", "fooconvert" ),
        label: __( "Small", "fooconvert" ),
    },
    {
        value: "480px",
        abbr: __( "M", "fooconvert" ),
        label: __( "Medium", "fooconvert" ),
    },
    {
        value: "720px",
        abbr: __( "L", "fooconvert" ),
        label: __( "Large", "fooconvert" ),
    },
    {
        value: "1024px",
        abbr: __( "XL", "fooconvert" ),
        label: __( "Extra Large", "fooconvert" ),
    },
];

export const BAR_WIDTH_UNITS = [
    { value: "px", label: "px", default: 480, step: 4, min: 200, max: 2048 },
    { value: "%", label: "%", default: 50, step: 1, min: 1, max: 100 },
];

export const getBarWidthMode = ( settings = {}, settingsDefaults = {} ) => {
    return settings?.widthMode ?? settingsDefaults?.widthMode ?? BAR_WIDTH_MODE_FULL;
};

export const isBarContentWidthMode = ( settings = {}, settingsDefaults = {} ) => {
    return getBarWidthMode( settings, settingsDefaults ) === BAR_WIDTH_MODE_CONTENT;
};

export const getBarContentWidth = ( styles = {}, stylesDefaults = {} ) => {
    return styles?.width ?? stylesDefaults?.width ?? BAR_WIDTH_DEFAULT;
};

export const BarWidthModeControl = ( { value, onChange } ) => (
    <ToggleSelectControl
        label={ __( "Mode", "fooconvert" ) }
        value={ value }
        onChange={ onChange }
        options={ BAR_WIDTH_MODE_OPTIONS }
    />
);

export const BarWidthControl = ( { value, onChange } ) => (
    <SizeControl
        label={ __( "Width", "fooconvert" ) }
        value={ value }
        onChange={ onChange }
        sizes={ BAR_WIDTH_SIZES }
        units={ BAR_WIDTH_UNITS }
    />
);
