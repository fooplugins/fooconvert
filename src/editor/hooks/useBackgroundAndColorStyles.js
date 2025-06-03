import { useMemo } from "@wordpress/element";
import getBackgroundAndColorStyles from "../utils/getBackgroundAndColorStyles";

const useBackgroundAndColorStyles = ( value, colorMap ) => {
    return useMemo( () => getBackgroundAndColorStyles( value, colorMap ), [ value, colorMap ] );
};

export default useBackgroundAndColorStyles;