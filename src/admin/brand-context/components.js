import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Flex,
	FlexBlock,
	Modal,
	Notice,
	SelectControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const cloneDeep = ( value ) => {
	if ( Array.isArray( value ) ) {
		return value.map( cloneDeep );
	}

	if ( value && typeof value === 'object' ) {
		return Object.entries( value ).reduce(
			( nextValue, [ key, childValue ] ) => ( {
				...nextValue,
				[ key ]: cloneDeep( childValue ),
			} ),
			{}
		);
	}

	return value;
};

export const isPlainObject = ( value ) =>
	!! value && typeof value === 'object' && ! Array.isArray( value );

export const createEmptyBrand = () => ( {
	brandOverview: '',
	colorScheme: 'light',
	colors: {
		primary: '',
		secondary: '',
		accent: '',
		background: '',
		textPrimary: '',
		textSecondary: '',
	},
	typography: {
		fontFamilies: {
			primary: '',
			heading: '',
		},
		fontSizes: {
			h1: {
				value: '',
				min: '',
				max: '',
			},
			h2: {
				value: '',
				min: '',
				max: '',
			},
			h3: {
				value: '',
				min: '',
				max: '',
			},
			body: {
				value: '',
				min: '',
				max: '',
			},
		},
		fontWeights: {
			regular: 400,
			medium: 500,
			bold: 700,
		},
	},
	spacing: {
		baseUnit: '',
		borderRadius: '',
	},
	components: {
		buttonPrimary: {
			background: '',
			textColor: '',
			borderRadius: '',
		},
		buttonSecondary: {
			background: '',
			textColor: '',
			borderColor: '',
			borderRadius: '',
		},
	},
} );

const deepMerge = ( base, overrides ) => {
	if ( Array.isArray( overrides ) ) {
		return overrides.map( cloneDeep );
	}

	if ( ! isPlainObject( base ) || ! isPlainObject( overrides ) ) {
		return cloneDeep( overrides );
	}

	const merged = { ...cloneDeep( base ) };

	Object.entries( overrides ).forEach( ( [ key, value ] ) => {
		if ( isPlainObject( value ) && isPlainObject( merged[ key ] ) ) {
			merged[ key ] = deepMerge( merged[ key ], value );
			return;
		}

		merged[ key ] = cloneDeep( value );
	} );

	return merged;
};

export const normalizeBrand = ( brand, defaultBrand = {} ) => {
	let nextBrand = deepMerge(
		createEmptyBrand(),
		isPlainObject( defaultBrand ) ? defaultBrand : {}
	);

	if ( isPlainObject( brand ) ) {
		nextBrand = deepMerge( nextBrand, brand );
	}

	return nextBrand;
};

export const serializeComparable = ( value, defaultBrand = {} ) =>
	JSON.stringify( normalizeBrand( value, defaultBrand ) );

const setNestedValue = ( source, path, value ) => {
	const nextValue = cloneDeep( source );
	const keys = String( path || '' )
		.split( '.' )
		.map( ( segment ) => segment.trim() )
		.filter( Boolean );

	if ( keys.length === 0 ) {
		return nextValue;
	}

	let current = nextValue;

	keys.forEach( ( key, index ) => {
		if ( index === keys.length - 1 ) {
			current[ key ] = value;
			return;
		}

		if ( ! isPlainObject( current[ key ] ) ) {
			current[ key ] = {};
		}

		current = current[ key ];
	} );

	return nextValue;
};

const getPreviewValue = ( value, fallback = __( 'Not set', 'fooconvert' ) ) => {
	const text = String( value || '' ).trim();
	return text.length > 0 ? text : fallback;
};

const truncateText = ( value, maxLength = 160 ) => {
	const text = String( value || '' ).trim();

	if ( text.length <= maxLength ) {
		return text;
	}

	return `${ text.slice( 0, maxLength - 1 ).trim() }...`;
};

const getColorSchemeLabel = ( value ) =>
	value === 'dark' ? __( 'Dark', 'fooconvert' ) : __( 'Light', 'fooconvert' );

