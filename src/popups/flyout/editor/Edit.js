import {
    getPopupEditorBackground,
    useInnerBlocks,
    PopupTypeTemplatePicker,
    $object
} from "#editor";
import { useEffect } from "@wordpress/element";
import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";
import ViewStateControls from "./components/view-state-controls";
import TriggerControls from "./components/trigger-controls/Component";

export const FLYOUT_CLASS_NAME = 'fc--flyout';
const FLYOUT_POST_TYPE = 'fc-flyout';

export const FLYOUT_DEFAULTS = {
    settings: {
        position: 'right-center',
        transitions: false,
        maxOnMobile: false
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
            color: {
                background: '#FFFFFF',
                icon: '#000000'
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
        }
    },
    content: {
        styles: {
            width: '480px',
            color: {
                background: '#FFFFFF',
                text: '#000000'
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
            postType: storedPostType,
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

    useEffect( () => {
        if ( FLYOUT_POST_TYPE !== storedPostType ) {
            setAttributes( { postType: FLYOUT_POST_TYPE } );
        }
    }, [ storedPostType ] );

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
        <PopupTypeTemplatePicker
            currentPopupType="flyout"
            { ...props }
        />
    );
};

const EditWrapper = props => {
    const { clientId } = props;
    const editorBackground = getPopupEditorBackground();
    const { hasInnerBlocks, innerBlocks } = useInnerBlocks( clientId );
    const Component = hasInnerBlocks ? Edit : EditPlaceholder;
    return (
        <div className={ `${ FLYOUT_CLASS_NAME }__editor ${ FLYOUT_CLASS_NAME }__editor--${ editorBackground }` }>
            <Component { ...{ ...props, hasInnerBlocks, innerBlocks } } />
        </div>
    );
};

export default EditWrapper;
