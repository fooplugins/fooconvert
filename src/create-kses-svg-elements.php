<?php
/**
 * This file should never be included in the plugin. It is used to generate the FOOCONVERT_SVG_ALLOWED_HTML constant.
 *
 * This is based off the MDN documentation for SVG elements, however it contains no deprecated elements or attributes.
 *
 * It also does not include the <script/> or <foreignObject/> SVG elements as they would open up XSS attack vectors.
 *
 * The reason this is in a separate file is it is simply easier to manage which element has what attributes when
 * broken down into smaller chunks, however the final result is used as a constant within the plugin.
 *
 * To use this file, just create a variable in 'fooconvert.php' and require this file to it.
 *
 * e.g.
 *
 *  $elements = require_once FOOCONVERT_PATH . 'src/create-kses-svg-elements.php';
 *
 * Then using PhpStorm place a breakpoint after $elements and run the file. When the breakpoint is hit, inspect the
 * $elements variable, and it will contain the new elements' definition.
 *
 * You can then use PhpStorms' "Copy Value As... > var_export" option to capture the variable to your clipboard where you
 * can then paste it into the FOOCONVERT_SVG_ALLOWED_HTML constant.
 *
 * @noinspection SpellCheckingInspection
 */

/**
 * Get the attributes for the given SVG element tagName.
 *
 * @param string $tag_name
 * @return array
 */
