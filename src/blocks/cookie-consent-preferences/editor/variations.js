/**
 * Registers three variations of `core/button` — Accept All, Reject All,
 * and Cookie Preferences — so admins can build or restyle a cookie
 * consent banner using ordinary core Button blocks without a bespoke
 * custom button block per action.
 *
 * Each variation sets a `consentAction` attribute on the block, and a
 * `blocks.getSaveContent.extraProps` filter stamps
 * `data-fc-consent-action="<action>"` onto the rendered `<a>` / `<button>`
 * so the frontend runtime can bind click handlers without us needing to
 * know the block's CSS structure.
 */

import { addFilter } from '@wordpress/hooks';
import { registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

const ACTION_ATTRIBUTE = 'consentAction';
const DATA_ATTRIBUTE = 'data-fc-consent-action';

const variations = [
    {
        name: 'fc-cookie-consent-accept',
        title: __( 'Cookie Consent — Accept All', 'fooconvert' ),
        description: __( 'A core Button variation that grants every consent category when clicked.', 'fooconvert' ),
        keywords: [ 'cookie', 'consent', 'accept', 'gdpr' ],
        scope: [ 'inserter', 'transform' ],
        attributes: {
            text: __( 'Accept all', 'fooconvert' ),
            [ ACTION_ATTRIBUTE ]: 'accept',
        },
        isActive: [ ACTION_ATTRIBUTE ],
    },
    {
        name: 'fc-cookie-consent-reject',
        title: __( 'Cookie Consent — Reject All', 'fooconvert' ),
        description: __( 'A core Button variation that rejects every non-essential consent category when clicked.', 'fooconvert' ),
        keywords: [ 'cookie', 'consent', 'reject', 'gdpr' ],
        scope: [ 'inserter', 'transform' ],
        attributes: {
            text: __( 'Reject all', 'fooconvert' ),
            className: 'is-style-outline',
            [ ACTION_ATTRIBUTE ]: 'reject',
        },
        isActive: [ ACTION_ATTRIBUTE ],
    },
    {
        name: 'fc-cookie-consent-preferences',
        title: __( 'Cookie Consent — Preferences', 'fooconvert' ),
        description: __( 'A core Button variation that opens the preferences panel when clicked.', 'fooconvert' ),
        keywords: [ 'cookie', 'consent', 'preferences', 'customize', 'gdpr' ],
        scope: [ 'inserter', 'transform' ],
        attributes: {
            text: __( 'Preferences', 'fooconvert' ),
            className: 'is-style-outline',
            [ ACTION_ATTRIBUTE ]: 'preferences',
        },
        isActive: [ ACTION_ATTRIBUTE ],
    },
];

/**
 * Register the `consentAction` attribute on `core/button` so the variation
 * can persist it. WP lets us extend a block type's attribute schema via a
 * `blocks.registerBlockType` filter.
 */
addFilter(
    'blocks.registerBlockType',
    'fooconvert/cookie-consent/button-attribute',
    ( settings, name ) => {
        if ( name !== 'core/button' ) {
            return settings;
        }

        return {
            ...settings,
            attributes: {
                ...settings.attributes,
                [ ACTION_ATTRIBUTE ]: {
                    type: 'string',
                },
            },
        };
    }
);

/**
 * Stamp the save-time `data-fc-consent-action` attribute onto the rendered
 * button when the consent action attribute is set. WP merges these props
 * onto the block's root element in the saved HTML, which ultimately lands
 * on the `<a>` the button block produces.
 */
addFilter(
    'blocks.getSaveContent.extraProps',
    'fooconvert/cookie-consent/button-save-props',
    ( props, blockType, attributes ) => {
        if ( blockType?.name !== 'core/button' ) {
            return props;
        }

        const action = attributes?.[ ACTION_ATTRIBUTE ];
        if ( typeof action !== 'string' || action === '' ) {
            return props;
        }

        return {
            ...props,
            [ DATA_ATTRIBUTE ]: action,
        };
    }
);

variations.forEach( ( variation ) => {
    registerBlockVariation( 'core/button', variation );
} );
