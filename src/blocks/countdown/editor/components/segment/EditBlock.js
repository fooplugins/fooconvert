import "./EditBlock.scss";

import { getCSSBackgroundProperty, useStyles, useTypographyStyle } from "#editor";
import { RichText } from "@wordpress/block-editor";
import classnames from "classnames";

import { COUNTDOWN_CLASS_NAME } from "../../Edit";

const EditBlock = ( props ) => {
    const {
        value,
        segmentName,
        placeholder = '',
        segmentStyles,
        segmentSettings,
        setSegmentSettings,
        segmentSettingsDefaults,
        digitsStyles,
    } = props;

    const textSetting = `${ segmentName }Text`;

    const setText = value => setSegmentSettings( { [ textSetting ]: value !== segmentSettingsDefaults?.[ textSetting ] ? value : undefined } );
    const text = segmentSettings?.[ textSetting ] ?? segmentSettingsDefaults?.[ textSetting ] ?? '';

    const layout = segmentSettings?.layout ?? segmentSettingsDefaults?.layout ?? 'stack';
    const padDigits = segmentSettings?.padDigits ?? segmentSettingsDefaults?.padDigits ?? false;
    const justifyContent = segmentSettings?.justify ?? segmentSettingsDefaults?.justify;
    const elementStyles = useStyles( segmentStyles, { background: getCSSBackgroundProperty, text: 'color', digits: '--digits-color' } );
    const valueStyles = useStyles( digitsStyles );

    const segmentProps = {
        className: classnames(
            `${ COUNTDOWN_CLASS_NAME }__segment`,
            `${ COUNTDOWN_CLASS_NAME }__segment-${ segmentName }`,
            `${ COUNTDOWN_CLASS_NAME }__segment__layout-${ layout }`
        ),
        style: {
            ...elementStyles,
            justifyContent
        }
    };

    const valueProps = {
        className: `${ COUNTDOWN_CLASS_NAME }__segment-value`,
        style: {
            ...valueStyles
        }
    };

    const textProps = {
        className: `${ COUNTDOWN_CLASS_NAME }__segment-text`,
        format: 'string',
        allowedFormats: [ 'core/bold', 'core/italic' ],
        tagName: 'span',
        withoutInteractiveFormatting: true
    };

    return (
        <div { ...segmentProps }>
            <span { ...valueProps }>{ padDigits ? `${ value }`.padStart( 2, '0' ) : value }</span>
            <RichText
                { ...textProps }
                value={ text }
                onChange={ setText }
                placeholder={ placeholder }
            />
        </div>
    );
};

export default EditBlock;