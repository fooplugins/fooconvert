import EditBlock from "./EditBlock";
import { $object, useBlockAttributes} from "#editor";
import { POPUP_DEFAULTS } from "../../../../Edit";
import EditStyles from "./EditStyles";

export const CONTENT_CLASS_NAME = 'fc--popup-content';

const Edit = props => {

    const {
        context: {
            'fc-popup/clientId': parentClientId
        }
    } = props;

    // strip out the original attributes and setAttributes as we store the values on the parent
    const {
        attributes: _attributes,
        setAttributes: _setAttributes,
        ...restProps
    } = props;

    const [ parentAttributes, setParentAttributes ] = useBlockAttributes( parentClientId );

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
    };

    return (
        <>
            <EditBlock { ...customProps }/>
            <EditStyles { ...customProps }/>
        </>
    );
};

export default Edit;