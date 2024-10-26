/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { seen as icon } from '@wordpress/icons';

import block from '../block.json';
import './index.scss';
import Edit from './Edit';

// Register the block
registerBlockType( block.name, {
    icon,
    edit: Edit,
    save: () => null
} );