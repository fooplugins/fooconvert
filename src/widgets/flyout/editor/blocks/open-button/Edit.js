import { useEffect } from "@wordpress/element";
import { $object, useBlockAttributes, useIconSets } from "#editor";

import { FLYOUT_DEFAULTS } from "../../Edit";

import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";
import EditStyles from "./EditStyles";

const Edit = props => {
    const {
        isSelected,
        context: {
            'fc-flyout/clientId': parentClientId
        }
    } = props;

    const {
        attributes: _attributes,
        setAttributes: _setAttributes,
        ...restProps
    } = props;

    const iconSets = useIconSets();

    const [ parentAttributes, setParentAttributes ] = useBlockAttributes( parentClientId );

    const attributes = parentAttributes?.openButton ?? {};
    const setAttributes = value => setParentAttributes( { openButton: $object( attributes, value ) } );
    const attributesDefaults = { ...( FLYOUT_DEFAULTS?.openButton ?? {} ) };

    const settings = attributes?.settings ?? {};
    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = { ...( attributesDefaults?.settings ?? {} ) };

    const styles = attributes?.styles ?? {};
    const setStyles = value => setAttributes( { styles: $object( styles, value ) } );
    const stylesDefaults = { ...( attributesDefaults?.styles ?? {} ) };

    const { viewState: parentViewState } = parentAttributes;

    useEffect( () => {
        if ( parentViewState === 'open' && isSelected ) {
            setParentAttributes( { viewState: 'closed' } );
        }
    }, [ isSelected ] );

    if ( parentViewState === 'open' || settings?.hidden ) {
        return null;
    }

    const customProps = {
        ...restProps,
        parentClientId,
        parentAttributes,
        setParentAttributes,
        attributes,
        setAttributes,
        settings,
        setSettings,
        styles,
        setStyles,
        attributesDefaults,
        settingsDefaults,
        stylesDefaults,
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