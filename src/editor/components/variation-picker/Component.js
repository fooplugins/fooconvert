import { isPlainObject, isString } from "@steveush/utils";
import classNames from "classnames";

import "./Component.scss";
import { Button, Modal, SearchControl } from "@wordpress/components";
import { createBlock, createBlocksFromInnerBlocksTemplate, store as blocksStore } from "@wordpress/blocks";
import { useDispatch, useSelect } from "@wordpress/data";
import { store as editorStore } from "@wordpress/editor";
import { useEffect, useMemo, useRef, useState } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";

const FILTER_ALL = "all";
const CLASS_NAME = "fc--variation-picker";
const POPUP_TYPE_META_KEY = "_fooconvert_popup_type";

const getFirstString = ( ...values ) => {
    for ( const value of values ) {
        if ( isString( value, true ) ) {
            return value;
        }
    }
    return "";
};

const getPickerData = variation => {
    return isPlainObject( variation?.picker ) ? variation.picker : {};
};

const getPickerCategory = variation => {
    const picker = getPickerData( variation );
    const category = isPlainObject( picker?.category ) ? picker.category : {};

    return {
        value: getFirstString( category?.value ).toLocaleLowerCase(),
        label: getFirstString( category?.label ),
    };
};

const getPreviewUrl = variation => {
    const picker = getPickerData( variation );
    const preview = getFirstString( picker?.preview, variation?.preview, variation?.screenshot );
    if ( preview ) {
        return preview;
    }

    const thumbnail = getFirstString( variation?.thumbnail );
    if ( !thumbnail ) {
        return "";
    }

    if ( thumbnail.includes( "/media/templates/" ) ) {
        return thumbnail.replace( "/media/templates/", "/media/templates/fullsize/" );
    }

    return thumbnail;
};

const getThumbnailUrl = variation => {
    return getFirstString( variation?.thumbnail );
};

const getPickerTags = variation => {
    const picker = getPickerData( variation );
    if ( !Array.isArray( picker?.tags ) ) {
        return [];
    }

    return picker.tags
        .filter( tag => isPlainObject( tag ) )
        .map( tag => ( {
            value: getFirstString( tag?.value ).toLocaleLowerCase(),
            label: getFirstString( tag?.label ),
        } ) )
        .filter( tag => isString( tag.value, true ) && isString( tag.label, true ) );
};

const getPickerAvailability = variation => {
    const picker = getPickerData( variation );
    return getFirstString( picker?.availability ).toLocaleLowerCase();
};

const getMonogram = title => {
    const letters = title
        .split( /\s+/ )
        .filter( Boolean )
        .slice( 0, 2 )
        .map( part => part.charAt( 0 ).toUpperCase() )
        .join( "" );

    return letters || title.slice( 0, 2 ).toUpperCase();
};

const getPreviewPosition = ( container, element ) => {
    const containerWindow = container?.ownerDocument?.defaultView;
    const elementWindow = element?.ownerDocument?.defaultView;

    if (
        !container ||
        !element ||
        typeof container.getBoundingClientRect !== "function" ||
        typeof element.getBoundingClientRect !== "function" ||
        container.nodeType !== 1 ||
        element.nodeType !== 1 ||
        !containerWindow ||
        !elementWindow
    ) {
        return null;
    }

    const containerRect = container.getBoundingClientRect();
    const rect = element.getBoundingClientRect();
    const panelWidth = 320;
    const gap = 16;
    const margin = 24;
    const viewportWidth = elementWindow.innerWidth || containerWindow.innerWidth || 0;
    const fitsRight = rect.right + gap + panelWidth <= viewportWidth - margin;
    const left = fitsRight
        ? rect.right - containerRect.left + gap
        : Math.max( 12, rect.left - containerRect.left - panelWidth - gap );
    const top = Math.max( 12, Math.min( rect.top - containerRect.top, containerRect.height - 420 ) );

    return {
        left: `${ Math.round( left ) }px`,
        top: `${ Math.round( top ) }px`,
    };
};

