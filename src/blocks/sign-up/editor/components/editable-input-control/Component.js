import { RichText } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import classnames from "classnames";

import "./Component.scss";

const EditableInputControl = ( props ) => {
    const {
        placeholder,
        onPlaceholderChange,
        placeholderPrompt = __( 'Add placeholder...', 'fooconvert' ),
        label,
        onLabelChange,
        labelPrompt = __( 'Add label...', 'fooconvert' ),
        className,
        showLabel = false,
        noLabel = false,
        stackLabel = false,
        allowedLabelFormats = [ 'core/bold', 'core/italic' ],
        inputType = 'text',
        styles = {}
    } = props;

    const wrapperProps = {
        className: classnames(
            "fc--sign-up__editable-input",
            className,
            { "fc--sign-up__no-label": noLabel },
            { "fc--sign-up__stack-label": !noLabel && stackLabel }
        )
    };

    const labelProps = {
        className: "fc--sign-up__input-label",
        style: {},
        format: 'string',
        allowedFormats: allowedLabelFormats,
        tagName: 'span',
        withoutInteractiveFormatting: true,
        placeholder: labelPrompt,
        value: label,
        onChange: onLabelChange
    };

    const inputProps = {
        className: "fc--sign-up__input",
        type: inputType,
        placeholder: placeholderPrompt,
        value: placeholder,
        onChange: e => onPlaceholderChange( e?.target?.value ),
        style: {
            ...styles
        }
    };

    return (
        <div { ...wrapperProps }>
            { !noLabel && ( <RichText { ...labelProps }/> ) }
            <input { ...inputProps }/>
        </div>
    );
};

export default EditableInputControl;