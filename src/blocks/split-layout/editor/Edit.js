import { useEffect } from "@wordpress/element";
import { $object, generateGUID } from "#editor";
import { isString } from "@steveush/utils";

import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";

export const SPLIT_LAYOUT_CLASS_NAME = "fc--split-layout";

export const SPLIT_LAYOUT_DEFAULTS = {
    settings: {
        fixedSide: "right",
        fixedWidth: "360px",
        verticalAlignment: "center",
    },
    styles: {
        dimensions: {
            gap: "16px",
        },
    },
};

const Edit = ( props ) => {
    const {
        setAttributes,
        attributes: {
            uniqueId,
            settings,
            styles,
        },
    } = props;

    useEffect( () => {
        if ( !isString( uniqueId ) ) {
            setAttributes( { uniqueId: generateGUID() } );
        }
    }, [ uniqueId ] );

    const attributesDefaults = { ...SPLIT_LAYOUT_DEFAULTS };

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
            <EditSettings { ...customProps } />
        </>
    );
};

export default Edit;
