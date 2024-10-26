import { useDispatch } from "@wordpress/data";
import { store as blockEditorStore } from "@wordpress/block-editor";
import { cleanObject } from "@steveush/utils";
import EditBlock from "./EditBlock";
import { useIconSets } from "#editor";
import EditSettings from "./EditSettings";

export const BUTTON_DEFAULTS = {
    action: 'close',
    position: 'right',
    styles: {
        dimensions: {
            padding: '16px',
            margin: '16px'
        }
    },
    icon: {
        size: '32px',
        close: { slug: 'wordpress-reset' },
        open: { slug: 'wordpress-create' }
    }
};

const Edit = props => {
    const {
        context: {
            'fc-bar/clientId': parentClientId,
            'fc-bar/button': buttonAttributes = {},
            'fc-bar/hideButton': isHidden
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

    const {
        attributes: _attributes,
        setAttributes: _setAttributes,
        ...restProps
    } = props;

    const customProps = {
        ...restProps,
        isHidden,
        parentClientId,
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