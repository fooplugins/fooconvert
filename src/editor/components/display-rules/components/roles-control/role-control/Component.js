import { Button } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { trash } from "@wordpress/icons";
import { isString } from "@steveush/utils";

import {
    GroupedSelectControl
} from "../../../../../components";

import "./Component.scss";
import classnames from "classnames";

/**
 * @typedef DisplayRulesRoleControlProps
 * @param {GroupedSelectOptions} options - The options to display in the select control.
 * @param {string} value - The current role.
 * @param {( value: string, previousValue: string ) => void} onChange - Callback for when an item has changed.
 * @param {( value: string ) => void} onRequestRemove - Callback for when an item should be removed.
 * @param {string} [className] - Optional. The CSS class name to append to the component.
 */

/**
 * Stores the root class for the component and is used to generate the class names for its children.
 * @type {string}
 * @const
 */
const rootClass = 'fc--display-rules__role-control';

const DisplayRulesRoleControl = ( {
                                      options,
                                      value,
                                      className,
                                      onChange,
                                      onRequestRemove,
                                      removeItemLabel,
                                      disabled = false,
                                  } ) => {

    value = isString( value ) ? value : '';

    const changed = role => onChange( role, value );
    const remove = () => onRequestRemove( value );

    return (
        <fieldset className={ classnames( rootClass, className ) } disabled={ disabled }>
            <div className={ `${ rootClass }__grouped-select-control` }>
                <GroupedSelectControl
                    label={ __( 'Select role', 'fooconvert' ) }
                    value={ value }
                    options={ options }
                    onChange={ value => changed( value ) }
                    hideLabelFromVision
                    __nextHasNoMarginBottom
                />
            </div>
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

export default DisplayRulesRoleControl;