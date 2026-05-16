import { cloneDeep, isPlainObject } from './serializer-support';

const matchesMediaItem = ( block, mediaItem ) => {
	if (
		block?.name !== 'core/image' ||
		! isPlainObject( block?.attributes ) ||
		! isPlainObject( mediaItem )
	) {
		return false;
	}

	const blockId = Number( block.attributes.id );
	const mediaId = Number( mediaItem.id );

	if (
		Number.isFinite( blockId ) &&
		blockId > 0 &&
		Number.isFinite( mediaId ) &&
		mediaId > 0
	) {
		return blockId === mediaId;
	}

	return (
		typeof block.attributes.url === 'string' &&
		typeof mediaItem.url === 'string' &&
		block.attributes.url === mediaItem.url
	);
};

const buildImageBlock = ( mediaItem ) => ( {
	name: 'core/image',
	attributes: {
		id: Number.isFinite( Number( mediaItem?.id ) )
			? Number( mediaItem.id )
			: undefined,
		url: typeof mediaItem?.url === 'string' ? mediaItem.url : '',
		alt: typeof mediaItem?.alt === 'string' ? mediaItem.alt : '',
		title: typeof mediaItem?.title === 'string' ? mediaItem.title : '',
	},
	inner_blocks: [],
} );

const replaceFirstImageBlock = ( blocks, imageBlock ) => {
	for ( let index = 0; index < blocks.length; index += 1 ) {
		const block = blocks[ index ];

		if ( ! isPlainObject( block ) ) {
			continue;
		}

		if ( block.name === 'core/image' ) {
			blocks[ index ] = imageBlock;
			return true;
		}

		if (
			Array.isArray( block.inner_blocks ) &&
			replaceFirstImageBlock( block.inner_blocks, imageBlock )
		) {
			return true;
		}
	}

	return false;
};

const getImageInsertIndex = ( blocks ) => {
	const actionIndex = blocks.findIndex( ( block ) =>
		[ 'fc/sign-up', 'core/buttons', 'core/button' ].includes( block?.name )
	);

	if ( actionIndex >= 0 ) {
		return actionIndex;
	}

	return Math.min( 2, blocks.length );
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
	nextDraft.content_blocks = Array.isArray( nextDraft.content_blocks )
		? nextDraft.content_blocks
		: [];

	const imageBlock = buildImageBlock( mediaItem );
	if ( replaceFirstImageBlock( nextDraft.content_blocks, imageBlock ) ) {
		return nextDraft;
	}

	nextDraft.content_blocks.splice(
		getImageInsertIndex( nextDraft.content_blocks ),
		0,
		imageBlock
	);
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

	return nextDraft;
};
