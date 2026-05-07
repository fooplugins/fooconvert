import "./EditBlock.scss";

import { useBlockProps } from "@wordpress/block-editor";
import { useStyles } from "#editor";
import classnames from "classnames";

import { COUPON_CLASS_NAME } from "../../Edit";

const EditBlock = ( props ) => {
    const {
        children,
        styles,
        settings,
        settingsDefaults
    } = props;

    const containerStyles = useStyles( styles );
    const layout = settings?.layout ?? settingsDefaults?.layout;
    const textAlign = settings?.textAlign ?? settingsDefaults?.textAlign;
    const noLabel = settings?.noLabel ?? settingsDefaults?.noLabel;
    const fillWidth = settings?.fillWidth ?? settingsDefaults?.fillWidth;

    const containerProps = useBlockProps( {
        className: classnames(
            COUPON_CLASS_NAME, {
                "fc--coupon__stack": layout === 'stack',
                "fc--coupon__no-label": noLabel,
                "fc--coupon__fill-width": fillWidth,
            }
        ),
        style: {
            ...containerStyles,
            textAlign
        }
    } );

    return (
        <div { ...containerProps }>
            { children }
        </div>
    );
};

export default EditBlock;
