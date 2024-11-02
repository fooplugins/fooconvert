import { useRootAttributes } from "#editor";
import { BlockControls } from "@wordpress/block-editor";
import { ToolbarButton, ToolbarGroup } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

const ViewStateControls = () => {
    const [ attributes, setAttributes ] = useRootAttributes( 'fc/bar' );
    const { viewState = 'open' } = attributes ?? {};
    const toggleViewState = () => setAttributes( { viewState: viewState === 'open' ? 'closed' : 'open' } );
    const viewStateLabel = viewState === 'open' ? __( 'Closed', 'fooconvert' ) : __( 'Open', 'fooconvert' );
    const label = sprintf(
        // translators: %s: Open/Closed State
        __( '%s View', 'fooconvert' ),
        viewStateLabel
    );
    return (
        <BlockControls group="other">
            <ToolbarGroup>
                <ToolbarButton label={ __( 'Toggle View', 'fooconvert' ) } onClick={ toggleViewState }>
                    { label }
                </ToolbarButton>
            </ToolbarGroup>
        </BlockControls>
    );
};

export default ViewStateControls;