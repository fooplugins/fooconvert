import { useRef, useState } from "@wordpress/element";
import { useDispatch, useSelect } from "@wordpress/data";
import { isBlobURL } from "@wordpress/blob";
import { __ } from "@wordpress/i18n";
import { MediaReplaceFlow, store as blockEditorStore } from "@wordpress/block-editor";
import { setImmutably } from "../../../utils";
import { hasBackgroundImageValue } from "../utils";
import { getFilename } from "@wordpress/url";
import LoadingSpinner from "./LoadingSpinner";
import InspectorImagePreviewItem from "./InspectorImagePreviewItem";
import { Button, DropZone, MenuItem } from "@wordpress/components";
import { store as noticesStore } from '@wordpress/notices';
import { focus } from '@wordpress/dom';
import clsx from "clsx";
import { VStack } from "../../experimental";

const IMAGE_BACKGROUND_TYPE = 'image';

const BackgroundImageControls = ( {
                                      onChange,
                                      style,
                                      inheritedValue,
                                      onSelectMedia,
                                      onRemoveImage = () => {},
                                      onResetImage = () => {},
                                      onOpenBackgroundGenerator = () => {},
                                      displayInPanel,
                                      defaultValues,
                                      showBackgroundGenerator = false,
                                  } ) => {
    const [ isUploading, setIsUploading ] = useState( false );
    const { getSettings } = useSelect( blockEditorStore, [] );

    const { id, title, url } = style?.background?.backgroundImage || {
        ...inheritedValue?.background?.backgroundImage,
    };
    const replaceContainerRef = useRef();
    const { createErrorNotice } = useDispatch( noticesStore );
    const onUploadError = ( message ) => {
        createErrorNotice( message, { type: 'snackbar' } );
        setIsUploading( false );
    };

    const resetBackgroundImage = () =>
        onChange(
            setImmutably(
                style,
                [ 'background', 'backgroundImage' ],
                undefined
            )
        );

    const applySelectedMedia = ( media ) => {
        if ( "function" === typeof onSelectMedia ) {
            onSelectMedia( media );
            return;
        }

        if ( ! media || ! media.url ) {
            resetBackgroundImage();
            return;
        }

        const sizeValue =
            style?.background?.backgroundSize || defaultValues?.backgroundSize;
        const positionValue = style?.background?.backgroundPosition;
        onChange(
            setImmutably( style, [ 'background' ], {
                ...style?.background,
                backgroundImage: {
                    url: media.url,
                    id: media.id,
                    source: 'file',
                    title: media.title || undefined,
                },
                backgroundPosition:
                /*
                 * A background image uploaded and set in the editor receives a default background position of '50% 0',
                 * when the background image size is the equivalent of "Tile".
                 * This is to increase the chance that the image's focus point is visible.
                 * This is in-editor only to assist with the user experience.
                 */
                    ! positionValue && ( 'auto' === sizeValue || ! sizeValue )
                        ? '50% 0'
                        : positionValue,
                backgroundSize: sizeValue,
            } )
        );
    };

    const handleSelectMedia = ( media ) => {
        if ( ! media || ! media.url ) {
            applySelectedMedia( media );
            setIsUploading( false );
            return;
        }

        if ( isBlobURL( media.url ) ) {
            setIsUploading( true );
            return;
        }

        // For media selections originated from a file upload.
        if (
            ( media.media_type &&
                media.media_type !== IMAGE_BACKGROUND_TYPE ) ||
            ( ! media.media_type &&
                media.type &&
                media.type !== IMAGE_BACKGROUND_TYPE )
        ) {
            onUploadError(
                __( 'Only images can be used as a background image.' )
            );
            return;
        }

        applySelectedMedia( media );
        setIsUploading( false );
    };

    // Drag and drop callback, restricting image to one.
    const onFilesDrop = ( filesList ) => {
        getSettings().mediaUpload( {
            allowedTypes: [ IMAGE_BACKGROUND_TYPE ],
            filesList,
            onFileChange( [ image ] ) {
                handleSelectMedia( image );
            },
            onError: onUploadError,
            multiple: false,
        } );
    };

    const hasValue = hasBackgroundImageValue( style );

    const closeAndFocus = () => {
        const [ toggleButton ] = focus.tabbable.find(
            replaceContainerRef.current
        );
        // Focus the toggle button and close the dropdown menu.
        // This ensures similar behaviour as to selecting an image, where the dropdown is
        // closed and focus is redirected to the dropdown toggle button.
        toggleButton?.focus();
        toggleButton?.click();
    };

    const onRemove = () =>
        onChange(
            setImmutably( style, [ 'background' ], {
                backgroundImage: 'none',
            } )
        );
    const canRemove = ! hasValue && hasBackgroundImageValue( inheritedValue );
    const imgLabel =
        title || getFilename( url ) || __( 'Add background image' );

    return (
        <div
            ref={ replaceContainerRef }
            className="block-editor-global-styles-background-panel__image-tools-panel-item"
        >
            { isUploading && <LoadingSpinner /> }
            <VStack spacing={ 2 }>
                <MediaReplaceFlow
                    mediaId={ id }
                    mediaURL={ url }
                    allowedTypes={ [ IMAGE_BACKGROUND_TYPE ] }
                    accept="image/*"
                    onSelect={ handleSelectMedia }
                    popoverProps={ {
                        className: clsx( {
                            'block-editor-global-styles-background-panel__media-replace-popover':
                            displayInPanel,
                        } ),
                    } }
                    name={
                        <InspectorImagePreviewItem
                            className="block-editor-global-styles-background-panel__image-preview"
                            imgUrl={ url }
                            filename={ title }
                            label={ imgLabel }
                        />
                    }
                    renderToggle={ ( props ) => (
                        <Button { ...props } __next40pxDefaultSize />
                    ) }
                    onError={ onUploadError }
                    onReset={ () => {
                        closeAndFocus();
                        onResetImage();
                    } }
                >
                    { canRemove && (
                        <MenuItem
                            onClick={ () => {
                                closeAndFocus();
                                onRemove();
                                onRemoveImage();
                            } }
                        >
                            { __( 'Remove' ) }
                        </MenuItem>
                    ) }
                </MediaReplaceFlow>
                { showBackgroundGenerator ? (
                    <Button
                        variant="secondary"
                        onClick={ onOpenBackgroundGenerator }
                        disabled={ isUploading }
                    >
                        { __( "Generate Background", "fooconvert" ) }
                    </Button>
                ) : null }
            </VStack>
            <DropZone
                onFilesDrop={ onFilesDrop }
                label={ __( 'Drop to upload' ) }
            />
        </div>
    );
};

export default BackgroundImageControls;
