import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import classnames from "classnames";
import { CONTAINER_CLASS_NAME } from "./Edit";
import { isBarContentWidthMode } from "../../size-controls";

const EditBlock = props => {

    const {
        parentAttributes,
        parentAttributesDefaults
    } = props;

    const settings = parentAttributes?.settings ?? {};
    const settingsDefaults = { ...( parentAttributesDefaults?.settings ?? {} ) };

    const isContentWidth = isBarContentWidthMode( settings, settingsDefaults );
    const maxWidth = settings?.maxWidth ?? settingsDefaults?.maxWidth;
    const style = {};
    if ( isContentWidth ) {
        style.width = "fit-content";
    } else if ( maxWidth ) {
        style.maxWidth = maxWidth;
    }

    const blockProps = useBlockProps( {
        className: classnames( CONTAINER_CLASS_NAME, {
            [ `${ CONTAINER_CLASS_NAME }--content-width` ]: isContentWidth,
        } ),
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
