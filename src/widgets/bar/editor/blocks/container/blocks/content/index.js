/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { postContent as icon } from '@wordpress/icons';

import block from './block.json';
import './index.scss';
import Edit from './Edit';
import { InnerBlocks } from "@wordpress/block-editor";

// Register the block
registerBlockType( block.name, {
    icon,
    edit: Edit,
    save: () => <InnerBlocks.Content/>,
} );