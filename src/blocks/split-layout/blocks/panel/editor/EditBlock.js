import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import { useStyles } from "#editor";
import classnames from "classnames";

import { SPLIT_LAYOUT_PANEL_CLASS_NAME } from "./Edit";

const EditBlock = ( props ) => {
    const {
        settings,
        settingsDefaults,
        styles,
    } = props;

    const justifyContent = settings?.justifyContent ?? settingsDefaults?.justifyContent;
    const horizontalAlignment = settings?.horizontalAlignment ?? settingsDefaults?.horizontalAlignment;
    const inlineStyles = useStyles( styles );
    const blockProps = useBlockProps( {
        className: classnames( SPLIT_LAYOUT_PANEL_CLASS_NAME ),
        style: {
            ...inlineStyles,
            justifyContent,
            alignItems: horizontalAlignment,
        },
    } );

    const { children, ...innerBlocksProps } = useInnerBlocksProps( blockProps, {
        templateLock: false,
        orientation: "vertical",
    } );

    return (
        <div { ...innerBlocksProps }>
            { children }
        </div>
    );
};

export default EditBlock;
