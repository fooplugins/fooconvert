import {
    getBoxUnitSizes, useColorStyle,
    useDimensionStyle
} from "#editor";
import { useBlockProps, useInnerBlocksProps, store as blockEditorStore } from "@wordpress/block-editor";
import useIsInnerBlockSelected from "../../../editor/hooks/useIsInnerBlockSelected";
import classnames from "classnames";

import { POPUP_CLASS_NAME } from "./Edit";
import { useDispatch } from "@wordpress/data";
import { getCSSButtonWidth } from "./button/utils";
import { BUTTON_DEFAULTS } from "./button/Edit";
import { capitalize } from "@steveush/utils";

/**
 *
 * @param props
 * @returns {JSX.Element}
 */
const EditBlock = props => {

    // extract the various values used to render the block
    const {
        isSelected,
        clientId,
        attributes,
        defaults,
        innerBlocks
    } = props;

    const {
        styles = {},
        position,
        hideButton
    } = attributes;

    // get the selectBlock function from the store so that we can set the backdrop click to select this block
    const { selectBlock } = useDispatch( blockEditorStore );

    const isInnerSelected = useIsInnerBlockSelected( clientId );
    const contentBlock = innerBlocks.find( block => block.name === 'fc/popup-content' ) ?? {};
    const isContentBlockEmpty = !Array.isArray( contentBlock?.innerBlocks ) || contentBlock.innerBlocks.length === 0;
    const showInserter = ( isSelected && isContentBlockEmpty ) || isInnerSelected;

    const blockProps = useBlockProps( {
        className: classnames( POPUP_CLASS_NAME, {
            'show-inserter': isContentBlockEmpty,
            [ `position-${ position }` ]: position === 'bottom'
        } )
    } );

    const marginStyle = useDimensionStyle( styles?.dimensions, [ 'margin' ] );
    const colorStyle = useColorStyle( styles?.color, { text: 'color' } );
    const backdropStyle = useColorStyle( styles?.color, { backdrop: 'background' } );
    // const buttonSpacerStyle = {};
    // if ( !hideButton ) {
    //     const button = attributes?.button ?? {};
    //     buttonSpacerStyle[`padding${ capitalize(button?.position ?? BUTTON_DEFAULTS.position) }`] = getCSSButtonWidth( button, BUTTON_DEFAULTS );
    // }

    const { children, ...innerBlocksProps } = useInnerBlocksProps( {
        className: `${ POPUP_CLASS_NAME }__container`,
        style: {
            ...marginStyle,
            ...colorStyle,
            // ...buttonSpacerStyle
        },
        orientation: 'vertical'
    } );

    const backdropProps = {
        className: `${ POPUP_CLASS_NAME }__backdrop`,
        style: {
            ...backdropStyle
        }
    };

    return (
        <>
            <div { ...backdropProps } onClick={ () => selectBlock( clientId ) }></div>
            <div { ...blockProps }>
                <div { ...innerBlocksProps }>
                    { children }
                </div>
            </div>
        </>
    );
};

export default EditBlock;