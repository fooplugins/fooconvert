import {
    ToggleSelectControl,
    useInnerBlocks,
    VariationPicker,
    $object
} from "#editor";
import { BlockControls, InspectorControls } from "@wordpress/block-editor";
import { seen, unseen, check } from "@wordpress/icons";
import { __ } from "@wordpress/i18n";
import { useEffect } from "@wordpress/element";
import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";
import {
    MenuGroup,
    MenuItem, PanelBody,
    PanelRow,
    ToolbarDropdownMenu
} from "@wordpress/components";

export const POPUP_CLASS_NAME = 'fc--popup';

export const POPUP_DEFAULTS = {
    settings: {
        trigger: {
            type: 'anchor'
        },
        transitions: false,
        maxOnMobile: false
    },
    styles: {
        dimensions: {
            padding: '32px'
        },
        color: {
            backdrop: '#00000059'
        }
    },
    closeButton: {
        settings: {
            hidden: false,
            position: 'right',
            icon: {
                size: '32px',
                close: { slug: 'wordpress-closeSmall' }
            }
        },
        styles: {
            dimensions: {
                padding: '6px'
            }
        }
    },
    content: {
        styles: {
            width: '720px',
            color: {
                background: '#FFFFFF',
                text: '#000000'
            },
            border: {
                radius: '4px',
                color: '#DDDDDD',
                style: 'solid',
                width: '1px'
            },
            dimensions: {
                padding: '16px'
            }
        }
    }
};

/**
 *
 * @param props
 * @returns {JSX.Element}
 */
const Edit = props => {
    // extract the various values used to render the block
    const {
        clientId,
        setAttributes,
        context: {
            postId
        },
        attributes: {
            clientId: storedClientId,
            postId: storedPostId,
            viewState,
            settings,
            styles,
            closeButton
        }
    } = props;

    const setSettings = value => {
        setAttributes( { settings: $object( settings, value ) } );
    };
    const settingsDefaults = { ...( POPUP_DEFAULTS?.settings ?? {} ) };

    const setStyles = value => {
        setAttributes( { styles: $object( styles, value ) } );
    };
    const stylesDefaults = { ...( POPUP_DEFAULTS?.styles ?? {} ) };

    const setCloseButton = value => {
        setAttributes( {
            closeButton: $object( closeButton, value )
        } );
    };

    // ensure the clientId attribute is always current
    useEffect( () => {
        if ( clientId !== storedClientId ) {
            setAttributes( { clientId } );
        }
    }, [ clientId, storedClientId ] );

    // ensure the postId attribute is always current
    useEffect( () => {
        if ( postId !== storedPostId ) {
            setAttributes( { postId } );
        }
    }, [ postId, storedPostId ] );

    const customProps = {
        ...props,
        viewState,
        settings,
        setSettings,
        settingsDefaults,
        styles,
        setStyles,
        stylesDefaults,
        closeButton,
        setCloseButton,
        defaults: POPUP_DEFAULTS
    };

    return (
        <>
            <EditBlock { ...customProps }/>
            <EditSettings { ...customProps }/>
        </>
    );
};

const EditPlaceholder = props => {
    return (
        <VariationPicker
            label={ __( "Choose a template", "fooconvert" ) }
            instructions={ __( "Select a template to start with.", "fooconvert" ) }
            reset={ { variation: undefined } }
            media="icon"
            { ...props }
        />
    );
};

const EditWrapper = props => {
    const { clientId } = props;
    const { hasInnerBlocks, innerBlocks } = useInnerBlocks( clientId );
    const Component = hasInnerBlocks ? Edit : EditPlaceholder;
    return (
        <div className={ `${ POPUP_CLASS_NAME }__editor` }>
            <Component { ...{ ...props, hasInnerBlocks, innerBlocks } } />
        </div>
    );
};

export default EditWrapper;