const getButtonPreviewStyle = ( button, fallbackBorderColor ) => {
	const borderColor =
		button?.borderColor ||
		button?.background ||
		fallbackBorderColor ||
		'#1d2327';

	return {
		background: button?.background || 'transparent',
		color: button?.textColor || '#1d2327',
		borderRadius: button?.borderRadius || '999px',
		border: `1px solid ${ borderColor }`,
	};
};

const createBrandSectionState = () => ( {
	overview: false,
	palette: false,
	typography: false,
	controls: false,
} );

export const BrandPreviewList = ( {
	rows,
	rootClass = 'fc-brand-context',
} ) => {
	const items = Array.isArray( rows )
		? rows.filter( ( row ) => row?.label )
		: [];

	if ( items.length === 0 ) {
		return null;
	}

	return (
		<div className={ `${ rootClass }__preview-list` }>
			{ items.map( ( row ) => (
				<div
					key={ row.label }
					className={ `${ rootClass }__preview-row` }
				>
					<span>{ row.label }</span>
					<strong>{ row.value }</strong>
				</div>
			) ) }
		</div>
	);
};

const BrandColorControl = ( { label, value, onChange, help, rootClass } ) => (
	<div className={ `${ rootClass }__color-control` }>
		<TextControl
			label={ label }
			value={ value }
			onChange={ onChange }
			help={ help }
			placeholder="#000000"
			__nextHasNoMarginBottom
			__next40pxDefaultSize
		/>
		<span
			className={ `${ rootClass }__color-swatch` }
			aria-hidden="true"
			style={ {
				background: value || 'transparent',
			} }
		/>
	</div>
);

const BrandSectionCard = ( {
	title,
	isEditing,
	onToggle,
	preview,
	children,
	rootClass,
} ) => (
	<Card
		className={ `${ rootClass }__brand-card ${
			isEditing ? `${ rootClass }__brand-card--editing` : ''
		}` }
	>
		<CardHeader>
			<Flex justify="space-between" align="center">
				<FlexBlock>
					<h3>{ title }</h3>
				</FlexBlock>
				<Button
					variant={ isEditing ? 'primary' : 'secondary' }
					onClick={ onToggle }
				>
					{ isEditing
						? __( 'Save', 'fooconvert' )
						: __( 'Edit', 'fooconvert' ) }
				</Button>
			</Flex>
		</CardHeader>
		<CardBody>{ isEditing ? children : preview }</CardBody>
	</Card>
);

