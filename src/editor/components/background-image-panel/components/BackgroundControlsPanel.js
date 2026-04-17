import { getFilename } from "@wordpress/url";
import { Dropdown } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import InspectorImagePreviewItem from "./InspectorImagePreviewItem";

import { DropdownContentWrapper } from "../../experimental";

const BACKGROUND_POPOVER_PROPS = {
    placement: 'left-start',
    offset: 36,
    shift: true,
    className: 'block-editor-global-styles-background-panel__popover',
};

const BackgroundControlsPanel = ( {
                                      label,
                                      filename,
                                      url: imgUrl,
                                      children,
                                      isOpen = false,
                                      setIsOpen = () => {},
                                      onToggle: onToggleCallback = () => {},
                                      hasImageValue,
                                  } ) => {
    if ( ! hasImageValue ) {
        return;
    }

    const imgLabel =
        label || getFilename( imgUrl ) || __( 'Add background image' );

    return (
        <Dropdown
            open={ isOpen }
            onToggle={ setIsOpen }
            onClose={ () => setIsOpen( false ) }
            popoverProps={ BACKGROUND_POPOVER_PROPS }
            renderToggle={ ( { onToggle, isOpen } ) => {
                const toggleProps = {
                    onClick: onToggle,
                    className:
                        'block-editor-global-styles-background-panel__dropdown-toggle',
                    'aria-expanded': isOpen,
                    'aria-label': __(
                        'Background size, position and repeat options.'
                    ),
                    isOpen,
                };
                return (
                    <InspectorImagePreviewItem
                        imgUrl={ imgUrl }
                        filename={ filename }
                        label={ imgLabel }
                        toggleProps={ toggleProps }
                        as="button"
                        onToggleCallback={ onToggleCallback }
                    />
                );
            } }
            renderContent={ () => (
                <DropdownContentWrapper
                    className="block-editor-global-styles-background-panel__dropdown-content-wrapper"
                    paddingSize="medium"
                >
                    { children }
                </DropdownContentWrapper>
            ) }
        />
    );
};

export default BackgroundControlsPanel;
