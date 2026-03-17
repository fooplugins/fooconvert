import { PanelBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { IconsPickerControl, SizeControl, ToggleSelectControl } from "#editor";
import {
    image,
    positionCenter,
    positionLeft,
    positionRight,
    postContent, pullLeft, pullRight
} from "@wordpress/icons";
import { cleanObject } from "@steveush/utils";

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

    const setIcon = ( newValue ) => {
        const previousValue = icon ?? {};
        const nextValue = typeof newValue === 'object' ? {
            ...previousValue,
            ...newValue
        } : undefined;
        setButtonSettings( { icon: cleanObject( nextValue ) } );
    };

    const setIconSlug = nextValue => setIcon( { slug: nextValue } );
    const setIconSize = nextValue => setIcon( { size: nextValue } );

    const iconSizeOptions = [{
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
    }];

    const layout = buttonSettings?.layout ?? buttonSettingsDefaults?.layout ?? 'text-only';
    const setLayout = value => setButtonSettings( { layout: value !== buttonSettingsDefaults?.layout ? value : undefined } );

    const layoutOptions = [ {
        value: 'text-only',
        label: __( 'Text only', 'fooconvert' ),
        icon: postContent
    }, {
        value: 'icon-only',
        label: __( 'Icon only', 'fooconvert' ),
        icon: image
    }, {
        value: 'icon-text',
        label: __( 'Icon before text', 'fooconvert' ),
        icon: pullLeft
    }, {
        value: 'text-icon',
        label: __( 'Text before icon', 'fooconvert' ),
        icon: pullRight
    } ];

    const showIcon = [ 'icon-only', 'icon-text', 'text-icon' ].includes( layout );

    return (
        <>
            <PanelBody title={ __( 'Button', 'fooconvert' ) } initialOpen={ true }>
                <ToggleSelectControl
                    label={ __( 'Layout', 'fooconvert' ) }
                    value={ layout }
                    onChange={ setLayout }
                    options={ layoutOptions }
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
                            sizes={ iconSizeOptions }
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