/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import { Icon } from '@wordpress/icons';
import { registerPlugin } from "@wordpress/plugins";

import { CustomEditorPlugin, OverrideTemplateValidityPlugin, DisplayRulesPlugin, CompatibilityPlugin, getBlockSettings } from "#editor";

import block from '../block.json';
import './index.scss';
import Edit from './Edit';

const { variations } = getBlockSettings( block.name );

registerPlugin( "fc-compatibility", { render: CompatibilityPlugin } );
registerPlugin( "fc-custom-editor", { render: CustomEditorPlugin } );
registerPlugin( "fc-display-rules", { render: DisplayRulesPlugin } );
registerPlugin( "fc-override-template-validity", { render: OverrideTemplateValidityPlugin } );

const icon = () => (
    <Icon icon={
        <svg viewBox="0 0 24 24">
            <path
                d="M18.984 18v-9.984h-13.969v9.984h13.969zM18.984 3.984q0.844 0 1.43 0.586t0.586 1.43v12q0 0.797-0.609 1.406t-1.406 0.609h-13.969q-0.844 0-1.43-0.586t-0.586-1.43v-12q0-0.844 0.586-1.43t1.43-0.586h13.969z"></path>
        </svg>
    }/>
);

// Register the block
registerBlockType( block.name, {
    icon,
    edit: Edit,
    save: () => <InnerBlocks.Content/>,
    variations
} );