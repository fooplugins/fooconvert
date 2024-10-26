import { __ } from "@wordpress/i18n";
// noinspection ES6PreferShortImport
import { ToolsPanel, ColorGradientSettingsDropdown } from "../experimental";

import classnames from "classnames";

import { isPossibleGradient } from "../../utils";
import { useColorsAndGradients } from "../../hooks";

import "./Component.scss";
import { cleanObject, isFunction } from "@steveush/utils";

const CLASS_NAME = 'fc--color-tools-panel';

/**
 * @typedef {{ key: string, label: string, enableAlpha?: boolean, enableGradient?: boolean }} ColorToolsPanelOption
 */

/**
 * @typedef {Omit<import('@wordpress/components/build-types/tools-panel/types').ToolsPanelProps, "children", "resetAll", "hasInnerWrapper", "shouldRenderPlaceholderItems", "dropdownMenuProps">} ColorToolsPanelProps
 * @property {Record<string, (string|undefined)>|undefined} value
 * @property {(value:(Record<string, string>|undefined))=>void} onChange
 * @property {ColorToolsPanelOption[]} options
 * @property {Record<string, (string|undefined)>|undefined} [defaults]
 * @property {()=>import('react').ReactNode} [itemRenderer]
 */

/**
 *
 * @param {ColorToolsPanelProps} props
 * @returns {JSX.Element}
 */
const ColorToolsPanel = ( props ) => {

    const {
        value,
        options,
        onChange,
        defaults = {},
        itemRenderer,
        panelId,
        label,
        className,
        ...restProps
    } = props;

    const hasItemRenderer = isFunction( itemRenderer );
    const colorGradientSettings = useColorsAndGradients();

    const setColor = newValue => {
        const previousValue = value ?? {};
        const nextValue = typeof newValue === 'object' ? {
            ...previousValue,
            ...newValue
        } : undefined;
        onChange( cleanObject( nextValue ) );
    };

    // convert the simplified options to the settings object required by the ColorGradientSettingsDropdown
    // see https://github.com/WordPress/gutenberg/blob/trunk/packages/block-editor/src/components/colors-gradients/dropdown.js#L107
    const settings = options.map( option => {
        const { key, enableGradient, ...restOption } = option;
        const currentValue = typeof value !== "undefined" ? value[ key ] : defaults[ key ];
        const isGradient = isPossibleGradient( currentValue );
        let isset = false;
        /**
         * Handles both the color and gradient change callbacks from the ColorGradientSettingsDropdown. Instead of storing the background color and gradient as separate properties this value
         * @param {string|undefined} nextValue
         */
        const onAnyChange = nextValue => {
            if ( !isset ) {
                setColor( { [ key ]: nextValue } );
                isset = true;
            }
        };
        return {
            colorValue: isGradient ? undefined : currentValue,
            onColorChange: onAnyChange,
            ...( enableGradient ? {
                gradientValue: isGradient ? currentValue : undefined,
                onGradientChange: onAnyChange,
            } : {} ),
            ...restOption
        };
    } );

    return (
        <ToolsPanel
            className={ classnames( CLASS_NAME, className ) }
            panelId={ panelId }
            label={ label ?? __( 'Color', 'fooconvert' ) }
            resetAll={ () => setColor( undefined ) }
            __experimentalFirstVisibleItemClass="first"
            __experimentalLastVisibleItemClass="last"
            { ...restProps }
        >
            <div className={ `${ CLASS_NAME }__inner` }>
                <ColorGradientSettingsDropdown
                    __experimentalHasMultipleOrigins
                    __experimentalIsRenderedInSidebar
                    settings={ settings }
                    panelId={ panelId }
                    { ...colorGradientSettings }
                />
            </div>
            { hasItemRenderer && itemRenderer() }
        </ToolsPanel>
    );
};

export default ColorToolsPanel;