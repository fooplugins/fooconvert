import { useMemo } from "@wordpress/element";
import getDimensionStyle from "../utils/getDimensionStyle";

const useDimensionStyle = ( dimensions, properties = [ 'padding', 'margin', 'gap' ] ) => {
    return useMemo( () => getDimensionStyle( dimensions, properties ), [ dimensions, properties ] );
};

export default useDimensionStyle;