function get_attributes( string $tag_name ) : array {
    // ALL_ELEMENTS
    $attributes = array(
        // Core attributes
        // see https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute#generic_attributes
        'class' => true,
        'data-*' => true,
        'id' => true,
        'lang' => true,
        'style' => true,
        'tabindex' => true,
        // Presentation attributes
        // These are stated to be allowed on all SVG elements
        // see https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute#presentation_attributes
        // e.g. https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/color
        'color' => true,
        'display' => true,
        'filter' => true,
        'transform' => true,
        'transform-origin' => true,
        // other - see function _wp_add_global_attributes()
        'aria-controls' => true,
        'aria-current' => true,
        'aria-describedby' => true,
        'aria-details' => true,
        'aria-expanded' => true,
        'aria-hidden' => true,
        'aria-label' => true,
        'aria-labelledby' => true,
        'aria-live' => true,
        'dir' => true,
        'hidden' => true,
        'title' => true,
        'role' => true,
        'xml:lang' => true,
    );

    $BASE_ANIMATION_ELEMENTS = array(
        'animate',
        'animatemotion',
        'animatetransform',
        'set'
    );
    if ( in_array( $tag_name, $BASE_ANIMATION_ELEMENTS ) ) {
        $attributes['begin'] = true;
        $attributes['dur'] = true;
        $attributes['end'] = true;
        $attributes['keypoints'] = true;
        $attributes['max'] = true;
        $attributes['min'] = true;
        $attributes['repeatcount'] = true;
        $attributes['repeatdur'] = true;
        $attributes['restart'] = true;
        $attributes['to'] = true;
    }

    $ANIMATION_ELEMENTS = array(
        'animate',
        'animatemotion',
        'animatetransform'
    );
    if ( in_array( $tag_name, $ANIMATION_ELEMENTS ) ) {
        $attributes['accumulate'] = true;
        $attributes['additive'] = true;
        $attributes['by'] = true;
        $attributes['calcmode'] = true;
        $attributes['from'] = true;
        $attributes['keysplines'] = true;
        $attributes['keytimes'] = true;
        $attributes['values'] = true;
    }

    $SHAPE_ELEMENTS = array(
        'circle',
        'ellipse',
        'line',
        'path',
        'polygon',
        'polyline',
        'rect'
    );
    if ( in_array( $tag_name, $SHAPE_ELEMENTS ) ) {
        $attributes['marker-end'] = true;
        $attributes['marker-mid'] = true;
        $attributes['marker-start'] = true;
        $attributes['pathlength'] = true;
        $attributes['shape-rendering'] = true;
    }

    $TEXT_ELEMENTS = array( 'text', 'textpath', 'tspan' );
    if ( in_array( $tag_name, $TEXT_ELEMENTS ) ) {
        $attributes['alignment-baseline'] = true;
        $attributes['direction'] = true;
        $attributes['dominant-baseline'] = true;
        $attributes['font-family'] = true;
        $attributes['font-size'] = true;
        $attributes['font-size-adjust'] = true;
        $attributes['font-stretch'] = true;
        $attributes['font-style'] = true;
        $attributes['font-variant'] = true;
        $attributes['font-weight'] = true;
        $attributes['lengthadjust'] = true;
        $attributes['letter-spacing'] = true;
        $attributes['text-anchor'] = true;
        $attributes['text-decoration'] = true;
        $attributes['textlength'] = true;
        $attributes['unicode-bidi'] = true;
        $attributes['word-spacing'] = true;
        $attributes['writing-mode'] = true;
    }

    $ATTRIBUTE_NAME_ELEMENTS = array(
        'animate',
        'animatetransform',
        'set',
    );
    if ( in_array( $tag_name, $ATTRIBUTE_NAME_ELEMENTS ) ) {
        $attributes['attributename'] = true;
    }

    $CLIP_RULE_ELEMENTS = array(
        'circle',
        'ellipse',
        'image',
        'line',
        'path',
        'polygon',
        'polyline',
        'rect',
        'text',
        'use'
    );
    if ( in_array( $tag_name, $CLIP_RULE_ELEMENTS ) ) {
        $attributes['clip-rule'] = true;
    }

    $COLOR_INTERPOLATION_ELEMENTS = array(
        'a',
        'animate',
        'circle',
        'clippath',
        'defs',
        'ellipse',
        'g',
        'image',
        'line',
        'lineargradient',
        'marker',
        'mask',
        'path',
        'polygon',
        'polyline',
        'radialgradient',
        'rect',
        'svg',
        'switch',
        'symbol',
        'text',
        'textpath',
        'tspan',
        'use'
    );
    if ( in_array( $tag_name, $COLOR_INTERPOLATION_ELEMENTS ) ) {
        $attributes['color-interpolation'] = true;
    }

    $COLOR_INTERPOLATION_FILTERS_ELEMENTS = array(
        'feblend',
        'fecolormatrix',
        'fecomponenttransfer',
        'fecomposite',
        'feconvolvematrix',
        'fediffuselighting',
        'fedisplacementmap',
        'fedropshadow',
        'feflood',
        'fegaussianblur',
        'feimage',
        'femerge',
        'femorphology',
        'feoffset',
        'fespecularlighting',
        'fespotlight',
        'fetile',
        'feturbulence',
    );
    if ( in_array( $tag_name, $COLOR_INTERPOLATION_FILTERS_ELEMENTS ) ) {
        $attributes['color-interpolation-filters'] = true;
    }

    $CURSOR_ELEMENTS = array(
        'a',
        'circle',
        'defs',
        'ellipse',
        'g',
        'image',
        'line',
        'marker',
        'mask',
        'path',
        'pattern',
        'polygon',
        'polyline',
        'rect',
        'svg',
        'switch',
        'symbol',
        'text',
        'use'
    );
    if ( in_array( $tag_name, $CURSOR_ELEMENTS ) ) {
        $attributes['cursor'] = true;
    }

    $FE_FUNC_ELEMENTS = array(
        'fefunca',
        'fefuncb',
        'fefuncg',
        'fefuncr'
    );
    if ( in_array( $tag_name, $FE_FUNC_ELEMENTS ) ) {
        $attributes['amplitude'] = true;
        $attributes['exponent'] = true;
        $attributes['intercept'] = true;
        $attributes['slope'] = true;
        $attributes['tablevalues'] = true;
    }

    $FILL_ELEMENTS = array(
        'animate',
        'animatemotion',
        'animatetransform',
        'circle',
        'ellipse',
        'path',
        'polygon',
        'polyline',
        'rect',
        'set',
        'text',
        'textpath',
        'tspan'
    );
    if ( in_array( $tag_name, $FILL_ELEMENTS ) ) {
        $attributes['fill'] = true;
    }

    $FILL_OPACITY_ELEMENTS = array(
        'circle',
        'ellipse',
        'path',
        'polygon',
        'polyline',
        'rect',
        'text',
        'textpath',
        'tspan'
    );
    if ( in_array( $tag_name, $FILL_OPACITY_ELEMENTS ) ) {
        $attributes['fill-opacity'] = true;
    }

    $FILL_RULE_ELEMENTS = array(
        'path',
        'polygon',
        'polyline',
        'text',
        'textpath',
        'tspan'
    );
    if ( in_array( $tag_name, $FILL_RULE_ELEMENTS ) ) {
        $attributes['fill-rule'] = true;
    }

    $HREF_ELEMENTS = array(
        'a',
        'animate',
        'animatemotion',
        'animatetransform',
        'feimage',
        'image',
        'lineargradient',
        'mpath',
        'pattern',
        'radialgradient',
        'set',
        'textpath',
        'use',
    );
    if ( in_array( $tag_name, $HREF_ELEMENTS ) ) {
        $attributes['href'] = true;
    }

    $IN_ELEMENTS = array(
        'feblend',
        'fecolormatrix',
        'fecomponenttransfer',
        'fecomposite',
        'feconvolvematrix',
        'fediffuselighting',
        'fedisplacementmap',
        'fedropshadow',
        'fegaussianblur',
        'femergemode',
        'femorphology',
        'feoffset',
        'fespecularlighting',
        'fetile',
    );
    if ( in_array( $tag_name, $IN_ELEMENTS ) ) {
        $attributes['in'] = true;
    }

    $MASK_ELEMENTS = array(
        'a',
        'circle',
        'clippath',
        'ellipse',
        'g',
        'image',
        'line',
        'marker',
        'mask',
        'path',
        'pattern',
        'polygon',
        'polyline',
        'rect',
        'svg',
        'symbol',
        'text',
        'use',
    );
    if ( in_array( $tag_name, $MASK_ELEMENTS ) ) {
        $attributes['mask'] = true;
    }

    $OPACITY_ELEMENTS = array(
        'a',
        'circle',
        'ellipse',
        'g',
        'image',
        'line',
        'path',
        'polygon',
        'polyline',
        'rect',
        'svg',
        'switch',
        'symbol',
        'text',
        'textpath',
        'tspan',
        'use'
    );
    if ( in_array( $tag_name, $OPACITY_ELEMENTS ) ) {
        $attributes['opacity'] = true;
    }

    $OVERFLOW_ELEMENTS = array(
        'image',
        'marker',
        'pattern',
        'svg',
        'symbol',
        'text',
    );
    if ( in_array( $tag_name, $OVERFLOW_ELEMENTS ) ) {
        $attributes['overflow'] = true;
    }

    $PAINT_ORDER_ELEMENTS = array(
        'circle',
        'ellipse',
        'line',
        'path',
        'polygon',
        'polyline',
        'rect',
        'text',
        'textpath',
        'tspan',
    );
    if ( in_array( $tag_name, $PAINT_ORDER_ELEMENTS ) ) {
        $attributes['paint-order'] = true;
    }

    $POINTER_EVENTS_ELEMENTS = array(
        'a',
        'circle',
        'clippath',
        'defs',
        'ellipse',
        'g',
        'image',
        'line',
        'marker',
        'mask',
        'path',
        'pattern',
        'polygon',
        'polyline',
        'rect',
        'svg',
        'switch',
        'symbol',
        'text',
        'textpath',
        'tspan',
        'use'
    );
    if ( in_array( $tag_name, $POINTER_EVENTS_ELEMENTS ) ) {
        $attributes['pointer-events'] = true;
    }

    $PRESERVE_ASPECT_RATIO_ELEMENTS = array(
        'feimage',
        'image',
        'marker',
        'pattern',
        'svg',
        'symbol',
        'view',
    );
    if ( in_array( $tag_name, $PRESERVE_ASPECT_RATIO_ELEMENTS ) ) {
        $attributes['preserveaspectratio'] = true;
    }

    $RESULT_ELEMENTS = array(
        'feblend',
        'fecolormatrix',
        'fecomponenttransfer',
        'fecomposite',
        'feconvolvematrix',
        'fediffuselighting',
        'fedisplacementmap',
        'fedropshadow',
        'feflood',
        'fegaussianblur',
        'feimage',
        'femerge',
        'femorphology',
        'feoffset',
        'fespecularlighting',
        'fetile',
        'feturbulence',
    );
    if ( in_array( $tag_name, $RESULT_ELEMENTS ) ) {
        $attributes['result'] = true;
    }

    $STROKE_ELEMENTS = array(
        'circle',
        'ellipse',
        'line',
        'path',
        'polygon',
        'polyline',
        'rect',
        'text',
        'textpath',
        'tspan',
    );
    if ( in_array( $tag_name, $STROKE_ELEMENTS ) ) {
        $attributes['stroke'] = true;
        $attributes['stroke-dasharray'] = true;
        $attributes['stroke-dashoffset'] = true;
        $attributes['stroke-opacity'] = true;
        $attributes['stroke-width'] = true;
    }

    $STROKE_LINECAP_ELEMENTS = array(
        'path',
        'polyline',
        'line',
        'text',
        'textpath',
        'tspan',
    );
    if ( in_array( $tag_name, $STROKE_LINECAP_ELEMENTS ) ) {
        $attributes['stroke-linecap'] = true;
    }

    $STROKE_LINEJOIN_AND_MITERLIMIT_ELEMENTS = array(
        'path',
        'polygon',
        'polyline',
        'rect',
        'text',
        'textpath',
        'tspan',
    );
    if ( in_array( $tag_name, $STROKE_LINEJOIN_AND_MITERLIMIT_ELEMENTS ) ) {
        $attributes['stroke-linejoin'] = true;
        $attributes['stroke-miterlimit'] = true;
    }

    $SYSTEM_LANGUAGE_ELEMENTS = array(
        'a',
        'animate',
        'animatemotion',
        'animatetransform',
        'circle',
        'clippath',
        'defs',
        'ellipse',
        'g',
        'image',
        'line',
        'mask',
        'path',
        'pattern',
        'polygon',
        'polyline',
        'rect',
        'set',
        'svg',
        'switch',
        'text',
        'textpath',
        'tspan',
        'use',
    );
    if ( in_array( $tag_name, $SYSTEM_LANGUAGE_ELEMENTS ) ) {
        $attributes['systemlanguage'] = true;
    }

    $TYPE_ELEMENTS = array(
        'animatetransform',
        'fecolormatrix',
        'fefunca',
        'fefuncb',
        'fefuncg',
        'fefuncr',
        'feturbulence',
        'style',
    );
    if ( in_array( $tag_name, $TYPE_ELEMENTS ) ) {
        $attributes['type'] = true;
    }

    $VECTOR_OFFSET_ELEMENTS = array(
        'circle',
        'ellipse',
        'image',
        'line',
        'path',
        'polygon',
        'polyline',
        'rect',
        'text',
        'textpath',
        'tspan',
        'use',
    );
    if ( in_array( $tag_name, $VECTOR_OFFSET_ELEMENTS ) ) {
        $attributes['vector-offset'] = true;
    }

    $VISIBILITY_ELEMENTS = array(
        'a',
        'circle',
        'ellipse',
        'image',
        'line',
        'path',
        'polygon',
        'polyline',
        'rect',
        'text',
        'textpath',
        'tspan'
    );
    if ( in_array( $tag_name, $VISIBILITY_ELEMENTS ) ) {
        $attributes['visibility'] = true;
    }

    $VIEWBOX_ELEMENTS = array(
        'marker',
        'pattern',
        'svg',
        'symbol',
        'view',
    );
    if ( in_array( $tag_name, $VIEWBOX_ELEMENTS ) ) {
        $attributes['viewbox'] = true;
    }

    $WIDTH_AND_HEIGHT_ELEMENTS = array(
        'feblend',
        'fecolormatrix',
        'fecomponenttransfer',
        'fecomposite',
        'feconvolvematrix',
        'fediffuselighting',
        'fedisplacementmap',
        'fedropshadow',
        'feflood',
        'fegaussianblur',
        'feimage',
        'femerge',
        'femorphology',
        'feoffset',
        'fespecularlighting',
        'fetile',
        'feturbulence',
        'filter',
        'image',
        'mask',
        'pattern',
        'rect',
        'svg',
        'use',
    );
    if ( in_array( $tag_name, $WIDTH_AND_HEIGHT_ELEMENTS ) ) {
        $attributes['width'] = true;
        $attributes['height'] = true;
    }

    $X_AND_Y_ELEMENTS = array(
        'feblend',
        'fecolormatrix',
        'fecomponenttransfer',
        'fecomposite',
        'feconvolvematrix',
        'fediffuselighting',
        'fedisplacementmap',
        'fedropshadow',
        'feflood',
        'fefunca',
        'fefuncb',
        'fefuncg',
        'fefuncr',
        'fegaussianblur',
        'feimage',
        'femerge',
        'femergenode',
        'femorphology',
        'feoffset',
        'fepointlight',
        'fespecularlighting',
        'fespotlight',
        'fetile',
        'feturbulence',
        'filter',
        'image',
        'mask',
        'pattern',
        'rect',
        'svg',
        'text',
        'tspan',
        'use',
    );
    if ( in_array( $tag_name, $X_AND_Y_ELEMENTS ) ) {
        $attributes['x'] = true;
        $attributes['y'] = true;
    }

    $DX_AND_DY_ELEMENTS = array(
        'fedropshadow',
        'feoffset',
        'text',
        'tspan',
    );
    if ( in_array( $tag_name, $DX_AND_DY_ELEMENTS ) ) {
        $attributes['dx'] = true;
        $attributes['dy'] = true;
    }

    return $attributes;
}

