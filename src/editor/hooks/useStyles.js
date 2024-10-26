import { useMemo } from "@wordpress/element";
import getStyles from "../utils/getStyles";

const useStyles = ( value, colorMap ) => {
    return useMemo( () => getStyles( value, colorMap ), [ value, colorMap ] );
};

export default useStyles;