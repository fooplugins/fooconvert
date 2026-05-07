import {
    getPopupEditorBackground,
    useInnerBlocks,
    PopupTypeTemplatePicker,
    $object
} from "#editor";
import { useEffect } from "@wordpress/element";
import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";
import { TriggerControls } from "./components/trigger-controls";

export const OVERLAY_CLASS_NAME = 'fc--overlay';
const OVERLAY_POST_TYPE = 'fc-popup';

export const OVERLAY_DEFAULTS = {
    settings: {
        trigger: {
            version: 2,
            lifetime: "page",
            frequency: {
                mode: "repeat",
                cooldownSeconds: 0
            },
            steps: [ {
                event: "fc.anchor.click",
                where: {
                    ids: []
                }
            } ]
        },
        transitions: false,
        maxOnMobile: false
    },
    styles: {
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
        }
    },
    content: {
        styles: {
            width: '720px',
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
            template: storedTemplate,
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
        if ( OVERLAY_POST_TYPE !== storedPostType ) {
            setAttributes( { postType: OVERLAY_POST_TYPE } );
        }
    }, [ storedPostType ] );

    const attributesDefaults = { ...OVERLAY_DEFAULTS };

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
        <PopupTypeTemplatePicker
            currentPopupType="overlay"
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
        <div className={ `${ OVERLAY_CLASS_NAME }__editor ${ OVERLAY_CLASS_NAME }__editor--${ editorBackground }` }>
            <Component { ...{ ...props, hasInnerBlocks, innerBlocks } } />
        </div>
    );
};

export default EditWrapper;
