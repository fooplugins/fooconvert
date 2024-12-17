import {
    useInnerBlocks,
    VariationPicker,
    $object
} from "#editor";
import { __ } from "@wordpress/i18n";
import { useEffect } from "@wordpress/element";
import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";
import ViewStateControls from "./components/view-state-controls";
import TriggerControls from "./components/trigger-controls/Component";

export const FLYOUT_CLASS_NAME = 'fc--flyout';

export const FLYOUT_DEFAULTS = {
    settings: {
        position: 'right-center',
        transitions: false,
        maxOnMobile: false
    },
    styles: {
        dimensions: {
            padding: '32px'
        }
    },
    openButton: {
        settings: {
            hidden: false,
            icon: {
                size: '32px',
                slug: 'default__plus'
            }
        },
        styles: {
            dimensions: {
                padding: '6px'
            },
            color: {
                background: '#FFFFFF',
                icon: '#000000'
            },
            border: {
                radius: '4px',
                color: '#DDDDDD',
                style: 'solid',
                width: '1px'
            }
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
            width: '480px',
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
            viewState,
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

    const attributesDefaults = { ...FLYOUT_DEFAULTS };

    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = { ...( attributesDefaults?.settings ?? {} ) };

    const setStyles = value => setAttributes( { styles: $object( styles, value ) } );
    const stylesDefaults = { ...( attributesDefaults?.styles ?? {} ) };

    const setViewState = value => setAttributes( { viewState: value } );

    const customProps = {
        ...props,
        attributesDefaults,
        viewState,
        setViewState,
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
        <div className={ `${ FLYOUT_CLASS_NAME }__editor` }>
            <Component { ...{ ...props, hasInnerBlocks, innerBlocks } } />
        </div>
    );
};

export default EditWrapper;