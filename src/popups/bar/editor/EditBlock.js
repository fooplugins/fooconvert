import { useBlockProps, useInnerBlocksProps, store as blockEditorStore } from "@wordpress/block-editor";
import classnames from "classnames";

import { BAR_CLASS_NAME } from "./Edit";
import { useDispatch } from "@wordpress/data";
import { useDimensionStyle } from "#editor";

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
        settings,
        settingsDefaults
    } = props;

    const position = settings?.position ?? settingsDefaults?.position;
    const paddingStyle = useDimensionStyle( styles?.dimensions, ['padding'] );

    const blockProps = useBlockProps( {
        className: classnames( BAR_CLASS_NAME, {
            [ `position-${ position }` ]: position !== settingsDefaults?.position,
        } ),
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
            <div className={ `${ BAR_CLASS_NAME }__backdrop` } onClick={ () => selectBlock( clientId ) }></div>
            <div { ...combinedProps }>
                { children }
            </div>
        </>
    );
};

export default EditBlock;