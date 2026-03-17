import { SegmentEditBlock } from "./components";
import { ContainerEditBlock } from "./components/container";
import getTimeDifference from "./utils/getTimeDifference";

const EditBlock = props => {

    const {
        settings,
        settingsDefaults
    } = props;

    let date = null;
    const timestamp = typeof settings?.value === 'string' ? Date.parse( settings.value ) : NaN;
    if ( !isNaN( timestamp ) ) {
        date = new Date( timestamp );
    }
    const diff = getTimeDifference( date );

    return (
        <ContainerEditBlock { ...props }>
            <SegmentEditBlock { ...props } value={ diff.days } key="days" segmentName="days" placeholder="d"/>
            <SegmentEditBlock { ...props } value={ diff.hours } key="hours" segmentName="hours" placeholder="h"/>
            <SegmentEditBlock { ...props } value={ diff.minutes } key="minutes" segmentName="minutes" placeholder="m"/>
            <SegmentEditBlock { ...props } value={ diff.seconds } key="seconds" segmentName="seconds" placeholder="s"/>
        </ContainerEditBlock>
    );
};

export default EditBlock;