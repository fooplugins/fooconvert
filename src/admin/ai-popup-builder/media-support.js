import { cloneDeep, isPlainObject } from './serializer-support';

const getMediaId = ( value ) => {
	const id = Number( value );
	return Number.isFinite( id ) && id > 0 ? id : 0;
};

const matchesMediaItem = ( block, mediaItem ) => {
	if (
		block?.name !== 'core/image' ||
		! isPlainObject( block?.attributes ) ||
		! isPlainObject( mediaItem )
	) {
		return false;
	}

	const blockId = getMediaId( block.attributes.id );
	const mediaId = getMediaId( mediaItem.id );

	if ( blockId > 0 && mediaId > 0 ) {
		return blockId === mediaId;
	}

	return (
		typeof block.attributes.url === 'string' &&
		typeof mediaItem.url === 'string' &&
		block.attributes.url === mediaItem.url
	);
};

const buildBackgroundImage = ( mediaItem ) => {
	const backgroundImage = {
		url: typeof mediaItem?.url === 'string' ? mediaItem.url : '',
		source: 'file',
	};
	const id = getMediaId( mediaItem?.id );

	if ( id > 0 ) {
		backgroundImage.id = id;
	}

	if ( typeof mediaItem?.title === 'string' && mediaItem.title.length > 0 ) {
		backgroundImage.title = mediaItem.title;
	}

	return backgroundImage;
};

const removeMatchingImageBlocks = ( blocks, mediaItem ) => {
	return blocks.reduce( ( nextBlocks, block ) => {
		if ( ! isPlainObject( block ) ) {
			return nextBlocks;
		}

		if ( matchesMediaItem( block, mediaItem ) ) {
			return nextBlocks;
		}

		const nextBlock = { ...block };

		if ( Array.isArray( block.inner_blocks ) ) {
			nextBlock.inner_blocks = removeMatchingImageBlocks(
				block.inner_blocks,
				mediaItem
			);
		}

		nextBlocks.push( nextBlock );
		return nextBlocks;
	}, [] );
};

const backgroundImageMatchesMediaItem = ( backgroundImage, mediaItem ) => {
	if ( ! isPlainObject( backgroundImage ) || ! isPlainObject( mediaItem ) ) {
		return false;
	}

	const backgroundId = getMediaId( backgroundImage.id );
	const mediaId = getMediaId( mediaItem.id );

	if ( backgroundId > 0 && mediaId > 0 ) {
		return backgroundId === mediaId;
	}

	return (
		typeof backgroundImage.url === 'string' &&
		typeof mediaItem.url === 'string' &&
		backgroundImage.url === mediaItem.url
	);
};

export const applyMediaItemToDraft = ( draft, mediaItem ) => {
	if (
		! isPlainObject( draft ) ||
		! isPlainObject( mediaItem ) ||
		typeof mediaItem?.url !== 'string' ||
		mediaItem.url.length === 0
	) {
		return draft;
	}

	const nextDraft = cloneDeep( draft );
	nextDraft.root_attributes = isPlainObject( nextDraft.root_attributes )
		? nextDraft.root_attributes
		: {};
	nextDraft.root_attributes.content = isPlainObject(
		nextDraft.root_attributes.content
	)
		? nextDraft.root_attributes.content
		: {};
	nextDraft.root_attributes.content.styles = isPlainObject(
		nextDraft.root_attributes.content.styles
	)
		? nextDraft.root_attributes.content.styles
		: {};
	nextDraft.root_attributes.content.styles.background = isPlainObject(
		nextDraft.root_attributes.content.styles.background
	)
		? nextDraft.root_attributes.content.styles.background
		: {};
	nextDraft.root_attributes.content.styles.background.backgroundImage =
		buildBackgroundImage( mediaItem );
	nextDraft.root_attributes.content.styles.background.backgroundSize =
		'cover';

	return nextDraft;
};

export const removeMediaItemFromDraft = ( draft, mediaItem ) => {
	if ( ! isPlainObject( draft ) || ! isPlainObject( mediaItem ) ) {
		return draft;
	}

	const nextDraft = cloneDeep( draft );
	nextDraft.content_blocks = Array.isArray( nextDraft.content_blocks )
		? removeMatchingImageBlocks( nextDraft.content_blocks, mediaItem )
		: [];

	const background =
		nextDraft.root_attributes?.content?.styles?.background || null;

	if (
		isPlainObject( background ) &&
		backgroundImageMatchesMediaItem( background.backgroundImage, mediaItem )
	) {
		delete background.backgroundImage;
		delete background.backgroundSize;
	}

	return nextDraft;
};
