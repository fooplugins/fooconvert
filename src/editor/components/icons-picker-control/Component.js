import { BaseControl, Button, useBaseControlProps } from "@wordpress/components";
import classnames from "classnames";

import { IconsDropdown } from "../icons-dropdown";

import "./Component.scss";
import { Icon, chevronDownSmall } from "@wordpress/icons";
import { getIconBySlug, SafeIcon } from "../../icons";

const CLASS_NAME = 'fc--icon-picker-control';

const IconsPickerControl = ( props ) => {
    const {
        value,
        onChange,
        allowReset = true,
        placeholder = '',
        className,
        ...restProps
    } = props;

    const current = getIconBySlug( value );

    const setNextValue = nextValue => onChange( nextValue );
    const onReset = () => setNextValue( undefined );

    const { baseControlProps, controlProps } = useBaseControlProps( {
        ...restProps,
        className: classnames( CLASS_NAME, className )
    } );

    // noinspection JSValidateTypes
    return (
        <BaseControl { ...baseControlProps }>
            <IconsDropdown
                className={ `${ CLASS_NAME }__dropdown` }
                value={ value }
                onChange={ setNextValue }
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
                            <SafeIcon icon={ current?.value } />
                        </span>
                        <span className={ `${ CLASS_NAME }__toggle-button__text` }>{ current?.label ?? placeholder }</span>
                        <span className={ `${ CLASS_NAME }__toggle-button__caret` }>
                            <Icon icon={ chevronDownSmall }/>
                        </span>
                    </Button>
                ) }
            />
        </BaseControl>
    );
};

export default IconsPickerControl;