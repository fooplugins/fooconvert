import { BaseControl, RangeControl, SelectControl, TextControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { cleanObject, isNumberWithin, isString } from "@steveush/utils";

import "./Component.scss";

/**
 * @typedef {"immediate"|"anchor"|"element"|"exit-intent"|"scroll"|"timer"|"visible"} TriggerType
 */

/**
 * @typedef {{value: TriggerType, label: import('react').ReactNode, help?: import('react').ReactNode, dataLabel?: import('react').ReactNode, dataHelp?: import('react').ReactNode, once?: boolean}} Trigger
 */

/**
 *
 * @type {Trigger[]}
 */
const TRIGGERS = [ {
    value: '',
    label: __( 'None', 'fooconvert' )
}, {
    value: 'immediate',
    label: __( 'On page load', 'fooconvert' ),
    help: __( 'Open immediately on page load.', 'fooconvert' ),
    once: true
}, {
    value: 'anchor',
    label: __( 'On anchor click', 'fooconvert' ),
    help: __( 'Open when an anchor is clicked.', 'fooconvert' ),
    dataLabel: __( 'Anchor', 'fooconvert' ),
    dataHelp: __( 'Add an anchor to a block and then insert the same value here.', 'fooconvert' )
}, {
    value: 'visible',
    label: __( 'On anchor visible', 'fooconvert' ),
    help: __( 'Open when an anchor becomes visible within the window.', 'fooconvert' ),
    dataLabel: __( 'Anchor', 'fooconvert' ),
    dataHelp: __( 'Add an anchor to a block and then insert the same value here.', 'fooconvert' ),
    once: true
}, {
    value: 'element',
    label: __( 'On element click', 'fooconvert' ),
    help: __( 'Open when an element is clicked.', 'fooconvert' ),
    dataLabel: __( 'Selector', 'fooconvert' ),
    dataHelp: __( 'A CSS selector that specifies the element(s) to target.', 'fooconvert' )
}, {
    value: 'exit-intent',
    label: __( 'On exit intent', 'fooconvert' ),
    help: __( 'Open when the mouse exits the top of the window.', 'fooconvert' ),
    dataLabel: __( 'Wait time in seconds', 'fooconvert' ),
    dataHelp: __( 'Only detect after the a user has been on the page for the specified amount of time.', 'fooconvert' ),
    once: true
}, {
    value: 'scroll',
    label: __( 'On page scroll', 'fooconvert' ),
    help: __( 'Open after the page has been scrolled.', 'fooconvert' ),
    dataLabel: __( 'Scroll percent', 'fooconvert' ),
    dataHelp: __( 'The percentage of the page to scroll before opening.', 'fooconvert' ),
    once: true
}, {
    value: 'timer',
    label: __( 'On timer elapsed', 'fooconvert' ),
    help: __( 'Open after a specified amount of time.', 'fooconvert' ),
    dataLabel: __( 'Wait time in seconds', 'fooconvert' ),
    dataHelp: __( 'The amount of time to wait before opening.', 'fooconvert' ),
    once: true
} ];

const OpenTriggerComponent = ( props ) => {
    const {
        value,
        onChange,
        locked = false,
        allowEmpty = false,
        label = __( 'Open Trigger', 'fooconvert' ),
        hideLabelFromVision
    } = props;

    const { type, data, once } = value ?? {};

    let options = [ ...TRIGGERS ];
    if ( !allowEmpty ) {
        options = options.slice( 1 );
    }

    const selected = options.find( o => o.value === type ) ?? options.at( 0 );

    const setTrigger = ( type, data, once ) => {
        once = Boolean( once );
        switch ( type ) {
            case "anchor":
            case "element":
            case "visible":
                data = isString( data ) ? data : "";
                break;
            case "scroll":
                data = isNumberWithin( data, 1, 100 ) ? data : 20;
                break;
            case "exit-intent":
            case "timeout":
                data = isNumberWithin( data, 0, 100 ) ? data : 15;
                break;
        }
        onChange( cleanObject( { type, data, once } ) );
    };

    const renderType = () => {
        if ( locked ) {
            return (
                <BaseControl
                    label={ label }
                    help={ selected?.help }
                    hideLabelFromVision={ hideLabelFromVision }
                    __nextHasNoMarginBottom
                >
                    <p>{ selected.label }</p>
                </BaseControl>
            );
        }
        return (
            <SelectControl
                label={ label }
                hideLabelFromVision={ hideLabelFromVision }
                help={ selected?.help }
                value={ selected.value }
                options={ options.map( opt => ({ label: opt.label, value: opt.value }) ) }
                onChange={ nextValue => setTrigger( nextValue ) }
                __nextHasNoMarginBottom
            />
        );
    };

    const renderData = () => {
        switch ( selected.value ) {
            case "anchor":
            case "element":
            case "visible":
                return (
                    <TextControl
                        label={ selected.dataLabel }
                        help={ selected?.dataHelp }
                        value={ data ?? "" }
                        onChange={ value => setTrigger( selected.value, value, once ) }
                        __nextHasNoMarginBottom
                    />
                );
            case "scroll":
            case "timer":
            case "exit-intent":
                return (
                    <RangeControl
                        label={ selected.dataLabel }
                        help={ selected?.dataHelp }
                        value={ data }
                        initialPosition={ selected.value === 'scroll' ? 20 : 15 }
                        min={ selected.value === 'scroll' ? 1 : 0 }
                        max={ 100 }
                        onChange={ value => setTrigger( selected.value, value, once ) }
                        __nextHasNoMarginBottom
                    />
                );
        }
        return null;
    };

    const renderOnce = () => {
        if ( selected.once ) {
            return (
                <ToggleControl
                    label={ __( 'Only show once', 'fooconvert' ) }
                    help={ __( 'Once closed will not be shown to a user again.', 'fooconvert' ) }
                    checked={ once ?? false  }
                    onChange={ value => setTrigger( selected.value, data, value ) }
                />
            )
        }
    };

    return (
        <div className="fc--open-trigger-component">
            { renderType() }
            { renderData() }
            { renderOnce() }
        </div>
    );
};

export default OpenTriggerComponent;