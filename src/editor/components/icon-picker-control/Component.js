import { BaseControl, Button, useBaseControlProps } from "@wordpress/components";
import classnames from "classnames";

import { IconSetsDropdown } from "../icon-sets-dropdown";

import "./Component.scss";
import { Icon, chevronDownSmall } from "@wordpress/icons";
import { isValidElement } from "@wordpress/element";

const CLASS_NAME = 'fc--icon-picker-control';

const IconPickerControl = ( props ) => {
    const {
        value,
        onChange,
        iconSets,
        allowReset = true,
        placeholder = '',
        className,
        ...restProps
    } = props;

    const setNextValue = nextValue => onChange( nextValue );
    const onReset = () => setNextValue( undefined );

    const { baseControlProps, controlProps } = useBaseControlProps( {
        ...restProps,
        className: classnames( CLASS_NAME, className )
    } );

    // noinspection JSValidateTypes
    return (
        <BaseControl { ...baseControlProps }>
            <IconSetsDropdown
                className={ `${ CLASS_NAME }__dropdown` }
                value={ value }
                onChange={ setNextValue }
                iconSets={ iconSets }
                allowReset={ allowReset }
                onRequestReset={ onReset }
                renderToggle={ ( { isOpen, onToggle } ) => (
                    <Button
                        { ...controlProps }
                        className={ `${ CLASS_NAME }__toggle-button` }
                        onClick={ onToggle }
                        aria-expanded={ isOpen }
                    >
                        <span className={ `${ CLASS_NAME }__toggle-button__icon` }>
                            { isValidElement( value.svg ) && ( <Icon icon={ value.svg }/> ) }
                        </span>
                        <span className={ `${ CLASS_NAME }__toggle-button__text` }>{ value?.name ?? placeholder }</span>
                        <span className={ `${ CLASS_NAME }__toggle-button__caret` }>
                            <Icon icon={ chevronDownSmall }/>
                        </span>
                    </Button>
                ) }
            />
        </BaseControl>
    );
};

export default IconPickerControl;