import { hasKeys } from "@steveush/utils";
import { isStringNotEmpty } from "../../../utils";
import maybeSVG from "../../../components/raw-svg/utils/maybeSVG";

export const SERIALIZABLE_ICON_SET_ICON_DEFINITION = {
    slug: isStringNotEmpty,
    name: isStringNotEmpty,
    svg: maybeSVG
};

const isSerializableIconSetIcon = value => hasKeys( value, SERIALIZABLE_ICON_SET_ICON_DEFINITION );

export default isSerializableIconSetIcon;