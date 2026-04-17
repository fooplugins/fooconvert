import { useState } from "@wordpress/element";
import clsx from "clsx";
import { setImmutably } from "../../../utils";
import { applySelectedBackgroundImage, hasBackgroundImageValue } from "../utils";
import BackgroundControlsPanel from "./BackgroundControlsPanel";
import BackgroundImageControls from "./BackgroundImageControls";
import BackgroundSizeControls from "./BackgroundSizeControls";

import { VStack } from "../../experimental";

const BackgroundImageControl = ( {
                               value,
                               onChange,
                               inheritedValue = value,
                               defaultValues = {},
                               backgroundGenerator = null,
                           } ) => {
    const PopupBackgroundGeneratorControl = globalThis?.FooConvertPro?.editor?.PopupBackgroundGeneratorControl;
    const shouldShowBackgroundGenerator = !! backgroundGenerator && 'function' === typeof PopupBackgroundGeneratorControl;

    const resetBackground = () => onChange( setImmutably( value, [ 'background' ], {} ) );

    const { title, url } = value?.background?.backgroundImage ?? {};
    const hasImageValue = hasBackgroundImageValue( value );
    const imageValue = value?.background?.backgroundImage || inheritedValue?.background?.backgroundImage;
    const shouldShowBackgroundImageControls = hasImageValue && 'none' !== imageValue;

    const [ isDropDownOpen, setIsDropDownOpen ] = useState( false );
    const [ isGeneratorOpen, setIsGeneratorOpen ] = useState( false );

    const selectBackgroundMedia = ( media ) => {
        onChange(
            applySelectedBackgroundImage( {
                style: value,
                media,
                defaultValues,
            } )
        );
    };

    const openBackgroundGenerator = () => {
        setIsDropDownOpen( false );
        setIsGeneratorOpen( true );
    };

    return (
        <>
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
                        isOpen={ isDropDownOpen }
                        setIsOpen={ setIsDropDownOpen }
                        onToggle={ setIsDropDownOpen }
                        hasImageValue={ hasImageValue }
                    >
                        <VStack spacing={ 3 } className="single-column">
                            <BackgroundImageControls
                                onChange={ onChange }
                                style={ value }
                                inheritedValue={ inheritedValue }
                                displayInPanel
                                onSelectMedia={ selectBackgroundMedia }
                                showBackgroundGenerator={ shouldShowBackgroundGenerator }
                                onOpenBackgroundGenerator={ openBackgroundGenerator }
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
                        onSelectMedia={ selectBackgroundMedia }
                        showBackgroundGenerator={ shouldShowBackgroundGenerator }
                        onOpenBackgroundGenerator={ openBackgroundGenerator }
                        onResetImage={ () => {
                            setIsDropDownOpen( false );
                            resetBackground();
                        } }
                        onRemoveImage={ () => setIsDropDownOpen( false ) }
                    />
                ) }
            </div>
            { shouldShowBackgroundGenerator ? (
                <PopupBackgroundGeneratorControl
                    context={ backgroundGenerator }
                    isOpen={ isGeneratorOpen }
                    onRequestClose={ () => setIsGeneratorOpen( false ) }
                    onSelectMedia={ ( media ) => {
                        selectBackgroundMedia( media );
                        setIsGeneratorOpen( false );
                    } }
                />
            ) : null }
        </>
    );
};

export default BackgroundImageControl;
