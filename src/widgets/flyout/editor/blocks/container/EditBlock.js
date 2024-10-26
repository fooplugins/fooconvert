import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import classnames from "classnames";
import { CONTAINER_CLASS_NAME } from "./Edit";

const EditBlock = props => {

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

export default EditBlock;