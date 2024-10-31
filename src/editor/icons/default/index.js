import { __ } from "@wordpress/i18n";

import arrowDown from './arrow-down.js';
import arrowLeft from './arrow-left.js';
import arrowRight from './arrow-right.js';
import arrowUp from './arrow-up.js';
import cancelCircleFilled from './cancel-circle-filled.js';
import chevronDown from './chevron-down.js';
import chevronLeft from './chevron-left.js';
import chevronRight from './chevron-right.js';
import chevronUp from './chevron-up.js';
import close from './close.js';
import closeSmall from './close-small.js';
import plus from './plus.js';
import plusCircle from './plus-circle.js';
import plusCircleFilled from './plus-circle-filled.js';
import plusSmall from './plus-small.js';

/**
 *
 * @type {FC_ICON_SET}
 */
export default {
    name: 'default',
    label: __( 'Default', 'fooconvert'),
    icons: [
        { name: 'arrow-down', label: __( 'Arrow Down', 'fooconvert' ), value: arrowDown },
        { name: 'arrow-left', label: __( 'Arrow Left', 'fooconvert' ), value: arrowLeft },
        { name: 'arrow-right', label: __( 'Arrow Right', 'fooconvert' ), value: arrowRight },
        { name: 'arrow-up', label: __( 'Arrow Up', 'fooconvert' ), value: arrowUp },
        { name: 'cancel-circle-filled', label: __( 'Cancel Circle Filled', 'fooconvert' ), value: cancelCircleFilled },
        { name: 'chevron-down', label: __( 'Chevron Down', 'fooconvert' ), value: chevronDown },
        { name: 'chevron-left', label: __( 'Chevron Left', 'fooconvert' ), value: chevronLeft },
        { name: 'chevron-right', label: __( 'Chevron Right', 'fooconvert' ), value: chevronRight },
        { name: 'chevron-up', label: __( 'Chevron Up', 'fooconvert' ), value: chevronUp },
        { name: 'close', label: __( 'Close', 'fooconvert' ), value: close },
        { name: 'close-small', label: __( 'Close Small', 'fooconvert' ), value: closeSmall },
        { name: 'plus', label: __( 'Plus', 'fooconvert' ), value: plus },
        { name: 'plus-circle', label: __( 'Plus Circle', 'fooconvert' ), value: plusCircle },
        { name: 'plus-circle-filled', label: __( 'Plus Circle Filled', 'fooconvert' ), value: plusCircleFilled },
        { name: 'plus-small', label: __( 'Plus Small', 'fooconvert' ), value: plusSmall },
    ]
};