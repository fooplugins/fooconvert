import { PanelBody, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

const EditSettings = ( props ) => {
    const {
        inputsSettings,
        setInputsSettings,
        inputsSettingsDefaults,
    } = props;

    const setEmailOnly = value => setInputsSettings( { emailOnly: value !== inputsSettingsDefaults?.emailOnly ? value : undefined } );
    const emailOnly = inputsSettings?.emailOnly ?? inputsSettingsDefaults?.emailOnly ?? false;

    const setNoLabels = value => setInputsSettings( { noLabels: value !== inputsSettingsDefaults?.noLabels ? value : undefined } );
    const noLabels = inputsSettings?.noLabels ?? inputsSettingsDefaults?.noLabels ?? false;

    const setStackLabels = value => setInputsSettings( { stackLabels: value !== inputsSettingsDefaults?.stackLabels ? value : undefined } );
    const stackLabels = inputsSettings?.stackLabels ?? inputsSettingsDefaults?.stackLabels ?? false;

    return (
        <>
            <PanelBody title={ __( 'Inputs', 'fooconvert' ) } initialOpen={ true }>
                <ToggleControl
                    label={ __( 'Email only', 'fooconvert' ) }
                    help={ __( 'Hide the name input.', 'fooconvert' ) }
                    checked={ emailOnly }
                    onChange={ setEmailOnly }
                    __nextHasNoMarginBottom
                />
                <ToggleControl
                    label={ __( 'Hide labels', 'fooconvert' ) }
                    help={ __( 'Hides the labels for the input elements.', 'fooconvert' ) }
                    checked={ noLabels }
                    onChange={ setNoLabels }
                    __nextHasNoMarginBottom
                />
                { !noLabels && (
                    <ToggleControl
                        label={ __( 'Stack labels', 'fooconvert' ) }
                        help={ __( 'Display labels above the input elements.', 'fooconvert' ) }
                        checked={ stackLabels }
                        onChange={ setStackLabels }
                        __nextHasNoMarginBottom
                    />
                ) }
            </PanelBody>
        </>
    );
};

export default EditSettings;