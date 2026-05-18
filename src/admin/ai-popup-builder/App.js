import {
	Button,
	Card,
	CardBody,
	CardHeader,
	CheckboxControl,
	Flex,
	FlexBlock,
	Modal,
	Notice,
	Spinner,
	TabPanel,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import {
	Fragment,
	startTransition,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	Icon,
	chevronDownSmall,
	chevronUpSmall,
	copySmall,
	external,
} from '@wordpress/icons';
import { apiFetch, setupApiFetch } from './api-client';
import {
	getActivityItemState,
	getActivityTimelineMode,
	getDisplayActivityLog,
	getFailedRequestActivityLog,
	getPendingActivitySteps,
} from './activity-support';
import {
	appendReasoningDelta,
	createAssistantChatMessage,
	getConversationPayloadMessages,
} from './chat-support';
import {
	BrandContextModal,
	BrandPreviewList as SharedBrandPreviewList,
	createEmptyBrand,
	normalizeBrand as normalizeBrandContext,
	serializeComparable as serializeBrandComparable,
} from '../brand-context/components';
import {
	applyMediaItemToDraft,
	removeMediaItemFromDraft,
} from './media-support';
import {
	buildLoadPopupPath,
	normalizeLoadedPopupResponse,
} from './initial-popup-support';
import { isPlainObject } from './serializer-support';
import { normalizePopupType, serializeDraftToMarkup } from './serializer';
import { streamChatRequest } from './stream-support';
import { config, debugTabAvailable, rootClass } from './config';
import {
	buildAiSettingsPayload,
	getBlockSource,
	getDefaultSelectedBlockNames,
	normalizeAiSettings,
	normalizeDisabledParams,
	sanitizeSelectedBlockNames,
	serializeAiSettingsComparable,
} from './settings-support';
import {
	buildLastAssistantMessage,
	getActionSummary,
	getTriggerSummary,
	truncateText,
} from './summary-support';
import { getSuggestionPrompts } from './suggestion-support';

const defaultBrandContext = isPlainObject( config?.brand?.defaultBrand )
	? config.brand.defaultBrand
	: {};
const normalizeBrand = ( brand ) =>
	normalizeBrandContext( brand, defaultBrandContext );
const serializeComparable = ( value ) =>
	serializeBrandComparable( value, defaultBrandContext );
const BrandPreviewList = ( props ) => (
	<SharedBrandPreviewList { ...props } rootClass={ rootClass } />
);
const aiClientAvailable = Boolean( config?.aiClientAvailable );
const aiConnectionReady =
	typeof config?.aiConnectionReady === 'boolean'
		? config.aiConnectionReady
		: aiClientAvailable;
const aiChatAvailable = aiClientAvailable && aiConnectionReady;
const aiImageGenerationAvailable =
	aiChatAvailable && Boolean( config?.imageGenerationAvailable );
const aiClientUpgradeUrl =
	typeof config?.aiClientUpgradeUrl === 'string'
		? config.aiClientUpgradeUrl
		: '';
const aiClientMessage =
	typeof config?.aiClientMessage === 'string' &&
	config.aiClientMessage.length > 0
		? config.aiClientMessage
		: __( 'WP 7.0 is required for this feature to work', 'fooconvert' );
const aiConnectionSetupUrl =
	typeof config?.aiConnectionSetupUrl === 'string'
		? config.aiConnectionSetupUrl
		: '';
const aiConnectionMessage =
	typeof config?.aiConnectionMessage === 'string' &&
	config.aiConnectionMessage.length > 0
		? config.aiConnectionMessage
		: __(
				'AI Popup Builder chat needs a valid WordPress AI connector before it can generate popups. Go to Settings > Connectors to add or verify a connector, then reload this page.',
				'fooconvert'
		  );
const aiUnavailableMessage = aiClientAvailable
	? aiConnectionMessage
	: aiClientMessage;
const aiUnavailableActionUrl = aiClientAvailable
	? aiConnectionSetupUrl
	: aiClientUpgradeUrl;
const aiUnavailableActionLabel = aiClientAvailable
	? __( 'Open Settings > Connectors', 'fooconvert' )
	: __( 'Upgrade WordPress', 'fooconvert' );
const configuredCurrentTextModel =
	typeof config?.models?.currentTextModel === 'string'
		? config.models.currentTextModel
		: '';
const configuredCurrentImageModel =
	typeof config?.models?.currentImageModel === 'string'
		? config.models.currentImageModel
		: '';
const configuredInitialPostId = Number( config?.initialPostId );
const initialPostId =
	Number.isFinite( configuredInitialPostId ) && configuredInitialPostId > 0
		? Math.floor( configuredInitialPostId )
		: 0;

setupApiFetch();

const templatesBySlug = Array.isArray( config?.templates )
	? config.templates.reduce( ( nextTemplates, template ) => {
			if (
				isPlainObject( template ) &&
				typeof template?.slug === 'string' &&
				template.slug.length > 0
			) {
				nextTemplates[ template.slug ] = template;
			}

			return nextTemplates;
	  }, {} )
	: {};
const tabDefinitions = [
	{
		name: 'chat',
		title: __( 'Chat', 'fooconvert' ),
	},
	{
		name: 'context',
		title: __( 'Context', 'fooconvert' ),
	},
	{
		name: 'details',
		title: __( 'Popup Details', 'fooconvert' ),
	},
	{
		name: 'media',
		title: __( 'Media', 'fooconvert' ),
	},
	{
		name: 'settings',
		title: __( 'Settings', 'fooconvert' ),
	},
].concat(
	debugTabAvailable
		? [
				{
					name: 'debug',
					title: __( 'Debug', 'fooconvert' ),
				},
		  ]
		: []
);

const getInitialContextModal = () => {
	if ( typeof window === 'undefined' || ! window.location?.search ) {
		return '';
	}

	try {
		return new URLSearchParams( window.location.search ).get(
			'fc_ai_context'
		) === 'brand'
			? 'brand'
			: '';
	} catch {
		return '';
	}
};

const initialContextModalName = getInitialContextModal();

const PromptChip = ( { label, onClick, disabled } ) => (
	<button
		type="button"
		className={ `${ rootClass }__prompt-chip` }
		onClick={ onClick }
		disabled={ disabled }
	>
		{ label }
	</button>
);

const ReasoningSummary = ( { content } ) => {
	const text = String( content || '' ).trim();

	if ( text.length === 0 ) {
		return null;
	}

	return (
		<details className={ `${ rootClass }__reasoning` }>
			<summary>{ __( 'Thinking', 'fooconvert' ) }</summary>
			<p>{ text }</p>
		</details>
	);
};

const MessageBubble = ( { message } ) => {
	const role = message?.role === 'assistant' ? 'assistant' : 'user';
	const content = String( message?.content || '' );
	const activityRows = Array.isArray( message?.activityLog )
		? message.activityLog
		: [];
	const requestStatus = message?.requestStatus || 'complete';

	return (
		<div
			className={ `${ rootClass }__message ${ rootClass }__message--${ role } ${ rootClass }__message--${ requestStatus }` }
		>
			<div className={ `${ rootClass }__message-label` }>
				{ role === 'assistant'
					? __( 'Popup Assistant', 'fooconvert' )
					: __( 'You', 'fooconvert' ) }
			</div>
			<div className={ `${ rootClass }__message-body` }>
				{ role === 'assistant' && (
					<Fragment>
						<ReasoningSummary
							content={ message?.reasoningSummary }
						/>
						<ActivityTimeline
							items={ activityRows }
							mode="complete"
							activeIndex={ 0 }
							defaultOpen={ false }
						/>
					</Fragment>
				) }
				{ content && (
					<div className={ `${ rootClass }__message-text` }>
						{ content }
					</div>
				) }
			</div>
		</div>
	);
};

const AssistantRunMessage = ( {
	activityLog,
	mode,
	activeIndex,
	reasoningSummary,
} ) => (
	<div
		className={ `${ rootClass }__message ${ rootClass }__message--assistant ${ rootClass }__message--running` }
	>
		<div className={ `${ rootClass }__message-label` }>
			{ __( 'Popup Assistant', 'fooconvert' ) }
		</div>
		<div className={ `${ rootClass }__message-body` }>
			<ReasoningSummary content={ reasoningSummary } />
			<ActivityTimeline
				items={ activityLog }
				mode={ mode }
				activeIndex={ activeIndex }
				defaultOpen={ true }
			/>
			<div className={ `${ rootClass }__message-pending` }>
				<Spinner />
				<span>{ __( 'Preparing response', 'fooconvert' ) }</span>
			</div>
		</div>
	</div>
);

const ConversionChecklist = ( { validation } ) => {
	if ( ! isPlainObject( validation ) ) {
		return null;
	}

	const strengths = Array.isArray( validation?.strengths )
		? validation.strengths
		: [];
	const warnings = Array.isArray( validation?.warnings )
		? validation.warnings
		: [];
	const suggestions = Array.isArray( validation?.suggestions )
		? validation.suggestions
		: [];

	return (
		<div className={ `${ rootClass }__checklist` }>
			<div className={ `${ rootClass }__score` }>
				<span>{ __( 'Conversion score', 'fooconvert' ) }</span>
				<strong>
					{ Number.isFinite( Number( validation?.score ) )
						? `${ validation.score }/100`
						: '–' }
				</strong>
			</div>
			{ strengths.length > 0 && (
				<div>
					<h4>{ __( 'Strengths', 'fooconvert' ) }</h4>
					<ul>
						{ strengths.map( ( item ) => (
							<li key={ item }>{ item }</li>
						) ) }
					</ul>
				</div>
			) }
			{ warnings.length > 0 && (
				<div>
					<h4>{ __( 'Watchouts', 'fooconvert' ) }</h4>
					<ul>
						{ warnings.map( ( item ) => (
							<li key={ item }>{ item }</li>
						) ) }
					</ul>
				</div>
			) }
			{ suggestions.length > 0 && (
				<div>
					<h4>{ __( 'Suggestions', 'fooconvert' ) }</h4>
					<ul>
						{ suggestions.map( ( item ) => (
							<li key={ item }>{ item }</li>
						) ) }
					</ul>
				</div>
			) }
		</div>
	);
};

const GuidanceList = ( { title, items } ) => {
	const rows = Array.isArray( items ) ? items.filter( Boolean ) : [];

	if ( rows.length === 0 ) {
		return null;
	}

	return (
		<div className={ `${ rootClass }__guidance-section` }>
			<h4>{ title }</h4>
			<ul className={ `${ rootClass }__plain-list` }>
				{ rows.map( ( item ) => (
					<li key={ item }>{ item }</li>
				) ) }
			</ul>
		</div>
	);
};

const formatPlaybookKey = ( key ) =>
	String( key || '' )
		.replace( /_/g, ' ' )
		.replace( /\b\w/g, ( char ) => char.toUpperCase() );

const playbookPopupFieldLabels = {
	best_for: __( 'Best for:', 'fooconvert' ),
	watchouts: __( 'Watchouts:', 'fooconvert' ),
	length: __( 'Length:', 'fooconvert' ),
	exit_intent: __( 'Exit intent:', 'fooconvert' ),
};

const getPlaybookStringEntries = ( source ) =>
	isPlainObject( source )
		? Object.entries( source ).filter(
				( [ , value ] ) =>
					typeof value === 'string' && value.trim().length > 0
		  )
		: [];

const PlaybookExampleList = ( { examples } ) => {
	const rows = isPlainObject( examples )
		? Object.entries( examples ).filter(
				( [ , example ] ) =>
					isPlainObject( example ) &&
					( example.before || example.after )
		  )
		: [];

	if ( rows.length === 0 ) {
		return null;
	}

	return (
		<div className={ `${ rootClass }__context-list` }>
			{ rows.map( ( [ key, example ] ) => (
				<div
					key={ key }
					className={ `${ rootClass }__context-inline-card` }
				>
					<strong>{ formatPlaybookKey( key ) }</strong>
					{ example.before && (
						<p>
							<strong>{ __( 'Before:', 'fooconvert' ) }</strong>{ ' ' }
							{ example.before }
						</p>
					) }
					{ example.after && (
						<p>
							<strong>{ __( 'After:', 'fooconvert' ) }</strong>{ ' ' }
							{ example.after }
						</p>
					) }
				</div>
			) ) }
		</div>
	);
};

const ContextSummaryCard = ( {
	title,
	summary,
	preview,
	onOpen,
	actionLabel = __( 'Open', 'fooconvert' ),
} ) => (
	<Card className={ `${ rootClass }__context-card` }>
		<CardHeader>
			<div className={ `${ rootClass }__context-card-head` }>
				<div className={ `${ rootClass }__context-item-head` }>
					<h3>{ title }</h3>
					<Button variant="secondary" onClick={ onOpen }>
						{ actionLabel }
					</Button>
				</div>
				{ summary && (
					<p className={ `${ rootClass }__muted-copy` }>
						{ summary }
					</p>
				) }
			</div>
		</CardHeader>
		<CardBody>
			<div className={ `${ rootClass }__context-card-preview` }>
				{ preview }
			</div>
		</CardBody>
	</Card>
);

const ReadOnlyTextField = ( { label, value, rows = 12 } ) => (
	<TextareaControl
		label={ label }
		value={ value }
		onChange={ () => {} }
		readOnly
		rows={ rows }
		__nextHasNoMarginBottom
		__next40pxDefaultSize
	/>
);

const ContextChipRow = ( { items, limit = 6 } ) => {
	const rows = Array.isArray( items )
		? items.filter( Boolean ).slice( 0, limit )
		: [];

	if ( rows.length === 0 ) {
		return null;
	}

	return (
		<div className={ `${ rootClass }__context-chip-row` }>
			{ rows.map( ( item, index ) => (
				<span
					key={ `${ item }-${ index }` }
					className={ `${ rootClass }__context-chip` }
				>
					{ item }
				</span>
			) ) }
		</div>
	);
};

const ContextCodePreview = ( { content } ) => {
	const text = String( content || '' ).trim();

	if ( text.length === 0 ) {
		return (
			<p className={ `${ rootClass }__muted-copy` }>
				{ __( 'No context available yet.', 'fooconvert' ) }
			</p>
		);
	}

	return <pre className={ `${ rootClass }__context-code` }>{ text }</pre>;
};

const formatJsonValue = ( value ) =>
	JSON.stringify( value ?? null, null, 2 ) || '';

const ActivityTimeline = ( {
	items,
	mode,
	activeIndex,
	defaultOpen = false,
} ) => {
	const rows = Array.isArray( items ) ? items.filter( Boolean ) : [];
	const [ isOpen, setOpen ] = useState( defaultOpen );

	if ( rows.length === 0 ) {
		return null;
	}

	return (
		<details
			className={ `${ rootClass }__activity` }
			open={ isOpen }
			onToggle={ ( event ) => setOpen( event.currentTarget.open ) }
		>
			<summary>
				<span>{ __( 'Activity', 'fooconvert' ) }</span>
				<span className={ `${ rootClass }__activity-summary-actions` }>
					<small>
						{ rows.length === 1
							? __( '1 event', 'fooconvert' )
							: sprintf(
									/* translators: %d is the number of activity events. */
									__( '%d events', 'fooconvert' ),
									rows.length
							  ) }
					</small>
					<span
						className={ `${ rootClass }__activity-toggle-icon` }
						aria-hidden="true"
					>
						<Icon
							icon={ isOpen ? chevronUpSmall : chevronDownSmall }
							size={ 16 }
						/>
					</span>
				</span>
			</summary>
			<div className={ `${ rootClass }__activity-list` }>
				{ rows.map( ( item, index ) => {
					const type = item?.type || 'status';
					const typeLabel =
						item?.hasResult && type === 'tool_call'
							? __( 'tool result', 'fooconvert' )
							: type.replace( '_', ' ' );
					const state = getActivityItemState( {
						mode,
						index,
						rowCount: rows.length,
						activeIndex,
					} );

					return (
						<div
							key={ `${ type }-${
								item?.label || 'step'
							}-${ index }` }
							className={ `${ rootClass }__activity-item ${ rootClass }__activity-item--${ state }` }
						>
							<div
								className={ `${ rootClass }__activity-marker` }
								aria-hidden="true"
							/>
							<div className={ `${ rootClass }__activity-copy` }>
								<div
									className={ `${ rootClass }__activity-label-row` }
								>
									<strong>
										{ item?.label ||
											__( 'Working', 'fooconvert' ) }
									</strong>
									<span>{ typeLabel }</span>
								</div>
								{ item?.summary && <p>{ item.summary }</p> }
								{ item?.resultSummary && (
									<p
										className={ `${ rootClass }__activity-result-summary` }
									>
										<strong>
											{ __( 'Result:', 'fooconvert' ) }
										</strong>{ ' ' }
										{ item.resultSummary }
									</p>
								) }
							</div>
						</div>
					);
				} ) }
			</div>
		</details>
	);
};

const formatDebugTimestamp = ( value ) => {
	if ( typeof value !== 'string' || value.length === 0 ) {
		return __( 'Unknown time', 'fooconvert' );
	}

	const date = new Date( value );

	if ( Number.isNaN( date.getTime() ) ) {
		return value;
	}

	return date.toLocaleString();
};

const DebugResponseInspector = ( {
	responses,
	currentResponse,
	isLoading,
	isClearing,
	error,
	onRefresh,
	onClear,
} ) => {
	const rows = Array.isArray( responses ) ? responses : [];

	return (
		<div className={ `${ rootClass }__stack` }>
			<div className={ `${ rootClass }__tab-intro` }>
				<div>
					<h2>{ __( 'AI Response Debug', 'fooconvert' ) }</h2>
					<p>
						{ __(
							'Raw invalid or repaired responses for troubleshooting.',
							'fooconvert'
						) }
					</p>
				</div>
				<div className={ `${ rootClass }__tab-actions` }>
					<Button
						variant="secondary"
						onClick={ onRefresh }
						disabled={ isLoading || isClearing }
					>
						{ isLoading
							? __( 'Loading…', 'fooconvert' )
							: __( 'Refresh', 'fooconvert' ) }
					</Button>
					<Button
						variant="tertiary"
						isDestructive
						onClick={ onClear }
						disabled={
							isLoading || isClearing || rows.length === 0
						}
					>
						{ isClearing
							? __( 'Clearing…', 'fooconvert' )
							: __( 'Clear Responses', 'fooconvert' ) }
					</Button>
				</div>
			</div>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			<Card>
				<CardHeader>
					<h3>{ __( 'Current Response', 'fooconvert' ) }</h3>
				</CardHeader>
				<CardBody>
					<ReadOnlyTextField
						label={ __( 'Streaming response chunks', 'fooconvert' ) }
						value={
							currentResponse ||
							__(
								'Send a chat request to watch response chunks stream here.',
								'fooconvert'
							)
						}
						rows={ 14 }
					/>
				</CardBody>
			</Card>

			{ isLoading && (
				<Card>
					<CardBody>
						<div className={ `${ rootClass }__inline-actions` }>
							<Spinner />
							<span>
								{ __(
									'Loading stored invalid responses…',
									'fooconvert'
								) }
							</span>
						</div>
					</CardBody>
				</Card>
			) }

			{ ! isLoading && rows.length === 0 && (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'No invalid AI popup builder responses have been stored yet.',
						'fooconvert'
					) }
				</Notice>
			) }

			{ ! isLoading && rows.length > 0 && (
				<div className={ `${ rootClass }__debug-list` }>
					{ rows.map( ( entry, index ) => {
						const rawResponse = String( entry?.raw_response || '' );
						const repairedResponse = String(
							entry?.repaired_response || ''
						);

						return (
							<Card
								key={ entry?.id || index }
								className={ `${ rootClass }__debug-entry` }
							>
								<CardHeader>
									<Flex
										justify="space-between"
										align="flex-start"
									>
										<FlexBlock>
											<div>
												<h3>
													{ sprintf(
														/* translators: %d is the response number in the debug list. */
														__(
															'Stored response #%d',
															'fooconvert'
														),
														index + 1
													) }
												</h3>
												<p
													className={ `${ rootClass }__muted-copy` }
												>
													{ formatDebugTimestamp(
														entry?.created_at
													) }
												</p>
											</div>
										</FlexBlock>
										<span
											className={ `${ rootClass }__meta-pill` }
										>
											{ entry?.will_retry
												? __( 'Retried', 'fooconvert' )
												: __(
														'Returned error',
														'fooconvert'
												  ) }
										</span>
									</Flex>
								</CardHeader>
								<CardBody>
									<div
										className={ `${ rootClass }__debug-meta` }
									>
										<div
											className={ `${ rootClass }__summary-row` }
										>
											<span>
												{ __( 'Type', 'fooconvert' ) }
											</span>
											<strong>
												{ entry?.failure_type ||
													__(
														'Unknown',
														'fooconvert'
													) }
											</strong>
										</div>
										<div
											className={ `${ rootClass }__summary-row` }
										>
											<span>
												{ __(
													'Attempt',
													'fooconvert'
												) }
											</span>
											<strong>
												{ entry?.attempt || 1 }
											</strong>
										</div>
										<div
											className={ `${ rootClass }__summary-row` }
										>
											<span>
												{ __( 'Error', 'fooconvert' ) }
											</span>
											<strong>
												{ entry?.error_code ||
													__(
														'Unknown',
														'fooconvert'
													) }
											</strong>
										</div>
										<div
											className={ `${ rootClass }__summary-row` }
										>
											<span>
												{ __( 'Length', 'fooconvert' ) }
											</span>
											<strong>
												{ sprintf(
													/* translators: %d is the response length in bytes. */
													__(
														'%d bytes',
														'fooconvert'
													),
													Number(
														entry?.raw_response_length ||
															rawResponse.length
													)
												) }
												{ entry?.raw_response_truncated
													? ` ${ __(
															'(truncated)',
															'fooconvert'
													  ) }`
													: '' }
											</strong>
										</div>
										{ entry?.repair_type && (
											<div
												className={ `${ rootClass }__summary-row` }
											>
												<span>
													{ __(
														'Repair',
														'fooconvert'
													) }
												</span>
												<strong>
													{ entry.repair_type }
												</strong>
											</div>
										) }
									</div>

									{ entry?.latest_user_message && (
										<ReadOnlyTextField
											label={ __(
												'Latest user message',
												'fooconvert'
											) }
											value={ entry.latest_user_message }
											rows={ 3 }
										/>
									) }

									<ReadOnlyTextField
										label={ __(
											'Error message',
											'fooconvert'
										) }
										value={ entry?.error_message || '' }
										rows={ 4 }
									/>

									<ReadOnlyTextField
										label={ __(
											'Raw AI response',
											'fooconvert'
										) }
										value={ rawResponse }
										rows={ 12 }
									/>

									{ repairedResponse && (
										<ReadOnlyTextField
											label={ __(
												'Repaired AI response',
												'fooconvert'
											) }
											value={ repairedResponse }
											rows={ 12 }
										/>
									) }

									<ReadOnlyTextField
										label={ __(
											'Error data',
											'fooconvert'
										) }
										value={ formatJsonValue(
											entry?.error_data || {}
										) }
										rows={ 8 }
									/>
								</CardBody>
							</Card>
						);
					} ) }
				</div>
			) }
		</div>
	);
};

