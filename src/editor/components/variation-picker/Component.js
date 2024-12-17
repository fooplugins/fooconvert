import { useVariations } from "./hooks";
import { isBoolean, isString, isUndefined } from "@steveush/utils";
import classNames from "classnames";

import "./Component.scss";
import { Button } from "@wordpress/components";
import { grid, list } from "@wordpress/icons";
import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import useDebounce from "../../hooks/useDebounce";
import SearchInput from "./components/search/Component";

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
                              initialMode = DEFAULT_MODE,
                              showSearch,
                              minSearchChars = 2
                          } ) => {

    media = medias.includes( media ) ? media : DEFAULT_MEDIA;
    initialMode = modes.includes( initialMode ) ? initialMode : DEFAULT_MODE;

    const [ mode, setMode ] = useState( initialMode );
    const [ search, setSearch ] = useState( '' );

    const { defaultVariation, blockVariations, setVariation } = useVariations( clientId, reset );

    const showLabel = isString( label, true );
    const onChange = ( nextVariation = defaultVariation ) => setVariation( nextVariation );
    const searchChanged = value => {
        value = isString( value ) && value.length >= minSearchChars ? value : '';
        setSearch( value );
    };

    const debouncedSearch = useDebounce( searchChanged, 300 );

    let variations = blockVariations.slice();
    if ( search !== '' ) {
        variations = variations.filter( variation => variation.title.toLocaleLowerCase().includes( search.toLocaleLowerCase() ) );
    }
    let shouldShowSearch;
    if ( isBoolean( showSearch ) ) {
        shouldShowSearch = showSearch;
    } else {
        shouldShowSearch = variations.length > 8;
    }

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
                { shouldShowSearch && ( <SearchInput value={ search } onChange={ debouncedSearch }/> ) }
                <ModeButton value="list" icon={ list } label={ __( "List View", "fooconvert" ) }/>
                <ModeButton value="grid" icon={ grid } label={ __( "Grid View", "fooconvert" ) }/>
            </div>
            <div className="fc-variation-picker__variations">
                { variations.map( renderVariation ) }
            </div>
        </div>
    );
};

export default VariationPicker;