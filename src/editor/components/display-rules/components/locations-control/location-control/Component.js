// external
import { Button, TextareaControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { trash } from "@wordpress/icons";
import classnames from "classnames";

// internal
import "./Component.scss";

import {
    GroupedSelectControl,
    EntityRecordControl, TextContentControl, getGroupedSelectOption
} from "../../../../../components";
import isDisplayRulesLocation from "../../../utils/isDisplayRulesLocation";
import { hasKeys, isPlainObject, isString } from "@steveush/utils";

/**
 * Stores the root class for the component and is used to generate the class names for its children.
 * @type {string}
 * @const
 */
const rootClass = 'fc--display-rules__location-control';

/**
 * @typedef DisplayRulesLocationControlProps
 * @property {GroupedSelectOptions} options - The options to display within the dropdown menu.
 * @property {DisplayRulesLocation} value - The rule to display.
 * @property {( value: DisplayRulesLocation, previousValue: DisplayRulesLocation ) => void} onChange - Called whenever a change is made to the rule.
 * @property {( value: DisplayRulesLocation ) => void} onRequestRemove - Called when the rule should be removed.
 * @property {string} [className] - Optional. A space separated string of CSS classes to apply to the component.
 * @property {boolean} [disabled] - Optional. Whether the component is disabled.
 */

/**
 * Display a dropdown of available locations and any additional controls to capture data for the current location.
 *
 * @component
 * @param {DisplayRulesLocationControlProps} props - The props for the display rules location control.
 * @returns {JSX.Element} The rendered visibility rule component.
 */
const DisplayRulesLocationControl = ( {
                                          options,
                                          value,
                                          className,
                                          onChange,
                                          onRequestRemove,
                                          removeItemLabel,
                                          disabled = false,
                                      } ) => {

    const { type = '', data = [] } = isDisplayRulesLocation( value ) ? value : {};
    const option = getGroupedSelectOption( options, type );
    const hasDataControls = hasKeys( option?.data, {
        name: isString,
        kind: isString,
        placeholder: isString
    } );

    const changed = ( type, data ) => {
        onChange( { type, data }, value );
    };
    const remove = () => {
        onRequestRemove( value );
    };

    const renderDataControls = () => {
        if ( hasDataControls ) {
            const {
                kind = '',
                name = '',
                placeholder = __( 'Type to choose...', 'fooconvert' )
            } = option.data;

            if ( kind !== '' && name !== '' ) {
                return (
                    <>
                        <div className={ `${ rootClass }__visualizer` }></div>
                        <EntityRecordControl
                            className={ `${ rootClass }__entity-record-control` }
                            kind={ kind }
                            name={ name }
                            tokens={ data }
                            onChange={ tokens => changed( type, tokens ) }
                            placeholder={ placeholder }
                            __next40pxDefaultSize
                        />
                    </>
                );
            }
        }
        return null;
    };

    return (
        <fieldset className={ classnames( rootClass, className, { 'has-data-controls': hasDataControls } ) } disabled={ disabled }>
            <div className={ `${ rootClass }__grouped-select-control` }>
                <GroupedSelectControl
                    label={ __( 'Select type', 'fooconvert' ) }
                    value={ type }
                    options={ options }
                    onChange={ value => changed( value, [] ) }
                    hideLabelFromVision
                    __nextHasNoMarginBottom
                />
            </div>
            { renderDataControls() }
            <Button
                variant="secondary"
                icon={ trash }
                title={ removeItemLabel }
                className={ `${ rootClass }__remove-button` }
                onClick={ remove }
                isDestructive
            />
        </fieldset>
    );
};

export default DisplayRulesLocationControl;