import EditBlock from "./EditBlock";
import { $object, useRootAttributes } from "#editor";
import { FLYOUT_DEFAULTS } from "../../../../Edit";
import EditStyles from "./EditStyles";
import ViewStateControls from "../../../../components/view-state-controls";

export const CONTENT_CLASS_NAME = 'fc--flyout-content';

const Edit = props => {

    // strip out the original attributes and setAttributes as we store the values on the parent
    const {
        attributes: _attributes,
        setAttributes: _setAttributes,
        ...restProps
    } = props;

    const [ parentAttributes, setParentAttributes ] = useRootAttributes( 'fc/flyout' );

    const attributes = parentAttributes?.content ?? {};
    const setAttributes = value => setParentAttributes( { content: $object( attributes, value ) } );
    const attributesDefaults = { ...( FLYOUT_DEFAULTS?.content ?? {} ) };

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
            <ViewStateControls/>
            <EditBlock { ...customProps }/>
            <EditStyles { ...customProps }/>
        </>
    );
};

export default Edit;