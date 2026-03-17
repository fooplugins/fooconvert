import { $object, makePropsFromObjectAttribute } from "#editor";
import { useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";

export const SIGN_UP_CLASS_NAME = 'fc--sign-up';

export const SIGN_UP_DEFAULTS = {
    settings: {
        layout: 'row',
        successMessage: __( 'Thanks!', 'fooconvert' )
    },
    styles: {
        dimensions: {
            gap: '12px'
        }
    },
    inputs: {
        settings: {
            nameLabel: __( 'Name', 'fooconvert' ),
            emailLabel: __( 'Email', 'fooconvert' )
        },
        styles: {
            border: {
                width: '1px',
                style: 'solid',
                color: '#949494',
                radius: '2px'
            },
            dimensions: {
                padding: {
                    top: '0.2em',
                    right: '0.8em',
                    bottom: '0.2em',
                    left: '0.8em'
                }
            }
        }
    },
    button: {
        settings: {
            text: __( 'Submit', 'fooconvert' ),
            icon: { slug: 'default__send' }
        }
    }
};

/**
 *
 * @param props
 * @returns {JSX.Element}
 */
const Edit = props => {
    // extract the various values used to render the block
    const {
        setAttributes,
        context: {
            postId
        },
        attributes: {
            postId: storedPostId,
            settings,
            styles
        }
    } = props;

    // ensure the postId attribute is always current
    useEffect( () => {
        if ( postId !== storedPostId ) {
            setAttributes( { postId } );
        }
    }, [ postId, storedPostId ] );

    const attributesDefaults = { ...SIGN_UP_DEFAULTS };

    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = { ...( attributesDefaults?.settings ?? {} ) };

    const setStyles = value => setAttributes( { styles: $object( styles, value ) } );
    const stylesDefaults = { ...( attributesDefaults?.styles ?? {} ) };

    const {
        settings: inputsSettings,
        setSettings: setInputsSettings,
        settingsDefaults: inputsSettingsDefaults,
        styles: inputsStyles,
        setStyles: setInputsStyles,
        stylesDefaults: inputsStylesDefaults
    } = makePropsFromObjectAttribute( 'inputs', props.attributes, setAttributes, attributesDefaults );

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
        inputsSettings,
        setInputsSettings,
        inputsSettingsDefaults,
        inputsStyles,
        setInputsStyles,
        inputsStylesDefaults,
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


