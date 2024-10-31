import EditBlock from "./EditBlock";
import { $object, useRootAttributes } from "#editor";
import EditSettings from "./EditSettings";

import EditStyles from "./EditStyles";
import { FLYOUT_DEFAULTS } from "../../../../Edit";
import ViewStateControls from "../../../../components/view-state-controls";

const Edit = props => {
    const [ parentAttributes, setParentAttributes ] = useRootAttributes( 'fc/flyout' );

    const attributes = parentAttributes?.closeButton ?? {};
    const setAttributes = value => setParentAttributes( { closeButton: $object( attributes, value ) } );
    const attributesDefaults = { ...( FLYOUT_DEFAULTS?.closeButton ?? {} ) };

    const settings = attributes?.settings ?? {};
    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = attributesDefaults?.settings;

    const styles = attributes?.styles ?? {};
    const setStyles = value => setAttributes( { styles: $object( styles, value ) } );
    const stylesDefaults = attributesDefaults?.styles;

    if ( settings?.hidden ) {
        return null;
    }

    const {
        attributes: _attributes,
        setAttributes: _setAttributes,
        ...restProps
    } = props;

    const customProps = {
        ...restProps,
        parentAttributes,
        setParentAttributes,
        // replace the default attributes and setAttributes with our custom ones that use the parent bar attributes
        attributes,
        setAttributes,
        attributesDefaults,
        settings,
        setSettings,
        settingsDefaults,
        styles,
        setStyles,
        stylesDefaults
    };

    return (
        <>
            <ViewStateControls/>
            <EditBlock { ...customProps }/>
            <EditSettings { ...customProps }/>
            <EditStyles { ...customProps }/>
        </>
    );
};

export default Edit;