import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { PluginPostStatusInfo, store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { pencil, trendingUp } from '@wordpress/icons';

import editorData from '../ai-builder/editorData';
import { getAiBuilderActionState } from './utils';
import './Plugin.scss';

const rootClass = 'fc--ai-builder-action';

const getEditedPostDirtyState = ( editor ) => {
	if ( typeof editor?.isEditedPostDirty === 'function' ) {
		return Boolean( editor.isEditedPostDirty() );
	}

	if ( typeof editor?.hasChangedContent === 'function' ) {
		return Boolean( editor.hasChangedContent() );
	}

	return false;
};

const AiBuilderActionPlugin = () => {
	const { currentPostId, currentPostType, hasUnsavedChanges, isSaving } =
		useSelect(
			( select ) => {
				const editor = select( editorStore );

				return {
					currentPostId: editor?.getCurrentPostId?.() || 0,
					currentPostType: editor?.getCurrentPostType?.() || '',
					hasUnsavedChanges: getEditedPostDirtyState( editor ),
					isSaving: Boolean(
						editor?.isSavingPost?.() ||
							editor?.isAutosavingPost?.()
					),
				};
			},
			[]
		);

	if ( currentPostType !== 'fc-popup' ) {
		return null;
	}

	const actionState = getAiBuilderActionState( {
		builderUrl: editorData?.builderUrl || '',
		statsUrlBase: editorData?.statsUrlBase || '',
		currentPostId,
		currentPostType,
		hasUnsavedChanges,
		isSaving,
	} );

	if ( ! actionState.shouldRender ) {
		return null;
	}

	const statsRow = actionState.rows.find( ( row ) => row.type === 'stats' );
	const aiBuilderRow = actionState.rows.find(
		( row ) => row.type === 'ai-builder'
	);
	const disabledReason =
		aiBuilderRow?.disabledReason === 'saving'
			? __( 'Saving in progress.', 'fooconvert' )
			: aiBuilderRow?.disabledReason === 'dirty'
			? __( 'Save before editing with AI.', 'fooconvert' )
			: '';

	return (
		<>
			{ statsRow && (
				<PluginPostStatusInfo
					className={ `${ rootClass }__post-status-info` }
				>
					<div className={ `${ rootClass }__label` }>
						{ __( 'Stats', 'fooconvert' ) }
					</div>
					<div className={ `${ rootClass }__control` }>
						<Button
							variant="secondary"
								size="compact"
								href={ statsRow.href }
								target={ statsRow.target }
								icon={ trendingUp }
							>
							{ __( 'View Stats', 'fooconvert' ) }
						</Button>
					</div>
				</PluginPostStatusInfo>
			) }

			{ aiBuilderRow && (
				<PluginPostStatusInfo
					className={ `${ rootClass }__post-status-info` }
				>
					<div className={ `${ rootClass }__label` }>
						{ __( 'AI Builder', 'fooconvert' ) }
					</div>
					<div className={ `${ rootClass }__control` }>
						<Button
							variant="secondary"
							size="compact"
							disabled={ aiBuilderRow.disabled }
							href={
								aiBuilderRow.disabled
									? undefined
									: aiBuilderRow.href
							}
							icon={ pencil }
						>
							{ __( 'Edit with AI', 'fooconvert' ) }
						</Button>
						{ disabledReason && (
							<div className={ `${ rootClass }__hint` }>
								{ disabledReason }
							</div>
						) }
					</div>
				</PluginPostStatusInfo>
			) }
		</>
	);
};

export default AiBuilderActionPlugin;
