import { __ } from "@wordpress/i18n";
import {
    IconsPickerControl,
    SizeControl,
    ToolsPanel,
    ToolsPanelItem
} from "../../components";
import { cleanObject, isString } from "@steveush/utils";

const IconToolsPanel = props => {
    const {
        value,
        onChange,
        defaults,
        panelId,
        title = __( "Icon", "fooconvert" ),
        iconLabel = __( "Icon", "fooconvert" ),
        iconHelp = __( "Choose the icon to display.", "fooconvert" ),
        sizeLabel = __( "Size", "fooconvert" ),
        sizeHelp = __( "Set the size of the icon.", "fooconvert" ),
        hideIconLabelFromVision = true,
    } = props;

    const setValue = ( newValue ) => {
        const previousValue = value ?? {};
        const nextValue = typeof newValue === 'object' ? {
            ...previousValue,
            ...newValue
        } : undefined;
        onChange( cleanObject( nextValue ) );
    };

    const setSize = nextValue => setValue( { size: nextValue } );
    const setSlug = nextValue => setValue( { slug: nextValue } );

    return (
        <ToolsPanel
            panelId={ panelId }
            label={ title }
            resetAll={ () => setIcon( undefined ) }
        >
            <ToolsPanelItem
                panelId={ panelId }
                label={ iconLabel }
                hasValue={ () => isString( value?.slug, true ) }
                onDeselect={ () => setSlug( undefined ) }
                isShownByDefault={ true }
            >
                <IconsPickerControl
                    label={ iconLabel }
                    hideLabelFromVision={ hideIconLabelFromVision }
                    value={ value?.slug ?? defaults?.slug }
                    onChange={ setSlug }
                    help={ iconHelp }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                panelId={ panelId }
                label={ sizeLabel }
                hasValue={ () => isString( value?.size, true ) }
                onDeselect={ () => setSize( undefined ) }
                isShownByDefault={ true }
            >
                <SizeControl
                    label={ sizeLabel }
                    help={ sizeHelp }
                    value={ value?.size ?? defaults?.size }
                    onChange={ setSize }
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
        </ToolsPanel>
    );
};

export default IconToolsPanel;