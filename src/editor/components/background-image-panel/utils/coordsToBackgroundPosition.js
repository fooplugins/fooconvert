const coordsToBackgroundPosition = ( value ) => {
    if ( ! value || ( isNaN( value.x ) && isNaN( value.y ) ) ) {
        return undefined;
    }

    const x = isNaN( value.x ) ? 0.5 : value.x;
    const y = isNaN( value.y ) ? 0.5 : value.y;

    return `${ x * 100 }% ${ y * 100 }%`;
};

export default coordsToBackgroundPosition;