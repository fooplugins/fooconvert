import "./EditBlock.scss";

import { getCSSBackgroundProperty, SlugIcon, useStyles } from "#editor";
import { RichText } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import classnames from "classnames";

import { BUTTON_TEXT_FORMATS, BUTTON_TEXT_LAYOUTS, isButtonIconLayout } from "../../../../shared/editor/button";

const EditBlock = ( props ) => {
    const {
        buttonStyles,
        buttonSettings,
        setButtonSettings,
        buttonSettingsDefaults
    } = props;

    const setText = value => setButtonSettings( { text: value !== buttonSettingsDefaults?.text ? value : undefined } );
    const text = buttonSettings?.text ?? buttonSettingsDefaults?.text ?? '';

    const layout = buttonSettings?.layout ?? buttonSettingsDefaults?.layout ?? 'text-only';
    const showIcon = isButtonIconLayout( layout );
    const showText = BUTTON_TEXT_LAYOUTS.includes( layout );

    const iconSlug = buttonSettings?.icon?.slug ?? buttonSettingsDefaults?.icon?.slug;
    const iconSize = buttonSettings?.icon?.size ?? buttonSettingsDefaults?.icon?.size;

    const styles = useStyles( buttonStyles, { background: getCSSBackgroundProperty, text: 'color', icon: '--icon-color' } );

    const buttonProps = {
        className: classnames( "fc--coupon__copy-button", {
            "fc--coupon__show-icon": showIcon,
            "fc--coupon__text-only": layout === 'text-only',
            "fc--coupon__icon-only": layout === 'icon-only',
            "fc--coupon__text-icon": layout === 'text-icon',
            "fc--coupon__icon-text": layout === 'icon-text'
        } ),
        type: "button",
        style: {
            ...styles
        }
    };

    const textProps = {
        className: "fc--coupon__copy-text",
        format: 'string',
        allowedFormats: BUTTON_TEXT_FORMATS,
        tagName: 'span',
        withoutInteractiveFormatting: true
    };

    return (
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
    );
};

export default EditBlock;