/**
 * Contains all allowed SVG elements and their specific attributes.
 */
$ALLOWED_ELEMENTS = array(
    //region A
    'a' => array(
        'download' => array(
            'valueless' => 'y',
        ),
        'hreflang' => true,
        'referrerpolicy' => true,
        'rel' => true,
        'target' => true,
    ),
    'animate' => array(),
    'animatemotion' => array(
        'origin' => true,
        'path' => true,
        'rotate' => true,
    ),
    'animatetransform' => array(),
    //endregion
    //region C
    'circle' => array(
        'cx' => true,
        'cy' => true,
        'r' => true,
    ),
    'clippath' => array(
        'clippathunits' => true,
    ),
    //endregion
    //region D
    'defs' => array(),
    'desc' => array(),
    //endregion
    //region E
    'ellipse' => array(
        'cx' => true,
        'cy' => true,
        'rx' => true,
        'ry' => true,
    ),
    //endregion
    //region F
    'feblend' => array(
        'in2' => true,
        'mode' => true,
    ),
    'fecolormatrix' => array(
        'values' => true,
    ),
    'fecomponenttransfer' => array(),
    'fecomposite' => array(
        'in2' => true,
        'k1' => true,
        'k2' => true,
        'k3' => true,
        'k4' => true,
        'operator' => true,
    ),
    'feconvolvematrix' => array(
        'bias' => true,
        'divisor' => true,
        'edgemode' => true,
        'kernelmatrix' => true,
        'order' => true,
        'preservealpha' => true,
        'targetx' => true,
        'targety' => true,
    ),
    'fediffuselighting' => array(
        'diffuseconstant' => true,
        'lighting-color' => true,
        'surfacescale' => true,
    ),
    'fedisplacementmap' => array(
        'in2' => true,
        'scale' => true,
        'xchannelselector' => true,
        'ychannelselector' => true,
    ),
    'fedistantlight' => array(
        'azimuth' => true,
        'elevation' => true,
    ),
    'fedropshadow' => array(
        'flood-color' => true,
        'flood-opacity' => true,
    ),
    'feflood' => array(
        'flood-color' => true,
        'flood-opacity' => true,
    ),
    'fefunca' => array(),
    'fefuncb' => array(),
    'fefuncg' => array(),
    'fefuncr' => array(),
    'fegaussianblur' => array(
        'edgemode' => true,
        'stddeviation' => true,
    ),
    'feimage' => array(
        'crossorigin' => true,
    ),
    'femerge' => array(),
    'femergenode' => array(),
    'femorphology' => array(
        'operator' => true,
        'radius' => true,
    ),
    'feoffset' => array(),
    'fepointlight' => array(
        'z' => true,
    ),
    'fespecularlighting' => array(
        'lighting-color' => true,
        'specularconstant' => true,
        'specularexponent' => true,
        'surfacescale' => true,
    ),
    'fespotlight' => array(
        'limitingconeangle' => true,
        'pointsatx' => true,
        'pointsaty' => true,
        'pointsatz' => true,
        'specularexponent' => true,
        'z' => true,
    ),
    'fetile' => array(),
    'feturbulence' => array(
        'basefrequency' => true,
        'numoctaves' => true,
        'seed' => true,
        'stitchtiles' => true,
    ),
    'filter' => array(
        'filterunits' => true,
        'primitiveunits' => true,
    ),
    //endregion
    //region G
    'g' => array(),
    //endregion
    //region I
    'image' => array(
        'decoding' => true,
        'image-rendering' => true,
        'crossorigin' => true,
    ),
    //endregion
    //region L
    'line' => array(
        'x1' => true,
        'x2' => true,
        'y1' => true,
        'y2' => true,
    ),
    'lineargradient' => array(
        'gradienttransform' => true,
        'gradientunits' => true,
        'spreadmethod' => true,
        'x1' => true,
        'x2' => true,
        'y1' => true,
        'y2' => true,
    ),
    //endregion
    //region M
    'marker' => array(
        'markerheight' => true,
        'markerunits' => true,
        'markerwidth' => true,
        'orient' => true,
        'refx' => true,
        'refy' => true,
    ),
    'mask' => array(
        'maskcontentunits' => true,
        'maskunits' => true,
    ),
    'metadata' => array(),
    'mpath' => array(),
    //endregion
    //region P
    'path' => array(
        'd' => true,
    ),
    'pattern' => array(
        'patterncontentunits' => true,
        'patterntransform' => true,
        'patternunits' => true,
    ),
    'polygon' => array(
        'points' => true,
    ),
    'polyline' => array(
        'points' => true,
    ),
    //endregion
    //region R
    'radialgradient' => array(
        'cx' => true,
        'cy' => true,
        'fr' => true,
        'fx' => true,
        'fy' => true,
        'gradienttransform' => true,
        'gradientunits' => true,
        'r' => true,
        'spreadmethod' => true,
    ),
    'rect' => array(
        'rx' => true,
        'ry' => true,
    ),
    //endregion
    //region S
    'set' => array(),
    'stop' => array(
        'stop-color' => true,
        'stop-opacity' => true,
    ),
    'style' => array(
        'media' => true,
    ),
    'svg' => array(
        'slot' => true,
        'is' => true,
        'xmlns' => true,
    ),
    'switch' => array(),
    'symbol' => array(
        'refx' => true,
        'refy' => true,
    ),
    //endregion
    //region T
    'text' => array(
        'text-rendering' => true,
    ),
    'textpath' => array(
        'baseline-shift' => true,
        'path' => true,
        'side' => true,
        'spacing' => true,
        'startoffset' => true,
    ),
    'title' => array(),
    'tspan' => array(
        'baseline-shift' => true,
    ),
    //endregion
    //region U
    'use' => array(),
    //endregion
    //region V
    'view' => array()
    //endregion
);

// merge in shared attributes
foreach ( $ALLOWED_ELEMENTS as $tag_name => $attr ) {
    $ALLOWED_ELEMENTS[ $tag_name ] = array_merge( get_attributes( $tag_name ), $attr );
    ksort( $ALLOWED_ELEMENTS[ $tag_name ] );
}

return $ALLOWED_ELEMENTS;