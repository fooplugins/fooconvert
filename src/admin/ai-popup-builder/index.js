import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { App } from './App';

import './index.scss';

domReady( () => {
	const container = document.getElementById( 'fc-ai-popup-builder-root' );
	if ( ! container ) {
		return;
	}

	createRoot( container ).render( <App /> );
} );
