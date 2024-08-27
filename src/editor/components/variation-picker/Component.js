import { useVariations } from "./hooks";
import { isString } from "@steveush/utils";
import classNames from "classnames";

import "./Component.scss";
import { Button } from "@wordpress/components";
import { grid, list } from "@wordpress/icons";
import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

const modes = [ 'grid', 'list' ];
const DEFAULT_MODE = 'grid';
const medias = [ 'icon', 'thumbnail' ];
const DEFAULT_MEDIA = 'icon';

const VariationPicker = ( {
                              clientId,
                              reset,
                              className,
                              label = '',
                              media = DEFAULT_MEDIA,
                              initialMode = DEFAULT_MODE
                          } ) => {

    media = medias.includes( media ) ? media : DEFAULT_MEDIA;
    initialMode = modes.includes( initialMode ) ? initialMode : DEFAULT_MODE;

    const [ mode, setMode ] = useState( initialMode );
    const { defaultVariation, blockVariations, setVariation } = useVariations( clientId, reset );

    const showLabel = isString( label, true );
    const onChange = ( nextVariation = defaultVariation ) => setVariation( nextVariation );

    const renderVariation = ( variation, i ) => {
        return (
            <button
                type="button"
                key={ i }
                className="fc-variation-picker__variation"
                onClick={ () => onChange( variation ) }
            >
                <div className="fc-variation-picker__variation__media">
                    { renderMedia( variation ) }
                </div>
                <div className="fc-variation-picker__variation__content">
                    <label className="fc-variation-picker__variation__title">{ variation.title }</label>
                    { isString( variation?.description, true ) && (
                        <p className="fc-variation-picker__variation__description">{ variation.description }</p>
                    ) }
                </div>
            </button>
        );
    };

    const renderMedia = variation => {
        if ( media === "thumbnail" ) {
            if ( isString( variation?.thumbnail, true ) ) {
                return ( <img src={ variation?.thumbnail } alt={ variation?.title }/> );
            }
            return null;
        }
        return variation?.icon ?? null;
    };

    const classes = classNames(
        'fc-variation-picker',
        `fc-variation-picker__mode-${ mode }`,
        `fc-variation-picker__media-${ media }`,
        className
    );

    const ModeButton = ( { value, icon, label } ) => {
        const isActive = value === mode;
        return (
            <Button
                size="compact"
                variant="tertiary"
                isPressed={ isActive }
                onClick={ () => setMode( value ) }
                icon={ icon }
                label={ label }
            />
        );
    };

    return (
        <div className={ classes }>
            <div className="fc-variation-picker__toolbar">
                { showLabel && ( <label className="fc-variation-picker__label">{ label }</label> ) }
                <ModeButton value="list" icon={ list } label={ __( "List View", "fooconvert" ) }/>
                <ModeButton value="grid" icon={ grid } label={ __( "Grid View", "fooconvert" ) }/>
            </div>
            <div className="fc-variation-picker__variations">
                { blockVariations.map( renderVariation ) }
            </div>
        </div>
    );
};

export default VariationPicker;