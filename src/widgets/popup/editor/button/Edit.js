import { useDispatch } from "@wordpress/data";
import { store as blockEditorStore } from "@wordpress/block-editor";
import { cleanObject } from "@steveush/utils";
import EditBlock from "./EditBlock";
import { getBorderSizes, useIconSets } from "#editor";
import EditSettings from "./EditSettings";

export const BUTTON_DEFAULTS = {
    position: 'right',
    alignment: 'inside',
    styles: {
        dimensions: {
            padding: '6px'
        }
    },
    icon: {
        size: '24px',
        close: { slug: 'wordpress-close' }
    }
};

const Edit = props => {
    const {
        context: {
            'fc-popup/clientId': parentClientId,
            'fc-popup/button': buttonAttributes = {},
            'fc-popup/hideButton': isHidden,
            'fc-popup/styles': popupStyles
        }
    } = props;

    const iconSets = useIconSets();
    const { updateBlockAttributes } = useDispatch( blockEditorStore );

    const setButtonAttributes = attributes => {
        if ( typeof parentClientId === "string" ) {
            // noinspection JSIgnoredPromiseFromCall,JSCheckFunctionSignatures
            updateBlockAttributes( parentClientId, {
                button: cleanObject( {
                    ...buttonAttributes,
                    ...attributes
                } )
            }, false );
        }
    };

    const borderSizes = getBorderSizes( popupStyles?.border );

    const {
        attributes: _attributes,
        setAttributes: _setAttributes,
        ...restProps
    } = props;

    const customProps = {
        ...restProps,
        isHidden,
        parentClientId,
        borderSizes,
        // replace the default attributes and setAttributes with our custom ones that use the parent bar attributes
        attributes: buttonAttributes,
        setAttributes: setButtonAttributes,
        // provide default values that the bar custom element uses when no user defined value exists
        defaults: {
            ...BUTTON_DEFAULTS
        },
        iconSets
    };

    if ( isHidden ) {
        return null;
    }

    return (
        <>
            <EditBlock { ...customProps }/>
            <EditSettings { ...customProps }/>
        </>
    );
};

export default Edit;