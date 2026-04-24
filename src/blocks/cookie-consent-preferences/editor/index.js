/**
 * Entry point for the Cookie Consent Preferences block's editor bundle.
 *
 * Does three things:
 *   1. Registers the `fc/cookie-consent-preferences` block type.
 *   2. Registers the three `core/button` variations used for the
 *      Accept / Reject / Preferences buttons (imported side-effect).
 *   3. Wires the `blocks.getSaveContent.extraProps` filter that stamps
 *      `data-fc-consent-action` on the saved button HTML.
 *
 * Loading both the block registration and the button variations from the
 * same editor asset means the variations become available as soon as the
 * popup editor opens — no separate admin enqueue dance.
 */

import { registerBlockType } from '@wordpress/blocks';
import { cog as icon } from '@wordpress/icons';

import block from '../block.json';
import './index.scss';
import './variations';
import Edit from './Edit';

registerBlockType( block.name, {
    icon,
    edit: Edit,
    save: () => null,
} );
