import { useBlockProps, useInnerBlocksProps, store as blockEditorStore } from "@wordpress/block-editor";
import classnames from "classnames";

import { POPUP_CLASS_NAME } from "./Edit";
import { useDispatch } from "@wordpress/data";
import { useEffect } from "@wordpress/element";
import { useColorStyle, useDimensionStyle, useStyles } from "#editor";

/**
 *
 * @param props
 * @returns {JSX.Element}
 */
const EditBlock = props => {

    // extract the various values used to render the block
    const {
        clientId,
        styles,
    } = props;

    const paddingStyle = useDimensionStyle( styles?.dimensions, ['padding'] );
    const colorStyle = useColorStyle( styles?.color, { backdrop: 'background' } );

    const backdropProps = {
        className: `${ POPUP_CLASS_NAME }__backdrop`,
        style: {
            ...colorStyle
        }
    };

    const blockProps = useBlockProps( {
        className: POPUP_CLASS_NAME,
        style: {
            ...paddingStyle
        }
    } );

    const { children, ...combinedProps } = useInnerBlocksProps( {
        ...blockProps,
        orientation: 'horizontal'
    } );

    const { selectBlock } = useDispatch( blockEditorStore );

    return (
        <>
            <div { ...backdropProps } onClick={ () => selectBlock( clientId ) }></div>
            <div { ...combinedProps }>
                { children }
            </div>
        </>
    );
};

export default EditBlock;