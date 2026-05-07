import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import { useStyles } from "#editor";
import classnames from "classnames";

import { SPLIT_LAYOUT_CLASS_NAME } from "./Edit";

const TEMPLATE = [
    [ "fc/split-layout-panel", { lock: { move: true, remove: true } }, [] ],
    [ "fc/split-layout-panel", { lock: { move: true, remove: true } }, [] ],
];

const EditBlock = ( props ) => {
    const {
        settings,
        settingsDefaults,
        styles,
    } = props;

    const fixedSide = settings?.fixedSide ?? settingsDefaults?.fixedSide;
    const fixedWidth = settings?.fixedWidth ?? settingsDefaults?.fixedWidth;
    const verticalAlignment = settings?.verticalAlignment ?? settingsDefaults?.verticalAlignment;
    const inlineStyles = useStyles( styles );

    const blockProps = useBlockProps( {
        className: classnames(
            SPLIT_LAYOUT_CLASS_NAME,
            `${ SPLIT_LAYOUT_CLASS_NAME }--fixed-${ fixedSide }`,
            `${ SPLIT_LAYOUT_CLASS_NAME }--align-${ verticalAlignment }`
        ),
        style: {
            ...inlineStyles,
            "--fc-split-layout-fixed-width": fixedWidth,
        },
    } );

    const { children, ...innerBlocksProps } = useInnerBlocksProps( blockProps, {
        allowedBlocks: [ "fc/split-layout-panel" ],
        template: TEMPLATE,
        templateLock: "insert",
        orientation: "horizontal",
    } );

    return (
        <div { ...innerBlocksProps }>
            { children }
        </div>
    );
};

export default EditBlock;
