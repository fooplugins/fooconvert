import { Button, Dropdown } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import classnames from "classnames";

import { isFunction, isNumberWithin } from "@steveush/utils";

import "./Component.scss";
import { useInstanceId } from "@wordpress/compose";
import { isIconSetIcon } from "../../hooks";

const CLASS_NAME = 'fc--icon-sets-dropdown';

/**
 * @typedef {Omit<import('@wordpress/components/build-types/dropdown/types').DropdownProps, "renderToggle", "renderContent">} IconSetsDropdownProps
 * @property {IconSetIcon|undefined} value
 * @property {(nextValue: IconSetIcon|undefined) => void} onChange
 * @property {( props: { isOpen: boolean, onToggle: () => void, onClose: () => void, selectedIcon?: IconSetIcon } ) => import('react').ReactNode} renderToggle
 * @property {IconSet[]} iconSets
 * @property {boolean} [allowReset]
 * @property {()=>void} [onRequestReset]
 */

/**
 *
 * @param {IconSetsDropdownProps} props
 * @returns {JSX.Element}
 */
const IconSetsDropdown = ( props ) => {

    const {
        value,
        onChange,
        renderToggle,
        iconSets,
        allowReset,
        onRequestReset,
        className,
        contentClassName,
        popoverProps = { placement: 'left-start', offset: 40 }
    } = props;

    const instanceId = useInstanceId( IconSetsDropdown, 'icon-sets-dropdown-' );
    const hasValue = isIconSetIcon( value );

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
                    { iconSets.map( ( { name, icons }, i ) => {
                        const iconSetId = `${ instanceId }-${ i }`;
                        return (
                            <div key={ iconSetId } className={ `${ CLASS_NAME }-content__icon-set` }>
                                <label htmlFor={ iconSetId } className={ `${ CLASS_NAME }-content__icon-set__name` }>{ name }</label>
                                <ul id={ iconSetId } className={ `${ CLASS_NAME }-content__icon-set__icons-list` }>
                                    { icons.map( icon => {
                                        return (
                                            <li key={ icon.slug } className={ `${ CLASS_NAME }-content__icon-set__icons-list-item` }>
                                                <Button
                                                    className={ `${ CLASS_NAME }-content__icon-set__icon-button` }
                                                    variant="tertiary"
                                                    size="small"
                                                    onClick={ () => setNextValue( icon ) }
                                                    icon={ icon.svg }
                                                    label={ icon.name }
                                                    isPressed={ icon.slug === value?.slug }
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
            renderToggle={ props => renderToggle( {
                ...props,
                selectedIcon: value
            } ) }
            renderContent={ () => (
                <>
                    { renderIcons() }
                    { renderReset() }
                </>
            ) }
        />
    );
};

export default IconSetsDropdown;