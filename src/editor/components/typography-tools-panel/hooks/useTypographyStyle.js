import { useMemo } from "@wordpress/element";
import getTypographyStyle from "../utils/getTypographyStyle";

const useTypographyStyle = ( typography ) => {
    return useMemo( () => getTypographyStyle( typography ), [ typography ] );
};

export default useTypographyStyle;