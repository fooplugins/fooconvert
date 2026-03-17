import { PanelBody, TextControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { DateTimeControl, NumberControl, ToggleSelectControl } from "#editor";
import { alignCenter, alignLeft, alignRight } from "@wordpress/icons";

const EditSettings = ( props ) => {
    const {
        codeSettings,
        setCodeSettings,
        codeSettingsDefaults,
    } = props;

    const setTextAlign = value => setCodeSettings( { textAlign: value !== codeSettingsDefaults?.textAlign ? value : undefined } );
    const textAlign = codeSettings?.textAlign ?? codeSettingsDefaults?.textAlign;

    const setOverrideText = value => setCodeSettings( { overrideText: value !== codeSettingsDefaults?.overrideText ? value : undefined } );
    const overrideText = codeSettings?.overrideText ?? codeSettingsDefaults?.overrideText;

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

    return (
        <>
            <PanelBody title={ __( 'Coupon', 'fooconvert' ) } initialOpen={ true }>
                <ToggleSelectControl
                    label={ __( 'Text Align', 'fooconvert' ) }
                    value={ textAlign }
                    onChange={ setTextAlign }
                    options={ textAlignOptions }
                    iconOnly={ true }
                    help={ __( 'The text alignment for the coupon code.', 'fooconvert' ) }
                />
                <TextControl
                    label={ __( 'Override Copied Text', 'fooconvert' ) }
                    value={ overrideText }
                    onChange={ setOverrideText }
                    help={ __( 'If supplied, this text will be copied to the clipboard instead of the text displayed by the element.', 'fooconvert' ) }
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                />
            </PanelBody>
        </>
    );
};

export default EditSettings;