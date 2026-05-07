import { $object } from "#editor";

import EditBlock from "./EditBlock";
import EditStyles from "./EditStyles";

export const SPLIT_LAYOUT_PANEL_CLASS_NAME = "fc--split-layout-panel";

export const SPLIT_LAYOUT_PANEL_DEFAULTS = {
    settings: {
        justifyContent: "center",
        horizontalAlignment: "center",
    },
    styles: {
        dimensions: {
            gap: "12px",
        },
    },
};

const Edit = ( props ) => {
    const {
        attributes: {
            settings,
            styles,
        },
        setAttributes,
    } = props;

    const attributesDefaults = { ...SPLIT_LAYOUT_PANEL_DEFAULTS };

    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = { ...( attributesDefaults?.settings ?? {} ) };

    const setStyles = value => setAttributes( { styles: $object( styles, value ) } );
    const stylesDefaults = { ...( attributesDefaults?.styles ?? {} ) };

    const customProps = {
        ...props,
        attributesDefaults,
        settings,
        setSettings,
        settingsDefaults,
        styles,
        setStyles,
        stylesDefaults,
    };

    return (
        <>
            <EditBlock { ...customProps } />
            <EditStyles { ...customProps } />
        </>
    );
};

export default Edit;
