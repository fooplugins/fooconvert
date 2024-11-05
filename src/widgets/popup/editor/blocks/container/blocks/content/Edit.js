import EditBlock from "./EditBlock";
import { $object, useRootAttributes } from "#editor";
import EditStyles from "./EditStyles";
import { POPUP_DEFAULTS } from "../../../../Edit";

export const CONTENT_CLASS_NAME = 'fc--popup-content';

const Edit = props => {

    // strip out the original attributes and setAttributes as we store the values on the parent
    const {
        attributes: _attributes,
        setAttributes: _setAttributes,
        ...restProps
    } = props;

    const [ parentAttributes, setParentAttributes ] = useRootAttributes( 'fc/popup' );

    const attributes = parentAttributes?.content ?? {};
    const setAttributes = value => setParentAttributes( { content: $object( attributes, value ) } );
    const attributesDefaults = { ...( POPUP_DEFAULTS?.content ?? {} ) };

    const settings = attributes?.settings ?? {};
    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = { ...( attributesDefaults?.settings ?? {} ) };

    const styles = attributes?.styles ?? {};
    const setStyles = value => setAttributes( { styles: $object( styles, value ) } );
    const stylesDefaults = { ...( attributesDefaults?.styles ?? {} ) };

    const customProps = {
        ...restProps,
        parentAttributes,
        setParentAttributes,
        attributes,
        setAttributes,
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
            <EditBlock { ...customProps }/>
            <EditStyles { ...customProps }/>
        </>
    );
};

export default Edit;