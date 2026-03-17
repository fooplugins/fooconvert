import { PanelBody, TextControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { ToggleSelectControl } from "#editor";
import { row, stack } from "@wordpress/icons";

const EditSettings = ( props ) => {
    const {
        settings,
        setSettings,
        settingsDefaults
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

    const setSuccessMessage = value => setSettings( { successMessage: value !== settingsDefaults?.successMessage ? value : undefined } );
    const successMessage = settings?.successMessage ?? settingsDefaults?.successMessage ?? '';

    const setErrorMessage = value => setSettings( { errorMessage: value !== settingsDefaults?.errorMessage ? value : undefined } );
    const errorMessage = settings?.errorMessage ?? settingsDefaults?.errorMessage ?? '';

    const setCloseOnSuccess = value => setSettings( { closeOnSuccess: value !== settingsDefaults?.closeOnSuccess ? value : undefined } );
    const closeOnSuccess = settings?.closeOnSuccess ?? settingsDefaults?.closeOnSuccess ?? false;

    return (
        <>
            <PanelBody title={ __( 'Form', 'fooconvert' ) } initialOpen={ true }>
                <ToggleSelectControl
                    label={ __( 'Layout', 'fooconvert' ) }
                    value={ layout }
                    onChange={ setLayout }
                    options={ layoutOptions }
                    iconOnly={ true }
                    help={ __( 'The overall layout of elements in the form.', 'fooconvert' ) }
                />
                <TextControl
                    label={ __( 'Success Message', 'fooconvert' ) }
                    value={ successMessage }
                    onChange={ setSuccessMessage }
                    help={ __( 'A short message displayed after a successful sign-up.', 'fooconvert' ) }
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                />
                <ToggleControl
                    label={ __( 'Close on success', 'fooconvert' ) }
                    help={ __( 'Close the parent widget after a successful sign-up.', 'fooconvert' ) }
                    checked={ closeOnSuccess }
                    onChange={ setCloseOnSuccess }
                    __nextHasNoMarginBottom
                />
            </PanelBody>
        </>
    );
};

export default EditSettings;