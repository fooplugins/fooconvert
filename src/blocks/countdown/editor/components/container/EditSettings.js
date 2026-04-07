import { PanelBody, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { DateTimeControl, generateGUID, NumberControl, ToggleSelectControl } from "#editor";
import { useEffect } from "@wordpress/element";

const EditSettings = ( props ) => {
    const {
        setAttributes,
        settings,
        setSettings,
        settingsDefaults,
        segmentSettings,
        setSegmentSettings,
        segmentSettingsDefaults
    } = props;

    const resetUniqueId = () => setAttributes( { uniqueId: generateGUID() } );

    const layout = segmentSettings?.layout ?? segmentSettings?.layout ?? 'stack';
    const setLayout = value => setSegmentSettings( { layout: value !== segmentSettingsDefaults?.layout ? value : undefined } );

    const padDigits = segmentSettings?.padDigits ?? segmentSettings?.padDigits ?? false;
    const setPadDigits = value => setSegmentSettings( { padDigits: value !== segmentSettingsDefaults?.padDigits ? value : undefined } );

    const layoutOptions = [ {
        value: 'stack',
        label: __( 'Under', 'fooconvert' )
    }, {
        value: 'row',
        label: __( 'Inline', 'fooconvert' )
    } ];

    const mode = settings?.mode ?? settingsDefaults?.mode ?? 'fomo';
    const setMode = value => setSettings( { mode: value !== settingsDefaults?.mode ? value : undefined } );

    const modeOptions = [ {
        value: 'fomo',
        label: __( 'FOMO', 'fooconvert' )
    }, {
        value: 'specific',
        label: __( 'Specific', 'fooconvert' )
    } ];

    const expiry = settings?.expiry ?? settingsDefaults?.expiry ?? 'session';
    const setExpiry = value => {
        setSettings( { expiry: value !== settingsDefaults?.expiry ? value : undefined } );
        resetUniqueId();
    };

    const expiryOptions = [ {
        value: 'session',
        label: __( 'Session', 'fooconvert' )
    }, {
        value: 'persist',
        label: __( 'Persist', 'fooconvert' )
    } ];

    const specificValue = settings?.specificValue ?? settingsDefaults?.specificValue ?? null;
    const setSpecificValue = value => {
        setSettings( { specificValue: value !== settingsDefaults?.specificValue ? value : undefined } );
        resetUniqueId();
    };

    const fomoValue = settings?.fomoValue ?? settingsDefaults?.fomoValue ?? 10;
    const setFOMOValue = value => {
        value = parseInt( value );
        setSettings( { fomoValue: !isNaN( value ) && value !== settingsDefaults?.fomoValue ? value : undefined } );
        resetUniqueId();
    };

    const setCloseOnExpire = value => setSettings( { closeOnExpire: value !== settingsDefaults?.closeOnExpire ? value : undefined } );
    const closeOnExpire = settings?.closeOnExpire ?? settingsDefaults?.closeOnExpire ?? false;

    return (
        <>
            <PanelBody title={ __( 'Settings', 'fooconvert' ) } initialOpen={ true }>
                <ToggleSelectControl
                    label={ __( 'Mode', 'fooconvert' ) }
                    value={ mode }
                    onChange={ setMode }
                    options={ modeOptions }
                    help={ __( 'Change how the countdown functions.', 'fooconvert' ) }
                />
                { mode === 'specific' && (
                    <DateTimeControl
                        label={ __( 'Date & time', 'fooconvert' ) }
                        value={ specificValue }
                        onChange={ newDate => setSpecificValue( newDate ) }
                        help={ __( 'The date and time the block will count down to.', 'fooconvert' ) }
                    />
                ) }
                { mode === 'fomo' && (
                    <>
                        <NumberControl
                            label={ __( "Minutes", "fooconvert" ) }
                            value={ fomoValue }
                            onChange={ setFOMOValue }
                            spinControls="custom"
                            min={ 1 }
                            step={ 1 }
                            help={ __( 'The number of minutes counting down for first time viewers.', 'fooconvert' ) }
                            __next40pxDefaultSize
                        />
                        <ToggleSelectControl
                            label={ __( 'Expiry', 'fooconvert' ) }
                            value={ expiry }
                            onChange={ setExpiry }
                            options={ expiryOptions }
                            help={ __( 'Change how an expired countdown is stored.', 'fooconvert' ) }
                        />
                    </>
                ) }
                <ToggleControl
                    label={ __( 'Close on expire', 'fooconvert' ) }
                    help={ __( 'Close the parent popup after the countdown expires.', 'fooconvert' ) }
                    checked={ closeOnExpire }
                    onChange={ setCloseOnExpire }
                    __nextHasNoMarginBottom
                />
                <ToggleSelectControl
                    label={ __( 'Text Position', 'fooconvert' ) }
                    value={ layout }
                    onChange={ setLayout }
                    options={ layoutOptions }
                    help={ __( 'The position of the text relative to the digit.', 'fooconvert' ) }
                />
                <ToggleControl
                    label={ __( 'Pad Single Digits', 'fooconvert' ) }
                    help={ __( 'Add a zero before single digits.', 'fooconvert' ) }
                    checked={ padDigits }
                    onChange={ setPadDigits }
                    __nextHasNoMarginBottom
                />
            </PanelBody>
        </>
    );
};

export default EditSettings;
