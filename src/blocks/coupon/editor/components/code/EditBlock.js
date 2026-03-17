import "./EditBlock.scss";

import { getBoxUnitStyle, useStyles } from "#editor";
import classnames from "classnames";

import { COUPON_CLASS_NAME } from "../../Edit";
import { RichText } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";

const EditBlock = ( props ) => {
    const {
        children,
        codeStyles,
        codeSettings,
        setCodeSettings,
        codeSettingsDefaults
    } = props;

    const setText = value => setCodeSettings( { text: value !== codeSettingsDefaults?.text ? value : undefined } );
    const text = codeSettings?.text ?? codeSettingsDefaults?.text ?? '';
    const textAlign = codeSettings?.textAlign ?? codeSettingsDefaults?.textAlign;

    const styles = useStyles( codeStyles );
    const innerPadding = getBoxUnitStyle( codeStyles?.innerPadding, 'padding' );


    const codeProps = {
        className: classnames(
            `${ COUPON_CLASS_NAME }__code`
        ),
        style: {
            ...styles,
            textAlign
        }
    };

    const codeTextProps = {
        className: `${ COUPON_CLASS_NAME }__code-text`,
        format: 'string',
        allowedFormats: [],
        tagName: 'span',
        withoutInteractiveFormatting: true,
        style: {
            ...innerPadding
        }
    };

    return (
        <div { ...codeProps }>
            <RichText
                { ...codeTextProps }
                value={ text }
                onChange={ setText }
                placeholder={ __( 'Add code...', 'fooconvert' ) }
            />
            { children }
        </div>
    );
};

export default EditBlock;