export const App = () => {
	const blockCatalog = useMemo(
		() =>
			Array.isArray( config?.blockCatalog ) ? config.blockCatalog : [],
		[]
	);
	const templateLibrary = useMemo(
		() => ( Array.isArray( config?.templates ) ? config.templates : [] ),
		[]
	);
	const initialBrand = normalizeBrand(
		config?.brand?.savedBrand || config?.brand?.defaultBrand || {}
	);
	const initialSavedBrand = config?.brand?.hasSavedBrand
		? normalizeBrand( config?.brand?.savedBrand || {} )
		: createEmptyBrand();
	const initialAiSettings = normalizeAiSettings(
		config?.settings,
		blockCatalog
	);

	const [ messages, setMessages ] = useState( [] );
	const [ input, setInput ] = useState( '' );
	const [ draft, setDraft ] = useState( null );
	const [ validation, setValidation ] = useState( null );
	const [ mediaItems, setMediaItems ] = useState(
		Array.isArray( config?.mediaItems ) ? config.mediaItems : []
	);
	const [ lastResponse, setLastResponse ] = useState( null );
	const [ generateImagesOnSubmit, setGenerateImagesOnSubmit ] =
		useState( false );
	const [ mediaInstructions, setMediaInstructions ] = useState( '' );
	const [ suggestedPrompts, setSuggestedPrompts ] = useState( [] );
	const [ saveTitle, setSaveTitle ] = useState( '' );
	const [ titleTouched, setTitleTouched ] = useState( false );
	const [ isSending, setSending ] = useState( false );
	const [ isSavingDraft, setSavingDraft ] = useState( false );
	const [ isLoadingInitialPopup, setLoadingInitialPopup ] = useState(
		initialPostId > 0
	);
	const [ deletingMediaId, setDeletingMediaId ] = useState( 0 );
	const [ error, setError ] = useState( '' );
	const [ statusNotice, setStatusNotice ] = useState( null );
	const [ savedPopup, setSavedPopup ] = useState( null );
	const [ activityLog, setActivityLog ] = useState( [] );
	const [ liveActivityLog, setLiveActivityLog ] = useState( [] );
	const [ liveReasoningSummary, setLiveReasoningSummary ] = useState( '' );
	const [ currentResponse, setCurrentResponse ] = useState( '' );
	const [ pendingActivityIndex, setPendingActivityIndex ] = useState( 0 );
	const [ requestHasExistingDraft, setRequestHasExistingDraft ] =
		useState( false );
	const [ brand, setBrand ] = useState( initialBrand );
	const [ savedBrandSnapshot, setSavedBrandSnapshot ] =
		useState( initialSavedBrand );
	const [ aiSettings, setAiSettings ] = useState( initialAiSettings );
	const [ savedAiSettingsSnapshot, setSavedAiSettingsSnapshot ] =
		useState( initialAiSettings );
	const [ isSavingAiSettings, setSavingAiSettings ] = useState( false );
	const [ isExtractingBrand, setExtractingBrand ] = useState( false );
	const [ isSavingBrand, setSavingBrand ] = useState( false );
	const [ contextModal, setContextModal ] = useState(
		initialContextModalName
	);
	const [ blockFilter, setBlockFilter ] = useState( 'all' );
	const [ templateFilter, setTemplateFilter ] = useState( 'all' );
	const [ debugResponses, setDebugResponses ] = useState( [] );
	const [ isLoadingDebugResponses, setLoadingDebugResponses ] =
		useState( false );
	const [ isClearingDebugResponses, setClearingDebugResponses ] =
		useState( false );
	const [ debugResponseError, setDebugResponseError ] = useState( '' );
	const [ confirmDialog, setConfirmDialog ] = useState( null );
	const chatEndRef = useRef( null );
	const requestActivityLogRef = useRef( [] );
	const requestReasoningSummaryRef = useRef( '' );
	const pendingActivityIndexRef = useRef( 0 );

	const generatedMarkup = useMemo( () => {
		if ( ! draft ) {
			return '';
		}

		try {
			return serializeDraftToMarkup(
				draft,
				templatesBySlug,
				blockCatalog
			);
		} catch {
			return '';
		}
	}, [ blockCatalog, draft ] );

	const summaryRows = useMemo( () => {
		if ( ! draft ) {
			return [];
		}

		return [
			{
				label: __( 'Type', 'fooconvert' ),
				value:
					config?.labels?.[
						normalizePopupType( draft.popup_type )
					] || draft.popup_type,
			},
			{
				label: __( 'Goal', 'fooconvert' ),
				value: draft.goal || __( 'Not set', 'fooconvert' ),
			},
			{
				label: __( 'Offer', 'fooconvert' ),
				value: draft.offer || __( 'Not set', 'fooconvert' ),
			},
			{
				label: __( 'Action', 'fooconvert' ),
				value: getActionSummary( draft ),
			},
			{
				label: __( 'Trigger', 'fooconvert' ),
				value: getTriggerSummary( draft ),
			},
		];
	}, [ draft ] );

	const brandIsDirty = useMemo(
		() =>
			serializeComparable( brand ) !==
			serializeComparable( savedBrandSnapshot ),
		[ brand, savedBrandSnapshot ]
	);
	const aiSettingsIsDirty = useMemo(
		() =>
			serializeAiSettingsComparable( aiSettings, blockCatalog ) !==
			serializeAiSettingsComparable(
				savedAiSettingsSnapshot,
				blockCatalog
			),
		[ aiSettings, blockCatalog, savedAiSettingsSnapshot ]
	);
	const activityMode = getActivityTimelineMode( {
		isSending,
		liveActivityLog,
	} );
	const pendingActivitySteps = getPendingActivitySteps( {
		hasExistingDraft: requestHasExistingDraft,
	} );
	const displayActivityLog = getDisplayActivityLog( {
		isSending,
		liveActivityLog,
		activityLog,
		hasExistingDraft: requestHasExistingDraft,
	} );
	const chatIsBusy = isSending || isExtractingBrand || isLoadingInitialPopup;
	const conversationPayloadMessages =
		getConversationPayloadMessages( messages );
	const previewUrl = savedPopup?.previewUrl || '';
	const savedPopupUpdatesExisting = Boolean( savedPopup?.updatedExisting );
	const draftActionsVisible = Boolean(
		draft &&
			savedPopup?.postId &&
			( savedPopup?.previewUrl || savedPopup?.editUrl )
	);
	const conversionRationale = Array.isArray( draft?.conversion_rationale )
		? draft.conversion_rationale.filter( Boolean )
		: [];
	const implementationNotes = Array.isArray( draft?.notes )
		? draft.notes.filter( Boolean )
		: [];
	const brandPalette = [
		{
			label: __( 'Primary', 'fooconvert' ),
			value: brand?.colors?.primary,
		},
		{
			label: __( 'Secondary', 'fooconvert' ),
			value: brand?.colors?.secondary,
		},
		{
			label: __( 'Accent', 'fooconvert' ),
			value: brand?.colors?.accent,
		},
		{
			label: __( 'Background', 'fooconvert' ),
			value: brand?.colors?.background,
		},
		{
			label: __( 'Primary text', 'fooconvert' ),
			value: brand?.colors?.textPrimary,
		},
		{
			label: __( 'Secondary text', 'fooconvert' ),
			value: brand?.colors?.textSecondary,
		},
	].filter(
		( color ) => typeof color.value === 'string' && color.value.length > 0
	);
	const conversionPlaybook = isPlainObject( config?.playbook )
		? config.playbook
		: {};
	const abilityNames = Array.isArray( config?.abilities )
		? config.abilities
		: [];
	const selectedBlockNameSet = useMemo(
		() =>
			new Set(
				sanitizeSelectedBlockNames(
					aiSettings?.selectedBlockNames,
					blockCatalog
				)
		),
		[ aiSettings?.selectedBlockNames, blockCatalog ]
	);
	const suggestionPrompts = useMemo(
		() =>
			getSuggestionPrompts( {
				draft,
				selectedBlockNames: selectedBlockNameSet,
				imageGenerationAvailable: aiImageGenerationAvailable,
				limit: 5,
			} ),
		[ draft, selectedBlockNameSet ]
	);
	const blockSourceCounts = useMemo(
		() =>
			blockCatalog.reduce(
				( counts, block ) => {
					const blockName = String( block?.name || '' );
					const source = getBlockSource( blockName );

					counts.total.available += 1;
					counts[ source ].available += 1;

					if ( selectedBlockNameSet.has( blockName ) ) {
						counts.total.selected += 1;
						counts[ source ].selected += 1;
					}

					if ( block?.supports_children ) {
						counts.containers += 1;
					}

					return counts;
				},
				{
					total: { available: 0, selected: 0 },
					core: { available: 0, selected: 0 },
					fooconvert: { available: 0, selected: 0 },
					woocommerce: { available: 0, selected: 0 },
					other: { available: 0, selected: 0 },
					containers: 0,
				}
			),
		[ blockCatalog, selectedBlockNameSet ]
	);
	const filteredBlockCatalog = useMemo(
		() =>
			blockCatalog.filter( ( block ) => {
				const blockName = String( block?.name || '' );

				switch ( blockFilter ) {
					case 'core':
						return blockName.startsWith( 'core/' );
					case 'fooconvert':
						return blockName.startsWith( 'fc/' );
					case 'woocommerce':
						return blockName.startsWith( 'woocommerce/' );
					default:
						return true;
				}
			} ),
		[ blockCatalog, blockFilter ]
	);
	const filteredSelectedBlockCount = useMemo(
		() =>
			filteredBlockCatalog.filter( ( block ) =>
				selectedBlockNameSet.has( String( block?.name || '' ) )
			).length,
		[ filteredBlockCatalog, selectedBlockNameSet ]
	);
	const templateCounts = useMemo(
		() =>
			templateLibrary.reduce( ( counts, template ) => {
				const popupType = normalizePopupType( template?.popup_type );
				counts[ popupType || 'other' ] =
					( counts[ popupType || 'other' ] || 0 ) + 1;
				return counts;
			}, {} ),
		[ templateLibrary ]
	);
	const filteredTemplateLibrary = useMemo(
		() =>
			templateLibrary.filter( ( template ) => {
				const popupType = normalizePopupType( template?.popup_type );

				switch ( templateFilter ) {
					case 'popup':
					case 'flyout':
					case 'bar':
						return popupType === templateFilter;
					default:
						return true;
				}
			} ),
		[ templateLibrary, templateFilter ]
	);
	const playbookPrinciples = Array.isArray( conversionPlaybook?.principles )
		? conversionPlaybook.principles
		: [];
	const playbookAvoid = Array.isArray( conversionPlaybook?.avoid )
		? conversionPlaybook.avoid
		: [];
	const playbookPopupTypes = isPlainObject( conversionPlaybook?.popup_types )
		? conversionPlaybook.popup_types
		: {};
	const playbookCopyTactics = Array.isArray(
		conversionPlaybook?.copy_tactics
	)
		? conversionPlaybook.copy_tactics
		: [];
	const playbookProofHierarchy = isPlainObject(
		conversionPlaybook?.proof_hierarchy
	)
		? conversionPlaybook.proof_hierarchy
		: {};
	const playbookProofGuidance =
		typeof playbookProofHierarchy?.guidance === 'string'
			? playbookProofHierarchy.guidance
			: '';
	const playbookProofRanked = Array.isArray( playbookProofHierarchy?.ranked )
		? playbookProofHierarchy.ranked
		: [];
	const playbookProofCount =
		playbookProofRanked.length +
		( playbookProofGuidance.length > 0 ? 1 : 0 );
	const playbookExamples = isPlainObject( conversionPlaybook?.examples )
		? conversionPlaybook.examples
		: {};
	const playbookExampleCount = Object.values( playbookExamples ).filter(
		( example ) =>
			isPlainObject( example ) && ( example.before || example.after )
	).length;
	const abilityPreviewLabels = abilityNames.map( ( ability ) =>
		String( ability ).replace( 'fooconvert/', '' )
	);
	const currentTextModel = String(
		aiSettings?.overrideModel || configuredCurrentTextModel || ''
	).trim();
	const currentImageModel = String( configuredCurrentImageModel || '' ).trim();
	const initialBuilderTab = initialContextModalName ? 'context' : 'chat';
	const liveRequestSummaryRows = [
		{
			label: __( 'Messages', 'fooconvert' ),
			value: sprintf(
				/* translators: %d is the number of conversation turns. */
				__( '%d turns', 'fooconvert' ),
				conversationPayloadMessages.length
			),
		},
		{
			label: __( 'Draft', 'fooconvert' ),
			value: draft
				? __( 'Included', 'fooconvert' )
				: __( 'None yet', 'fooconvert' ),
		},
		{
			label: __( 'Media', 'fooconvert' ),
			value: sprintf(
				/* translators: %d is the number of generated media items. */
				__( '%d items', 'fooconvert' ),
				mediaItems.length
			),
		},
		{
			label: __( 'Brand', 'fooconvert' ),
			value: __( 'Always attached separately', 'fooconvert' ),
		},
	];

	useEffect( () => {
		if ( draft?.title && ! titleTouched ) {
			setSaveTitle( draft.title );
		}
	}, [ draft, titleTouched ] );

	useEffect( () => {
		if ( initialPostId <= 0 ) {
			return undefined;
		}

		let isCurrent = true;

		const loadInitialPopup = async () => {
			setLoadingInitialPopup( true );
			setError( '' );

			try {
				const response = await apiFetch( {
					path: buildLoadPopupPath(
						config?.api?.loadPopupPath,
						initialPostId
					),
					method: 'GET',
				} );
				const loaded = normalizeLoadedPopupResponse( response );

				if ( ! loaded.draft ) {
					throw new Error(
						__(
							'The saved popup could not be loaded into the AI builder.',
							'fooconvert'
						)
					);
				}

				if ( ! isCurrent ) {
					return;
				}

				setDraft( loaded.draft );
				setValidation( loaded.validation );
				setMessages( loaded.messages );
				setMediaItems( loaded.mediaItems );
				setSuggestedPrompts( loaded.suggestedPrompts );
				setLastResponse( loaded.lastResponse );
				setSavedPopup( loaded.savedPopup );
				setSaveTitle( loaded.saveTitle || loaded.draft.title || '' );
				setTitleTouched( false );
				setActivityLog( [] );
				setStatusNotice( {
					status: 'info',
					message: __(
						'Popup loaded. Ask AI for changes and they will update this popup.',
						'fooconvert'
					),
				} );
				setLoadingInitialPopup( false );
			} catch ( exception ) {
				if ( isCurrent ) {
					setError(
						exception?.message ||
							__(
								'The saved popup could not be loaded into the AI builder.',
								'fooconvert'
							)
					);
					setLoadingInitialPopup( false );
				}
			}
		};

		loadInitialPopup();

		return () => {
			isCurrent = false;
		};
	}, [] );

	useEffect( () => {
		chatEndRef.current?.scrollIntoView( {
			block: 'end',
		} );
	}, [ messages, isSending, liveActivityLog, liveReasoningSummary ] );

	useEffect( () => {
		if ( ! isSending ) {
			pendingActivityIndexRef.current = 0;
			setPendingActivityIndex( 0 );
			return undefined;
		}

		if ( activityMode !== 'placeholder' ) {
			return undefined;
		}

		const intervalId = window.setInterval( () => {
			setPendingActivityIndex( ( currentIndex ) => {
				const nextIndex = Math.min(
					currentIndex + 1,
					pendingActivitySteps.length - 1
				);

				pendingActivityIndexRef.current = nextIndex;

				return nextIndex;
			} );
		}, 3000 );

		return () => {
			window.clearInterval( intervalId );
		};
	}, [ isSending, activityMode, pendingActivitySteps.length ] );

	useEffect( () => {
		if ( config?.brand?.hasSavedBrand ) {
			return;
		}

		const extractBrand = async () => {
			setExtractingBrand( true );

			try {
				const response = await apiFetch( {
					path:
						config?.api?.extractBrandPath ||
						'/fooconvert/v1/brand-context/extract',
					method: 'POST',
					data: {
						mode: 'local',
					},
				} );

				const nextBrand = normalizeBrand( response?.brand );

				startTransition( () => {
					setBrand( nextBrand );
					setStatusNotice( {
						status: 'info',
						message: __(
							'Starter brand profile extracted.',
							'fooconvert'
						),
					} );
				} );
			} catch ( exception ) {
				setError(
					exception?.message ||
						__(
							'Brand extraction failed. You can fill in the brand details manually and save them.',
							'fooconvert'
						)
				);
			} finally {
				setExtractingBrand( false );
			}
		};

		extractBrand();
	}, [] );

	const persistDraft = async ( {
		nextDraft = draft,
		nextValidation = validation,
		nextMediaItems = mediaItems,
		nextMessages = messages,
		nextResponse = lastResponse,
		nextSuggestedPrompts = suggestedPrompts,
		nextTitle = saveTitle,
		options = {
			generate_images: generateImagesOnSubmit,
			force_image_generation: false,
		},
	} = {} ) => {
		if ( ! nextDraft ) {
			return null;
		}

		let nextMarkup = '';

		try {
			nextMarkup = serializeDraftToMarkup(
				nextDraft,
				templatesBySlug,
				blockCatalog
			);
		} catch {
			nextMarkup = '';
		}

		if ( ! nextMarkup ) {
			setError(
				__(
					'The popup draft could not be serialized into blocks.',
					'fooconvert'
				)
			);
			return null;
		}

		setSavingDraft( true );

		try {
			const response = await apiFetch( {
				path:
					config?.api?.savePath ||
					'/fooconvert/v1/ai-popup-builder/save',
				method: 'POST',
				data: {
					post_id: Number.isFinite( Number( savedPopup?.postId ) )
						? Number( savedPopup.postId )
						: undefined,
					title: nextTitle || nextDraft.title,
					popup_type: nextDraft.popup_type,
					post_content: nextMarkup,
					ai_metadata: {
						messages:
							getConversationPayloadMessages( nextMessages ),
						assistant_message: nextResponse
							? nextResponse?.assistant_message || ''
							: buildLastAssistantMessage( nextMessages ),
						clarifying_question: nextResponse
							? nextResponse?.clarifying_question || ''
							: '',
						suggested_prompts: nextSuggestedPrompts,
						popup_draft: nextDraft,
						validation: nextValidation,
						media_items: nextMediaItems,
						options,
					},
				},
			} );

			setSavedPopup( response );
			setStatusNotice( {
				status: 'success',
				message: response?.updatedExisting
					? __(
							'Popup updated automatically. Preview or edit it whenever you want.',
							'fooconvert'
					  )
					: __(
							'Draft popup created automatically. Preview it or open it in the editor.',
							'fooconvert'
					  ),
			} );

			return response;
		} catch ( exception ) {
			setError(
				exception?.message ||
					__( 'The popup draft could not be saved.', 'fooconvert' )
			);
			return null;
		} finally {
			setSavingDraft( false );
		}
	};

	const sendPrompt = async ( promptText, options = {} ) => {
		const prompt = String( promptText || '' ).trim();

		if ( prompt.length === 0 || chatIsBusy || ! aiChatAvailable ) {
			return;
		}

		const shouldGenerateImages =
			options?.generateImages ?? generateImagesOnSubmit;
		const shouldForceImageGeneration = Boolean(
			options?.forceImageGeneration
		);

		const nextMessages = [ ...messages, { role: 'user', content: prompt } ];

		setMessages( nextMessages );
		setInput( '' );
		setSending( true );
		setError( '' );
		setStatusNotice( null );
		setPendingActivityIndex( 0 );
		pendingActivityIndexRef.current = 0;
		const draftBeforeRequest = draft;
		const hasExistingDraftForRequest = Boolean( draftBeforeRequest );
		setRequestHasExistingDraft( hasExistingDraftForRequest );
		requestActivityLogRef.current = [];
		requestReasoningSummaryRef.current = '';
		setLiveActivityLog( [] );
		setLiveReasoningSummary( '' );
		setCurrentResponse( '' );

		try {
			const settingsPayload = buildAiSettingsPayload(
				aiSettings,
				blockCatalog
			);
			const requestPayload = {
				messages: getConversationPayloadMessages( nextMessages ),
				popup_draft: draftBeforeRequest || undefined,
				generate_images: shouldGenerateImages,
				force_image_generation: shouldForceImageGeneration,
				brand,
				settings: settingsPayload,
				model: settingsPayload.overrideModel || undefined,
				timeout: settingsPayload.timeout,
				max_tool_calls: settingsPayload.maxToolCalls,
				disabled_params: settingsPayload.disabledParams,
			};
			const canStream = Boolean(
				config?.streamingAvailable &&
					config?.restRoot &&
					typeof config?.api?.chatStreamPath === 'string' &&
					config.api.chatStreamPath.length > 0 &&
					typeof fetch === 'function' &&
					typeof TextDecoder !== 'undefined'
			);
			let streamStarted = false;
			let response;

			if ( canStream ) {
				try {
					response = await streamChatRequest( {
						restRoot: config?.restRoot,
						path:
							config?.api?.chatStreamPath ||
							'/fooconvert/v1/ai-popup-builder/chat-stream',
						nonce: config?.restNonce,
						payload: requestPayload,
						onChunk: ( chunk ) => {
							if ( typeof chunk !== 'string' || chunk.length === 0 ) {
								return;
							}

							startTransition( () => {
								setCurrentResponse(
									( current ) => `${ current }${ chunk }`
								);
							} );
						},
						onEvent: ( event ) => {
							if ( ! isPlainObject( event ) ) {
								return;
							}

							streamStarted = true;

							if ( event.event === 'assistant_delta' ) {
								return;
							}

							if (
								event.event === 'reasoning_delta' &&
								typeof event?.data?.content === 'string'
							) {
								const nextReasoningSummary =
									appendReasoningDelta(
										requestReasoningSummaryRef.current,
										event.data.content
									);

								requestReasoningSummaryRef.current =
									nextReasoningSummary;
								startTransition( () => {
									setLiveReasoningSummary(
										nextReasoningSummary
									);
								} );
								return;
							}

							if (
								event.event === 'activity' &&
								isPlainObject( event.data )
							) {
								const nextLiveActivityLog = [
									...requestActivityLogRef.current,
									event.data,
								];

								requestActivityLogRef.current =
									nextLiveActivityLog;
								startTransition( () => {
									setLiveActivityLog( nextLiveActivityLog );
								} );
							}
						},
					} );
				} catch ( exception ) {
					if ( streamStarted ) {
						throw exception;
					}
				}
			}

			if ( ! isPlainObject( response ) ) {
				response = await apiFetch( {
					path:
						config?.api?.chatPath ||
						'/fooconvert/v1/ai-popup-builder/chat',
					method: 'POST',
					data: requestPayload,
				} );
				setCurrentResponse( formatJsonValue( response || null ) );
			}

			const assistantMessage =
				response?.clarifying_question ||
				response?.assistant_message ||
				__( 'I prepared a popup direction for you.', 'fooconvert' );
			const responseDraft = isPlainObject( response?.popup_draft )
				? response.popup_draft
				: null;
			const nextDraft =
				responseDraft ||
				( hasExistingDraftForRequest ? draftBeforeRequest : null );
			let nextValidation = validation;
			if ( isPlainObject( response?.validation ) ) {
				nextValidation = response.validation;
			} else if ( responseDraft ) {
				nextValidation = null;
			}
			const nextMediaItems =
				Array.isArray( response?.media_items ) &&
				( responseDraft || ! hasExistingDraftForRequest )
					? response.media_items
					: mediaItems;
			const nextActivityLog = Array.isArray( response?.activity_log )
				? response.activity_log
				: [];
			const nextPrompts = Array.isArray( response?.suggested_prompts )
				? response.suggested_prompts
				: [];
			const nextAssistantMessage = createAssistantChatMessage( {
				content: assistantMessage,
				activityLog: nextActivityLog,
				reasoningSummary: requestReasoningSummaryRef.current,
			} );
			const nextConversation = [ ...nextMessages, nextAssistantMessage ];
			const nextResponseSettings = isPlainObject( response?.settings )
				? normalizeAiSettings( response.settings, blockCatalog )
				: null;

			requestActivityLogRef.current = nextActivityLog;

			startTransition( () => {
				setMessages( nextConversation );
				setSuggestedPrompts( nextPrompts );
				setDraft( nextDraft );
				setValidation( nextValidation );
				setMediaItems( nextMediaItems );
				setLastResponse( isPlainObject( response ) ? response : null );
				setActivityLog( nextActivityLog );

				if ( nextResponseSettings ) {
					setAiSettings( ( currentSettings ) =>
						normalizeAiSettings(
							{
								...currentSettings,
								disabledParams:
									nextResponseSettings.disabledParams,
								disabledParamsText:
									nextResponseSettings.disabledParamsText,
								timeoutDefault:
									nextResponseSettings.timeoutDefault,
								maxToolCallsDefault:
									nextResponseSettings.maxToolCallsDefault,
								selectedBlockNames:
									nextResponseSettings.selectedBlockNames ||
									currentSettings.selectedBlockNames,
								canManage: nextResponseSettings.canManage,
							},
							blockCatalog
						)
					);
					setSavedAiSettingsSnapshot( ( currentSettings ) =>
						normalizeAiSettings(
							{
								...currentSettings,
								disabledParams:
									nextResponseSettings.disabledParams,
								disabledParamsText:
									nextResponseSettings.disabledParamsText,
								timeoutDefault:
									nextResponseSettings.timeoutDefault,
								maxToolCallsDefault:
									nextResponseSettings.maxToolCallsDefault,
								selectedBlockNames:
									nextResponseSettings.selectedBlockNames ||
									currentSettings.selectedBlockNames,
								canManage: nextResponseSettings.canManage,
							},
							blockCatalog
						)
					);
				}
			} );

			if ( responseDraft ) {
				const nextTitle = titleTouched
					? saveTitle || nextDraft.title
					: nextDraft.title;

				await persistDraft( {
					nextDraft,
					nextValidation,
					nextMediaItems,
					nextMessages: nextConversation,
					nextResponse: response,
					nextSuggestedPrompts: nextPrompts,
					nextTitle,
					options: {
						generate_images: shouldGenerateImages,
						force_image_generation: shouldForceImageGeneration,
					},
				} );
			}
		} catch ( exception ) {
			const failedActivityLog = getFailedRequestActivityLog( {
				liveActivityLog: requestActivityLogRef.current,
				activeIndex: pendingActivityIndexRef.current,
				hasExistingDraft: hasExistingDraftForRequest,
			} );
			const errorMessage =
				exception?.message ||
				__(
					'The AI popup builder could not complete that request.',
					'fooconvert'
				);
			const failedConversation = [
				...nextMessages,
				createAssistantChatMessage( {
					content: errorMessage,
					activityLog: failedActivityLog,
					reasoningSummary: requestReasoningSummaryRef.current,
					requestStatus: 'error',
				} ),
			];

			setMessages( failedConversation );
			setActivityLog( failedActivityLog );
			setError( errorMessage );

			if ( debugTabAvailable ) {
				await loadDebugResponses();
			}
		} finally {
			setSending( false );
			setLiveActivityLog( [] );
			setLiveReasoningSummary( '' );
		}
	};

	const handleSubmit = async ( event ) => {
		event.preventDefault();
		await sendPrompt( input );
	};

	const copyMarkup = async () => {
		if ( ! generatedMarkup ) {
			return;
		}

		try {
			await navigator.clipboard.writeText( generatedMarkup );
			setStatusNotice( {
				status: 'success',
				message: __(
					'Popup block HTML copied to the clipboard.',
					'fooconvert'
				),
			} );
		} catch {
			setError(
				__(
					'Could not copy the block HTML to the clipboard.',
					'fooconvert'
				)
			);
		}
	};

	const extractBrand = async ( mode = 'local', remoteUrlValue = '' ) => {
		const remoteUrl = String( remoteUrlValue || '' ).trim();

		if ( mode === 'remote' && remoteUrl.length === 0 ) {
			setError(
				__(
					'Enter a remote URL before starting remote brand extraction.',
					'fooconvert'
				)
			);
			return false;
		}

		setExtractingBrand( true );
		setError( '' );

		try {
			const response = await apiFetch( {
				path:
					config?.api?.extractBrandPath ||
					'/fooconvert/v1/brand-context/extract',
				method: 'POST',
				data:
					mode === 'remote'
						? {
								mode: 'remote',
								url: remoteUrl,
						  }
						: {
								mode: 'local',
						  },
			} );

			const nextBrand = normalizeBrand( response?.brand );

			startTransition( () => {
				setBrand( nextBrand );
				setStatusNotice( {
					status: 'info',
					message:
						mode === 'remote'
							? __(
									'Remote brand extraction completed. The extracted values are ready to review and save.',
									'fooconvert'
							  )
							: __(
									'Brand extraction completed. The extracted values are ready to review and save.',
									'fooconvert'
							  ),
				} );
			} );

			return true;
		} catch ( exception ) {
			setError(
				exception?.message ||
					__( 'Brand extraction failed.', 'fooconvert' )
			);
			return false;
		} finally {
			setExtractingBrand( false );
		}
	};

	const saveBrandProfile = async () => {
		setSavingBrand( true );
		setError( '' );

		try {
			const response = await apiFetch( {
				path: config?.api?.brandPath || '/fooconvert/v1/brand-context',
				method: 'POST',
				data: {
					brand,
				},
			} );

			const nextBrand = normalizeBrand( response?.brand || brand );

			startTransition( () => {
				setBrand( nextBrand );
				setSavedBrandSnapshot( nextBrand );
				setStatusNotice( {
					status: 'success',
					message: __(
						'Brand saved for reuse. The AI builder will now use it as the main styling source.',
						'fooconvert'
					),
				} );
			} );

			return true;
		} catch ( exception ) {
			setError(
				exception?.message ||
					__( 'The brand profile could not be saved.', 'fooconvert' )
			);
			return false;
		} finally {
			setSavingBrand( false );
		}
	};

	const saveAiSettings = async () => {
		if ( ! aiSettings?.canManage ) {
			return;
		}

		setSavingAiSettings( true );
		setError( '' );

		try {
			const response = await apiFetch( {
				path:
					config?.api?.settingsPath ||
					'/fooconvert/v1/ai-popup-builder/settings',
				method: 'POST',
				data: buildAiSettingsPayload( aiSettings, blockCatalog ),
			} );
			const nextSettings = normalizeAiSettings(
				response?.settings || aiSettings,
				blockCatalog
			);

			startTransition( () => {
				setAiSettings( nextSettings );
				setSavedAiSettingsSnapshot( nextSettings );
				setStatusNotice( {
					status: 'success',
					message: __(
						'AI Popup Builder settings saved.',
						'fooconvert'
					),
				} );
			} );
		} catch ( exception ) {
			setError(
				exception?.message ||
					__(
						'The AI Popup Builder settings could not be saved.',
						'fooconvert'
					)
			);
		} finally {
			setSavingAiSettings( false );
		}
	};

	const updateAiSettings = ( updates ) => {
		setAiSettings( ( currentSettings ) => ( {
			...currentSettings,
			...updates,
		} ) );
	};

	const updateSelectedBlockNames = ( updater ) => {
		setAiSettings( ( currentSettings ) => {
			const currentBlockNames = sanitizeSelectedBlockNames(
				currentSettings?.selectedBlockNames,
				blockCatalog
			);
			const nextBlockNames =
				typeof updater === 'function'
					? updater( currentBlockNames )
					: updater;
			const sanitizedBlockNames = sanitizeSelectedBlockNames(
				nextBlockNames,
				blockCatalog,
				false
			);

			if ( sanitizedBlockNames.length === 0 ) {
				return currentSettings;
			}

			return {
				...currentSettings,
				selectedBlockNames: sanitizedBlockNames,
			};
		} );
	};

	const toggleSelectedBlockName = ( blockName ) => {
		const normalizedBlockName = String( blockName || '' ).trim();

		if ( normalizedBlockName.length === 0 || ! aiSettings?.canManage ) {
			return;
		}

		updateSelectedBlockNames( ( currentBlockNames ) => {
			const nextBlockNames = new Set( currentBlockNames );

			if ( nextBlockNames.has( normalizedBlockName ) ) {
				nextBlockNames.delete( normalizedBlockName );
			} else {
				nextBlockNames.add( normalizedBlockName );
			}

			return Array.from( nextBlockNames );
		} );
	};

	const selectFilteredBlocks = () => {
		if ( ! aiSettings?.canManage ) {
			return;
		}

		updateSelectedBlockNames( ( currentBlockNames ) =>
			Array.from(
				new Set( [
					...currentBlockNames,
					...filteredBlockCatalog
						.map( ( block ) => block?.name )
						.filter( Boolean ),
				] )
			)
		);
	};

	const clearFilteredBlocks = () => {
		if ( ! aiSettings?.canManage ) {
			return;
		}

		const filteredBlockNames = new Set(
			filteredBlockCatalog
				.map( ( block ) => block?.name )
				.filter( Boolean )
		);

		updateSelectedBlockNames( ( currentBlockNames ) =>
			currentBlockNames.filter(
				( blockName ) => ! filteredBlockNames.has( blockName )
			)
		);
	};

	const resetDefaultSelectedBlocks = () => {
		if ( ! aiSettings?.canManage ) {
			return;
		}

		updateSelectedBlockNames(
			getDefaultSelectedBlockNames( blockCatalog )
		);
	};

	const loadDebugResponses = async () => {
		if ( ! debugTabAvailable ) {
			return;
		}

		setLoadingDebugResponses( true );
		setDebugResponseError( '' );

		try {
			const response = await apiFetch( {
				path:
					config?.api?.debugResponsesPath ||
					'/fooconvert/v1/ai-popup-builder/debug-responses',
				method: 'GET',
			} );

			startTransition( () => {
				setDebugResponses(
					Array.isArray( response?.responses )
						? response.responses
						: []
				);
			} );
		} catch ( exception ) {
			setDebugResponseError(
				exception?.message ||
					__(
						'The stored AI responses could not be loaded.',
						'fooconvert'
					)
			);
		} finally {
			setLoadingDebugResponses( false );
		}
	};

	useEffect( () => {
		if ( debugTabAvailable ) {
			loadDebugResponses();
		}
	}, [] );

	const requestConfirmation = ( message, options = {} ) =>
		new Promise( ( resolve ) => {
			setConfirmDialog( {
				message,
				title: options?.title || __( 'Confirm action', 'fooconvert' ),
				confirmButtonText:
					options?.confirmButtonText || __( 'Confirm', 'fooconvert' ),
				cancelButtonText:
					options?.cancelButtonText || __( 'Cancel', 'fooconvert' ),
				resolve,
			} );
		} );

	const closeConfirmDialog = ( confirmed ) => {
		const resolve = confirmDialog?.resolve;

		setConfirmDialog( null );

		if ( typeof resolve === 'function' ) {
			resolve( confirmed );
		}
	};

	const clearDebugResponses = async () => {
		if (
			! debugTabAvailable ||
			isClearingDebugResponses ||
			debugResponses.length === 0
		) {
			return;
		}

		const confirmed = await requestConfirmation(
			__(
				'Clear all stored invalid AI popup builder responses?',
				'fooconvert'
			),
			{
				confirmButtonText: __( 'Clear', 'fooconvert' ),
			}
		);
		if ( ! confirmed ) {
			return;
		}

		setClearingDebugResponses( true );
		setDebugResponseError( '' );

		try {
			const response = await apiFetch( {
				path:
					config?.api?.debugResponsesPath ||
					'/fooconvert/v1/ai-popup-builder/debug-responses',
				method: 'DELETE',
			} );

			startTransition( () => {
				setDebugResponses(
					Array.isArray( response?.responses )
						? response.responses
						: []
				);
				setStatusNotice( {
					status: 'success',
					message: __(
						'Stored invalid AI responses cleared.',
						'fooconvert'
					),
				} );
			} );
		} catch ( exception ) {
			setDebugResponseError(
				exception?.message ||
					__(
						'The stored AI responses could not be cleared.',
						'fooconvert'
					)
			);
		} finally {
			setClearingDebugResponses( false );
		}
	};

	const generatePopupImage = async () => {
		if ( ! draft || isSending || ! aiImageGenerationAvailable ) {
			return;
		}

		const prompt =
			mediaInstructions.trim().length > 0
				? mediaInstructions
				: __(
						'Generate a new popup image that fits this popup and incorporate it into the draft.',
						'fooconvert'
				  );

		await sendPrompt( prompt, {
			generateImages: true,
			forceImageGeneration: true,
		} );

		setMediaInstructions( '' );
	};

	const insertMediaIntoDraft = async ( mediaItem ) => {
		if ( ! draft ) {
			return;
		}

		const nextDraft = applyMediaItemToDraft( draft, mediaItem );

		startTransition( () => {
			setDraft( nextDraft );
		} );

		await persistDraft( {
			nextDraft,
		} );
	};

	const deleteMediaItem = async ( mediaItem ) => {
		const mediaId = Number( mediaItem?.id );

		if (
			! Number.isFinite( mediaId ) ||
			mediaId <= 0 ||
			deletingMediaId > 0
		) {
			return;
		}

		const confirmed = await requestConfirmation(
			__(
				'Delete this generated image from the media library?',
				'fooconvert'
			),
			{
				confirmButtonText: __( 'Delete', 'fooconvert' ),
			}
		);
		if ( ! confirmed ) {
			return;
		}

		setDeletingMediaId( mediaId );
		setError( '' );

		try {
			const response = await apiFetch( {
				path: `${
					config?.api?.deleteMediaPath ||
					'/fooconvert/v1/ai-popup-builder/media'
				}/${ mediaId }`,
				method: 'DELETE',
			} );

			const nextMediaItems = Array.isArray( response?.media_items )
				? response.media_items
				: mediaItems.filter(
						( item ) => Number( item?.id ) !== mediaId
				  );
			const nextDraft = draft
				? removeMediaItemFromDraft( draft, mediaItem )
				: null;

			startTransition( () => {
				setMediaItems( nextMediaItems );
				setDraft( nextDraft );
			} );

			if ( nextDraft ) {
				await persistDraft( {
					nextDraft,
					nextMediaItems,
				} );
			}
		} catch ( exception ) {
			setError(
				exception?.message ||
					__(
						'The generated image could not be deleted.',
						'fooconvert'
					)
			);
		} finally {
			setDeletingMediaId( 0 );
		}
	};

	const syncTitleToDraft = async () => {
		if ( ! draft ) {
			return;
		}

		const nextTitle = String( saveTitle || draft.title || '' ).trim();

		if ( nextTitle.length === 0 || nextTitle === draft?.title ) {
			return;
		}

		const nextDraft = {
			...draft,
			title: nextTitle,
		};

		startTransition( () => {
			setDraft( nextDraft );
		} );

		await persistDraft( {
			nextDraft,
			nextTitle,
		} );
	};

	let promptInputHelp;
	if ( ! aiChatAvailable ) {
		promptInputHelp = __(
			'Configure a valid WordPress AI connector before sending requests.',
			'fooconvert'
		);
	} else if ( isLoadingInitialPopup ) {
		promptInputHelp = __(
			'Loading the saved popup before chat starts.',
			'fooconvert'
		);
	} else if ( isExtractingBrand ) {
		promptInputHelp = __(
			'Brand extraction is still running. Wait for it to finish before sending the next request.',
			'fooconvert'
		);
	}

	const settingsContent = (
		<div className={ `${ rootClass }__stack` }>
			{ ! aiSettings?.canManage && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'You can view settings but cannot save defaults.',
						'fooconvert'
					) }
				</Notice>
			) }

			<p className={ `${ rootClass }__muted-copy` }>
				{ __( 'Tune model behavior and block context.', 'fooconvert' ) }
			</p>

			<Card>
				<CardHeader>
					<h3>{ __( 'Current Models', 'fooconvert' ) }</h3>
				</CardHeader>
				<CardBody>
					<div className={ `${ rootClass }__preview-stack` }>
						<BrandPreviewList
							rows={ [
								{
									label: __(
										'Current Text Model',
										'fooconvert'
									),
									value:
										currentTextModel ||
										__(
											'Connector default',
											'fooconvert'
										),
								},
								{
									label: __(
										'Current Image Model',
										'fooconvert'
									),
									value:
										currentImageModel ||
										__( 'None available', 'fooconvert' ),
								},
							] }
						/>
						{ ! currentImageModel && (
							<Notice status="info" isDismissible={ false }>
								{ __(
									'No image generation model is available.',
									'fooconvert'
								) }
							</Notice>
						) }
					</div>
				</CardBody>
			</Card>

			<div className={ `${ rootClass }__field-grid` }>
				<TextControl
					label={ __( 'Override model', 'fooconvert' ) }
					value={ aiSettings?.overrideModel || '' }
					onChange={ ( value ) =>
						updateAiSettings( { overrideModel: value } )
					}
					placeholder={ __(
						'Optional custom model name',
						'fooconvert'
					) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				<TextControl
					label={ __( 'Timeout', 'fooconvert' ) }
					type="number"
					min={ 1 }
					step={ 1 }
					value={ String(
						aiSettings?.timeout ?? aiSettings?.timeoutDefault ?? 45
					) }
					onChange={ ( value ) =>
						updateAiSettings( { timeout: value } )
					}
					help={ __( 'Maximum seconds to wait.', 'fooconvert' ) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				<TextControl
					label={ __( 'Max Tool Calls', 'fooconvert' ) }
					type="number"
					min={ 1 }
					step={ 1 }
					value={ String(
						aiSettings?.maxToolCalls ??
							aiSettings?.maxToolCallsDefault ??
							10
					) }
					onChange={ ( value ) =>
						updateAiSettings( { maxToolCalls: value } )
					}
					help={ __(
						'Increase this if complex prompts stop with the tool-call limit error.',
						'fooconvert'
					) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				<div className={ `${ rootClass }__field-grid-span` }>
					<TextareaControl
						label={ __( 'Disabled Params', 'fooconvert' ) }
						value={ aiSettings?.disabledParamsText || '' }
						onChange={ ( value ) =>
							updateAiSettings( {
								disabledParamsText: value,
								disabledParams:
									normalizeDisabledParams( value ),
							} )
						}
						placeholder={ 'temperature\nresponse_format' }
						help={ __( 'One parameter per line.', 'fooconvert' ) }
						rows={ 8 }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</div>
			</div>

			<div className={ `${ rootClass }__tab-actions` }>
				<Button
					variant="primary"
					onClick={ saveAiSettings }
					disabled={
						isSavingAiSettings ||
						! aiSettingsIsDirty ||
						! aiSettings?.canManage
					}
				>
					{ isSavingAiSettings
						? __( 'Saving…', 'fooconvert' )
						: __( 'Save Settings', 'fooconvert' ) }
				</Button>
			</div>
		</div>
	);

	const debugContent = debugTabAvailable ? (
		<DebugResponseInspector
			responses={ debugResponses }
			currentResponse={ currentResponse }
			isLoading={ isLoadingDebugResponses }
			isClearing={ isClearingDebugResponses }
			error={ debugResponseError }
			onRefresh={ loadDebugResponses }
			onClear={ clearDebugResponses }
		/>
	) : null;

	const renderContextModal = () => {
		if ( ! contextModal ) {
			return null;
		}

		if ( 'brand' === contextModal ) {
			return (
				<BrandContextModal
					isOpen={ true }
					title={ __( 'Brand Context', 'fooconvert' ) }
					rootClass={ rootClass }
					modalClassName={ `${ rootClass }__context-modal ${ rootClass }__context-modal--wide` }
					onClose={ () => setContextModal( '' ) }
					brand={ brand }
					setBrand={ setBrand }
					brandIsDirty={ brandIsDirty }
					isExtractingBrand={ isExtractingBrand }
					isSavingBrand={ isSavingBrand }
					onExtractBrand={ extractBrand }
					onSaveBrand={ saveBrandProfile }
				/>
			);
		}

		if ( 'blocks' === contextModal ) {
			return (
				<Modal
					title={ __( 'Supported Blocks', 'fooconvert' ) }
					onRequestClose={ () => setContextModal( '' ) }
					className={ `${ rootClass }__context-modal ${ rootClass }__context-modal--wide` }
					shouldCloseOnClickOutside={ true }
				>
					<div className={ `${ rootClass }__stack` }>
						<p className={ `${ rootClass }__muted-copy` }>
							{ __(
								'Choose blocks the builder may use.',
								'fooconvert'
							) }
						</p>
						{ ! aiSettings?.canManage && (
							<Notice status="warning" isDismissible={ false }>
								{ __(
									'You can inspect blocks but cannot save defaults.',
									'fooconvert'
								) }
							</Notice>
						) }
						<div className={ `${ rootClass }__context-stat-row` }>
							<button
								type="button"
								className={ `${ rootClass }__context-stat-pill ${
									'all' === blockFilter
										? `${ rootClass }__context-stat-pill--active`
										: ''
								}` }
								onClick={ () => setBlockFilter( 'all' ) }
							>
								<span>{ __( 'Total', 'fooconvert' ) }</span>
								<strong>{ `${ blockSourceCounts.total.selected } / ${ blockSourceCounts.total.available }` }</strong>
							</button>
							<button
								type="button"
								className={ `${ rootClass }__context-stat-pill ${
									'core' === blockFilter
										? `${ rootClass }__context-stat-pill--active`
										: ''
								}` }
								onClick={ () => setBlockFilter( 'core' ) }
							>
								<span>{ __( 'Core', 'fooconvert' ) }</span>
								<strong>{ `${ blockSourceCounts.core.selected } / ${ blockSourceCounts.core.available }` }</strong>
							</button>
							<button
								type="button"
								className={ `${ rootClass }__context-stat-pill ${
									'fooconvert' === blockFilter
										? `${ rootClass }__context-stat-pill--active`
										: ''
								}` }
								onClick={ () => setBlockFilter( 'fooconvert' ) }
							>
								<span>
									{ __( 'Popup Blocks', 'fooconvert' ) }
								</span>
								<strong>{ `${ blockSourceCounts.fooconvert.selected } / ${ blockSourceCounts.fooconvert.available }` }</strong>
							</button>
							<button
								type="button"
								className={ `${ rootClass }__context-stat-pill ${
									'woocommerce' === blockFilter
										? `${ rootClass }__context-stat-pill--active`
										: ''
								}` }
								onClick={ () =>
									setBlockFilter( 'woocommerce' )
								}
							>
								<span>
									{ __( 'WooCommerce', 'fooconvert' ) }
								</span>
								<strong>{ `${ blockSourceCounts.woocommerce.selected } / ${ blockSourceCounts.woocommerce.available }` }</strong>
							</button>
						</div>
						<div
							className={ `${ rootClass }__block-selection-actions` }
						>
							<Button
								variant="secondary"
								onClick={ selectFilteredBlocks }
								disabled={
									! aiSettings?.canManage ||
									filteredSelectedBlockCount ===
										filteredBlockCatalog.length
								}
							>
								{ __( 'Select visible', 'fooconvert' ) }
							</Button>
							<Button
								variant="secondary"
								onClick={ clearFilteredBlocks }
								disabled={
									! aiSettings?.canManage ||
									0 === filteredSelectedBlockCount ||
									filteredSelectedBlockCount >=
										selectedBlockNameSet.size
								}
							>
								{ __( 'Clear visible', 'fooconvert' ) }
							</Button>
							<Button
								variant="tertiary"
								onClick={ resetDefaultSelectedBlocks }
								disabled={ ! aiSettings?.canManage }
							>
								{ __( 'Reset defaults', 'fooconvert' ) }
							</Button>
							<Button
								variant="primary"
								onClick={ saveAiSettings }
								disabled={
									isSavingAiSettings ||
									! aiSettingsIsDirty ||
									! aiSettings?.canManage
								}
							>
								{ isSavingAiSettings
									? __( 'Saving…', 'fooconvert' )
									: __( 'Save selection', 'fooconvert' ) }
							</Button>
						</div>
						<div
							className={ `${ rootClass }__context-list ${ rootClass }__context-list--compact` }
						>
							{ filteredBlockCatalog.map( ( block ) => {
								const blockName = String( block?.name || '' );
								const isSelected =
									selectedBlockNameSet.has( blockName );

								return (
									<Card
										key={ block?.name || block?.label }
										className={ `${ rootClass }__block-card ${
											isSelected
												? ''
												: `${ rootClass }__block-card--unselected`
										}` }
									>
										<CardBody>
											<div
												className={ `${ rootClass }__context-item ${ rootClass }__context-item--compact` }
											>
												<div
													className={ `${ rootClass }__context-item-head ${ rootClass }__context-item-head--stacked` }
												>
													<div
														className={ `${ rootClass }__block-selection-head` }
													>
														<CheckboxControl
															label={
																block?.label ||
																block?.name
															}
															checked={
																isSelected
															}
															onChange={ () =>
																toggleSelectedBlockName(
																	blockName
																)
															}
															disabled={
																! aiSettings?.canManage
															}
															__nextHasNoMarginBottom
														/>
														<p
															className={ `${ rootClass }__context-slug` }
														>
															{ block?.name }
														</p>
													</div>
												</div>
												{ block?.description && (
													<p
														className={ `${ rootClass }__muted-copy` }
													>
														{ truncateText(
															block.description,
															82
														) }
													</p>
												) }
											</div>
										</CardBody>
									</Card>
								);
							} ) }
							{ 0 === filteredBlockCatalog.length && (
								<div
									className={ `${ rootClass }__context-inline-card` }
								>
									<strong>
										{ __(
											'No blocks in this filter',
											'fooconvert'
										) }
									</strong>
									<p>
										{ __(
											'Try another filter.',
											'fooconvert'
										) }
									</p>
								</div>
							) }
						</div>
					</div>
				</Modal>
			);
		}

		if ( 'templates' === contextModal ) {
			return (
				<Modal
					title={ __( 'Structural Templates', 'fooconvert' ) }
					onRequestClose={ () => setContextModal( '' ) }
					className={ `${ rootClass }__context-modal ${ rootClass }__context-modal--wide` }
					shouldCloseOnClickOutside={ true }
				>
					<div className={ `${ rootClass }__stack` }>
						<p className={ `${ rootClass }__muted-copy` }>
							{ __(
								'Optional layout starting points.',
								'fooconvert'
							) }
						</p>
						<div className={ `${ rootClass }__context-stat-row` }>
							<button
								type="button"
								className={ `${ rootClass }__context-stat-pill ${
									'all' === templateFilter
										? `${ rootClass }__context-stat-pill--active`
										: ''
								}` }
								onClick={ () => setTemplateFilter( 'all' ) }
							>
								<span>{ __( 'Total', 'fooconvert' ) }</span>
								<strong>{ templateLibrary.length }</strong>
							</button>
							<button
								type="button"
								className={ `${ rootClass }__context-stat-pill ${
									'popup' === templateFilter
										? `${ rootClass }__context-stat-pill--active`
										: ''
								}` }
								onClick={ () => setTemplateFilter( 'popup' ) }
							>
								<span>{ __( 'Popups', 'fooconvert' ) }</span>
								<strong>{ templateCounts.popup || 0 }</strong>
							</button>
							<button
								type="button"
								className={ `${ rootClass }__context-stat-pill ${
									'flyout' === templateFilter
										? `${ rootClass }__context-stat-pill--active`
										: ''
								}` }
								onClick={ () => setTemplateFilter( 'flyout' ) }
							>
								<span>{ __( 'Flyouts', 'fooconvert' ) }</span>
								<strong>{ templateCounts.flyout || 0 }</strong>
							</button>
							<button
								type="button"
								className={ `${ rootClass }__context-stat-pill ${
									'bar' === templateFilter
										? `${ rootClass }__context-stat-pill--active`
										: ''
								}` }
								onClick={ () => setTemplateFilter( 'bar' ) }
							>
								<span>{ __( 'Bars', 'fooconvert' ) }</span>
								<strong>{ templateCounts.bar || 0 }</strong>
							</button>
						</div>
						<div
							className={ `${ rootClass }__context-list ${ rootClass }__context-list--compact` }
						>
							{ filteredTemplateLibrary.map( ( template ) => (
								<Card
									key={ template?.slug || template?.title }
									className={ `${ rootClass }__template-card` }
								>
									<CardBody>
										<div
											className={ `${ rootClass }__context-item ${ rootClass }__context-item--compact` }
										>
											<div
												className={ `${ rootClass }__context-item-head ${ rootClass }__context-item-head--stacked` }
											>
												<div>
													<h3>
														{ template?.title ||
															template?.slug }
													</h3>
												</div>
												<ContextChipRow
													items={ [
														config?.labels?.[
															normalizePopupType(
																template?.popup_type
															)
														] ||
															template?.popup_type,
													] }
													limit={ 1 }
												/>
											</div>
											{ template?.description && (
												<p
													className={ `${ rootClass }__muted-copy` }
												>
													{ truncateText(
														template.description,
														90
													) }
												</p>
											) }
										</div>
									</CardBody>
								</Card>
							) ) }
							{ 0 === filteredTemplateLibrary.length && (
								<div
									className={ `${ rootClass }__context-inline-card` }
								>
									<strong>
										{ __(
											'No templates in this filter',
											'fooconvert'
										) }
									</strong>
									<p>
										{ __(
											'Try another type.',
											'fooconvert'
										) }
									</p>
								</div>
							) }
						</div>
					</div>
				</Modal>
			);
		}

		if ( 'playbook' === contextModal ) {
			return (
				<Modal
					title={ __( 'Conversion Playbook', 'fooconvert' ) }
					onRequestClose={ () => setContextModal( '' ) }
					className={ `${ rootClass }__context-modal` }
					shouldCloseOnClickOutside={ true }
				>
					<div className={ `${ rootClass }__stack` }>
						<p className={ `${ rootClass }__muted-copy` }>
							{ __(
								'Conversion guidance available to the builder.',
								'fooconvert'
							) }
						</p>
						<Card>
							<CardHeader>
								<h3>{ __( 'Principles', 'fooconvert' ) }</h3>
							</CardHeader>
							<CardBody>
								<ul className={ `${ rootClass }__plain-list` }>
									{ playbookPrinciples.map( ( principle ) => (
										<li key={ principle }>{ principle }</li>
									) ) }
								</ul>
							</CardBody>
						</Card>
						{ playbookAvoid.length > 0 && (
							<Card>
								<CardHeader>
									<h3>{ __( 'Avoid', 'fooconvert' ) }</h3>
								</CardHeader>
								<CardBody>
									<ul
										className={ `${ rootClass }__plain-list` }
									>
										{ playbookAvoid.map( ( item ) => (
											<li key={ item }>{ item }</li>
										) ) }
									</ul>
								</CardBody>
							</Card>
						) }
						<Card>
							<CardHeader>
								<h3>{ __( 'Popup Types', 'fooconvert' ) }</h3>
							</CardHeader>
							<CardBody>
								<div
									className={ `${ rootClass }__context-list` }
								>
									{ Object.entries( playbookPopupTypes ).map(
										( [ popupType, details ] ) => (
											<div
												key={ popupType }
												className={ `${ rootClass }__context-inline-card` }
											>
												<strong>
													{ config?.labels?.[
														normalizePopupType(
															popupType
														)
													] || popupType }
												</strong>
												{ getPlaybookStringEntries(
													details
												).map( ( [ key, value ] ) => (
													<p key={ key }>
														<strong>
															{ playbookPopupFieldLabels[
																key
															] ||
																sprintf(
																	/* translators: %s is a conversion playbook field label. */
																	__(
																		'%s:',
																		'fooconvert'
																	),
																	formatPlaybookKey(
																		key
																	)
																) }
														</strong>{ ' ' }
														{ value }
													</p>
												) ) }
											</div>
										)
									) }
								</div>
							</CardBody>
						</Card>
						<Card>
							<CardHeader>
								<h3>{ __( 'Copy Tactics', 'fooconvert' ) }</h3>
							</CardHeader>
							<CardBody>
								<ul className={ `${ rootClass }__plain-list` }>
									{ playbookCopyTactics.map( ( tactic ) => (
										<li key={ tactic }>{ tactic }</li>
									) ) }
								</ul>
							</CardBody>
						</Card>
						{ playbookProofCount > 0 && (
							<Card>
								<CardHeader>
									<h3>
										{ __(
											'Proof Hierarchy',
											'fooconvert'
										) }
									</h3>
								</CardHeader>
								<CardBody>
									{ playbookProofGuidance.length > 0 && (
										<p
											className={ `${ rootClass }__muted-copy` }
										>
											{ playbookProofGuidance }
										</p>
									) }
									<GuidanceList
										title={ __(
											'Ranked proof types',
											'fooconvert'
										) }
										items={ playbookProofRanked }
									/>
								</CardBody>
							</Card>
						) }
						{ playbookExampleCount > 0 && (
							<Card>
								<CardHeader>
									<h3>{ __( 'Examples', 'fooconvert' ) }</h3>
								</CardHeader>
								<CardBody>
									<PlaybookExampleList
										examples={ playbookExamples }
									/>
								</CardBody>
							</Card>
						) }
					</div>
				</Modal>
			);
		}

		if ( 'system-prompt' === contextModal ) {
			return (
				<Modal
					title={ __( 'System Prompt', 'fooconvert' ) }
					onRequestClose={ () => setContextModal( '' ) }
					className={ `${ rootClass }__context-modal` }
					shouldCloseOnClickOutside={ true }
				>
					<div className={ `${ rootClass }__stack` }>
						<p className={ `${ rootClass }__muted-copy` }>
							{ __(
								'Default instructions for generated popups.',
								'fooconvert'
							) }
						</p>
						<ReadOnlyTextField
							label={ __(
								'Builder system instruction',
								'fooconvert'
							) }
							value={ String( config?.systemPrompt || '' ) }
							rows={ 20 }
						/>
					</div>
				</Modal>
			);
		}

		if ( 'abilities' === contextModal ) {
			return (
				<Modal
					title={ __( 'Abilities', 'fooconvert' ) }
					onRequestClose={ () => setContextModal( '' ) }
					className={ `${ rootClass }__context-modal` }
					shouldCloseOnClickOutside={ true }
				>
					<div className={ `${ rootClass }__stack` }>
						<p className={ `${ rootClass }__muted-copy` }>
							{ __(
								'Tools available during generation.',
								'fooconvert'
							) }
						</p>
						<BrandPreviewList
							rows={ [
								{
									label: __( 'Abilities API', 'fooconvert' ),
									value: config?.abilitiesAvailable
										? __( 'Available', 'fooconvert' )
										: __( 'Unavailable', 'fooconvert' ),
								},
								{
									label: __( 'Allowed tools', 'fooconvert' ),
									value: String( abilityNames.length ),
								},
							] }
						/>
						<div className={ `${ rootClass }__context-list` }>
							{ abilityNames.map( ( ability ) => (
								<div
									key={ ability }
									className={ `${ rootClass }__context-inline-card` }
								>
									<strong>{ ability }</strong>
								</div>
							) ) }
						</div>
					</div>
				</Modal>
			);
		}

		if ( 'request' === contextModal ) {
			return (
				<Modal
					title={ __( 'Current Request Context', 'fooconvert' ) }
					onRequestClose={ () => setContextModal( '' ) }
					className={ `${ rootClass }__context-modal ${ rootClass }__context-modal--wide` }
					shouldCloseOnClickOutside={ true }
				>
					<div className={ `${ rootClass }__stack` }>
						<p className={ `${ rootClass }__muted-copy` }>
							{ __(
								'Data sent with the next chat message.',
								'fooconvert'
							) }
						</p>
						<BrandPreviewList rows={ liveRequestSummaryRows } />
						<ReadOnlyTextField
							label={ __( 'Conversation', 'fooconvert' ) }
							value={
								conversationPayloadMessages.length > 0
									? conversationPayloadMessages
											.map(
												( message ) =>
													`[${ message.role }] ${ message.content }`
											)
											.join( '\n\n' )
									: __( 'No messages yet.', 'fooconvert' )
							}
							rows={ 14 }
						/>
						<ReadOnlyTextField
							label={ __(
								'Current popup draft JSON',
								'fooconvert'
							) }
							value={
								draft
									? formatJsonValue( draft )
									: __( 'No popup draft yet.', 'fooconvert' )
							}
							rows={ 16 }
						/>
						<ReadOnlyTextField
							label={ __( 'Current media JSON', 'fooconvert' ) }
							value={
								mediaItems.length > 0
									? formatJsonValue( mediaItems )
									: __(
											'No generated media yet.',
											'fooconvert'
									  )
							}
							rows={ 12 }
						/>
					</div>
				</Modal>
			);
		}

		return null;
	};

	return (
		<div className={ rootClass }>
			{ confirmDialog && (
				<Modal
					title={ confirmDialog.title }
					onRequestClose={ () => closeConfirmDialog( false ) }
					className={ `${ rootClass }__confirm-modal` }
				>
					<div className={ `${ rootClass }__stack` }>
						<p>{ confirmDialog.message }</p>
						<div className={ `${ rootClass }__inline-actions` }>
							<Button
								variant="secondary"
								onClick={ () => closeConfirmDialog( false ) }
							>
								{ confirmDialog.cancelButtonText }
							</Button>
							<Button
								variant="primary"
								isDestructive
								onClick={ () => closeConfirmDialog( true ) }
							>
								{ confirmDialog.confirmButtonText }
							</Button>
						</div>
					</div>
				</Modal>
			) }

			<Card className={ `${ rootClass }__header-card` }>
				<CardBody>
					<div className={ `${ rootClass }__header` }>
						<div className={ `${ rootClass }__header-main` }>
							<h1>{ __( 'AI Popup Builder', 'fooconvert' ) }</h1>
							<p>
								{ __(
									'Create a draft popup from your brand, offer, audience, and trigger.',
									'fooconvert'
								) }
							</p>
						</div>

						{ ( isLoadingInitialPopup ||
							isSavingDraft ||
							savedPopup?.postId ) && (
							<div className={ `${ rootClass }__header-status` }>
								{ isLoadingInitialPopup
									? __( 'Loading popup…', 'fooconvert' )
									: isSavingDraft
									? savedPopupUpdatesExisting
										? __( 'Saving popup…', 'fooconvert' )
										: __( 'Saving draft…', 'fooconvert' )
									: savedPopupUpdatesExisting
									? __(
											'Popup ready for preview and editing.',
											'fooconvert'
									  )
									: __(
											'Draft ready for preview and editing.',
											'fooconvert'
									  ) }
							</div>
						) }
					</div>
				</CardBody>
			</Card>

			{ ! aiChatAvailable && (
				<Notice status="warning" isDismissible={ false }>
					<div className={ `${ rootClass }__connection-notice` }>
						<span>{ aiUnavailableMessage }</span>
						{ aiUnavailableActionUrl && (
							<Button
								variant="secondary"
								href={ aiUnavailableActionUrl }
							>
								{ aiUnavailableActionLabel }
							</Button>
						) }
					</div>
				</Notice>
			) }

			{ error && (
				<Notice
					status="error"
					isDismissible={ true }
					onRemove={ () => setError( '' ) }
				>
					{ error }
				</Notice>
			) }

			{ statusNotice?.message && (
				<Notice
					status={ statusNotice.status || 'info' }
					isDismissible={ true }
					onRemove={ () => setStatusNotice( null ) }
				>
					{ statusNotice.message }
				</Notice>
			) }

			<Card className={ `${ rootClass }__tabs-card` }>
				<CardBody>
					<TabPanel
						className={ `${ rootClass }__tabs` }
						activeClass="is-active"
						initialTabName={ initialBuilderTab }
						tabs={ tabDefinitions }
					>
						{ ( tab ) => (
							<div className={ `${ rootClass }__tab-panel` }>
								{ tab.name === 'context' && (
									<div className={ `${ rootClass }__stack` }>
										<div
											className={ `${ rootClass }__tab-intro` }
										>
											<div>
												<h2>
													{ __(
														'AI Context',
														'fooconvert'
													) }
												</h2>
												<p>
													{ __(
														'Review what the builder can use.',
														'fooconvert'
													) }
												</p>
											</div>
										</div>
										<div
											className={ `${ rootClass }__context-grid` }
										>
											<ContextSummaryCard
												title={ __(
													'Brand',
													'fooconvert'
												) }
												summary={ __(
													'Brand details sent with each request.',
													'fooconvert'
												) }
												onOpen={ () =>
													setContextModal( 'brand' )
												}
												preview={
													<div
														className={ `${ rootClass }__preview-stack` }
													>
														<BrandPreviewList
															rows={ [
																{
																	label: __(
																		'Overview',
																		'fooconvert'
																	),
																	value:
																		truncateText(
																			brand?.brandOverview,
																			72
																		) ||
																		__(
																			'Not set',
																			'fooconvert'
																		),
																},
															] }
														/>
														<div
															className={ `${ rootClass }__swatch-row ${ rootClass }__swatch-row--compact` }
														>
															{ brandPalette
																.slice( 0, 3 )
																.map(
																	(
																		color
																	) => (
																		<div
																			key={
																				color.label
																			}
																			className={ `${ rootClass }__swatch-chip` }
																		>
																			<span
																				aria-hidden="true"
																				style={ {
																					background:
																						color.value,
																				} }
																			/>
																			<strong>
																				{
																					color.label
																				}
																			</strong>
																		</div>
																	)
																) }
														</div>
													</div>
												}
											/>

											<ContextSummaryCard
												title={ __(
													'Blocks',
													'fooconvert'
												) }
												summary={ __(
													'Blocks the builder may use.',
													'fooconvert'
												) }
												onOpen={ () => {
													setBlockFilter( 'all' );
													setContextModal( 'blocks' );
												} }
												preview={
													<div
														className={ `${ rootClass }__preview-stack` }
													>
														<BrandPreviewList
															rows={ [
																{
																	label: __(
																		'Total',
																		'fooconvert'
																	),
																	value: `${ blockSourceCounts.total.selected } / ${ blockSourceCounts.total.available }`,
																},
																{
																	label: __(
																		'Core',
																		'fooconvert'
																	),
																	value: `${ blockSourceCounts.core.selected } / ${ blockSourceCounts.core.available }`,
																},
																{
																	label: __(
																		'Popup',
																		'fooconvert'
																	),
																	value: `${ blockSourceCounts.fooconvert.selected } / ${ blockSourceCounts.fooconvert.available }`,
																},
																{
																	label: __(
																		'WooCommerce',
																		'fooconvert'
																	),
																	value: `${ blockSourceCounts.woocommerce.selected } / ${ blockSourceCounts.woocommerce.available }`,
																},
															] }
														/>
													</div>
												}
											/>

											<ContextSummaryCard
												title={ __(
													'Templates',
													'fooconvert'
												) }
												summary={ __(
													'Optional layout starting points.',
													'fooconvert'
												) }
												onOpen={ () => {
													setTemplateFilter( 'all' );
													setContextModal(
														'templates'
													);
												} }
												preview={
													<div
														className={ `${ rootClass }__preview-stack` }
													>
														<BrandPreviewList
															rows={ [
																{
																	label: __(
																		'Total',
																		'fooconvert'
																	),
																	value: String(
																		templateLibrary.length
																	),
																},
																{
																	label: __(
																		'Bars',
																		'fooconvert'
																	),
																	value: String(
																		templateCounts.bar ||
																			0
																	),
																},
																{
																	label: __(
																		'Flyouts',
																		'fooconvert'
																	),
																	value: String(
																		templateCounts.flyout ||
																			0
																	),
																},
																{
																	label: __(
																		'Overlays',
																		'fooconvert'
																	),
																	value: String(
																		templateCounts.popup ||
																			0
																	),
																},
															] }
														/>
													</div>
												}
											/>

											<ContextSummaryCard
												title={ __(
													'Playbook',
													'fooconvert'
												) }
												summary={ __(
													'Conversion guidance available to the builder.',
													'fooconvert'
												) }
												onOpen={ () =>
													setContextModal(
														'playbook'
													)
												}
												preview={
													<div
														className={ `${ rootClass }__preview-stack` }
													>
														<BrandPreviewList
															rows={ [
																{
																	label: __(
																		'Principles',
																		'fooconvert'
																	),
																	value: String(
																		playbookPrinciples.length
																	),
																},
																{
																	label: __(
																		'Avoid',
																		'fooconvert'
																	),
																	value: String(
																		playbookAvoid.length
																	),
																},
																{
																	label: __(
																		'Popup types',
																		'fooconvert'
																	),
																	value: String(
																		Object.keys(
																			playbookPopupTypes
																		).length
																	),
																},
																{
																	label: __(
																		'Copy tactics',
																		'fooconvert'
																	),
																	value: String(
																		playbookCopyTactics.length
																	),
																},
																{
																	label: __(
																		'Proof hierarchy',
																		'fooconvert'
																	),
																	value: String(
																		playbookProofCount
																	),
																},
																{
																	label: __(
																		'Examples',
																		'fooconvert'
																	),
																	value: String(
																		playbookExampleCount
																	),
																},
															] }
														/>
													</div>
												}
											/>

											<ContextSummaryCard
												title={ __(
													'System Prompt',
													'fooconvert'
												) }
												summary={ __(
													'Default builder instructions.',
													'fooconvert'
												) }
												onOpen={ () =>
													setContextModal(
														'system-prompt'
													)
												}
												preview={
													<ContextCodePreview
														content={ truncateText(
															String(
																config?.systemPrompt ||
																	''
															),
															140
														) }
													/>
												}
											/>

											<ContextSummaryCard
												title={ __(
													'Abilities',
													'fooconvert'
												) }
												summary={ __(
													'Tools available during generation.',
													'fooconvert'
												) }
												onOpen={ () =>
													setContextModal(
														'abilities'
													)
												}
												preview={
													<div
														className={ `${ rootClass }__preview-stack` }
													>
														<BrandPreviewList
															rows={ [
																{
																	label: __(
																		'Tools',
																		'fooconvert'
																	),
																	value: config?.abilitiesAvailable
																		? String(
																				abilityNames.length
																		  )
																		: __(
																				'Unavailable',
																				'fooconvert'
																		  ),
																},
															] }
														/>
														<ContextChipRow
															items={
																abilityPreviewLabels
															}
															limit={ 4 }
														/>
													</div>
												}
											/>

											<ContextSummaryCard
												title={ __(
													'Current Request',
													'fooconvert'
												) }
												summary={ __(
													'Data sent with the next chat message.',
													'fooconvert'
												) }
												onOpen={ () =>
													setContextModal( 'request' )
												}
												preview={
													<div
														className={ `${ rootClass }__preview-stack` }
													>
														<BrandPreviewList
															rows={ liveRequestSummaryRows.slice(
																0,
																3
															) }
														/>
													</div>
												}
											/>
										</div>
									</div>
								) }

								{ tab.name === 'chat' && (
									<div
										className={ `${ rootClass }__chat-grid` }
									>
										<Card>
											<CardHeader>
												<Flex
													justify="space-between"
													align="center"
												>
													<FlexBlock>
														<div>
															<h2>
																{ __(
																	'Chat Builder',
																	'fooconvert'
																) }
															</h2>
															<p
																className={ `${ rootClass }__muted-copy` }
															>
																{ __(
																	'Include the goal, audience, offer, popup type, trigger, and tone.',
																	'fooconvert'
																) }
															</p>
														</div>
													</FlexBlock>
												</Flex>
											</CardHeader>
											<CardBody>
												<div
													className={ `${ rootClass }__messages` }
												>
													{ messages.length === 0 ? (
														<div
															className={ `${ rootClass }__empty-state` }
														>
															<p>
																{ __(
																	'Start with a clear popup brief or choose a suggestion from the sidebar.',
																	'fooconvert'
																) }
															</p>
														</div>
													) : (
														<Fragment>
															{ messages.map(
																(
																	message,
																	index
																) => (
																	<MessageBubble
																		key={ `${ message.role }-${ index }` }
																		message={
																			message
																		}
																	/>
																)
															) }
															{ isSending && (
																<AssistantRunMessage
																	activityLog={
																		displayActivityLog
																	}
																	mode={
																		activityMode
																	}
																	activeIndex={
																		pendingActivityIndex
																	}
																	reasoningSummary={
																		liveReasoningSummary
																	}
																/>
															) }
														</Fragment>
													) }
													<div ref={ chatEndRef } />
												</div>

												<form
													className={ `${ rootClass }__composer` }
													onSubmit={ handleSubmit }
												>
													<TextareaControl
														label={ __(
															'Describe the popup you want',
															'fooconvert'
														) }
														value={ input }
														onChange={ setInput }
														disabled={
															! aiChatAvailable ||
															chatIsBusy
														}
														help={ promptInputHelp }
														__nextHasNoMarginBottom
														__next40pxDefaultSize
														onKeyDown={ async (
															event
														) => {
															if (
																( event.metaKey ||
																	event.ctrlKey ) &&
																event.key ===
																	'Enter'
															) {
																event.preventDefault();
																if (
																	! chatIsBusy
																) {
																	await sendPrompt(
																		input
																	);
																}
															}
														} }
													/>

													<CheckboxControl
														label={ __(
															'Generate AI Images',
															'fooconvert'
														) }
														checked={
															generateImagesOnSubmit
														}
														onChange={
															setGenerateImagesOnSubmit
														}
														disabled={
															chatIsBusy ||
															! aiImageGenerationAvailable
														}
														help={
															aiImageGenerationAvailable
																? undefined
																: __(
																		'Requires media upload permission and image support.',
																		'fooconvert'
																  )
														}
														__nextHasNoMarginBottom
													/>

													<div
														className={ `${ rootClass }__composer-actions` }
													>
														<div
															className={ `${ rootClass }__prompt-strip` }
														>
															{ suggestedPrompts.map(
																( prompt ) => (
																	<PromptChip
																		key={
																			prompt
																		}
																		label={
																			prompt
																		}
																		onClick={ () =>
																			sendPrompt(
																				prompt
																			)
																		}
																		disabled={
																			! aiChatAvailable ||
																			chatIsBusy
																		}
																	/>
																)
															) }
														</div>
														<div
															className={ `${ rootClass }__composer-primary-actions` }
														>
															{ draftActionsVisible && (
																<Fragment>
																	<Button
																		variant="secondary"
																		href={
																			previewUrl ||
																			undefined
																		}
																		target="_blank"
																		rel="noreferrer"
																		icon={
																			external
																		}
																		disabled={
																			! previewUrl ||
																			isSavingDraft
																		}
																	>
																		{ __(
																			'Open Preview',
																			'fooconvert'
																		) }
																	</Button>
																	<Button
																		variant="secondary"
																		href={
																			savedPopup?.editUrl ||
																			undefined
																		}
																		target="_blank"
																		rel="noreferrer"
																		icon={
																			external
																		}
																		disabled={
																			! savedPopup?.editUrl ||
																			isSavingDraft
																		}
																	>
																		{ savedPopupUpdatesExisting
																			? __(
																					'Edit Popup',
																					'fooconvert'
																			  )
																			: __(
																					'Edit Draft',
																					'fooconvert'
																			  ) }
																	</Button>
																</Fragment>
															) }
															<Button
																variant="primary"
																type="submit"
																disabled={
																	chatIsBusy ||
																	! aiChatAvailable ||
																	input.trim()
																		.length ===
																		0
																}
															>
																{ __(
																	'Send',
																	'fooconvert'
																) }
															</Button>
														</div>
													</div>
												</form>
											</CardBody>
										</Card>

										<Card>
											<CardHeader>
												<h2>
													{ __(
														'Suggestions',
														'fooconvert'
													) }
												</h2>
											</CardHeader>
											<CardBody>
												<p
													className={ `${ rootClass }__muted-copy` }
												>
													{ __(
														'Use these as a starting point, then refine the popup in chat.',
														'fooconvert'
													) }
												</p>
												<div
													className={ `${ rootClass }__starter-list` }
												>
													{ suggestionPrompts.map(
														( suggestion ) => (
															<button
																key={
																	suggestion.text
																}
																type="button"
																className={ `${ rootClass }__starter-card` }
																onClick={ () =>
																	sendPrompt(
																		suggestion.text
																	)
																}
																disabled={
																	! aiChatAvailable ||
																	chatIsBusy
																}
															>
																<span>
																	{
																		suggestion.text
																	}
																</span>
																{ Array.isArray(
																	suggestion.tags
																) &&
																	suggestion
																		.tags
																		.length >
																		0 && (
																		<span
																			className={ `${ rootClass }__starter-tags` }
																		>
																			{ suggestion.tags.map(
																				(
																					tag
																				) => (
																					<small
																						key={
																							tag
																						}
																					>
																						{
																							tag
																						}
																					</small>
																				)
																			) }
																		</span>
																	) }
															</button>
														)
													) }
												</div>
											</CardBody>
										</Card>
									</div>
								) }

								{ tab.name === 'settings' && settingsContent }

								{ tab.name === 'debug' && debugContent }

								{ tab.name === 'details' && (
									<div className={ `${ rootClass }__stack` }>
										<div
											className={ `${ rootClass }__tab-intro` }
										>
											<div>
												<h2>
													{ __(
														'Popup Details',
														'fooconvert'
													) }
												</h2>
												<p>
													{ __(
														'Review the saved draft, strategy, and generated HTML.',
														'fooconvert'
													) }
												</p>
											</div>
										</div>

										{ ! draft ? (
											<Notice
												status="info"
												isDismissible={ false }
											>
												{ __(
													'Generate a popup in Chat first.',
													'fooconvert'
												) }
											</Notice>
										) : (
											<div
												className={ `${ rootClass }__details-grid` }
											>
												<Card>
													<CardHeader>
														<h3>
															{ __(
																'Strategy Summary',
																'fooconvert'
															) }
														</h3>
													</CardHeader>
													<CardBody>
														<TextControl
															label={ __(
																'Draft title',
																'fooconvert'
															) }
															value={ saveTitle }
															onChange={ (
																value
															) => {
																setTitleTouched(
																	true
																);
																setSaveTitle(
																	value
																);
															} }
															onBlur={
																syncTitleToDraft
															}
															help={ __(
																'Title changes sync on blur.',
																'fooconvert'
															) }
															__nextHasNoMarginBottom
															__next40pxDefaultSize
														/>

														<div
															className={ `${ rootClass }__summary` }
														>
															{ summaryRows.map(
																( row ) => (
																	<div
																		key={
																			row.label
																		}
																		className={ `${ rootClass }__summary-row` }
																	>
																		<span>
																			{
																				row.label
																			}
																		</span>
																		<strong>
																			{
																				row.value
																			}
																		</strong>
																	</div>
																)
															) }
														</div>

														{ draft?.template_slug &&
															templatesBySlug?.[
																draft
																	.template_slug
															] && (
																<div
																	className={ `${ rootClass }__template-chip` }
																>
																	{ sprintf(
																		/* translators: %s is the template title used as a structural guide. */
																		__(
																			'Structural template guide: %s',
																			'fooconvert'
																		),
																		templatesBySlug[
																			draft
																				.template_slug
																		].title
																	) }
																</div>
															) }
													</CardBody>
												</Card>

												<Card>
													<CardHeader>
														<h3>
															{ __(
																'Conversion Checklist',
																'fooconvert'
															) }
														</h3>
													</CardHeader>
													<CardBody>
														{ validation ? (
															<ConversionChecklist
																validation={
																	validation
																}
															/>
														) : (
															<p
																className={ `${ rootClass }__muted-copy` }
															>
																{ __(
																	'Validation appears after scoring.',
																	'fooconvert'
																) }
															</p>
														) }
													</CardBody>
												</Card>

												<Card>
													<CardHeader>
														<h3>
															{ __(
																'AI Guidance',
																'fooconvert'
															) }
														</h3>
													</CardHeader>
													<CardBody>
														<GuidanceList
															title={ __(
																'Why this should convert',
																'fooconvert'
															) }
															items={
																conversionRationale
															}
														/>
														<GuidanceList
															title={ __(
																'Implementation notes',
																'fooconvert'
															) }
															items={
																implementationNotes
															}
														/>

														{ conversionRationale.length ===
															0 &&
															implementationNotes.length ===
																0 && (
																<p
																	className={ `${ rootClass }__muted-copy` }
																>
																	{ __(
																		'Ask the AI to explain or refine the strategy.',
																		'fooconvert'
																	) }
																</p>
															) }
													</CardBody>
												</Card>

												<Card>
													<CardHeader>
														<Flex
															justify="space-between"
															align="center"
														>
															<FlexBlock>
																<h3>
																	{ __(
																		'Popup HTML',
																		'fooconvert'
																	) }
																</h3>
															</FlexBlock>
															<Button
																variant="secondary"
																icon={
																	copySmall
																}
																onClick={
																	copyMarkup
																}
																disabled={
																	! generatedMarkup
																}
															>
																{ __(
																	'Copy',
																	'fooconvert'
																) }
															</Button>
														</Flex>
													</CardHeader>
													<CardBody>
														<TextareaControl
															value={
																generatedMarkup
															}
															onChange={ () => {} }
															readOnly
															rows={ 14 }
															__nextHasNoMarginBottom
															__next40pxDefaultSize
														/>
													</CardBody>
												</Card>
											</div>
										) }
									</div>
								) }

								{ tab.name === 'media' && (
									<div className={ `${ rootClass }__stack` }>
										<div
											className={ `${ rootClass }__tab-intro` }
										>
											<div>
												<h2>
													{ __(
														'Media',
														'fooconvert'
													) }
												</h2>
												<p>
													{ __(
														'Generate images for the current popup draft.',
														'fooconvert'
													) }
												</p>
											</div>
											<div
												className={ `${ rootClass }__tab-actions` }
											>
												<Button
													variant="secondary"
													onClick={
														generatePopupImage
													}
													disabled={
														! draft ||
														isSending ||
														! aiImageGenerationAvailable
													}
												>
													{ __(
														'Generate Image',
														'fooconvert'
													) }
												</Button>
											</div>
										</div>

										<Card>
											<CardBody>
												{ config?.canUploadMedia ? (
													<Fragment>
														<TextControl
															label={ __(
																'New image direction',
																'fooconvert'
															) }
															value={
																mediaInstructions
															}
															onChange={
																setMediaInstructions
															}
															help={ __(
																'Describe the next popup image.',
																'fooconvert'
															) }
															disabled={
																isSending ||
																! draft ||
																! aiImageGenerationAvailable
															}
															__nextHasNoMarginBottom
															__next40pxDefaultSize
														/>

														{ mediaItems.length >
														0 ? (
															<div
																className={ `${ rootClass }__media-grid` }
															>
																{ mediaItems.map(
																	(
																		mediaItem
																	) => (
																		<div
																			key={
																				mediaItem.id ||
																				mediaItem.url
																			}
																			className={ `${ rootClass }__media-card` }
																		>
																			<div
																				className={ `${ rootClass }__media-preview` }
																			>
																				<img
																					src={
																						mediaItem.previewUrl ||
																						mediaItem.url
																					}
																					alt={
																						mediaItem.alt ||
																						mediaItem.title ||
																						''
																					}
																				/>
																			</div>
																			<div
																				className={ `${ rootClass }__media-body` }
																			>
																				<strong>
																					{ mediaItem.title ||
																						__(
																							'Generated popup image',
																							'fooconvert'
																						) }
																				</strong>
																				{ mediaItem.prompt && (
																					<p>
																						{ truncateText(
																							mediaItem.prompt
																						) }
																					</p>
																				) }
																				<div
																					className={ `${ rootClass }__media-actions` }
																				>
																					<Button
																						variant="secondary"
																						onClick={ () =>
																							insertMediaIntoDraft(
																								mediaItem
																							)
																						}
																						disabled={
																							! draft ||
																							isSavingDraft
																						}
																					>
																						{ __(
																							'Use In Popup',
																							'fooconvert'
																						) }
																					</Button>
																					{ mediaItem.editUrl && (
																						<Button
																							variant="tertiary"
																							href={
																								mediaItem.editUrl
																							}
																							icon={
																								external
																							}
																						>
																							{ __(
																								'Edit',
																								'fooconvert'
																							) }
																						</Button>
																					) }
																					<Button
																						variant="tertiary"
																						isDestructive
																						onClick={ () =>
																							deleteMediaItem(
																								mediaItem
																							)
																						}
																						disabled={
																							deletingMediaId ===
																							Number(
																								mediaItem.id
																							)
																						}
																					>
																						{ deletingMediaId ===
																						Number(
																							mediaItem.id
																						)
																							? __(
																									'Deleting…',
																									'fooconvert'
																							  )
																							: __(
																									'Delete',
																									'fooconvert'
																							  ) }
																					</Button>
																				</div>
																			</div>
																		</div>
																	)
																) }
															</div>
														) : (
															<p
																className={ `${ rootClass }__muted-copy` }
															>
																{ draft
																	? __(
																			'Generate an image or let chat create one.',
																			'fooconvert'
																	  )
																	: __(
																			'Generate a popup draft first.',
																			'fooconvert'
																	  ) }
															</p>
														) }
													</Fragment>
												) : (
													<p
														className={ `${ rootClass }__muted-copy` }
													>
														{ __(
															'This account cannot upload media.',
															'fooconvert'
														) }
													</p>
												) }
											</CardBody>
										</Card>
									</div>
								) }
							</div>
						) }
					</TabPanel>
				</CardBody>
			</Card>

			{ renderContextModal() }
		</div>
	);
};
