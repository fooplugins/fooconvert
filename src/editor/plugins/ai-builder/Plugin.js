import { useSelect } from '@wordpress/data';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import editorData from './editorData';
import {
	formatAiBuilderDate,
	hasAiBuilderMetadata,
	normalizeAiBuilderMetadata,
} from './utils';
import './Plugin.scss';

const rootClass = 'fc--ai-builder-panel';

const GuidanceList = ( { title, items } ) => {
	if ( ! Array.isArray( items ) || items.length === 0 ) {
		return null;
	}

	return (
		<div className={ `${ rootClass }__section` }>
			<h3>{ title }</h3>
			<ul className={ `${ rootClass }__list` }>
				{ items.map( ( item ) => (
					<li key={ item }>{ item }</li>
				) ) }
			</ul>
		</div>
	);
};

const AiBuilderPlugin = () => {
	const metadataKey = editorData?.meta?.key || '';
	const { currentPostType, metadataValue } = useSelect(
		( select ) => {
			const editor = select( editorStore );
			const meta = editor?.getEditedPostAttribute( 'meta' ) || {};

			return {
				currentPostType: editor?.getCurrentPostType() || '',
				metadataValue: metadataKey ? meta?.[ metadataKey ] : null,
			};
		},
		[ metadataKey ]
	);

	const metadata = useMemo(
		() => normalizeAiBuilderMetadata( metadataValue ),
		[ metadataValue ]
	);

	if ( ! metadataKey ) {
		return null;
	}

	if (
		currentPostType !== 'fc-popup' ||
		! hasAiBuilderMetadata( metadata )
	) {
		return null;
	}

	const response = metadata.response;
	const popupDraft = response?.popup_draft;
	const validation = response?.validation;
	const formattedSavedAt = formatAiBuilderDate( metadata.saved_at );
	const summaryRows = [
		popupDraft?.popup_type
			? {
					label: __( 'Type', 'fooconvert' ),
					value:
						editorData?.labels?.[ popupDraft.popup_type ] ||
						popupDraft.popup_type,
			  }
			: null,
		popupDraft?.goal
			? {
					label: __( 'Goal', 'fooconvert' ),
					value: popupDraft.goal,
			  }
			: null,
		popupDraft?.audience
			? {
					label: __( 'Audience', 'fooconvert' ),
					value: popupDraft.audience,
			  }
			: null,
		popupDraft?.offer
			? {
					label: __( 'Offer', 'fooconvert' ),
					value: popupDraft.offer,
			  }
			: null,
		popupDraft?.template_slug
			? {
					label: __( 'Template', 'fooconvert' ),
					value: popupDraft.template_slug,
			  }
			: null,
	].filter( Boolean );

	return (
		<PluginDocumentSettingPanel
			name="fc-ai-builder"
			title={ __( 'AI Builder', 'fooconvert' ) }
			className={ rootClass }
		>
			<div className={ `${ rootClass }__stack` }>
				<div className={ `${ rootClass }__section` }>
					<strong>
						{ __( 'Generated with AI Popup Builder', 'fooconvert' ) }
					</strong>
					{ formattedSavedAt && (
						<p className={ `${ rootClass }__muted` }>
							{ sprintf(
								/* translators: %s is the formatted date and time when the popup draft was saved. */
								__( 'Saved %s', 'fooconvert' ),
								formattedSavedAt
							) }
						</p>
					) }
				</div>

				{ validation?.score !== null &&
					validation?.score !== undefined && (
						<div className={ `${ rootClass }__score` }>
							<span>
								{ __( 'Conversion score', 'fooconvert' ) }
							</span>
							<strong>{ `${ validation.score }/100` }</strong>
						</div>
					) }

				{ summaryRows.length > 0 && (
					<div className={ `${ rootClass }__section` }>
						<h3>{ __( 'Saved strategy', 'fooconvert' ) }</h3>
						<dl className={ `${ rootClass }__summary` }>
							{ summaryRows.map( ( row ) => (
								<div
									key={ row.label }
									className={ `${ rootClass }__summary-row` }
								>
									<dt>{ row.label }</dt>
									<dd>{ row.value }</dd>
								</div>
							) ) }
						</dl>
					</div>
				) }

				{ response?.assistant_message && (
					<div className={ `${ rootClass }__section` }>
						<h3>{ __( 'Builder summary', 'fooconvert' ) }</h3>
						<p>{ response.assistant_message }</p>
					</div>
				) }

				{ response?.clarifying_question && (
					<div className={ `${ rootClass }__section` }>
						<h3>{ __( 'Clarifying question', 'fooconvert' ) }</h3>
						<p>{ response.clarifying_question }</p>
					</div>
				) }

				<GuidanceList
					title={ __( 'Strengths', 'fooconvert' ) }
					items={ validation?.strengths }
				/>
				<GuidanceList
					title={ __( 'Watchouts', 'fooconvert' ) }
					items={ validation?.warnings }
				/>
				<GuidanceList
					title={ __( 'Suggestions', 'fooconvert' ) }
					items={ validation?.suggestions }
				/>
			</div>
		</PluginDocumentSettingPanel>
	);
};

export default AiBuilderPlugin;
