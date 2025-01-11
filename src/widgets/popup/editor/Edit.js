import {
    useInnerBlocks,
    VariationPicker,
    $object
} from "#editor";
import { __ } from "@wordpress/i18n";
import { useEffect } from "@wordpress/element";
import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";
import { TriggerControls } from "./components/trigger-controls";

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
                slug: 'default__close-small'
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
        setAttributes,
        context: {
            postId
        },
        attributes: {
            postId: storedPostId,
            settings,
            styles
        }
    } = props;

    // ensure the postId attribute is always current
    useEffect( () => {
        if ( postId !== storedPostId ) {
            setAttributes( { postId } );
        }
    }, [ postId, storedPostId ] );

    const attributesDefaults = { ...POPUP_DEFAULTS };

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
        stylesDefaults
    };

    return (
        <>
            <TriggerControls/>
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
            media="thumbnail"
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