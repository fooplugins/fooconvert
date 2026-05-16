import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';
import {
	createRoot,
	startTransition,
	useEffect,
	useMemo,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import {
	BrandContextModal,
	createEmptyBrand,
	isPlainObject,
	normalizeBrand as normalizeBrandContext,
	serializeComparable as serializeBrandComparable,
} from './components';

import './index.scss';

const config = window.FC_BRAND_CONTEXT || {};
const defaultBrandContext = isPlainObject( config?.brand?.defaultBrand )
	? config.brand.defaultBrand
	: {};
const normalizeBrand = ( brand ) =>
	normalizeBrandContext( brand, defaultBrandContext );
const serializeComparable = ( value ) =>
	serializeBrandComparable( value, defaultBrandContext );

if ( ! window.FC_BRAND_CONTEXT_API_FETCH_READY ) {
	if ( typeof config?.restRoot === 'string' && config.restRoot.length > 0 ) {
		apiFetch.use( apiFetch.createRootURLMiddleware( config.restRoot ) );
	}

	if (
		typeof config?.restNonce === 'string' &&
		config.restNonce.length > 0
	) {
		apiFetch.use( apiFetch.createNonceMiddleware( config.restNonce ) );
	}

	window.FC_BRAND_CONTEXT_API_FETCH_READY = true;
}

const App = () => {
	const initialBrand = normalizeBrand(
		config?.brand?.savedBrand || config?.brand?.defaultBrand || {}
	);
	const initialSavedBrand = config?.brand?.hasSavedBrand
		? normalizeBrand( config?.brand?.savedBrand || {} )
		: createEmptyBrand();

	const [ isOpen, setOpen ] = useState( false );
	const [ brand, setBrand ] = useState( initialBrand );
	const [ savedBrandSnapshot, setSavedBrandSnapshot ] =
		useState( initialSavedBrand );
	const [ isExtractingBrand, setExtractingBrand ] = useState( false );
	const [ isSavingBrand, setSavingBrand ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ statusNotice, setStatusNotice ] = useState( null );

	const brandIsDirty = useMemo(
		() =>
			serializeComparable( brand ) !==
			serializeComparable( savedBrandSnapshot ),
		[ brand, savedBrandSnapshot ]
	);

	useEffect( () => {
		const openBrandContext = ( event ) => {
			const trigger = event.target?.closest?.(
				'[data-fc-brand-context-open]'
			);

			if ( ! trigger ) {
				return;
			}

			event.preventDefault();
			setOpen( true );
		};

		document.addEventListener( 'click', openBrandContext );

		return () => {
			document.removeEventListener( 'click', openBrandContext );
		};
	}, [] );

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
		setStatusNotice( null );

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
		setStatusNotice( null );

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
						'Brand context saved for reuse across popup tools.',
						'fooconvert'
					),
				} );
			} );

			return true;
		} catch ( exception ) {
			setError(
				exception?.message ||
					__( 'The brand context could not be saved.', 'fooconvert' )
			);
			return false;
		} finally {
			setSavingBrand( false );
		}
	};

	return (
		<BrandContextModal
			isOpen={ isOpen }
			onClose={ () => setOpen( false ) }
			brand={ brand }
			setBrand={ setBrand }
			brandIsDirty={ brandIsDirty }
			isExtractingBrand={ isExtractingBrand }
			isSavingBrand={ isSavingBrand }
			notice={ statusNotice }
			error={ error }
			onClearNotice={ () => setStatusNotice( null ) }
			onClearError={ () => setError( '' ) }
			onExtractBrand={ extractBrand }
			onSaveBrand={ saveBrandProfile }
		/>
	);
};

domReady( () => {
	const rootId = 'fc-brand-context-root';
	let rootElement = document.getElementById( rootId );

	if ( ! rootElement ) {
		rootElement = document.createElement( 'div' );
		rootElement.id = rootId;
		document.body.appendChild( rootElement );
	}

	createRoot( rootElement ).render( <App /> );
} );
