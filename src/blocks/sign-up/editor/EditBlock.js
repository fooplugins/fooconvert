import { useBlockProps } from "@wordpress/block-editor";
import { SIGN_UP_CLASS_NAME } from "./Edit";
import classnames from "classnames";
import { useStyles } from "#editor";
import { InputsEditBlock, ButtonEditBlock } from "./components";

const EditBlock = ( props ) => {
    const {
        settings,
        settingsDefaults,
        styles,
        inputsSettings,
        inputsSettingsDefaults
    } = props;

    const formStyles = useStyles( styles );
    const layout = settings?.layout ?? settingsDefaults?.layout;
    const noLabels = inputsSettings?.noLabels ?? inputsSettingsDefaults?.noLabels;
    const stackLabels = inputsSettings?.stackLabels ?? inputsSettingsDefaults?.stackLabels;

    const blockProps = useBlockProps( {
        className: classnames(
            SIGN_UP_CLASS_NAME, {
                "fc--sign-up__stack": layout === 'stack',
                "fc--sign-up__no-labels": noLabels,
                "fc--sign-up__stack-labels": stackLabels
            }
        ),
        style: {
            ...formStyles
        }
    } );

    return (
        <div { ...blockProps }>
            <InputsEditBlock { ...props }/>
            <ButtonEditBlock { ...props }/>
        </div>
    );
};

export default EditBlock;