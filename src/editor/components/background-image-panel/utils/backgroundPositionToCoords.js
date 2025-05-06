const backgroundPositionToCoords = ( value ) => {
    if ( ! value ) {
        return { x: undefined, y: undefined };
    }

    let [ x, y ] = value.split( ' ' ).map( ( v ) => parseFloat( v ) / 100 );
    x = isNaN( x ) ? undefined : x;
    y = isNaN( y ) ? x : y;

    return { x, y };
};

export default backgroundPositionToCoords;