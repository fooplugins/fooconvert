import { useVariations } from "./hooks";
import { isBoolean, isPlainObject, isString, isUndefined } from "@steveush/utils";
import classNames from "classnames";

import "./Component.scss";
import { Button, Modal } from "@wordpress/components";
import { grid, list } from "@wordpress/icons";
import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { useDispatch } from "@wordpress/data";
import { store as editorStore } from "@wordpress/editor";

import useDebounce from "../../hooks/useDebounce";
import SearchInput from "./components/search/Component";

const modes = [ 'grid', 'list' ];
const DEFAULT_MODE = 'grid';
const medias = [ 'icon', 'thumbnail' ];
const DEFAULT_MEDIA = 'icon';

const CLASS_NAME = 'fc--variation-picker';

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
    const [ proModal, setProModal ] = useState( { open: false, upsell: undefined } );

    const openProModal = ( variation ) => {
        if ( isPlainObject( variation?.upsell ) ) {
            setProModal( { open: true, upsell: variation?.upsell } );
        } else {
            console.error( 'Pro variation is missing the "upsell" object.', variation );
        }
    };

    const closeProModal = () => {
        setProModal( { open: false } );
    };

    const { editPost } = useDispatch( editorStore );
    const { defaultVariation, blockVariations, setVariation } = useVariations( clientId, reset );

    const showLabel = isString( label, true );
    const onChange = ( nextVariation = defaultVariation ) => {
        // noinspection JSIgnoredPromiseFromCall
        setVariation( nextVariation );
        // set the post title to the current variation title by default
        if ( isString( nextVariation?.title ) ) {
            editPost( { title: nextVariation.title } );
        }
    };
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
        const isPro = variation?.pro ?? false;
        const onClick = () => {
            if ( isPro ) {
                openProModal( variation );
            } else {
                onChange( variation );
            }
        };

        return (
            <button
                type="button"
                key={ i }
                className={ classNames( "fc-variation-picker__variation", { "fc-variation-picker__pro-only": isPro } ) }
                onClick={ onClick }
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

    const ProModalButton = ({ value, ...props }) => {
        const { text = '', href = '' } = value ?? {};
        if ( isString( text, true ) && isString( href, true ) ) {
            return (
                <Button { ...props } href={ href }>
                    { text }
                </Button>
            );
        }
        return null;
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
            { proModal.open && (
                <Modal
                    className={ `${ CLASS_NAME }__pro-modal` }
                    title={ proModal.upsell.title }
                    onRequestClose={ closeProModal }>
                    <div className={ `${ CLASS_NAME }__pro-modal__body` }>
                        <div className={ `${ CLASS_NAME }__pro-modal__upsell-image` }>
                            <img src={ proModal.upsell.image } alt={ proModal.upsell.title } />
                        </div>
                        <div className={ `${ CLASS_NAME }__pro-modal__upsell-content` } dangerouslySetInnerHTML={{ __html: proModal.upsell.content }}></div>
                    </div>
                    <div className={ `${ CLASS_NAME }__pro-modal__footer` }>
                        <ProModalButton variant="primary" value={ proModal.upsell.primary }></ProModalButton>
                        <ProModalButton variant="secondary" value={ proModal.upsell.secondary }></ProModalButton>
                        <Button variant="secondary" isDestructive onClick={ closeProModal }>
                            { __( 'Close', 'fooconvert' ) }
                        </Button>
                    </div>
                </Modal>
            ) }
        </div>
    );
};

export default VariationPicker;