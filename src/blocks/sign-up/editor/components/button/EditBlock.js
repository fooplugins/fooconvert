import { getCSSBackgroundProperty, SlugIcon, useStyles } from "#editor";
import { RichText } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import classnames from "classnames";

const EditBlock = ( props ) => {
    const {
        buttonStyles,
        buttonSettings,
        setButtonSettings,
        buttonSettingsDefaults
    } = props;

    const setText = value => setButtonSettings( { text: value !== buttonSettingsDefaults?.text ? value : undefined } );
    const text = buttonSettings?.text ?? buttonSettingsDefaults?.text ?? '';

    const justifyContent = buttonSettings?.justify ?? buttonSettingsDefaults?.justify;
    const width = buttonSettings?.width ?? buttonSettingsDefaults?.width;

    const layout = buttonSettings?.layout ?? buttonSettingsDefaults?.layout ?? 'text-only';
    const showIcon = [ 'icon-only', 'icon-text', 'text-icon' ].includes( layout );
    const showText = [ 'text-only', 'icon-text', 'text-icon' ].includes( layout );

    const iconSlug = buttonSettings?.icon?.slug ?? buttonSettingsDefaults?.icon?.slug;
    const iconSize = buttonSettings?.icon?.size ?? buttonSettingsDefaults?.icon?.size;

    const styles = useStyles( buttonStyles, { background: getCSSBackgroundProperty, text: 'color', icon: '--icon-color' } );

    const wrapperProps = {
        className: "fc--sign-up__buttons",
        style: {
            justifyContent
        }
    };

    const buttonProps = {
        className: classnames( "fc--sign-up__submit", {
            "fc--sign-up__show-icon": showIcon,
            "fc--sign-up__text-only": layout === 'text-only',
            "fc--sign-up__icon-only": layout === 'icon-only',
            "fc--sign-up__text-icon": layout === 'text-icon',
            "fc--sign-up__icon-text": layout === 'icon-text'
        } ),
        type: "button",
        style: {
            ...styles,
            width
        }
    };

    const textProps = {
        className: "fc--sign-up__submit-text",
        format: 'string',
        allowedFormats: [ 'core/bold', 'core/italic' ],
        tagName: 'span',
        withoutInteractiveFormatting: true
    };

    return (
        <div { ...wrapperProps }>
            <button { ...buttonProps }>
                { showIcon && (
                    <SlugIcon slug={ iconSlug } size={ iconSize }/>
                ) }
                { showText && (
                    <RichText
                        { ...textProps }
                        value={ text }
                        onChange={ setText }
                        placeholder={ __( 'Add text...', 'fooconvert' ) }
                    />
                ) }
            </button>
        </div>
    );
};

export default EditBlock;