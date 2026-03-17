import { useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { $object, generateGUID, makePropsFromObjectAttribute } from "#editor";
import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";
import { isString } from "@steveush/utils";

export const COUPON_CLASS_NAME = 'fc--coupon';

export const COUPON_DEFAULTS = {
    settings: {
        layout: 'row',
        copiedMessage: __( 'Copied!', 'fooconvert' )
    },
    styles: {
        dimensions: {
            gap: '12px'
        }
    },
    button: {
        settings: {
            layout: 'icon-only',
            text: __( 'Copy', 'fooconvert' ),
            icon: { slug: 'default__copy' }
        },
        styles: {

        },
    },
    code: {

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

    const attributesDefaults = { ...COUPON_DEFAULTS };

    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = { ...( attributesDefaults?.settings ?? {} ) };

    const setStyles = value => setAttributes( { styles: $object( styles, value ) } );
    const stylesDefaults = { ...( attributesDefaults?.styles ?? {} ) };

    const {
        settings: codeSettings,
        setSettings: setCodeSettings,
        settingsDefaults: codeSettingsDefaults,
        styles: codeStyles,
        setStyles: setCodeStyles,
        stylesDefaults: codeStylesDefaults
    } = makePropsFromObjectAttribute( 'code', props.attributes, setAttributes, attributesDefaults );

    const {
        settings: buttonSettings,
        setSettings: setButtonSettings,
        settingsDefaults: buttonSettingsDefaults,
        styles: buttonStyles,
        setStyles: setButtonStyles,
        stylesDefaults: buttonStylesDefaults
    } = makePropsFromObjectAttribute( 'button', props.attributes, setAttributes, attributesDefaults );

    const customProps = {
        ...props,
        attributesDefaults,
        settings,
        setSettings,
        settingsDefaults,
        styles,
        setStyles,
        stylesDefaults,
        codeSettings,
        setCodeSettings,
        codeSettingsDefaults,
        codeStyles,
        setCodeStyles,
        codeStylesDefaults,
        buttonSettings,
        setButtonSettings,
        buttonSettingsDefaults,
        buttonStyles,
        setButtonStyles,
        buttonStylesDefaults
    };

    return (
        <>
            <EditBlock { ...customProps }/>
            <EditSettings { ...customProps }/>
        </>
    );
};

export default Edit;

