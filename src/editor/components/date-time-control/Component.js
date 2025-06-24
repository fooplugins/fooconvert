import { BaseControl, Button, DateTimePicker, Dropdown, useBaseControlProps } from "@wordpress/components";
import classnames from "classnames";
import { chevronDownSmall, calendar, Icon } from "@wordpress/icons";
import { __ } from "@wordpress/i18n";

import "./Component.scss";
import { isNumber, isString } from "@steveush/utils";

const CLASS_NAME = 'fc--date-time-control';

const DateTimeControl = props => {
    const {
        value,
        onChange,
        className,
        contentClassName,
        emptyText = __( 'Not set', 'fooconvert' ),
        popoverProps = { placement: 'left-start', offset: 40 },
        ...restProps
    } = props;

    const setNextValue = ( newValue ) => {
        let formatted = undefined;
        const timestamp = typeof newValue === 'string' ? Date.parse( newValue ) : NaN;
        if ( !isNaN( timestamp ) ) {
            formatted = new Date( timestamp ).toISOString();
        }
        return onChange( formatted );
    };

    const { baseControlProps, controlProps } = useBaseControlProps( {
        ...restProps,
        className: classnames( CLASS_NAME, className ),
        __nextHasNoMarginBottom: true
    } );

    const renderToggle = ( { onToggle, isOpen, selectedDate } ) => {
        let formatted = emptyText;
        const timestamp = isString( selectedDate ) ? Date.parse( selectedDate ) : ( isNumber( selectedDate ) ? selectedDate : NaN );
        if ( !isNaN( timestamp ) ) {
            formatted = new Date( timestamp ).toLocaleString();
        }
        return (
            <Button
                { ...controlProps }
                className={ `${ CLASS_NAME }__toggle-button` }
                onClick={ onToggle }
                aria-expanded={ isOpen }
            >
                    <span className={ `${ CLASS_NAME }__toggle-button__icon` }>
                        <Icon icon={ calendar }/>
                    </span>
                <span className={ `${ CLASS_NAME }__toggle-button__text` }>
                        { formatted }
                    </span>
                <span className={ `${ CLASS_NAME }__toggle-button__caret` }>
                        <Icon icon={ chevronDownSmall }/>
                    </span>
            </Button>
        );
    };

    return (
        <BaseControl { ...baseControlProps }>
            <Dropdown
                className={ classnames( CLASS_NAME, className ) }
                contentClassName={ classnames( `${ CLASS_NAME }-content`, contentClassName ) }
                popoverProps={ popoverProps }
                renderToggle={ props => renderToggle( {
                    ...props,
                    selectedDate: value
                } ) }
                renderContent={ () => (
                    <DateTimePicker
                        currentDate={ value }
                        onChange={ setNextValue }
                    />
                ) }
            />
        </BaseControl>
    );
};

export default DateTimeControl;