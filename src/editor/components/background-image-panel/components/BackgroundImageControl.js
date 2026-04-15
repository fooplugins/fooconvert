import { useSelect } from "@wordpress/data";
import { store as blockEditorStore } from "@wordpress/block-editor";
import { useMemo, useState } from "@wordpress/element";
import clsx from "clsx";
import { setImmutably } from "../../../utils";
import { hasBackgroundImageValue } from "../utils";
import BackgroundControlsPanel from "./BackgroundControlsPanel";
import BackgroundImageControls from "./BackgroundImageControls";
import BackgroundSizeControls from "./BackgroundSizeControls";

import { VStack } from "../../experimental";

const BackgroundImageControl = ( {
                                   value,
                                   onChange,
                                   inheritedValue = value,
                                   defaultValues = {},
                               } ) => {

    const resetBackground = () => onChange( setImmutably( value, [ 'background' ], {} ) );

    const { title, url } = value?.background?.backgroundImage ?? {};
    const hasImageValue = hasBackgroundImageValue( value );
    const imageValue = value?.background?.backgroundImage || inheritedValue?.background?.backgroundImage;
    const shouldShowBackgroundImageControls = hasImageValue && 'none' !== imageValue;

    const [ isDropDownOpen, setIsDropDownOpen ] = useState( false );

    return (
        <div
            className={ clsx(
                'block-editor-global-styles-background-panel__inspector-media-replace-container',
                {
                    'is-open': isDropDownOpen,
                }
            ) }
        >
            { shouldShowBackgroundImageControls ? (
                <BackgroundControlsPanel
                    label={ title }
                    filename={ title }
                    url={ url }
                    onToggle={ setIsDropDownOpen }
                    hasImageValue={ hasImageValue }
                >
                    <VStack spacing={ 3 } className="single-column">
                        <BackgroundImageControls
                            onChange={ onChange }
                            style={ value }
                            inheritedValue={ inheritedValue }
                            displayInPanel
                            onResetImage={ () => {
                                setIsDropDownOpen( false );
                                resetBackground();
                            } }
                            onRemoveImage={ () => setIsDropDownOpen( false ) }
                            defaultValues={ defaultValues }
                        />
                        <BackgroundSizeControls
                            onChange={ onChange }
                            style={ value }
                            defaultValues={ defaultValues }
                            inheritedValue={ inheritedValue }
                        />
                    </VStack>
                </BackgroundControlsPanel>
            ) : (
                <BackgroundImageControls
                    onChange={ onChange }
                    style={ value }
                    inheritedValue={ inheritedValue }
                    defaultValues={ defaultValues }
                    onResetImage={ () => {
                        setIsDropDownOpen( false );
                        resetBackground();
                    } }
                    onRemoveImage={ () => setIsDropDownOpen( false ) }
                />
            ) }
        </div>
    );
};

export default BackgroundImageControl;
