import {
    getBoxUnitSizes, useColorStyle,
    useDimensionStyle
} from "#editor";
import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import useIsInnerBlockSelected from "../../../editor/hooks/useIsInnerBlockSelected";
import classnames from "classnames";

const CLASS_NAME = 'fc--bar';

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
        attributes: {
            styles = {},
            position
        },
        defaults
    } = props;

    const isInnerSelected = useIsInnerBlockSelected( clientId );
    const showInserter = isSelected || isInnerSelected;

    const blockProps = useBlockProps( {
        className: classnames( CLASS_NAME, {
            'show-inserter': showInserter,
            [`position-${ position }`]: position === 'bottom'
        } )
    } );

    const { left: marginLeft, right: marginRight } = getBoxUnitSizes( styles?.dimensions?.margin ?? defaults?.styles?.dimensions?.margin );
    const marginStyle = useDimensionStyle( styles?.dimensions, [ 'margin' ] );
    const colorStyle = useColorStyle( styles?.color, { text: 'color' } );

    const { children, ...innerBlocksProps } = useInnerBlocksProps( {
        className: `${ CLASS_NAME }__container`,
        style: {
            ...marginStyle,
            ...colorStyle,
            width: `calc( 100% - ${ marginLeft } - ${ marginRight } )`
        },
        orientation: 'horizontal'
    } );

    return (
        <div { ...blockProps }>
            <div { ...innerBlocksProps }>
                { children }
            </div>
        </div>
    );
};

export default EditBlock;