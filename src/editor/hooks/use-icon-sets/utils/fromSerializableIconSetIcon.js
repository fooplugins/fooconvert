import { RawSVG } from "../../../components/raw-svg";

/**
 *
 * @param {SerializableIconSetIcon} icon
 * @returns {IconSetIcon}
 */
const fromSerializableIconSetIcon = icon => {
    const { svg, ...restProps } = icon;
    return {
        ...restProps,
        svg: <RawSVG value={ svg }/>
    };
};

export default fromSerializableIconSetIcon;