export const BrandContextEditor = ( {
	brand,
	setBrand,
	brandIsDirty,
	isExtractingBrand = false,
	isSavingBrand = false,
	showIntro = true,
	notice = null,
	error = '',
	onClearNotice,
	onClearError,
	onExtractBrand,
	onSaveBrand,
	rootClass = 'fc-brand-context',
} ) => {
	const [ editingBrandSections, setEditingBrandSections ] = useState(
		createBrandSectionState()
	);
	const [ remoteBrandUrl, setRemoteBrandUrl ] = useState( '' );
	const [ showRemoteBrandInput, setShowRemoteBrandInput ] = useState( false );

	const updateBrandField = ( path, value ) => {
		setBrand( ( currentBrand ) =>
			setNestedValue( currentBrand, path, value )
		);
	};

	const toggleBrandSection = ( section ) => {
		setEditingBrandSections( ( currentSections ) => ( {
			...currentSections,
			[ section ]: ! currentSections?.[ section ],
		} ) );
	};

	const handleExtractBrand = async ( mode = 'local' ) => {
		const remoteUrl = String( remoteBrandUrl || '' ).trim();
		const completed = await onExtractBrand?.( mode, remoteUrl );

		if ( completed && mode === 'remote' ) {
			setRemoteBrandUrl( '' );
			setShowRemoteBrandInput( false );
		}
	};

	const handleSaveBrand = async () => {
		const completed = await onSaveBrand?.();

		if ( completed ) {
			setEditingBrandSections( createBrandSectionState() );
		}
	};

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
	const primaryButtonPreviewStyle = getButtonPreviewStyle(
		brand?.components?.buttonPrimary,
		brand?.colors?.primary
	);
	const secondaryButtonPreviewStyle = getButtonPreviewStyle(
		brand?.components?.buttonSecondary,
		brand?.colors?.primary
	);

	return (
		<div className={ `${ rootClass }__stack` }>
			{ showIntro && (
				<div className={ `${ rootClass }__tab-intro` }>
					<div>
						<p>
							{ __(
								'Brand context gives popup tools shared colors, typography, spacing, and button styling for generated and assisted experiences.',
								'fooconvert'
							) }
						</p>
					</div>
				</div>
			) }

			{ error && (
				<Notice
					status="error"
					isDismissible={ true }
					onRemove={ onClearError }
				>
					{ error }
				</Notice>
			) }

			{ notice?.message && (
				<Notice
					status={ notice.status || 'info' }
					isDismissible={ true }
					onRemove={ onClearNotice }
				>
					{ notice.message }
				</Notice>
			) }

			<div className={ `${ rootClass }__tab-intro` }>
				<div className={ `${ rootClass }__tab-actions` }>
					<Button
						variant="secondary"
						onClick={ () => handleExtractBrand( 'local' ) }
						disabled={ isExtractingBrand }
					>
						{ isExtractingBrand
							? __( 'Extracting…', 'fooconvert' )
							: __( 'Extract Current Site', 'fooconvert' ) }
					</Button>
					<Button
						variant="secondary"
						onClick={ () =>
							setShowRemoteBrandInput( ( current ) => ! current )
						}
						disabled={ isExtractingBrand }
					>
						{ showRemoteBrandInput
							? __( 'Hide Remote URL', 'fooconvert' )
							: __( 'Extract Remote URL', 'fooconvert' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ handleSaveBrand }
						disabled={ isSavingBrand || ! brandIsDirty }
					>
						{ isSavingBrand
							? __( 'Saving…', 'fooconvert' )
							: __( 'Save Brand', 'fooconvert' ) }
					</Button>
				</div>
			</div>

			{ showRemoteBrandInput && (
				<Card>
					<CardBody>
						<div
							className={ `${ rootClass }__remote-extract-panel` }
						>
							<div
								className={ `${ rootClass }__remote-extract-row` }
							>
								<TextControl
									label={ __( 'Remote URL', 'fooconvert' ) }
									value={ remoteBrandUrl }
									onChange={ setRemoteBrandUrl }
									__nextHasNoMarginBottom
									__next40pxDefaultSize
								/>
								<div
									className={ `${ rootClass }__inline-actions` }
								>
									<Button
										variant="secondary"
										onClick={ () =>
											handleExtractBrand( 'remote' )
										}
										disabled={
											isExtractingBrand ||
											remoteBrandUrl.trim().length === 0
										}
									>
										{ isExtractingBrand
											? __( 'Extracting…', 'fooconvert' )
											: __(
													'Run Remote Extract',
													'fooconvert'
											  ) }
									</Button>
								</div>
							</div>
							<p className={ `${ rootClass }__muted-copy` }>
								{ __(
									'Optional. Use this when you want to extract brand details from another live URL instead of the current site.',
									'fooconvert'
								) }
							</p>
						</div>
					</CardBody>
				</Card>
			) }

			<div className={ `${ rootClass }__brand-grid` }>
				<BrandSectionCard
					rootClass={ rootClass }
					title={ __( 'Brand Overview', 'fooconvert' ) }
					isEditing={ !! editingBrandSections?.overview }
					onToggle={ () => toggleBrandSection( 'overview' ) }
					preview={
						<div className={ `${ rootClass }__preview-stack` }>
							<div
								className={ `${ rootClass }__overview-preview` }
							>
								<p>
									{ truncateText(
										brand?.brandOverview,
										220
									) ||
										__(
											'Add a short brand overview so generated popups have tone and positioning context.',
											'fooconvert'
										) }
								</p>
							</div>
						</div>
					}
				>
					<div className={ `${ rootClass }__field-grid` }>
						<TextareaControl
							label={ __( 'Brand Overview', 'fooconvert' ) }
							value={ brand?.brandOverview || '' }
							onChange={ ( value ) =>
								updateBrandField( 'brandOverview', value )
							}
							help={ __(
								'This starts from the site tagline on first run and gives generated popups tone and positioning context.',
								'fooconvert'
							) }
							rows={ 5 }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</div>
				</BrandSectionCard>

				<BrandSectionCard
					rootClass={ rootClass }
					title={ __( 'Palette', 'fooconvert' ) }
					isEditing={ !! editingBrandSections?.palette }
					onToggle={ () => toggleBrandSection( 'palette' ) }
					preview={
						brandPalette.length > 0 ? (
							<div className={ `${ rootClass }__preview-stack` }>
								<div
									className={ `${ rootClass }__brand-meta-row` }
								>
									<span
										className={ `${ rootClass }__meta-pill` }
									>
										{ `${ getColorSchemeLabel(
											brand?.colorScheme
										) } ${ __( 'scheme', 'fooconvert' ) }` }
									</span>
								</div>
								<div className={ `${ rootClass }__swatch-row` }>
									{ brandPalette.map( ( color ) => (
										<div
											key={ color.label }
											className={ `${ rootClass }__swatch-chip` }
										>
											<span
												aria-hidden="true"
												style={ {
													background: color.value,
												} }
											/>
											<strong>{ color.label }</strong>
											<small>{ color.value }</small>
										</div>
									) ) }
								</div>
							</div>
						) : (
							<p className={ `${ rootClass }__muted-copy` }>
								{ __(
									'Extract or set the core brand colors to guide popup styling.',
									'fooconvert'
								) }
							</p>
						)
					}
				>
					<div className={ `${ rootClass }__field-grid` }>
						<BrandColorControl
							rootClass={ rootClass }
							label={ __( 'Primary', 'fooconvert' ) }
							value={ brand?.colors?.primary || '' }
							onChange={ ( value ) =>
								updateBrandField( 'colors.primary', value )
							}
						/>
						<BrandColorControl
							rootClass={ rootClass }
							label={ __( 'Secondary', 'fooconvert' ) }
							value={ brand?.colors?.secondary || '' }
							onChange={ ( value ) =>
								updateBrandField( 'colors.secondary', value )
							}
						/>
						<BrandColorControl
							rootClass={ rootClass }
							label={ __( 'Accent', 'fooconvert' ) }
							value={ brand?.colors?.accent || '' }
							onChange={ ( value ) =>
								updateBrandField( 'colors.accent', value )
							}
						/>
						<BrandColorControl
							rootClass={ rootClass }
							label={ __( 'Background', 'fooconvert' ) }
							value={ brand?.colors?.background || '' }
							onChange={ ( value ) =>
								updateBrandField( 'colors.background', value )
							}
						/>
						<BrandColorControl
							rootClass={ rootClass }
							label={ __( 'Primary text', 'fooconvert' ) }
							value={ brand?.colors?.textPrimary || '' }
							onChange={ ( value ) =>
								updateBrandField( 'colors.textPrimary', value )
							}
						/>
						<BrandColorControl
							rootClass={ rootClass }
							label={ __( 'Secondary text', 'fooconvert' ) }
							value={ brand?.colors?.textSecondary || '' }
							onChange={ ( value ) =>
								updateBrandField(
									'colors.textSecondary',
									value
								)
							}
						/>
					</div>
					<div className={ `${ rootClass }__compact-control` }>
						<SelectControl
							label={ __( 'Color scheme', 'fooconvert' ) }
							value={ brand?.colorScheme || 'light' }
							onChange={ ( value ) =>
								updateBrandField( 'colorScheme', value )
							}
							options={ [
								{
									label: __( 'Light', 'fooconvert' ),
									value: 'light',
								},
								{
									label: __( 'Dark', 'fooconvert' ),
									value: 'dark',
								},
							] }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</div>
				</BrandSectionCard>

				<BrandSectionCard
					rootClass={ rootClass }
					title={ __( 'Typography', 'fooconvert' ) }
					isEditing={ !! editingBrandSections?.typography }
					onToggle={ () => toggleBrandSection( 'typography' ) }
					preview={
						<div className={ `${ rootClass }__preview-stack` }>
							<div className={ `${ rootClass }__type-specimen` }>
								<div
									className={ `${ rootClass }__type-specimen-heading` }
									style={ {
										fontFamily:
											brand?.typography?.fontFamilies
												?.heading ||
											brand?.typography?.fontFamilies
												?.primary ||
											undefined,
										fontSize:
											brand?.typography?.fontSizes?.h1
												?.value || undefined,
										fontWeight:
											brand?.typography?.fontWeights
												?.bold || undefined,
									} }
								>
									{ __( 'Headline Sample', 'fooconvert' ) }
								</div>
								<div
									className={ `${ rootClass }__type-specimen-body` }
									style={ {
										fontFamily:
											brand?.typography?.fontFamilies
												?.primary || undefined,
										fontSize:
											brand?.typography?.fontSizes?.body
												?.value || undefined,
										fontWeight:
											brand?.typography?.fontWeights
												?.regular || undefined,
									} }
								>
									{ __(
										'Body copy sample for popup descriptions, proof points, and CTA support text.',
										'fooconvert'
									) }
								</div>
							</div>
							<BrandPreviewList
								rootClass={ rootClass }
								rows={ [
									{
										label: __( 'Primary', 'fooconvert' ),
										value: getPreviewValue(
											brand?.typography?.fontFamilies
												?.primary
										),
									},
									{
										label: __( 'Heading', 'fooconvert' ),
										value: getPreviewValue(
											brand?.typography?.fontFamilies
												?.heading
										),
									},
									{
										label: __( 'H1 size', 'fooconvert' ),
										value: getPreviewValue(
											brand?.typography?.fontSizes?.h1
												?.value
										),
									},
									{
										label: __( 'Body size', 'fooconvert' ),
										value: getPreviewValue(
											brand?.typography?.fontSizes?.body
												?.value
										),
									},
									{
										label: __( 'Weights', 'fooconvert' ),
										value: `${
											brand?.typography?.fontWeights
												?.regular || 400
										} / ${
											brand?.typography?.fontWeights
												?.bold || 700
										}`,
									},
								] }
							/>
						</div>
					}
				>
					<div className={ `${ rootClass }__field-grid` }>
						<TextControl
							label={ __( 'Primary font family', 'fooconvert' ) }
							value={
								brand?.typography?.fontFamilies?.primary || ''
							}
							onChange={ ( value ) =>
								updateBrandField(
									'typography.fontFamilies.primary',
									value
								)
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<TextControl
							label={ __( 'Heading font family', 'fooconvert' ) }
							value={
								brand?.typography?.fontFamilies?.heading || ''
							}
							onChange={ ( value ) =>
								updateBrandField(
									'typography.fontFamilies.heading',
									value
								)
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<TextControl
							label={ __( 'H1 size', 'fooconvert' ) }
							value={
								brand?.typography?.fontSizes?.h1?.value || ''
							}
							onChange={ ( value ) =>
								updateBrandField(
									'typography.fontSizes.h1.value',
									value
								)
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<TextControl
							label={ __( 'Body size', 'fooconvert' ) }
							value={
								brand?.typography?.fontSizes?.body?.value || ''
							}
							onChange={ ( value ) =>
								updateBrandField(
									'typography.fontSizes.body.value',
									value
								)
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<TextControl
							label={ __( 'Regular weight', 'fooconvert' ) }
							type="number"
							value={ String(
								brand?.typography?.fontWeights?.regular || ''
							) }
							onChange={ ( value ) =>
								updateBrandField(
									'typography.fontWeights.regular',
									value
								)
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<TextControl
							label={ __( 'Bold weight', 'fooconvert' ) }
							type="number"
							value={ String(
								brand?.typography?.fontWeights?.bold || ''
							) }
							onChange={ ( value ) =>
								updateBrandField(
									'typography.fontWeights.bold',
									value
								)
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</div>
				</BrandSectionCard>

				<BrandSectionCard
					rootClass={ rootClass }
					title={ __( 'Controls', 'fooconvert' ) }
					isEditing={ !! editingBrandSections?.controls }
					onToggle={ () => toggleBrandSection( 'controls' ) }
					preview={
						<div className={ `${ rootClass }__preview-stack` }>
							<div
								className={ `${ rootClass }__button-preview-row` }
							>
								<span
									className={ `${ rootClass }__button-preview` }
									style={ primaryButtonPreviewStyle }
								>
									{ __( 'Primary CTA', 'fooconvert' ) }
								</span>
								<span
									className={ `${ rootClass }__button-preview` }
									style={ secondaryButtonPreviewStyle }
								>
									{ __( 'Secondary CTA', 'fooconvert' ) }
								</span>
							</div>
							<BrandPreviewList
								rootClass={ rootClass }
								rows={ [
									{
										label: __( 'Base unit', 'fooconvert' ),
										value: brand?.spacing?.baseUnit
											? `${ brand.spacing.baseUnit }px`
											: '',
									},
									{
										label: __( 'Radius', 'fooconvert' ),
										value: getPreviewValue(
											brand?.spacing?.borderRadius
										),
									},
									{
										label: __(
											'Primary button',
											'fooconvert'
										),
										value: getPreviewValue(
											brand?.components?.buttonPrimary
												?.background
										),
									},
									{
										label: __(
											'Secondary border',
											'fooconvert'
										),
										value: getPreviewValue(
											brand?.components?.buttonSecondary
												?.borderColor
										),
									},
								] }
							/>
						</div>
					}
				>
					<div className={ `${ rootClass }__field-grid` }>
						<TextControl
							label={ __( 'Base spacing unit', 'fooconvert' ) }
							type="number"
							value={ String( brand?.spacing?.baseUnit || '' ) }
							onChange={ ( value ) =>
								updateBrandField( 'spacing.baseUnit', value )
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<TextControl
							label={ __( 'Global border radius', 'fooconvert' ) }
							value={ brand?.spacing?.borderRadius || '' }
							onChange={ ( value ) =>
								updateBrandField(
									'spacing.borderRadius',
									value
								)
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<BrandColorControl
							rootClass={ rootClass }
							label={ __(
								'Primary button background',
								'fooconvert'
							) }
							value={
								brand?.components?.buttonPrimary?.background ||
								''
							}
							onChange={ ( value ) =>
								updateBrandField(
									'components.buttonPrimary.background',
									value
								)
							}
						/>
						<BrandColorControl
							rootClass={ rootClass }
							label={ __( 'Primary button text', 'fooconvert' ) }
							value={
								brand?.components?.buttonPrimary?.textColor ||
								''
							}
							onChange={ ( value ) =>
								updateBrandField(
									'components.buttonPrimary.textColor',
									value
								)
							}
						/>
						<TextControl
							label={ __(
								'Primary button radius',
								'fooconvert'
							) }
							value={
								brand?.components?.buttonPrimary
									?.borderRadius || ''
							}
							onChange={ ( value ) =>
								updateBrandField(
									'components.buttonPrimary.borderRadius',
									value
								)
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<BrandColorControl
							rootClass={ rootClass }
							label={ __(
								'Secondary button background',
								'fooconvert'
							) }
							value={
								brand?.components?.buttonSecondary
									?.background || ''
							}
							onChange={ ( value ) =>
								updateBrandField(
									'components.buttonSecondary.background',
									value
								)
							}
						/>
						<BrandColorControl
							rootClass={ rootClass }
							label={ __(
								'Secondary button text',
								'fooconvert'
							) }
							value={
								brand?.components?.buttonSecondary?.textColor ||
								''
							}
							onChange={ ( value ) =>
								updateBrandField(
									'components.buttonSecondary.textColor',
									value
								)
							}
						/>
						<BrandColorControl
							rootClass={ rootClass }
							label={ __(
								'Secondary button border',
								'fooconvert'
							) }
							value={
								brand?.components?.buttonSecondary
									?.borderColor || ''
							}
							onChange={ ( value ) =>
								updateBrandField(
									'components.buttonSecondary.borderColor',
									value
								)
							}
						/>
						<TextControl
							label={ __(
								'Secondary button radius',
								'fooconvert'
							) }
							value={
								brand?.components?.buttonSecondary
									?.borderRadius || ''
							}
							onChange={ ( value ) =>
								updateBrandField(
									'components.buttonSecondary.borderRadius',
									value
								)
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</div>
				</BrandSectionCard>
			</div>
		</div>
	);
};

export const BrandContextModal = ( {
	isOpen,
	onClose,
	rootClass = 'fc-brand-context',
	modalClassName = '',
	title = __( 'Brand Context', 'fooconvert' ),
	...editorProps
} ) => {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ title }
			onRequestClose={ onClose }
			className={ `${ rootClass }__modal ${ modalClassName }` }
			shouldCloseOnClickOutside={ true }
		>
			<BrandContextEditor rootClass={ rootClass } { ...editorProps } />
		</Modal>
	);
};
