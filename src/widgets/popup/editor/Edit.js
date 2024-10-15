import {
    useInnerBlocks,
    VariationPicker
} from "#editor";
import { __ } from "@wordpress/i18n";
import { useEffect } from "@wordpress/element";
import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";

export const POPUP_CLASS_NAME = 'fc--popup';

export const POPUP_DEFAULTS = {
    styles: {
        color: {
            background: '#ffffff',
            text: '#000000',
            backdrop: '#000000b3'
        },
        dimensions: {
            padding: '16px'
        }
    },
    position: 'top',
    hideButton: false,
    hideScrollbar: false,
    backdropIgnore: false,
    transitions: false,
    lockTrigger: false,
    closeAnchor: ''
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
            postId: storedPostId
        }
    } = props;

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