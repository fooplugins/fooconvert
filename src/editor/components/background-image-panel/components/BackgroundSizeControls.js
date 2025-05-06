import { setImmutably } from "../../../utils";
import { backgroundPositionToCoords, backgroundSizeHelpText, coordsToBackgroundPosition } from "../utils";
import { FocalPointPicker, ToggleControl } from "@wordpress/components";
import { __, _x } from "@wordpress/i18n";

import { HStack, ToggleGroupControl, ToggleGroupControlOption, UnitControl, VStack } from "../../experimental";

const BackgroundSizeControls = ({
                                    onChange,
                                    style,
                                    inheritedValue,
                                    defaultValues,
                                }) => {
    const sizeValue =
        style?.background?.backgroundSize ||
        inheritedValue?.background?.backgroundSize;
    const repeatValue =
        style?.background?.backgroundRepeat ||
        inheritedValue?.background?.backgroundRepeat;
    const imageValue =
        style?.background?.backgroundImage?.url ||
        inheritedValue?.background?.backgroundImage?.url;
    const isUploadedImage = style?.background?.backgroundImage?.id;
    const positionValue =
        style?.background?.backgroundPosition ||
        inheritedValue?.background?.backgroundPosition;
    const attachmentValue =
        style?.background?.backgroundAttachment ||
        inheritedValue?.background?.backgroundAttachment;

    /*
     * Set default values for uploaded images.
     * The default values are passed by the consumer.
     * Block-level controls may have different defaults to root-level controls.
     * A falsy value is treated by default as `auto` (Tile).
     */
    let currentValueForToggle =
        ! sizeValue && isUploadedImage
            ? defaultValues?.backgroundSize
            : sizeValue || 'auto';
    /*
     * The incoming value could be a value + unit, e.g. '20px'.
     * In this case set the value to 'tile'.
     */
    currentValueForToggle = ! [ 'cover', 'contain', 'auto' ].includes(
        currentValueForToggle
    )
        ? 'auto'
        : currentValueForToggle;
    /*
     * If the current value is `cover` and the repeat value is `undefined`, then
     * the toggle should be unchecked as the default state. Otherwise, the toggle
     * should reflect the current repeat value.
     */
    const repeatCheckedValue = ! (
        repeatValue === 'no-repeat' ||
        ( currentValueForToggle === 'cover' && repeatValue === undefined )
    );

    const updateBackgroundSize = ( next ) => {
        // When switching to 'contain' toggle the repeat off.
        let nextRepeat = repeatValue;
        let nextPosition = positionValue;

        if ( next === 'contain' ) {
            nextRepeat = 'no-repeat';
            nextPosition = undefined;
        }

        if ( next === 'cover' ) {
            nextRepeat = undefined;
            nextPosition = undefined;
        }

        if (
            ( currentValueForToggle === 'cover' ||
                currentValueForToggle === 'contain' ) &&
            next === 'auto'
        ) {
            nextRepeat = undefined;
            /*
             * A background image uploaded and set in the editor (an image with a record id),
             * receives a default background position of '50% 0',
             * when the toggle switches to "Tile". This is to increase the chance that
             * the image's focus point is visible.
             * This is in-editor only to assist with the user experience.
             */
            if ( !! style?.background?.backgroundImage?.id ) {
                nextPosition = '50% 0';
            }
        }

        /*
         * Next will be null when the input is cleared,
         * in which case the value should be 'auto'.
         */
        if ( ! next && currentValueForToggle === 'auto' ) {
            next = 'auto';
        }

        onChange(
            setImmutably( style, [ 'background' ], {
                ...style?.background,
                backgroundPosition: nextPosition,
                backgroundRepeat: nextRepeat,
                backgroundSize: next,
            } )
        );
    };

    const updateBackgroundPosition = ( next ) => {
        onChange(
            setImmutably(
                style,
                [ 'background', 'backgroundPosition' ],
                coordsToBackgroundPosition( next )
            )
        );
    };

    const toggleIsRepeated = () =>
        onChange(
            setImmutably(
                style,
                [ 'background', 'backgroundRepeat' ],
                repeatCheckedValue === true ? 'no-repeat' : 'repeat'
            )
        );

    const toggleScrollWithPage = () =>
        onChange(
            setImmutably(
                style,
                [ 'background', 'backgroundAttachment' ],
                attachmentValue === 'fixed' ? 'scroll' : 'fixed'
            )
        );

    // Set a default background position for non-site-wide, uploaded images with a size of 'contain'.
    const backgroundPositionValue =
        ! positionValue && isUploadedImage && 'contain' === sizeValue
            ? defaultValues?.backgroundPosition
            : positionValue;

    return (
        <VStack spacing={ 3 } className="single-column">
            <FocalPointPicker
                __nextHasNoMarginBottom
                label={ __( 'Focal point' ) }
                url={ imageValue }
                value={ backgroundPositionToCoords( backgroundPositionValue ) }
                onChange={ updateBackgroundPosition }
            />
            <ToggleControl
                __nextHasNoMarginBottom
                label={ __( 'Fixed background' ) }
                checked={ attachmentValue === 'fixed' }
                onChange={ toggleScrollWithPage }
            />
            <ToggleGroupControl
                __nextHasNoMarginBottom
                size="__unstable-large"
                label={ __( 'Size' ) }
                value={ currentValueForToggle }
                onChange={ updateBackgroundSize }
                isBlock
                help={ backgroundSizeHelpText(
                    sizeValue || defaultValues?.backgroundSize
                ) }
            >
                <ToggleGroupControlOption
                    key="cover"
                    value="cover"
                    label={ _x(
                        'Cover',
                        'Size option for background image control'
                    ) }
                />
                <ToggleGroupControlOption
                    key="contain"
                    value="contain"
                    label={ _x(
                        'Contain',
                        'Size option for background image control'
                    ) }
                />
                <ToggleGroupControlOption
                    key="tile"
                    value="auto"
                    label={ _x(
                        'Tile',
                        'Size option for background image control'
                    ) }
                />
            </ToggleGroupControl>
            <HStack justify="flex-start" spacing={ 2 } as="span">
                <UnitControl
                    aria-label={ __( 'Background image width' ) }
                    onChange={ updateBackgroundSize }
                    value={ sizeValue }
                    size="__unstable-large"
                    __unstableInputWidth="100px"
                    min={ 0 }
                    placeholder={ __( 'Auto' ) }
                    disabled={
                        currentValueForToggle !== 'auto' ||
                        currentValueForToggle === undefined
                    }
                />
                <ToggleControl
                    __nextHasNoMarginBottom
                    label={ __( 'Repeat' ) }
                    checked={ repeatCheckedValue }
                    onChange={ toggleIsRepeated }
                    disabled={ currentValueForToggle === 'cover' }
                />
            </HStack>
        </VStack>
    );
};

export default BackgroundSizeControls;