import { __ } from "@wordpress/i18n";
import {
    getIconSetsIcon,
    IconPickerControl,
    renderIconSetIconToString,
    SizeControl,
    ToolsPanel,
    ToolsPanelItem
} from "#editor";
import { cleanObject, isString } from "@steveush/utils";

const IconToolsPanel = props => {
    const {
        value,
        onChange,
        iconSets,
        defaults = {},
        panelId
    } = props;

    const setIcon = ( newValue ) => {
        const previousValue = value ?? {};
        const nextValue = typeof newValue === 'object' ? {
            ...previousValue,
            ...newValue
        } : undefined;
        onChange( cleanObject( nextValue ) );
    };

    const setIconSize = nextValue => setIcon( { size: nextValue } );
    const setIconState = ( key, selectedIcon, extra ) => {
        let nextValue = undefined;
        if ( typeof selectedIcon === 'object' ) {
            const svg = renderIconSetIconToString( selectedIcon, value?.size ?? defaults?.icon?.size, extra );
            if ( svg ) {
                nextValue = { slug: selectedIcon.slug, svg };
            }
        }
        setIcon( { [ key ]: nextValue } );
    };

    const iconClose = getIconSetsIcon( iconSets, value?.close?.slug ?? defaults?.close?.slug ?? 'wordpress-close' );
    const setIconClose = selectedIcon => setIconState( 'close', selectedIcon, { slot: 'button-icon', className: 'button-icon button-icon--close' } );

    return (
        <ToolsPanel
            panelId={ panelId }
            label={ __( "Icon", "fooconvert" ) }
            resetAll={ () => setIcon( undefined ) }
        >
            <ToolsPanelItem
                panelId={ panelId }
                label={ __( 'Size', 'fooconvert' ) }
                hasValue={ () => isString( value?.size, true ) }
                onDeselect={ () => setIconSize( undefined ) }
                isShownByDefault={ true }
            >
                <SizeControl
                    label={ __( 'Size', 'fooconvert' ) }
                    value={ value?.size ?? defaults?.size }
                    onChange={ setIconSize }
                    sizes={ [{
                        value: '16px',
                        abbr: __( 'S', 'fooconvert' ),
                        label: __( 'Small', 'fooconvert' )
                    },{
                        value: '24px',
                        abbr: __( 'M', 'fooconvert' ),
                        label: __( 'Medium', 'fooconvert' )
                    },{
                        value: '32px',
                        abbr: __( 'L', 'fooconvert' ),
                        label: __( 'Large', 'fooconvert' )
                    },{
                        value: '48px',
                        abbr: __( 'XL', 'fooconvert' ),
                        label: __( 'Extra Large', 'fooconvert' )
                    }] }
                    units={ [
                        { value: 'px', label: 'px', default: 24, step: 4, min: 16, max: 256 },
                        { value: 'em', label: 'em', default: 1, step: 0.1, min: 1, max: 16 },
                        { value: 'rem', label: 'rem', default: 1, step: 0.1, min: 1, max: 16 }
                    ] }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                panelId={ panelId }
                label={ __( 'Close', 'fooconvert' ) }
                hasValue={ () => isString( value?.close?.slug, true ) }
                onDeselect={ () => setIconClose( undefined ) }
            >
                <IconPickerControl
                    label={ __( 'Close', 'fooconvert' ) }
                    value={ iconClose }
                    onChange={ setIconClose }
                    iconSets={ iconSets }
                    help={ __( 'The icon displayed when clicking the button will close the bar.', 'fooconvert' ) }
                />
            </ToolsPanelItem>
        </ToolsPanel>
    );
};

export default IconToolsPanel;