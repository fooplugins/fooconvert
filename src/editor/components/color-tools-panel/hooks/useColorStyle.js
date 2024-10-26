import { useMemo } from "@wordpress/element";
import getColorStyle from "../utils/getColorStyle";

const useColorStyle = ( colors, keyToCSSMap ) => {
    return useMemo( () => getColorStyle( colors, keyToCSSMap ), [ colors, keyToCSSMap ] );
};

export default useColorStyle;