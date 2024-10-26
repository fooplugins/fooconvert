import { useBlockProps, useInnerBlocksProps, store as blockEditorStore } from "@wordpress/block-editor";
import classnames from "classnames";

import { FLYOUT_CLASS_NAME } from "./Edit";
import { useDispatch } from "@wordpress/data";
import { useEffect } from "@wordpress/element";
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
        defaults,
        viewState
    } = props;

    const position = settings?.position ?? defaults?.settings?.position;
    const paddingStyle = useDimensionStyle( styles?.dimensions, ['padding'] );

    const blockProps = useBlockProps( {
        className: classnames( FLYOUT_CLASS_NAME, `view-state__${ viewState }`, {
            [ `position-${ position }` ]: position !== defaults?.settings?.position
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
            <div className={ `${ FLYOUT_CLASS_NAME }__backdrop` } onClick={ () => selectBlock( clientId ) }></div>
            <div { ...combinedProps }>
                { children }
            </div>
        </>
    );
};

export default EditBlock;