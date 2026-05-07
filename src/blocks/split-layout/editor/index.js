import { registerBlockType } from "@wordpress/blocks";
import { InnerBlocks } from "@wordpress/block-editor";
import { group as icon } from "@wordpress/icons";

import block from "../block.json";
import "./index.scss";
import Edit from "./Edit";

registerBlockType( block.name, {
    icon,
    edit: Edit,
    save: () => <InnerBlocks.Content />,
} );
