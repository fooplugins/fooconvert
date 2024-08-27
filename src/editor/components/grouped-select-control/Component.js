import { SelectControl } from "@wordpress/components";
import { isArray, isFunction, isString } from "@steveush/utils";
import { __ } from "@wordpress/i18n";

import { isGroupedSelectOption, isGroupedSelectOptGroup, isGroupedSelectOptions } from "./utils";
import classnames from "classnames";

import "./Component.scss";

const renderOptionOrOptGroup = ( optionOrOptGroup, key ) => {
    if ( isGroupedSelectOption( optionOrOptGroup ) ) {
        const { label, value } = optionOrOptGroup;
        return ( <option key={ key } value={ value }>{ label }</option> );
    }
    if ( isGroupedSelectOptGroup( optionOrOptGroup ) ) {
        const { label, options } = optionOrOptGroup;
        return (
            <optgroup key={ key } label={ label }>
                { options.map( ( option, index ) => renderOptionOrOptGroup( option, `${ key }-${ index }` ) ) }
            </optgroup>
        );
    }
};

const renderPlaceholder = placeholder => {
    if ( isString( placeholder, true ) ) {
        return ( <option key={ -1 } value="">{ placeholder }</option> );
    }
};

const GroupedSelectControl = ( {
                                       value,
                                       onChange,
                                       options = [],
                                       placeholder = __( 'Select...', 'fooconvert' ),
    className,
                                       ...props
                                   } ) => {

    if ( !isGroupedSelectOptions( options ) ) {
        options = [];
    }

    return (
        <SelectControl
            className={ classnames( 'fc-grouped-select-control', className ) }
            value={ value }
            onChange={ onChange }
            { ...props }
        >
            { renderPlaceholder( placeholder ) }
            { options.map( ( optionOrOptGroup, index ) => renderOptionOrOptGroup( optionOrOptGroup, String( index ) ) ) }
        </SelectControl>
    );
};

export default GroupedSelectControl;