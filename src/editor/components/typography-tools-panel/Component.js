import { __ } from "@wordpress/i18n";
import {
    NumberControl,
    ToggleGroupControl,
    ToggleGroupControlOption,
    ToolsPanel,
    ToolsPanelItem,
    UnitControl
} from "../experimental";

import classnames from "classnames";
import { cleanObject, isFunction, isString } from "@steveush/utils";

import { SizeControl } from "../size-control";
import { FontAppearanceControl, isFontAppearance } from "../font-appearance-control";
import { FontFamilyControl } from "../font-family-control";

import "./Component.scss";
import { Icon, formatUnderline, formatStrikethrough, formatUppercase, formatLowercase, formatCapitalize, reset } from "@wordpress/icons";
import { renderToString } from "@wordpress/element";

const CLASS_NAME = 'fc--typography-tools-panel';

const TypographyToolsPanel = ( props ) => {

    const {
        value,
        onChange,
        controls = [ "fontFamily", "fontSize", "fontAppearance", "lineHeight", "letterSpacing", "textDecoration", "textTransform" ],
        fontSizes = [
            {
                abbr: __( 'S', 'fooconvert' ),
                label: __( 'Small', 'fooconvert' ),
                slug: 'small',
                value: '1rem',
            },
            {
                abbr: __( 'M', 'fooconvert' ),
                label: __( 'Medium', 'fooconvert' ),
                slug: 'medium',
                value: '1.125rem',
            },
            {
                abbr: __( 'L', 'fooconvert' ),
                label: __( 'Large', 'fooconvert' ),
                slug: 'large',
                value: '1.75rem',
            },
            {
                abbr: __( 'XL', 'fooconvert' ),
                label: __( 'Extra Large', 'fooconvert' ),
                slug: 'x-large',
                value: 'clamp(1.75rem, 3vw, 2.25rem)',
            },
        ],
        defaults = {},
        itemRenderer,
        panelId,
        label,
        className,
        ...restProps
    } = props;

    const hasItemRenderer = isFunction( itemRenderer );
    const {
        fontFamily,
        fontSize,
        fontStyle, // FontAppearance = fontStyle & fontWeight
        fontWeight,
        lineHeight,
        letterSpacing,
        textDecoration,
        textTransform
    } = value ?? {};
    let fontAppearance = { fontStyle, fontWeight };

    const hasFontFamily = isString( fontFamily, true );
    const hasFontSize = isString( fontSize, true );
    const hasFontAppearance = isFontAppearance( fontAppearance );
    const hasLineHeight = isString( lineHeight, true );
    const hasLetterSpacing = isString( letterSpacing, true );
    const hasTextDecoration = isString( textDecoration, true );
    const hasTextTransform = isString( textTransform, true );

    if ( !hasFontAppearance ){
        const { fontStyle: defaultFontStyle, fontWeight: defaultFontWeight } = defaults;
        const defaultFontAppearance = { fontStyle: defaultFontStyle, fontWeight: defaultFontWeight };
        fontAppearance = isFontAppearance( defaultFontAppearance ) ? defaultFontAppearance : undefined;
    }

    const setTypography = newValue => {
        const previousValue = value ?? {};
        const nextValue = typeof newValue === 'object' ? {
            ...previousValue,
            ...newValue
        } : undefined;
        onChange( cleanObject( nextValue ) );
    };

    const setFontFamily = nextValue => setTypography( { fontFamily: nextValue } );
    const setFontSize = nextValue => setTypography( { fontSize: nextValue } );
    const setFontAppearance = newValue => {
        const { fontStyle, fontWeight } = newValue ?? {};
        setTypography( { fontStyle: fontStyle, fontWeight: fontWeight } );
    };
    const setLineHeight = nextValue => setTypography( { lineHeight: nextValue } );
    const setLetterSpacing = nextValue => setTypography( { letterSpacing: nextValue } );
    const setTextDecoration = nextValue => setTypography( { textDecoration: nextValue } );
    const setTextTransform = nextValue => setTypography( { textTransform: nextValue } );

    const resetAll = () => setTypography( undefined );

    return (
        <ToolsPanel
            panelId={ panelId }
            className={ classnames( CLASS_NAME, className ) }
            label={ label ?? __( "Typography", "fooconvert" ) }
            resetAll={ resetAll }
            hasInnerWrapper={ true }
            { ...restProps }
        >
            <div className={ `${ CLASS_NAME }__inner` }>
                <ToolsPanelItem
                    panelId={ panelId }
                    hasValue={ () => hasFontFamily }
                    label={ __( "Font", "fooconvert" ) }
                    onDeselect={ () => setFontFamily( undefined ) }
                >
                    <FontFamilyControl
                        label={ __( "Font", "fooconvert" ) }
                        value={ fontFamily ?? defaults?.fontFamily }
                        onChange={ setFontFamily }
                    />
                </ToolsPanelItem>
                <ToolsPanelItem
                    panelId={ panelId }
                    hasValue={ () => hasFontSize }
                    label={ __( "Size", "fooconvert" ) }
                    onDeselect={ () => setFontSize( undefined ) }
                    isShownByDefault={ true }
                >
                    <SizeControl
                        label={ __( "Size", "fooconvert" ) }
                        value={ fontSize ?? defaults?.fontSize }
                        onChange={ setFontSize }
                        sizes={ fontSizes }
                    />
                </ToolsPanelItem>
                <ToolsPanelItem
                    className="single-column"
                    panelId={ panelId }
                    hasValue={ () => hasFontAppearance }
                    label={ __( "Appearance", "fooconvert" ) }
                    onDeselect={ () => setFontAppearance( undefined ) }
                >
                    <FontAppearanceControl
                        label={ __( "Appearance", "fooconvert" ) }
                        value={ fontAppearance }
                        onChange={ setFontAppearance }
                    />
                </ToolsPanelItem>
                <ToolsPanelItem
                    className="single-column"
                    panelId={ panelId }
                    hasValue={ () => hasLineHeight }
                    label={ __( "Line height", "fooconvert" ) }
                    onDeselect={ () => setLineHeight( undefined ) }
                >
                    <NumberControl
                        label={ __( "Line height", "fooconvert" ) }
                        value={ lineHeight ?? defaults?.lineHeight }
                        onChange={ setLineHeight }
                        spinControls="custom"
                        min={ 1 }
                        step={ 0.1 }
                    />
                </ToolsPanelItem>
                <ToolsPanelItem
                    className="single-column"
                    panelId={ panelId }
                    hasValue={ () => hasLetterSpacing }
                    label={ __( "Letter spacing", "fooconvert" ) }
                    onDeselect={ () => setLetterSpacing( undefined ) }
                >
                    <UnitControl
                        label={ __( "Letter spacing", "fooconvert" ) }
                        value={ letterSpacing ?? defaults?.letterSpacing }
                        onChange={ setLetterSpacing }
                        units={ [
                            { value: 'px', label: 'px', default: 0, step: 1 },
                            { value: 'em', label: 'em', default: 0, step: 0.1 },
                            { value: 'rem', label: 'rem', default: 0, step: 0.1 },
                            { value: 'vw', label: 'vw', default: 0, step: 0.1 },
                            { value: 'vh', label: 'vh', default: 0, step: 0.1 },
                        ] }
                        size={ "__unstable-large" }
                    />
                </ToolsPanelItem>
                <ToolsPanelItem
                    className="single-column"
                    panelId={ panelId }
                    hasValue={ () => hasTextDecoration }
                    label={ __( "Decoration", "fooconvert" ) }
                    onDeselect={ () => setTextDecoration( undefined ) }
                >
                    <ToggleGroupControl
                        label={ __( "Decoration", "fooconvert" ) }
                        value={ textDecoration ?? defaults?.textDecoration }
                        onChange={ setTextDecoration }
                        isDeselectable={ true }
                        isBlock={ true }
                        __nextHasNoMarginBottom={ true }
                    >
                        <ToggleGroupControlOption
                            value="none"
                            label={ <Icon icon={ reset }/> }
                            aria-label={ __( 'None', 'fooconvert' ) }
                            showTooltip
                        />
                        <ToggleGroupControlOption
                            value="underline"
                            label={ <Icon icon={ formatUnderline }/> }
                            aria-label={ __( 'Underline', 'fooconvert' ) }
                            showTooltip
                        />
                        <ToggleGroupControlOption
                            value="line-through"
                            label={ <Icon icon={ formatStrikethrough }/> }
                            aria-label={ __( 'Strikethrough', 'fooconvert' ) }
                            showTooltip
                        />
                    </ToggleGroupControl>
                </ToolsPanelItem>
                <ToolsPanelItem
                    panelId={ panelId }
                    hasValue={ () => hasTextTransform }
                    label={ __( "Letter case", "fooconvert" ) }
                    onDeselect={ () => setTextTransform( undefined ) }
                >
                    <ToggleGroupControl
                        label={ __( "Letter case", "fooconvert" ) }
                        value={ textTransform ?? defaults?.textTransform }
                        onChange={ setTextTransform }
                        isDeselectable={ true }
                        __nextHasNoMarginBottom={ true }
                    >
                        <ToggleGroupControlOption
                            value="none"
                            label={ <Icon icon={ reset }/> }
                            aria-label={ __( 'None', 'fooconvert' ) }
                            showTooltip
                        />
                        <ToggleGroupControlOption
                            value="uppercase"
                            label={ <Icon icon={ formatUppercase }/> }
                            aria-label={ __( 'Uppercase', 'fooconvert' ) }
                            showTooltip
                        />
                        <ToggleGroupControlOption
                            value="lowercase"
                            label={ <Icon icon={ formatLowercase }/> }
                            aria-label={ __( 'Lowercase', 'fooconvert' ) }
                            showTooltip
                        />
                        <ToggleGroupControlOption
                            value="capitalize"
                            label={ <Icon icon={ formatCapitalize }/> }
                            aria-label={ __( 'Capitalize', 'fooconvert' ) }
                            showTooltip
                        />
                    </ToggleGroupControl>
                </ToolsPanelItem>
            </div>
            { hasItemRenderer && itemRenderer( panelId ) }
        </ToolsPanel>
    )
};

export default TypographyToolsPanel;