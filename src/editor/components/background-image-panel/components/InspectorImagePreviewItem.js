import { useEffect } from "@wordpress/element";
import { Button, FlexItem, VisuallyHidden } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";

import { HStack, Truncate } from "../../experimental";

const InspectorImagePreviewItem = ( {
                                        as = 'span',
                                        imgUrl,
                                        toggleProps = {},
                                        filename,
                                        label,
                                        className,
                                        onToggleCallback = ( isOpen ) => {},
                                    } ) => {
    const { isOpen, ...restToggleProps } = toggleProps;

    useEffect( () => {
        if ( typeof isOpen !== 'undefined' ) {
            onToggleCallback( isOpen );
        }
    }, [ isOpen, onToggleCallback ] );

    const renderPreviewContent = () => {
        return (
            <HStack
                justify="flex-start"
                as="span"
                className="block-editor-global-styles-background-panel__inspector-preview-inner"
            >
                { imgUrl && (
                    <span
                        className="block-editor-global-styles-background-panel__inspector-image-indicator-wrapper"
                        aria-hidden
                    >
						<span
                            className="block-editor-global-styles-background-panel__inspector-image-indicator"
                            style={ {
                                backgroundImage: `url(${ imgUrl })`,
                            } }
                        />
					</span>
                ) }
                <FlexItem as="span" style={ imgUrl ? {} : { flexGrow: 1 } }>
                    <Truncate
                        numberOfLines={ 1 }
                        className="block-editor-global-styles-background-panel__inspector-media-replace-title"
                    >
                        { label }
                    </Truncate>
                    <VisuallyHidden as="span">
                        { imgUrl
                            ? sprintf(
                                /* translators: %s: file name */
                                __( 'Background image: %s' ),
                                filename || label
                            )
                            : __( 'No background image selected' ) }
                    </VisuallyHidden>
                </FlexItem>
            </HStack>
        );
    };

    return as === 'button' ? (
        <Button
            __next40pxDefaultSize
            className={ className }
            { ...restToggleProps }
            aria-expanded={ isOpen }
        >
            { renderPreviewContent() }
        </Button>
    ) : (
        renderPreviewContent()
    );
};

export default InspectorImagePreviewItem;