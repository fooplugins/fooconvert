// external
import { Button, TextareaControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { trash } from "@wordpress/icons";
import classnames from "classnames";

// internal
import "./Component.scss";

import {
    GroupedSelectControl,
    EntityRecordControl, TextContentControl
} from "../../../../../components";
import { isDisplayRulesLocation } from "../utils";

/**
 * Stores the root class for the component and is used to generate the class names for its children.
 * @type {string}
 * @const
 */
const rootClass = 'fc-display-rules-location-control';

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
                                          disabled = false,
                                      } ) => {

    const { type = '', data = [] } = isDisplayRulesLocation( value ) ? value : {};

    const changed = ( type, data ) => {
        onChange( { type, data }, value );
    };
    const remove = () => {
        onRequestRemove( value );
    };

    const renderDataControls = () => {
        switch ( type ) {
            case 'specific:page':
                return (
                    <EntityRecordControl
                        kind="postType"
                        name="page"
                        tokens={ data }
                        onChange={ tokens => changed( type, tokens ) }
                        placeholder={ __( 'Type to choose page...', 'fooconvert' ) }
                    />
                );
            case 'specific:post':
                return (
                    <EntityRecordControl
                        kind="postType"
                        name="post"
                        tokens={ data }
                        onChange={ tokens => changed( type, tokens ) }
                        placeholder={ __( 'Type to choose post...', 'fooconvert' ) }
                        hideLabelFromVision
                    />
                );
            case 'specific:category':
                return (
                    <EntityRecordControl
                        kind="taxonomy"
                        name="category"
                        tokens={ data }
                        onChange={ tokens => changed( type, tokens ) }
                        placeholder={ __( 'Type to choose category...', 'fooconvert' ) }
                    />
                );
            case 'specific:tag':
                return (
                    <EntityRecordControl
                        kind="taxonomy"
                        name="tag"
                        tokens={ data }
                        onChange={ tokens => changed( type, tokens ) }
                        placeholder={ __( 'Type to choose tag...', 'fooconvert' ) }
                    />
                );
        }
    };

    return (
        <fieldset className={ classnames( rootClass, className ) } disabled={ disabled }>
            <GroupedSelectControl
                className={ `${ rootClass }__grouped-select-control` }
                label={ __( 'Select type', 'fooconvert' ) }
                value={ type }
                options={ options }
                onChange={ value => changed( value, [] ) }
                hideLabelFromVision
                __nextHasNoMarginBottom
            />
            <div className={ `${ rootClass }__data-controls` }>
                { renderDataControls() }
            </div>
            <Button
                variant="tertiary"
                icon={ trash }
                className={ `${ rootClass }__remove-button` }
                onClick={ remove }
                isDestructive
            />
        </fieldset>
    );
};

export default DisplayRulesLocationControl;