/**
 * Editor preview for the Cookie Consent Preferences block.
 *
 * The block is server-rendered so the real category list always matches
 * settings; this component is only what the admin sees in the editor.
 * It reads the defaults from the localized config (populated server-side
 * by the editor config filter) and falls back to a hard-coded preview so
 * the block is useful even when the config hasn't been wired up yet.
 */

import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const FALLBACK_CATEGORIES = [
    {
        key: 'necessary',
        label: __( 'Necessary', 'fooconvert' ),
        description: __( 'Required for the site to work. Cannot be disabled.', 'fooconvert' ),
        locked: true,
    },
    {
        key: 'preferences',
        label: __( 'Preferences', 'fooconvert' ),
        description: __( 'Remember choices you make so you don\'t have to set them again.', 'fooconvert' ),
    },
    {
        key: 'statistics',
        label: __( 'Statistics', 'fooconvert' ),
        description: __( 'Help us understand how visitors use the site, in aggregate.', 'fooconvert' ),
    },
    {
        key: 'marketing',
        label: __( 'Marketing', 'fooconvert' ),
        description: __( 'Used to show you relevant ads on this site and elsewhere.', 'fooconvert' ),
    },
];

const getCategories = () => {
    // Produced server-side by `BaseBlock::enqueue_editor_settings()`, which
    // serialises this block's `get_editor_data()` under an uppercased,
    // underscore-separated identifier derived from the block name.
    const fromServer = globalThis?.FC_COOKIE_CONSENT_PREFERENCES?.data?.categories;
    if ( Array.isArray( fromServer ) && fromServer.length > 0 ) {
        return fromServer;
    }
    return FALLBACK_CATEGORIES;
};

const Edit = ( { attributes, setAttributes } ) => {
    const blockProps = useBlockProps( {
        className: 'fc-cookie-consent-preferences fc-cookie-consent-preferences--editor',
    } );

    const { startExpanded } = attributes;
    const categories = getCategories();

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Preferences Panel', 'fooconvert' ) }>
                    <ToggleControl
                        label={ __( 'Start expanded', 'fooconvert' ) }
                        help={ __( 'Show the panel as soon as the banner appears. Most sites leave this off and reveal the panel only after the visitor clicks "Preferences".', 'fooconvert' ) }
                        checked={ !! startExpanded }
                        onChange={ ( value ) => setAttributes( { startExpanded: !! value } ) }
                    />
                </PanelBody>
            </InspectorControls>
            <div { ...blockProps }>
                <p className="fc-cookie-consent-preferences__editor-note">
                    { __( 'Category labels and descriptions are managed on the Cookie Consent settings page.', 'fooconvert' ) }
                </p>
                <div className="fc-cookie-consent-preferences__list">
                    { categories.map( ( cat ) => (
                        <div
                            key={ cat.key }
                            className={ `fc-cookie-consent-preferences__item${ cat.locked ? ' fc-cookie-consent-preferences__item--locked' : '' }` }
                            data-category={ cat.key }
                            data-state={ cat.locked ? 'on' : 'off' }
                            data-locked={ cat.locked ? 'true' : 'false' }
                        >
                            <div className="fc-cookie-consent-preferences__item-head">
                                <span className="fc-cookie-consent-preferences__label">{ cat.label }</span>
                                <span className="fc-cookie-consent-preferences__state">
                                    { cat.locked ? __( 'Always on', 'fooconvert' ) : __( 'Off', 'fooconvert' ) }
                                </span>
                            </div>
                            { cat.description ? (
                                <p className="fc-cookie-consent-preferences__desc">{ cat.description }</p>
                            ) : null }
                        </div>
                    ) ) }
                </div>
            </div>
        </>
    );
};

export default Edit;