const SidebarSection = ( { title, children } ) => {
    return (
        <section className="fc-variation-picker__sidebar-section">
            <h3 className="fc-variation-picker__sidebar-title">{ title }</h3>
            { children }
        </section>
    );
};

const FilterButton = ( { label, isActive, onClick, count } ) => {
    return (
        <button
            type="button"
            className={ classNames( "fc-variation-picker__filter-button", {
                "is-active": isActive,
            } ) }
            aria-pressed={ isActive }
            onClick={ onClick }
        >
            <span>{ label }</span>
            { Number.isInteger( count ) && (
                <span className="fc-variation-picker__filter-count">{ count }</span>
            ) }
        </button>
    );
};

const VariationPicker = ( {
    clientId,
    reset,
    className,
    label = "",
    currentPopupType = "",
    popupTypeOptions = [],
    onSelectTemplate,
} ) => {
    const rootRef = useRef( null );
    const [ search, setSearch ] = useState( "" );
    const [ selectedPopupType, setSelectedPopupType ] = useState( FILTER_ALL );
    const [ selectedCategory, setSelectedCategory ] = useState( FILTER_ALL );
    const [ selectedTag, setSelectedTag ] = useState( FILTER_ALL );
    const [ availability, setAvailability ] = useState( FILTER_ALL );
    const [ previewState, setPreviewState ] = useState( null );
    const [ proModal, setProModal ] = useState( { open: false, upsell: undefined } );

    const meta = useSelect( select => {
        return select( editorStore )?.getEditedPostAttribute( "meta" ) || {};
    }, [] );
    const popupTypeLibraries = useSelect( select => {
        const { getBlockVariations, getDefaultBlockVariation } = select( blocksStore );

        return popupTypeOptions.map( option => ( {
            ...option,
            defaultVariation: getDefaultBlockVariation( option.blockName ),
            variations: getBlockVariations( option.blockName ) ?? [],
        } ) );
    }, [ popupTypeOptions ] );
    const { editPost, resetEditorBlocks } = useDispatch( editorStore );

    const libraryItems = useMemo( () => {
        return popupTypeLibraries.flatMap( popupTypeLibrary => popupTypeLibrary.variations.map( ( variation, index ) => {
            const title = getFirstString( variation?.title, __( "Untitled template", "fooconvert" ) );
            const description = getFirstString(
                variation?.description,
                __( "A ready-made starting point for your popup.", "fooconvert" )
            );
            const templateKey = getFirstString( variation?.attributes?.template, variation?.name, title )
                .toLocaleLowerCase();
            const category = getPickerCategory( variation );
            const tags = getPickerTags( variation );
            const availabilityKey = getPickerAvailability( variation );
            const tagValues = tags.map( tag => tag.value );
            const tagLabels = tags.map( tag => tag.label );

            return {
                variation,
                index,
                key: templateKey || `${ popupTypeLibrary.value }-${ index }`,
                title,
                description,
                monogram: getMonogram( title ),
                popupType: popupTypeLibrary.value,
                popupTypeLabel: popupTypeLibrary.label,
                blockName: popupTypeLibrary.blockName,
                defaultVariation: popupTypeLibrary.defaultVariation,
                categoryKey: category.value,
                categoryLabel: category.label,
                tagValues,
                tagLabels,
                availability: availabilityKey,
                thumbnailUrl: getThumbnailUrl( variation ),
                previewUrl: getPreviewUrl( variation ),
                isPro: availabilityKey === "pro",
                searchContent: [
                    title,
                    description,
                    templateKey,
                    category.label,
                    popupTypeLibrary.label,
                    ...tagValues,
                    ...tagLabels,
                ].join( " " ).toLocaleLowerCase(),
            };
        } ) );
    }, [ popupTypeLibraries ] );

    const categoryOptions = useMemo( () => {
        const categoryMap = new Map();

        libraryItems.forEach( item => {
            if ( !isString( item.categoryKey, true ) || !isString( item.categoryLabel, true ) ) {
                return;
            }

            const existing = categoryMap.get( item.categoryKey );
            categoryMap.set( item.categoryKey, {
                value: item.categoryKey,
                label: item.categoryLabel,
                count: ( existing?.count ?? 0 ) + 1,
            } );
        } );

        return Array.from( categoryMap.values() ).sort( ( left, right ) => {
            if ( left.value === "blank" && right.value !== "blank" ) {
                return -1;
            }
            if ( right.value === "blank" && left.value !== "blank" ) {
                return 1;
            }
            return left.label.localeCompare( right.label );
        } );
    }, [ libraryItems ] );

    const tagOptions = useMemo( () => {
        const tagMap = new Map();

        libraryItems.forEach( item => {
            item.tagValues.forEach( ( tagValue, index ) => {
                const tagLabel = item.tagLabels[ index ];
                if ( !isString( tagValue, true ) || !isString( tagLabel, true ) ) {
                    return;
                }

                const existing = tagMap.get( tagValue );
                tagMap.set( tagValue, {
                    value: tagValue,
                    label: tagLabel,
                    count: ( existing?.count ?? 0 ) + 1,
                } );
            } );
        } );

        return Array.from( tagMap.values() )
            .sort( ( a, b ) => {
                if ( b.count !== a.count ) {
                    return b.count - a.count;
                }
                return a.label.localeCompare( b.label );
            } );
    }, [ libraryItems ] );

    useEffect( () => {
        if ( selectedCategory !== FILTER_ALL && !categoryOptions.some( option => option.value === selectedCategory ) ) {
            setSelectedCategory( FILTER_ALL );
        }
    }, [ categoryOptions, selectedCategory ] );

    useEffect( () => {
        if ( selectedPopupType !== FILTER_ALL && !popupTypeOptions.some( option => option.value === selectedPopupType ) ) {
            setSelectedPopupType( FILTER_ALL );
        }
    }, [ popupTypeOptions, selectedPopupType ] );

    useEffect( () => {
        if ( selectedTag !== FILTER_ALL && !tagOptions.some( option => option.value === selectedTag ) ) {
            setSelectedTag( FILTER_ALL );
        }
    }, [ selectedTag, tagOptions ] );

    useEffect( () => {
        setPreviewState( null );
    }, [ search, selectedPopupType, selectedCategory, selectedTag, availability, currentPopupType ] );

    const filteredItems = useMemo( () => {
        return libraryItems
            .filter( item => {
                if ( search && !item.searchContent.includes( search.toLocaleLowerCase() ) ) {
                    return false;
                }

                if ( selectedPopupType !== FILTER_ALL && item.popupType !== selectedPopupType ) {
                    return false;
                }

                if ( selectedCategory !== FILTER_ALL && item.categoryKey !== selectedCategory ) {
                    return false;
                }

                if ( selectedTag !== FILTER_ALL && !item.tagValues.includes( selectedTag ) ) {
                    return false;
                }

                if ( availability === "free" && item.availability !== "included" ) {
                    return false;
                }

                if ( availability === "pro" && item.availability !== "pro" ) {
                    return false;
                }

                return true;
            } )
            .sort( ( left, right ) => {
                if ( left.categoryKey === "blank" && right.categoryKey !== "blank" ) {
                    return -1;
                }
                if ( right.categoryKey === "blank" && left.categoryKey !== "blank" ) {
                    return 1;
                }
                if ( left.isPro !== right.isPro ) {
                    return left.isPro ? 1 : -1;
                }
                if ( left.popupType !== right.popupType ) {
                    return left.popupTypeLabel.localeCompare( right.popupTypeLabel );
                }
                return left.title.localeCompare( right.title );
            } );
    }, [ availability, libraryItems, search, selectedCategory, selectedPopupType, selectedTag ] );

    const filtersAreDirty = search !== "" || selectedPopupType !== FILTER_ALL || selectedCategory !== FILTER_ALL || selectedTag !== FILTER_ALL || availability !== FILTER_ALL;

    const openProModal = variation => {
        if ( isPlainObject( variation?.upsell ) ) {
            setProModal( { open: true, upsell: variation.upsell } );
        } else {
            console.error( 'Pro variation is missing the "upsell" object.', variation );
        }
    };

    const closeProModal = () => {
        setProModal( { open: false, upsell: undefined } );
    };

    const showPreview = ( item, element ) => {
        if ( !item.previewUrl ) {
            return;
        }

        const position = getPreviewPosition( rootRef.current, element );
        if ( !position ) {
            return;
        }

        setPreviewState( {
            item,
            style: position,
        } );
    };

    const hidePreview = () => {
        setPreviewState( null );
    };

    const handleChange = item => {
        const variation = item?.variation ?? item?.defaultVariation;
        setPreviewState( null );

        if ( variation?.pro ) {
            openProModal( variation );
            return;
        }

        if ( !isString( item?.blockName, true ) ) {
            return;
        }

        const nextAttributes = isPlainObject( variation?.attributes ) ? variation.attributes : ( reset ?? {} );
        const nextInnerBlocks = createBlocksFromInnerBlocksTemplate( variation?.innerBlocks ?? [] );
        const nextMeta = {
            ...meta,
            [ POPUP_TYPE_META_KEY ]: item.popupType,
            ...( isPlainObject( variation?.meta ) ? variation.meta : {} ),
        };

        resetEditorBlocks( [
            createBlock( item.blockName, nextAttributes, nextInnerBlocks )
        ] );

        editPost( {
            meta: nextMeta,
            ...( isString( variation?.title, true ) ? { title: variation.title } : {} ),
        } );

        onSelectTemplate?.();
    };

    const clearFilters = () => {
        setSearch( "" );
        setSelectedPopupType( FILTER_ALL );
        setSelectedCategory( FILTER_ALL );
        setSelectedTag( FILTER_ALL );
        setAvailability( FILTER_ALL );
    };

    const classes = classNames( "fc-variation-picker", className );

    const ProModalButton = ( { value, ...props } ) => {
        const { text = "", href = "" } = value ?? {};
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
        <div className={ classes } ref={ rootRef }>
            <div className="fc-variation-picker__layout">
                <aside className="fc-variation-picker__sidebar" aria-label={ __( "Template filters", "fooconvert" ) }>
                    <SidebarSection title={ __( "Popup type", "fooconvert" ) }>
                        <div className="fc-variation-picker__filter-list">
                            <FilterButton
                                label={ __( "All popup types", "fooconvert" ) }
                                isActive={ selectedPopupType === FILTER_ALL }
                                onClick={ () => setSelectedPopupType( FILTER_ALL ) }
                                count={ libraryItems.length }
                            />
                            { popupTypeOptions.map( option => (
                                <FilterButton
                                    key={ option.value }
                                    label={ option.label }
                                    isActive={ option.value === selectedPopupType }
                                    onClick={ () => setSelectedPopupType( option.value ) }
                                    count={ libraryItems.filter( item => item.popupType === option.value ).length }
                                />
                            ) ) }
                        </div>
                    </SidebarSection>

                    <SidebarSection title={ __( "Use case", "fooconvert" ) }>
                        <div className="fc-variation-picker__filter-list">
                            <FilterButton
                                label={ __( "All templates", "fooconvert" ) }
                                isActive={ selectedCategory === FILTER_ALL }
                                onClick={ () => setSelectedCategory( FILTER_ALL ) }
                                count={ libraryItems.length }
                            />
                            { categoryOptions.map( option => (
                                <FilterButton
                                    key={ option.value }
                                    label={ option.label }
                                    isActive={ selectedCategory === option.value }
                                    onClick={ () => setSelectedCategory( option.value ) }
                                    count={ option.count }
                                />
                            ) ) }
                        </div>
                    </SidebarSection>

                    <SidebarSection title={ __( "Availability", "fooconvert" ) }>
                        <div className="fc-variation-picker__filter-list">
                            <FilterButton
                                label={ __( "All", "fooconvert" ) }
                                isActive={ availability === FILTER_ALL }
                                onClick={ () => setAvailability( FILTER_ALL ) }
                            />
                            <FilterButton
                                label={ __( "Included", "fooconvert" ) }
                                isActive={ availability === "free" }
                                onClick={ () => setAvailability( "free" ) }
                            />
                            <FilterButton
                                label={ __( "Pro", "fooconvert" ) }
                                isActive={ availability === "pro" }
                                onClick={ () => setAvailability( "pro" ) }
                            />
                        </div>
                    </SidebarSection>

                    { tagOptions.length > 0 && (
                        <SidebarSection title={ __( "Tags", "fooconvert" ) }>
                            <div className="fc-variation-picker__tag-cloud">
                                <button
                                    type="button"
                                    className={ classNames( "fc-variation-picker__tag", {
                                        "is-active": selectedTag === FILTER_ALL,
                                    } ) }
                                    aria-pressed={ selectedTag === FILTER_ALL }
                                    onClick={ () => setSelectedTag( FILTER_ALL ) }
                                >
                                    { __( "All", "fooconvert" ) }
                                </button>
                                { tagOptions.map( option => (
                                    <button
                                        type="button"
                                        key={ option.value }
                                        className={ classNames( "fc-variation-picker__tag", {
                                            "is-active": selectedTag === option.value,
                                        } ) }
                                        aria-pressed={ selectedTag === option.value }
                                        onClick={ () => setSelectedTag( option.value ) }
                                    >
                                        { option.label }
                                    </button>
                                ) ) }
                            </div>
                        </SidebarSection>
                    ) }
                </aside>

                <section className="fc-variation-picker__content">
                    <div className="fc-variation-picker__toolbar">
                        <div className="fc-variation-picker__toolbar-copy">
                            { isString( label, true ) && (
                                <h2 className="fc-variation-picker__label">{ label }</h2>
                            ) }
                            <p className="fc-variation-picker__subheading">
                                { __( "Search by name, use case, or tag.", "fooconvert" ) }
                            </p>
                        </div>
                        <div className="fc-variation-picker__toolbar-search">
                            <SearchControl
                                __nextHasNoMarginBottom
                                label={ __( "Search templates", "fooconvert" ) }
                                placeholder={ __( "Search templates", "fooconvert" ) }
                                value={ search }
                                onChange={ value => setSearch( value ?? "" ) }
                            />
                        </div>
                        <div className="fc-variation-picker__toolbar-meta">
                            <span className="fc-variation-picker__result-count">
                                { sprintf(
                                    /* translators: %d: number of templates */
                                    __( "%d templates", "fooconvert" ),
                                    filteredItems.length
                                ) }
                            </span>
                            { filtersAreDirty && (
                                <Button
                                    size="compact"
                                    variant="tertiary"
                                    onClick={ clearFilters }
                                >
                                    { __( "Clear filters", "fooconvert" ) }
                                </Button>
                            ) }
                        </div>
                    </div>

                    { filteredItems.length > 0 ? (
                        <div className="fc-variation-picker__cards" role="list">
                            { filteredItems.map( item => (
                                <button
                                    type="button"
                                    key={ item.key }
                                    role="listitem"
                                    className={ classNames(
                                        "fc-variation-picker__card",
                                        `is-${ item.categoryKey }`,
                                        `is-${ item.popupType }`,
                                        {
                                            "is-pro": item.isPro,
                                            "has-preview": isString( item.previewUrl, true ),
                                        }
                                    ) }
                                    onClick={ () => handleChange( item ) }
                                    onMouseEnter={ event => showPreview( item, event.currentTarget ) }
                                    onMouseLeave={ hidePreview }
                                    onFocus={ event => showPreview( item, event.currentTarget ) }
                                    onBlur={ hidePreview }
                                >
                                    <div className="fc-variation-picker__card-header">
                                        <span className="fc-variation-picker__card-category">{ item.categoryLabel }</span>
                                        <div className="fc-variation-picker__card-status">
                                            <span className="fc-variation-picker__card-type">{ item.popupTypeLabel }</span>
                                            { item.isPro && (
                                                <span className="fc-variation-picker__card-badge">
                                                    { __( "PRO", "fooconvert" ) }
                                                </span>
                                            ) }
                                        </div>
                                    </div>
                                    <div className="fc-variation-picker__card-body">
                                        <div
                                            className={ classNames( "fc-variation-picker__card-media", {
                                                "has-thumbnail": isString( item.thumbnailUrl, true ),
                                            } ) }
                                            aria-hidden="true"
                                        >
                                            { isString( item.thumbnailUrl, true ) ? (
                                                <img
                                                    src={ item.thumbnailUrl }
                                                    alt=""
                                                    className="fc-variation-picker__card-thumbnail"
                                                />
                                            ) : (
                                                <div className="fc-variation-picker__card-monogram">
                                                    { item.monogram }
                                                </div>
                                            ) }
                                        </div>
                                        <div className="fc-variation-picker__card-copy">
                                            <span className="fc-variation-picker__card-title">{ item.title }</span>
                                            <p className="fc-variation-picker__card-description">{ item.description }</p>
                                        </div>
                                    </div>
                                    { item.tagLabels.length > 0 && (
                                        <div className="fc-variation-picker__card-tags">
                                            { item.tagLabels.slice( 0, 4 ).map( tag => (
                                                <span
                                                    key={ `${ item.key }-${ tag }` }
                                                    className="fc-variation-picker__card-tag"
                                                >
                                                    { tag }
                                                </span>
                                            ) ) }
                                        </div>
                                    ) }
                                </button>
                            ) ) }
                        </div>
                    ) : (
                        <div className="fc-variation-picker__empty-state">
                            <strong>{ __( "No templates matched those filters.", "fooconvert" ) }</strong>
                            <p>{ __( "Try a broader search term or clear the active filters.", "fooconvert" ) }</p>
                        </div>
                    ) }
                </section>
            </div>

            { previewState?.item && (
                <div className="fc-variation-picker__preview" style={ previewState.style } aria-hidden="true">
                    <div className="fc-variation-picker__preview-frame">
                        <img
                            src={ previewState.item.previewUrl }
                            alt=""
                            className="fc-variation-picker__preview-image"
                        />
                    </div>
                    <div className="fc-variation-picker__preview-copy">
                        <strong>{ previewState.item.title }</strong>
                        <span>
                            { previewState.item.popupTypeLabel } / { previewState.item.categoryLabel }
                        </span>
                    </div>
                </div>
            ) }

            { proModal.open && (
                <Modal
                    className={ `${ CLASS_NAME }__pro-modal` }
                    title={ proModal.upsell.title }
                    onRequestClose={ closeProModal }
                >
                    <div className={ `${ CLASS_NAME }__pro-modal__body` }>
                        <div className={ `${ CLASS_NAME }__pro-modal__upsell-image` }>
                            <img src={ proModal.upsell.image } alt={ proModal.upsell.title } />
                        </div>
                        <div
                            className={ `${ CLASS_NAME }__pro-modal__upsell-content` }
                            dangerouslySetInnerHTML={ { __html: proModal.upsell.content } }
                        />
                    </div>
                    <div className={ `${ CLASS_NAME }__pro-modal__footer` }>
                        <ProModalButton variant="primary" value={ proModal.upsell.primary } />
                        <ProModalButton variant="secondary" value={ proModal.upsell.secondary } />
                        <Button variant="secondary" isDestructive onClick={ closeProModal }>
                            { __( "Close", "fooconvert" ) }
                        </Button>
                    </div>
                </Modal>
            ) }
        </div>
    );
};

export default VariationPicker;
