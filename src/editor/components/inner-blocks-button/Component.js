import { useState } from "@wordpress/element";
import { Button, Modal, TabPanel, TextareaControl, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

import "./Component.scss";
import { useSelect } from "@wordpress/data";
import { store as blockEditorStore } from "@wordpress/block-editor";
import { isString } from "@steveush/utils";

const rootClass = 'fc--inner-blocks-button';

const slugify = value => {
    if ( isString( value, true ) ) {
        return value.replaceAll( /\W/g, '_' ).replace( /^(\d)/, '$$1' ).toLowerCase();
    }
    return '';
}

const InnerBlocksButton = ( { children, targetClientId, prepareAttributes = (attr, slug) => attr, ...buttonProps } ) => {
    const [ isOpen, setOpen ] = useState( false );
    const [ title, setTitle ] = useState( '' );
    const [ description, setDescription ] = useState( '' );

    const target = useSelect(
        ( select ) => select( blockEditorStore )?.getBlock( targetClientId ),
        [ targetClientId ]
    );

    const openModal = () => {
        setOpen( true );
    };
    const closeModal = () => {
        setOpen( false );
    };

    const getInnerBlocks = innerBlocks => innerBlocks.map( innerBlock => Array.isArray( innerBlock ) ? innerBlock : [ innerBlock.name, innerBlock.attributes, getInnerBlocks( innerBlock.innerBlocks ) ] );

    const getInnerBlocksJSON = () => {
        if ( target && target?.innerBlocks?.length ) {
            const innerBlocks = getInnerBlocks( target?.innerBlocks ?? [] );
            const slug = slugify( title );
            const attr = prepareAttributes( target?.attributes ?? {}, slug );
            const output = {
                slug,
                title,
                description,
                icon: '',
                attributes: attr,
                innerBlocks: innerBlocks,
                scope: [ 'block' ]
            };
            return JSON.stringify( output, null, '\t' );
        }
        return '';
    };

    const getInnerBlocksPHP = () => {
        return getInnerBlocksJSON()
            .replaceAll( /[{\[]/g, 'array(' )
            .replaceAll( /[}\]]/g, ')' )
            .replaceAll( /(?<!\\)"/g, "'" )
            .replaceAll( /\\"/g, '"' )
            .replaceAll( /\s?:\s?/g, ' => ' );
    };

    const tabs = [
        {
            name: 'json',
            title: __( 'JSON', 'fooconvert' ),
            className: `${ rootClass }__json-tab`
        },
        {
            name: 'php',
            title: __( 'PHP', 'fooconvert' ),
            className: `${ rootClass }__php-tab`
        }
    ];

    return (
        <>
            <Button className={ `${ rootClass }__button` } { ...buttonProps } isPressed={ isOpen }
                    onClick={ openModal }>
                { children }
            </Button>
            { isOpen && (
                <Modal className={ `${ rootClass }__modal` } title={ __( 'Inner Blocks', 'fooconvert' ) }
                       onRequestClose={ closeModal }>
                    <div className={ `${ rootClass }__input` }>
                        <TextControl
                            label={ __( 'Title', 'fooconvert' ) }
                            value={ title }
                            onChange={ setTitle }
                        />
                        <TextareaControl
                            label={ __( 'Description', 'fooconvert' ) }
                            value={ description }
                            onChange={ setDescription }
                        />
                    </div>
                    <TabPanel
                        className={ `${ rootClass }__modal-tabs` }
                        tabs={ tabs }
                    >
                        { tab => {
                            switch ( tab.name ) {
                                case "json":
                                    return (
                                        <textarea className={ `${ rootClass }__json-output` }
                                                  value={ getInnerBlocksJSON() } rows={ 15 }
                                                  readOnly={ true }></textarea>
                                    );
                                case "php":
                                    return (
                                        <textarea className={ `${ rootClass }__php-output` }
                                                  value={ getInnerBlocksPHP() } rows={ 15 }
                                                  readOnly={ true }></textarea>
                                    );
                            }
                            return null;
                        } }
                    </TabPanel>
                    <div className={ `${ rootClass }__modal-footer` }>
                        <Button variant="secondary" onClick={ closeModal }>
                            { __( 'Close', 'fooconvert' ) }
                        </Button>
                    </div>
                </Modal>
            ) }
        </>
    );
};

export default InnerBlocksButton;