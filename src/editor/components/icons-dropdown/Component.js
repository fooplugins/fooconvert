import { Button, Dropdown } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import classnames from "classnames";

import { isFunction, isNumberWithin } from "@steveush/utils";

import "./Component.scss";
import { useInstanceId } from "@wordpress/compose";
import { getIconBySlug, ICON_SETS } from "../../icons";

const CLASS_NAME = 'fc--icons-dropdown';

/**
 * @typedef {Omit<import('@wordpress/components/build-types/dropdown/types').DropdownProps, "renderToggle", "renderContent">} IconsDropdownProps
 * @property {string|undefined} value
 * @property {(nextValue: string|undefined) => void} onChange
 * @property {( props: { isOpen: boolean, onToggle: () => void, onClose: () => void } ) => import('react').ReactNode} renderToggle
 * @property {boolean} [allowReset]
 * @property {()=>void} [onRequestReset]
 */

/**
 *
 * @param {IconsDropdownProps} props
 * @returns {JSX.Element}
 */
const IconsDropdown = ( props ) => {

    const {
        value,
        onChange,
        renderToggle,
        allowReset,
        onRequestReset,
        className,
        contentClassName,
        popoverProps = { placement: 'left-start', offset: 40 }
    } = props;

    const instanceId = useInstanceId( IconsDropdown, 'icons-dropdown-' );
    const current = getIconBySlug( value );
    const hasValue = typeof current === 'object';

    const setNextValue = nextValue => onChange( nextValue );

    const setFocus = listElement => {
        if ( listElement ) {
            const focusTarget = listElement.querySelector( `.${ CLASS_NAME }-content__icon-set__icon-button${ hasValue ? '.is-pressed' : '' }` );
            if ( focusTarget ) {
                focusTarget.focus( { preventScroll: true } );
                const labelHeight = focusTarget.closest( `.${ CLASS_NAME }-content__icon-set` )?.querySelector( `.${ CLASS_NAME }-content__icon-set__name` )?.clientHeight ?? 0;
                const focusTargetScrollY = focusTarget.offsetTop - focusTarget.clientHeight - labelHeight;
                const viewPortTop = listElement.scrollTop - labelHeight;
                const viewPortBottom = listElement.clientHeight - labelHeight;
                if ( !isNumberWithin( focusTargetScrollY, viewPortTop, viewPortTop + viewPortBottom ) ) {
                    listElement.scrollTo( 0, focusTargetScrollY );
                }
            }
        }
    };

    const renderIcons = () => {
        return (
            <div className={ `${ CLASS_NAME }-content__wrapper` }>
                <div className={ `${ CLASS_NAME }-content__icon-sets` } ref={ setFocus }>
                    { ICON_SETS.map( ( { name, label, icons }, i ) => {
                        const iconSetId = `${ instanceId }-${ i }`;
                        return (
                            <div key={ iconSetId } className={ `${ CLASS_NAME }-content__icon-set` }>
                                <label htmlFor={ iconSetId } className={ `${ CLASS_NAME }-content__icon-set__name` }>{ label }</label>
                                <ul id={ iconSetId } className={ `${ CLASS_NAME }-content__icon-set__icons-list` }>
                                    { icons.map( icon => {
                                        const slug = `${ name }__${ icon.name }`;
                                        return (
                                            <li key={ slug } className={ `${ CLASS_NAME }-content__icon-set__icons-list-item` }>
                                                <Button
                                                    className={ `${ CLASS_NAME }-content__icon-set__icon-button` }
                                                    variant="tertiary"
                                                    size="small"
                                                    onClick={ () => setNextValue( slug ) }
                                                    icon={ icon.value }
                                                    label={ icon.label }
                                                    isPressed={ slug === value }
                                                />
                                            </li>
                                        );
                                    } ) }
                                </ul>
                            </div>
                        );
                    } ) }
                </div>
            </div>
        );
    };

    const renderReset = () => {
        if ( allowReset && isFunction( onRequestReset ) ) {
            return (
                <div className={ `${ CLASS_NAME }-content__wrapper` }>
                    <div className={ `${ CLASS_NAME }-content__buttons` }>
                        <Button
                            className={ `${ CLASS_NAME }-content__reset-button` }
                            variant="tertiary"
                            onClick={ onRequestReset }
                            text={ __( 'Reset', 'fooconvert' ) }
                        />
                    </div>
                </div>
            );
        }
        return null;
    };

    return (
        <Dropdown
            className={ classnames( CLASS_NAME, className ) }
            contentClassName={ classnames( `${ CLASS_NAME }-content`, contentClassName ) }
            popoverProps={ popoverProps }
            focusOnMount={ false }
            renderToggle={ renderToggle }
            renderContent={ () => (
                <>
                    { renderIcons() }
                    { renderReset() }
                </>
            ) }
        />
    );
};

export default IconsDropdown;