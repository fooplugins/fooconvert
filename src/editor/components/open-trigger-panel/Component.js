import { BaseControl, PanelBody, PanelRow, RangeControl, SelectControl, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { cleanObject, isNumberWithin, isString } from "@steveush/utils";

/**
 * @typedef {"immediate"|"anchor"|"exit-intent"|"scroll"|"timer"|"visible"} TriggerType
 */

/**
 * @typedef {{value: TriggerType, label: import('react').ReactNode, help: import('react').ReactNode, dataLabel?: import('react').ReactNode, dataHelp?: import('react').ReactNode}} Trigger
 */

/**
 *
 * @type {Trigger[]}
 */
const TRIGGERS = [ {
    value: 'immediate',
    label: __( 'On page load', 'fooconvert' ),
    help: __( 'Open immediately on page load.', 'fooconvert' )
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
    dataHelp: __( 'Add an anchor to a block and then insert the same value here.', 'fooconvert' )
}, {
    value: 'exit-intent',
    label: __( 'On exit intent', 'fooconvert' ),
    help: __( 'Open when the mouse exits the top of the window.', 'fooconvert' ),
    dataLabel: __( 'Wait time in seconds', 'fooconvert' ),
    dataHelp: __( 'Only detect after the a user has been on the page for the specified amount of time.', 'fooconvert' )
}, {
    value: 'scroll',
    label: __( 'On page scroll', 'fooconvert' ),
    help: __( 'Open after the page has been scrolled.', 'fooconvert' ),
    dataLabel: __( 'Scroll percent', 'fooconvert' ),
    dataHelp: __( 'The percentage of the page to scroll before opening.', 'fooconvert' )
}, {
    value: 'timer',
    label: __( 'On timer elapsed', 'fooconvert' ),
    help: __( 'Open after a specified amount of time.', 'fooconvert' ),
    dataLabel: __( 'Wait time in seconds', 'fooconvert' ),
    dataHelp: __( 'The amount of time to wait before opening.', 'fooconvert' )
} ];

const OpenTriggerPanel = ( props ) => {
    const {
        value,
        onChange,
        locked = false,
        ...restProps
    } = props;

    const { type, data } = value ?? {};

    const selected = TRIGGERS.find( o => o.value === type ) ?? TRIGGERS.at( 0 );

    const setTrigger = ( type, data ) => {
        switch ( type ) {
            case "anchor":
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
        onChange( cleanObject( { type, data } ) );
    };

    const renderType = () => {
        if ( locked ) {
            return (
                <PanelRow>
                    <BaseControl
                        label={ __( 'Type', 'fooconvert' ) }
                        help={ selected.help }
                        hideLabelFromVision={ true }
                        __nextHasNoMarginBottom
                    >
                        <p>{ selected.label }</p>
                    </BaseControl>
                </PanelRow>
            );
        }
        return (
            <PanelRow>
                <SelectControl
                    label={ __( 'Type', 'fooconvert' ) }
                    hideLabelFromVision={ true }
                    help={ selected.help }
                    value={ selected.value }
                    options={ TRIGGERS }
                    onChange={ nextValue => setTrigger( nextValue ) }
                />
            </PanelRow>
        );
    };

    const renderData = () => {
        switch ( selected.value ) {
            case "anchor":
            case "visible":
                return (
                    <PanelRow>
                        <TextControl
                            label={ selected.dataLabel }
                            help={ selected.dataHelp }
                            value={ data ?? "" }
                            onChange={ value => setTrigger( selected.value, value ) }
                        />
                    </PanelRow>
                );
            case "scroll":
            case "timer":
            case "exit-intent":
                return (
                    <PanelRow>
                        <RangeControl
                            label={ selected.dataLabel }
                            help={ selected.dataHelp }
                            value={ data }
                            initialPosition={ selected.value === 'scroll' ? 20 : 15 }
                            min={ selected.value === 'scroll' ? 1 : 0 }
                            max={ 100 }
                            onChange={ value => setTrigger( selected.value, value ) }
                        />
                    </PanelRow>
                );
        }
        return null;
    };

    return (
        <PanelBody title={ __( 'Open Trigger', 'fooconvert' ) }>
            { renderType() }
            { renderData() }
        </PanelBody>
    );
};

export default OpenTriggerPanel;