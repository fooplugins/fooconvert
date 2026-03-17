import { RichText, useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";

import { COUPON_CLASS_NAME } from "./Edit";
import classnames from "classnames";
import { useStyles } from "#editor";

import {
    ButtonEditBlock, CodeEditBlock,
    ContainerEditBlock
} from "./components"

const EditBlock = props => {

    const {
        settings,
        settingsDefaults,
        setSettings
    } = props;

    const setLabel = value => setSettings( { label: value !== settingsDefaults?.label ? value : undefined } );
    const label = settings?.label ?? settingsDefaults?.label ?? '';

    const labelProps = {
        className: `${ COUPON_CLASS_NAME }__label`,
        format: 'string',
        allowedFormats: [ 'core/bold', 'core/italic' ],
        tagName: 'span',
        withoutInteractiveFormatting: true
    };

    return (
        <ContainerEditBlock { ...props }>
            <RichText
                { ...labelProps }
                value={ label }
                onChange={ setLabel }
                placeholder={ __( 'Add text...', 'fooconvert' ) }
            />
            <CodeEditBlock { ...props }>
                <ButtonEditBlock { ...props }/>
            </CodeEditBlock>
        </ContainerEditBlock>
    );
};

export default EditBlock;