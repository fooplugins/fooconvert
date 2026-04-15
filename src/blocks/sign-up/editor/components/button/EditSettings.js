import { PanelBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { IconsPickerControl, SizeControl, ToggleSelectControl } from "#editor";
import { positionCenter, positionLeft, positionRight } from "@wordpress/icons";
import { BUTTON_ICON_SIZE_OPTIONS, BUTTON_LAYOUT_OPTIONS, createButtonIconSetter, isButtonIconLayout } from "../../../../shared/editor/button";

const EditSettings = ( props ) => {
    const {
        settings,
        settingsDefaults,
        buttonSettings,
        setButtonSettings,
        buttonSettingsDefaults
    } = props;

    const rootLayout = settings?.layout ?? settingsDefaults?.layout ?? 'row';

    const justify = buttonSettings?.justify ?? buttonSettingsDefaults?.justify ?? 'center';
    const setJustify = value => setButtonSettings( { justify: value !== buttonSettingsDefaults?.justify ? value : undefined } );

    const justifyOptions = [ {
        value: 'flex-start',
        label: __( 'Left', 'fooconvert' ),
        icon: positionLeft
    }, {
        value: 'center',
        label: __( 'Center', 'fooconvert' ),
        icon: positionCenter
    }, {
        value: 'flex-end',
        label: __( 'Right', 'fooconvert' ),
        icon: positionRight
    } ];

    const width = buttonSettings?.width ?? buttonSettingsDefaults?.width ?? 'fit-content';
    const setWidth = value => setButtonSettings( { width: value !== buttonSettingsDefaults?.width ? value : undefined } );

    const widthOptions = [ {
        value: 'fit-content',
        label: __( 'Fit', 'fooconvert' )
    }, {
        value: '25%',
        label: __( '25%', 'fooconvert' )
    }, {
        value: '50%',
        label: __( '50%', 'fooconvert' )
    }, {
        value: '75%',
        label: __( '75%', 'fooconvert' )
    }, {
        value: '100%',
        label: __( '100%', 'fooconvert' )
    } ];

    const icon = buttonSettings?.icon ?? buttonSettingsDefaults?.icon;
    const iconSlug = buttonSettings?.icon?.slug ?? buttonSettingsDefaults?.icon?.slug;
    const iconSize = buttonSettings?.icon?.size ?? buttonSettingsDefaults?.icon?.size;
    const setIcon = createButtonIconSetter( setButtonSettings, icon );
    const setIconSlug = nextValue => setIcon( { slug: nextValue } );
    const setIconSize = nextValue => setIcon( { size: nextValue } );

    const layout = buttonSettings?.layout ?? buttonSettingsDefaults?.layout ?? 'text-only';
    const setLayout = value => setButtonSettings( { layout: value !== buttonSettingsDefaults?.layout ? value : undefined } );
    const showIcon = isButtonIconLayout( layout );

    return (
        <>
            <PanelBody title={ __( 'Button', 'fooconvert' ) } initialOpen={ true }>
                <ToggleSelectControl
                    label={ __( 'Layout', 'fooconvert' ) }
                    value={ layout }
                    onChange={ setLayout }
                    options={ BUTTON_LAYOUT_OPTIONS }
                    iconOnly={ true }
                    help={ __( 'The layout of the text and/or icon in the button.', 'fooconvert' ) }
                />
                { rootLayout !== 'row' && (
                    <>
                        <ToggleSelectControl
                            label={ __( 'Position', 'fooconvert' ) }
                            value={ justify }
                            onChange={ setJustify }
                            options={ justifyOptions }
                            iconOnly={ true }
                            help={ __( 'Horizontal position of the button in the form.', 'fooconvert' ) }
                        />
                        <ToggleSelectControl
                            label={ __( 'Width', 'fooconvert' ) }
                            value={ width }
                            onChange={ setWidth }
                            options={ widthOptions }
                            help={ __( 'The width for the button within the form.', 'fooconvert' ) }
                        />
                    </>
                ) }
                { showIcon && (
                    <>
                        <IconsPickerControl
                            label={ __( 'Icon', 'fooconvert' ) }
                            value={ iconSlug }
                            onChange={ setIconSlug }
                            help={ __( "Choose the icon to display.", "fooconvert" ) }
                        />
                        <SizeControl
                            label={ __( 'Icon Size', 'fooconvert' ) }
                            help={ __( 'Set the size of the icon.' ) }
                            value={ iconSize }
                            onChange={ setIconSize }
                            sizes={ BUTTON_ICON_SIZE_OPTIONS }
                            units={ [
                                { value: 'px', label: 'px', default: 24, step: 4, min: 16, max: 256 },
                                { value: 'em', label: 'em', default: 1, step: 0.1, min: 1, max: 16 },
                                { value: 'rem', label: 'rem', default: 1, step: 0.1, min: 1, max: 16 }
                            ] }
                        />
                    </>
                ) }
            </PanelBody>
        </>
    );
};

export default EditSettings;
