import { useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { $object, generateGUID, makePropsFromObjectAttribute } from "#editor";
import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";
import { isString } from "@steveush/utils";

export const COUNTDOWN_CLASS_NAME = 'fc--countdown';

export const COUNTDOWN_DEFAULTS = {
    settings: {

    },
    styles: {

    },
    segment: {
        settings: {
            daysText: __( 'Days', 'fooconvert' ),
            hoursText: __( 'Hours', 'fooconvert' ),
            minutesText: __( 'Minutes', 'fooconvert' ),
            secondsText: __( 'Seconds', 'fooconvert' )
        },
        styles: {

        }
    },
    digits: {
        settings: {

        },
        styles: {
            typography: {
                fontSize: '3em'
            }
        },
    }
};

const Edit = ( props ) => {

    // extract the various values used to render the block
    const {
        setAttributes,
        attributes: {
            uniqueId,
            settings,
            styles
        }
    } = props;

    // ensure the postId attribute is always current
    useEffect( () => {
        if ( !isString( uniqueId ) ) {
            setAttributes( { uniqueId: generateGUID() } );
        }
    }, [ uniqueId ] );

    const attributesDefaults = { ...COUNTDOWN_DEFAULTS };

    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = { ...( attributesDefaults?.settings ?? {} ) };

    const setStyles = value => setAttributes( { styles: $object( styles, value ) } );
    const stylesDefaults = { ...( attributesDefaults?.styles ?? {} ) };

    const {
        settings: segmentSettings,
        setSettings: setSegmentSettings,
        settingsDefaults: segmentSettingsDefaults,
        styles: segmentStyles,
        setStyles: setSegmentStyles,
        stylesDefaults: segmentStylesDefaults
    } = makePropsFromObjectAttribute( 'segment', props.attributes, setAttributes, attributesDefaults );

    const {
        settings: digitsSettings,
        setSettings: setDigitsSettings,
        settingsDefaults: digitsSettingsDefaults,
        styles: digitsStyles,
        setStyles: setDigitsStyles,
        stylesDefaults: digitsStylesDefaults
    } = makePropsFromObjectAttribute( 'text', props.attributes, setAttributes, attributesDefaults );

    const customProps = {
        ...props,
        attributesDefaults,
        settings,
        setSettings,
        settingsDefaults,
        styles,
        setStyles,
        stylesDefaults,
        segmentSettings,
        setSegmentSettings,
        segmentSettingsDefaults,
        segmentStyles,
        setSegmentStyles,
        segmentStylesDefaults,
        digitsSettings,
        setDigitsSettings,
        digitsSettingsDefaults,
        digitsStyles,
        setDigitsStyles,
        digitsStylesDefaults
    };

    return (
        <>
            <EditBlock { ...customProps }/>
            <EditSettings { ...customProps }/>
        </>
    );
};

export default Edit;

