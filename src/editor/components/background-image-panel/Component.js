/**
 * WordPress dependencies
 */
import { useCallback, Platform } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { ToolsPanel, ToolsPanelItem } from '../experimental';
/**
 * Internal dependencies
 */
import BackgroundImageControl from "./components/BackgroundImageControl";
import { setImmutably } from '../../utils';

const DEFAULT_CONTROLS = {
    backgroundImage: true,
};

/**
 * Checks if there is a current value in the background size block support
 * attributes. Background size values include background size as well
 * as background position.
 *
 * @param {Object} style Style attribute.
 * @return {boolean}     Whether the block has a background size value set.
 */
export function hasBackgroundSizeValue( style ) {
    return (
        style?.background?.backgroundPosition !== undefined ||
        style?.background?.backgroundSize !== undefined
    );
}

/**
 * Checks if there is a current value in the background image block support
 * attributes.
 *
 * @param {Object} style Style attribute.
 * @return {boolean}     Whether the block has a background image value set.
 */
export function hasBackgroundImageValue( style ) {
    return (
        !! style?.background?.backgroundImage?.id ||
        // Supports url() string values in theme.json.
        'string' === typeof style?.background?.backgroundImage ||
        !! style?.background?.backgroundImage?.url
    );
}

function BackgroundToolsPanel( {
                                   resetAllFilter,
                                   onChange,
                                   value,
                                   panelId,
                                   children,
                                   headerLabel,
                               } ) {

    const resetAll = () => {
        const updatedValue = resetAllFilter( value );
        onChange( updatedValue );
    };

    return (
        <ToolsPanel
            label={ headerLabel }
            resetAll={ resetAll }
            panelId={ panelId }
        >
            { children }
        </ToolsPanel>
    );
}

export default function BackgroundImagePanel( {
                                                  as: Wrapper = BackgroundToolsPanel,
                                                  value,
                                                  onChange,
                                                  inheritedValue,
                                                  panelId,
                                                  defaultControls = DEFAULT_CONTROLS,
                                                  defaultValues = {},
                                                  backgroundGenerator = null,
                                                  headerLabel = __( 'Background Image' ),
                                              } ) {

    const resetBackground = () => onChange( setImmutably( value, [ 'background' ], {} ) );
    const resetAllFilter = useCallback( ( previousValue ) => {
        return {
            ...previousValue,
            background: {},
        };
    }, [] );

    return (
        <Wrapper
            resetAllFilter={ resetAllFilter }
            value={ value }
            onChange={ onChange }
            panelId={ panelId }
            headerLabel={ headerLabel }
        >
            <ToolsPanelItem
                hasValue={ () => !! value?.background }
                label={ __( 'Image' ) }
                onDeselect={ resetBackground }
                isShownByDefault={ defaultControls.backgroundImage }
                panelId={ panelId }
            >
                <BackgroundImageControl
                    value={ value }
                    onChange={ onChange }
                    inheritedValue={ inheritedValue }
                    defaultControls={ defaultControls }
                    defaultValues={ defaultValues }
                    backgroundGenerator={ backgroundGenerator }
                />
            </ToolsPanelItem>
        </Wrapper>
    );
};
