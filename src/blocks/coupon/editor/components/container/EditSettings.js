import { PanelBody, TextControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { ToggleSelectControl } from "#editor";
import { alignCenter, alignLeft, alignRight, row, stack } from "@wordpress/icons";

const EditSettings = ( props ) => {
    const {
        settings,
        setSettings,
        settingsDefaults,
    } = props;

    const setLayout = value => setSettings( { layout: value !== settingsDefaults?.layout ? value : undefined } );
    const layout = settings?.layout ?? settingsDefaults?.layout ?? 'row';

    const layoutOptions = [ {
        value: 'row',
        label: __( 'Row', 'fooconvert' ),
        icon: row
    }, {
        value: 'stack',
        label: __( 'Stack', 'fooconvert' ),
        icon: stack
    } ];

    const setTextAlign = value => setSettings( { textAlign: value !== settingsDefaults?.textAlign ? value : undefined } );
    const textAlign = settings?.textAlign ?? settingsDefaults?.textAlign ?? 'left';

    const textAlignOptions = [ {
        value: 'left',
        label: __( 'Left', 'fooconvert' ),
        icon: alignLeft
    }, {
        value: 'center',
        label: __( 'Center', 'fooconvert' ),
        icon: alignCenter
    }, {
        value: 'right',
        label: __( 'Right', 'fooconvert' ),
        icon: alignRight
    } ];

    const setCopiedMessage = value => setSettings( { copiedMessage: value !== settingsDefaults?.copiedMessage ? value : undefined } );
    const copiedMessage = settings?.copiedMessage ?? settingsDefaults?.copiedMessage ?? '';

    const setCloseOnCopy = value => setSettings( { closeOnCopy: value !== settingsDefaults?.closeOnCopy ? value : undefined } );
    const closeOnCopy = settings?.closeOnCopy ?? settingsDefaults?.closeOnCopy ?? false;

    const setRedirectOnCopy = value => {
        if ( value === false ) {
            setSettings( { redirectURL: undefined, redirectOnCopy: value !== settingsDefaults?.redirectOnCopy ? value : undefined } );
        } else {
            setSettings( { redirectOnCopy: value !== settingsDefaults?.redirectOnCopy ? value : undefined } );
        }
    };
    const redirectOnCopy = settings?.redirectOnCopy ?? settingsDefaults?.redirectOnCopy ?? false;

    const setRedirectURL = value => setSettings( { redirectURL: value !== settingsDefaults?.redirectURL ? value : undefined } );
    const redirectURL = settings?.redirectURL ?? settingsDefaults?.redirectURL ?? '';

    const setNoLabel = value => setSettings( { noLabel: value !== settingsDefaults?.noLabel ? value : undefined } );
    const noLabel = settings?.noLabel ?? settingsDefaults?.noLabel ?? false;
    const setFillWidth = value => setSettings( { fillWidth: value !== settingsDefaults?.fillWidth ? value : undefined } );
    const fillWidth = settings?.fillWidth ?? settingsDefaults?.fillWidth ?? false;

    return (
        <>
            <PanelBody title={ __( 'Settings', 'fooconvert' ) } initialOpen={ true }>
                <ToggleSelectControl
                    label={ __( 'Layout', 'fooconvert' ) }
                    value={ layout }
                    onChange={ setLayout }
                    options={ layoutOptions }
                    iconOnly={ true }
                    help={ __( 'The overall layout of elements in the coupon.', 'fooconvert' ) }
                />
                <ToggleSelectControl
                    label={ __( 'Text Align', 'fooconvert' ) }
                    value={ textAlign }
                    onChange={ setTextAlign }
                    options={ textAlignOptions }
                    iconOnly={ true }
                    help={ __( 'The general text alignment within the coupon.', 'fooconvert' ) }
                />
                <TextControl
                    label={ __( 'Copied Message', 'fooconvert' ) }
                    value={ copiedMessage }
                    onChange={ setCopiedMessage }
                    help={ __( 'A short message displayed after the coupon is copied.', 'fooconvert' ) }
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                />
                { !redirectOnCopy && (
                    <ToggleControl
                        label={ __( 'Close on copy', 'fooconvert' ) }
                        help={ __( 'Close the parent popup after the coupon is copied.', 'fooconvert' ) }
                        checked={ closeOnCopy }
                        onChange={ setCloseOnCopy }
                        __nextHasNoMarginBottom
                    />
                ) }
                { !closeOnCopy && (
                    <ToggleControl
                        label={ __( 'Redirect on copy', 'fooconvert' ) }
                        help={ __( 'Redirect to the supplied URL after the coupon is copied.', 'fooconvert' ) }
                        checked={ redirectOnCopy }
                        onChange={ setRedirectOnCopy }
                        __nextHasNoMarginBottom
                    />
                ) }
                { !closeOnCopy && redirectOnCopy && (
                    <TextControl
                        label={ __( 'Redirect URL', 'fooconvert' ) }
                        value={ redirectURL }
                        onChange={ setRedirectURL }
                        help={ __( 'The URL to redirect to after the coupon is copied.', 'fooconvert' ) }
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                ) }
                <ToggleControl
                    label={ __( 'Hide label', 'fooconvert' ) }
                    help={ __( 'Hide the label for the coupon.', 'fooconvert' ) }
                    checked={ noLabel }
                    onChange={ setNoLabel }
                    __nextHasNoMarginBottom
                />
                <ToggleControl
                    label={ __( 'Fill available width', 'fooconvert' ) }
                    checked={ fillWidth }
                    onChange={ setFillWidth }
                    __nextHasNoMarginBottom
                />
            </PanelBody>
        </>
    );
};

export default EditSettings;
