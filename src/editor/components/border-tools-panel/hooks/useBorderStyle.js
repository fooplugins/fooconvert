import { useMemo } from "@wordpress/element";
import { getBorderStyle } from "../utils";
import getBorderRadiusStyle from "../../border-radius-control/utils/getBorderRadiusStyle";
import getBoxShadowStyle from "../../box-shadow-control/utils/getBoxShadowStyle";

const useBorderStyle = ( border, styleRequired = false ) => {
    return useMemo( () => {
        const borderProps = getBorderStyle( border, styleRequired );
        const borderRadiusProps = getBorderRadiusStyle( border?.radius );
        const boxShadowProps = getBoxShadowStyle( border?.shadow );
        return { ...borderProps, ...borderRadiusProps, ...boxShadowProps };
    }, [ border, styleRequired ] );
};

export default useBorderStyle;