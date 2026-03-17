import { EditableInputControl } from "../editable-input-control";
import { __ } from "@wordpress/i18n";
import { getCSSBackgroundProperty, useStyles } from "#editor";

const EditBlock = ( props ) => {
    const {
        isSelected,
        inputsSettings,
        setInputsSettings,
        inputsSettingsDefaults,
        inputsStyles
    } = props;

    const setNameLabel = value => setInputsSettings( { nameLabel: value !== inputsSettingsDefaults?.nameLabel ? value : undefined } );
    const nameLabel = inputsSettings?.nameLabel ?? inputsSettingsDefaults?.nameLabel ?? __( 'Name', 'fooconvert' );

    const setNamePlaceholder = value => setInputsSettings( { namePlaceholder: value !== inputsSettingsDefaults?.namePlaceholder ? value : undefined } );
    const namePlaceholder = inputsSettings?.namePlaceholder ?? inputsSettingsDefaults?.namePlaceholder ?? '';

    const setEmailLabel = value => setInputsSettings( { emailLabel: value !== inputsSettingsDefaults?.emailLabel ? value : undefined } );
    const emailLabel = inputsSettings?.emailLabel ?? inputsSettingsDefaults?.emailLabel ?? __( 'Email', 'fooconvert' );

    const setEmailPlaceholder = value => setInputsSettings( { emailPlaceholder: value !== inputsSettingsDefaults?.emailPlaceholder ? value : undefined } );
    const emailPlaceholder = inputsSettings?.emailPlaceholder ?? inputsSettingsDefaults?.emailPlaceholder ?? '';

    const inputStyles = useStyles( inputsStyles, {
        // text: 'color',
        background: getCSSBackgroundProperty,
        placeholder: '--placeholder-color'
    } );

    return (
        <div className="fc--sign-up__inputs">
            { !inputsSettings?.emailOnly && (
                <EditableInputControl
                    noLabel={ inputsSettings?.noLabels }
                    stackLabel={ inputsSettings?.stackLabels }
                    label={ nameLabel }
                    onLabelChange={ setNameLabel }
                    placeholderPrompt={ isSelected ? __( 'Add name placeholder...', 'fooconvert' ) : '' }
                    placeholder={ namePlaceholder }
                    onPlaceholderChange={ setNamePlaceholder }
                    styles={ inputStyles }
                />
            ) }
            <EditableInputControl
                noLabel={ inputsSettings?.noLabels }
                stackLabel={ inputsSettings?.stackLabels }
                label={ emailLabel }
                onLabelChange={ setEmailLabel }
                placeholderPrompt={ isSelected ? __( 'Add email placeholder...', 'fooconvert' ) : '' }
                placeholder={ emailPlaceholder }
                onPlaceholderChange={ setEmailPlaceholder }
                styles={ inputStyles }
            />
        </div>
    );
};

export default EditBlock;