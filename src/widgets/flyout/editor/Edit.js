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
                open: { slug: 'wordpress-plus' }
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
            openButton,
            closeButton
        }
    } = props;

    const setSettings = value => {
        setAttributes( { settings: $object( settings, value ) } );
    };

    const setStyles = value => {
        setAttributes( { styles: $object( styles, value ) } );
    };

    const setOpenButton = value => {
        setAttributes( {
            openButton: $object( openButton, value )
        } );
    };

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

    const setViewState = value => setAttributes( { viewState: value } );

    // const { selectBlock } = useDispatch( blockEditorStore );
    // const isInnerBlockSelected = useIsInnerBlockSelected( clientId, true );
    // const isAnySelected = isSelected || isInnerBlockSelected;
    // useEffect( () => {
    //     if ( !isAnySelected ) {
    //         selectBlock( clientId );
    //     }
    // }, [ isAnySelected ] );

    const viewStates = [ {
        value: 'open',
        label: __( 'Open', 'fooconvert' )
    }, {
        value: 'closed',
        label: __( 'Closed', 'fooconvert' )
    } ];

    const customProps = {
        ...props,
        viewState,
        setViewState,
        settings,
        setSettings,
        styles,
        setStyles,
        openButton,
        setOpenButton,
        closeButton,
        setCloseButton,
        defaults: FLYOUT_DEFAULTS
    };

    const viewStateHelp = __( 'Switch between the different flyout view states.', 'fooconvert' );
    const viewStateText = viewState === 'closed' ? __( 'Closed', 'fooconvert' ) : __( 'Open', 'fooconvert' );

    return (
        <>
            <InspectorControls group="settings">
                <PanelBody title={ __( 'View', 'fooconvert' ) } isOpen={ true }>
                    <PanelRow>
                        <ToggleSelectControl
                            label={ __( 'View', 'fooconvert' ) }
                            hideLabelFromVision={ true }
                            value={ viewState }
                            onChange={ setViewState }
                            options={ viewStates }
                            help={ viewStateHelp }
                        />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <BlockControls group="block">
                <ToolbarDropdownMenu
                    icon={ viewState === 'closed' ? unseen : seen }
                    label={ __( 'View state', 'fooconvert' ) }
                    text={ viewStateText }
                >
                    { ( { onClose } ) => (
                        <MenuGroup>
                            { viewStates.map( ( vs, i ) => (
                                <MenuItem
                                    key={ vs.value }
                                    icon={ vs.value === viewState ? check : undefined }
                                    onClick={ () => {
                                        setViewState( vs.value );
                                        onClose();
                                    } }
                                >
                                    { vs.label }
                                </MenuItem>
                            ) ) }
                        </MenuGroup>
                    ) }
                </ToolbarDropdownMenu>
            </BlockControls>
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
        <div className={ `${ FLYOUT_CLASS_NAME }__editor` }>
            <Component { ...{ ...props, hasInnerBlocks, innerBlocks } } />
        </div>
    );
};

export default EditWrapper;