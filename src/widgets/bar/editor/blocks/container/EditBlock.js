import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import classnames from "classnames";
import { CONTAINER_CLASS_NAME } from "./Edit";
import { $object } from "#editor";

const EditBlock = props => {

    const {
        parentAttributes,
        parentAttributesDefaults
    } = props;

    const settings = parentAttributes?.settings ?? {};
    const settingsDefaults = { ...( parentAttributesDefaults?.settings ?? {} ) };

    const maxWidth = settings?.maxWidth ?? settingsDefaults?.maxWidth;
    const style = {};
    if ( maxWidth ) {
        style.maxWidth = maxWidth;
    }

    const blockProps = useBlockProps( {
        className: classnames( CONTAINER_CLASS_NAME ),
        style: {
            ...style
        }
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