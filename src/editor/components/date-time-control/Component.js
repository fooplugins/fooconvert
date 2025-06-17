import { BaseControl, Button, DateTimePicker, Dropdown, useBaseControlProps } from "@wordpress/components";
import classnames from "classnames";
import { chevronDownSmall, calendar, Icon } from "@wordpress/icons";

import "./Component.scss";

const CLASS_NAME = 'fc--date-time-control';

const DateTimeControl = props => {
    const {
        value,
        onChange,
        renderToggle,
        className,
        contentClassName,
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

    let toggle = renderToggle;
    if ( typeof renderToggle !== 'function' ) {
        toggle = ( { onToggle, isOpen, selectedDate } ) => {
            let formatted = '';
            const timestamp = typeof selectedDate === 'string' ? Date.parse( selectedDate ) : NaN;
            if ( !isNaN( timestamp ) ) {
                formatted = new Date( timestamp ).toLocaleString();
            }
            return (
                <Button
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
    }

    const { baseControlProps, controlProps } = useBaseControlProps( {
        ...restProps,
        className: classnames( CLASS_NAME, className ),
        __nextHasNoMarginBottom: true
    } );

    return (
        <BaseControl { ...baseControlProps }>
            <Dropdown
                { ...controlProps }
                className={ classnames( CLASS_NAME, className ) }
                contentClassName={ classnames( `${ CLASS_NAME }-content`, contentClassName ) }
                popoverProps={ popoverProps }
                renderToggle={ props => toggle( {
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