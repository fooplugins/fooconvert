import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import classnames from "classnames";

export const CONTAINER_CLASS_NAME = 'fc--popup-container';

const Edit = props => {

    const blockProps = useBlockProps( {
        className: classnames( CONTAINER_CLASS_NAME )
    } );

    const { children, ...contentProps } = useInnerBlocksProps( blockProps, {
        templateLock: "all",
        orientation: 'horizontal'
    } );

    return (
        <div { ...contentProps }>
            { children }
        </div>
    );
};

export default Edit;