import { useMemo } from "@wordpress/element";
import { getBorderStyle } from "../utils";
import getBorderRadiusStyle from "../../border-radius-control/utils/getBorderRadiusStyle";

const useBorderStyle = ( border, styleRequired = false ) => {
    return useMemo( () => {
        const borderProps = getBorderStyle( border, styleRequired );
        const borderRadiusProps = getBorderRadiusStyle( border?.radius );
        return { ...borderProps, ...borderRadiusProps };
    }, [ border, styleRequired ] );
};

export default useBorderStyle;