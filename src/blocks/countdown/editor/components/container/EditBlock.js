import "./EditBlock.scss";

import { useBlockProps } from "@wordpress/block-editor";
import { useStyles } from "#editor";
import classnames from "classnames";

import { COUNTDOWN_CLASS_NAME } from "../../Edit";

const EditBlock = ( props ) => {
    const {
        children,
        styles,
        settings,
        settingsDefaults,
        segmentSettings,
        segmentSettingsDefaults
    } = props;

    const segmentLayout = segmentSettings?.layout ?? segmentSettingsDefaults?.layout;
    const containerStyles = useStyles( styles );

    const containerProps = useBlockProps( {
        className: classnames(
            COUNTDOWN_CLASS_NAME,
            `${ COUNTDOWN_CLASS_NAME }__segments__layout-${ segmentLayout }`
        ),
        style: {
            ...containerStyles
        }
    } );

    const segmentsProps = {
        className: classnames( `${ COUNTDOWN_CLASS_NAME }__segments`, `${ COUNTDOWN_CLASS_NAME }__segments__layout-${ segmentLayout }` )
    };

    return (
        <div { ...containerProps }>
            <div { ...segmentsProps }>
                { children }
            </div>
        </div>
    );
};

export default EditBlock;