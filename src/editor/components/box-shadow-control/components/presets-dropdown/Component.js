import { Button, Dropdown } from "@wordpress/components";
import classnames from "classnames";
import { shadow } from "@wordpress/icons";
import { __ } from "@wordpress/i18n";
import { InspectorPopoverHeader } from "../../../experimental";

import "./Component.scss";

const CLASS_NAME = 'fc--box-shadow-presets-dropdown';

const PRESETS = [
    {
        value: 'unset',
        label: __( 'Unset', 'fooconvert' )
    },
    {
        value: '6px 6px 9px rgba(0, 0, 0, 0.2)',
        label: __( 'Natural', 'fooconvert' )
    },
    {
        value: '12px 12px 50px rgba(0, 0, 0, 0.4)',
        label: __( 'Deep', 'fooconvert' )
    },
    {
        value: '6px 6px 0px rgba(0, 0, 0, 0.2)',
        label: __( 'Sharp', 'fooconvert' )
    },
    {
        value: '6px 6px 0px -3px rgb(255, 255, 255), 6px 6px rgb(0, 0, 0)',
        label: __( 'Outlined', 'fooconvert' )
    },
    {
        value: '6px 6px 0px rgb(0, 0, 0)',
        label: __( 'Crisp', 'fooconvert' )
    }
];

export const isBoxShadowPreset = value => PRESETS.some( preset => preset.value === value );

const BoxShadowPresetsDropdown = ( props ) => {
    const {
        value,
        onChange,
        className,
        contentClassName,
        popoverProps = { placement: 'left-start', offset: 40 },
        ...restProps
    } = props;

    let currentPreset = PRESETS.find( preset => preset.value === value );
    if ( !currentPreset ) {
        currentPreset = PRESETS.find( preset => preset.value === 'unset' );
    }

    const setNextPreset = value => {
        if ( value === 'unset' ) {
            onChange( undefined );
        } else {
            const nextPreset = PRESETS.find( preset => preset.value === value );
            if ( nextPreset ) {
                onChange( nextPreset.value );
            }
        }
    };

    return (
        <Dropdown
            popoverProps={ popoverProps }
            className={ classnames( CLASS_NAME, className ) }
            contentClassName={ classnames( `${ CLASS_NAME }__content`, contentClassName ) }
            renderToggle={ ( { isOpen, onToggle } ) => (
                <Button
                    className={ classnames( `${ CLASS_NAME }__toggle`, { 'is-open': isOpen } ) }
                    icon={ shadow }
                    label={ __( 'Select a shadow', 'fooconvert' ) }
                    text={ currentPreset.label }
                    onClick={ onToggle }
                    aria-expanded={ isOpen }
                    __next40pxDefaultSize
                />
            ) }
            renderContent={ ( { onClose } ) => (
                <>
                    <InspectorPopoverHeader
                        title={ __( 'Box shadow', 'fooconvert' ) }
                        onClose={ onClose }
                    />
                    <div className={ `${ CLASS_NAME }__presets` }>
                        { PRESETS.map( ( preset, i ) => {
                            const isUnset = preset.value === 'unset';
                            return (
                                <Button
                                    className={ classnames( `${ CLASS_NAME }__preset`, { 'is-unset': isUnset, 'is-active': preset.value === currentPreset.value } ) }
                                    key={ i }
                                    label={ preset.label }
                                    onClick={ () => {
                                        setNextPreset( preset.value );
                                        onClose();
                                    } }
                                    style={ { boxShadow: isUnset ? 'none' : preset.value } }
                                />
                            );
                        } ) }
                    </div>
                    <div className={ `${ CLASS_NAME }__buttons` }>
                        <Button
                            className={ `${ CLASS_NAME }__reset` }
                            variant={ "tertiary" }
                            label={ __( 'Reset', 'fooconvert' ) }
                            text={ __( 'Reset', 'fooconvert' ) }
                            onClick={ () => {
                                setNextPreset( 'unset' );
                                onClose();
                            } }
                        />
                    </div>
                </>
            ) }
        />
    );
};

export default BoxShadowPresetsDropdown;