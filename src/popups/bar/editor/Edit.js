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
import { BAR_WIDTH_MODE_FULL } from "./size-controls";

export const BAR_CLASS_NAME = 'fc--bar';
const BAR_POST_TYPE = 'fc-bar';

export const BAR_DEFAULTS = {
    settings: {
        position: 'top',
        transitions: false,
        widthMode: BAR_WIDTH_MODE_FULL
    },
    openButton: {
        settings: {
            hidden: false,
            position: 'right',
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
        },
        styles: {
            color: {
                icon: '#000000'
            }
        }
    },
    content: {
        styles: {
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
            styles,
            content
        }
    } = props;

    // ensure the postId attribute is always current
    useEffect( () => {
        if ( postId !== storedPostId ) {
            setAttributes( { postId } );
        }
    }, [ postId, storedPostId ] );

    useEffect( () => {
        if ( BAR_POST_TYPE !== storedPostType ) {
            setAttributes( { postType: BAR_POST_TYPE } );
        }
    }, [ storedPostType ] );

    const attributesDefaults = { ...BAR_DEFAULTS };

    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = { ...( attributesDefaults?.settings ?? {} ) };

    const setStyles = value => setAttributes( { styles: $object( styles, value ) } );
    const stylesDefaults = { ...( attributesDefaults?.styles ?? {} ) };

    const setContent = value => setAttributes( { content: $object( content, value ) } );
    const contentDefaults = { ...( attributesDefaults?.content ?? {} ) };

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
        stylesDefaults,
        content,
        setContent,
        contentDefaults
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
            currentPopupType="bar"
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
        <div className={ `${ BAR_CLASS_NAME }__editor ${ BAR_CLASS_NAME }__editor--${ editorBackground }` }>
            <Component { ...{ ...props, hasInnerBlocks, innerBlocks } } />
        </div>
    );
};

export default EditWrapper;
