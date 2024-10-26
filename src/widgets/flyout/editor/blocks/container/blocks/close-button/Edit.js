import EditBlock from "./EditBlock";
import { $object, getBorderSizes, useBlockAttributes, useIconSets } from "#editor";
import EditSettings from "./EditSettings";

import EditStyles from "./EditStyles";
import { FLYOUT_DEFAULTS } from "../../../../Edit";

const Edit = props => {
    const {
        context: {
            'fc-flyout/clientId': parentClientId
        }
    } = props;

    const iconSets = useIconSets();

    const [ parentAttributes, setParentAttributes ] = useBlockAttributes( parentClientId );

    const attributes = parentAttributes?.closeButton ?? {};
    const setAttributes = value => setParentAttributes( { closeButton: $object( attributes, value ) } );
    const defaults = FLYOUT_DEFAULTS?.closeButton ?? {};

    const settings = attributes?.settings ?? {};
    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const defaultSettings = defaults?.settings;

    const styles = attributes?.styles ?? {};
    const setStyles = value => setAttributes( { styles: $object( styles, value ) } );
    const defaultStyles = defaults?.styles;

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
        parentClientId,
        parentAttributes,
        setParentAttributes,
        // replace the default attributes and setAttributes with our custom ones that use the parent bar attributes
        attributes,
        setAttributes,
        defaults,
        settings,
        setSettings,
        defaultSettings,
        styles,
        setStyles,
        defaultStyles,
        iconSets
    };

    return (
        <>
            <EditBlock { ...customProps }/>
            <EditSettings { ...customProps }/>
            <EditStyles { ...customProps }/>
        </>
    );
};

export default Edit;