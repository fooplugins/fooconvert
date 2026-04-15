import { __ } from "@wordpress/i18n";
import { image, postContent, pullLeft, pullRight } from "@wordpress/icons";
import { cleanObject } from "@steveush/utils";

export const BUTTON_ICON_LAYOUTS = [ "icon-only", "icon-text", "text-icon" ];
export const BUTTON_TEXT_LAYOUTS = [ "text-only", "icon-text", "text-icon" ];

export const BUTTON_TEXT_FORMATS = [ "core/bold", "core/italic" ];

export const BUTTON_LAYOUT_OPTIONS = [ {
    value: "text-only",
    label: __( "Text only", "fooconvert" ),
    icon: postContent,
}, {
    value: "icon-only",
    label: __( "Icon only", "fooconvert" ),
    icon: image,
}, {
    value: "icon-text",
    label: __( "Icon before text", "fooconvert" ),
    icon: pullLeft,
}, {
    value: "text-icon",
    label: __( "Text before icon", "fooconvert" ),
    icon: pullRight,
} ];

export const BUTTON_ICON_SIZE_OPTIONS = [ {
    value: "16px",
    abbr: __( "S", "fooconvert" ),
    label: __( "Small", "fooconvert" ),
}, {
    value: "24px",
    abbr: __( "M", "fooconvert" ),
    label: __( "Medium", "fooconvert" ),
}, {
    value: "32px",
    abbr: __( "L", "fooconvert" ),
    label: __( "Large", "fooconvert" ),
}, {
    value: "48px",
    abbr: __( "XL", "fooconvert" ),
    label: __( "Extra Large", "fooconvert" ),
} ];

export const isButtonIconLayout = layout => BUTTON_ICON_LAYOUTS.includes( layout );

export const createButtonIconSetter = ( setButtonSettings, currentIcon ) => newValue => {
    const previousValue = currentIcon ?? {};
    const nextValue = typeof newValue === "object"
        ? {
            ...previousValue,
            ...newValue,
        }
        : undefined;

    setButtonSettings( {
        icon: cleanObject( nextValue ),
    } );
};
