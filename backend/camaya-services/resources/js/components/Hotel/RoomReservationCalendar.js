import React, {Suspense} from 'react'
import RoomTypeService from 'services/Hotel/RoomType'
import RoomService from 'services/Hotel/Room'
import RoomReservationService from 'services/Hotel/RoomReservation'
import moment from 'moment'
import Loading from 'common/Loading'

import { Row, Col, message, DatePicker, Popover, Tag, Statistic, Card, Select } from 'antd'
import { FilterFilled } from '@ant-design/icons'
const { CheckableTag } = Tag;

import { DataProvider } from 'components/Hotel/RoomReservationCalendar/DataContext';
const ItemList = React.lazy(() => import('components/Hotel/RoomReservationCalendar/ItemList'));


function Page(props) {

    const roomListQuery = RoomService.list();
    const roomTypeListQuery = RoomTypeService.list();  
    const roomReservationDashboardQuery = RoomReservationService.dashboard();

    const tagsData = _.map(roomTypeListQuery.data, 'code');
    const [selectedTags, setSelectedTags] = React.useState([]);
    const [checkedProperties, setcheckedProperties] = React.useState(['AF']);

    const handleChange = (tag, checked) => {
        const nextSelectedTags = checked ? [...selectedTags, tag] : selectedTags.filter(t => t !== tag);
        setSelectedTags(nextSelectedTags);
    }

    const handleCheckedPropertiesChange = (value) => {
        setcheckedProperties(value);
    }

    // console.log(room_reservations);

    const [calendarViewDates, setcalendarViewDates] = React.useState([]);
    const [calendarDateRangeStart, setcalendarDateRangeStart] = React.useState(moment());
    const [calendarDateRangeEnd, setcalendarDateRangeEnd] = React.useState(moment().add(7, 'days'));

    const [targetRef, setTargetRef] = React.useState(null);
    const [selectableWidth, setselectableWidth] = React.useState(0);

    const roomReservationsListQuery = RoomReservationService.list(calendarDateRangeStart, calendarDateRangeEnd);

    const resizeBookingPane = () => {
        if (targetRef) {
            // console.log('resizing...', targetRef.offsetWidth);
            setselectableWidth(targetRef.offsetWidth);
        }
    };

    React.useLayoutEffect(() => {
        resizeBookingPane();
    }, [targetRef]);

    let movement_timer = null;
    window.addEventListener("resize", () => {
        clearInterval(movement_timer);
        movement_timer = setTimeout(resizeBookingPane, 100);
    });

    const enumerateDaysBetweenDates = (start_date, end_date) => {
        let dates = [];
    
        const currDate = moment(start_date).startOf('day');
        const lastDate = moment(end_date).startOf('day');

        dates.push(currDate.clone().toDate());
    
        while (currDate.add(1, 'days').diff(lastDate) < 0) {
            // console.log(currDate.toDate());
            dates.push(currDate.clone().toDate());
        }

        dates.push(lastDate.clone().toDate());
    
        // return dates;
        // console.log(dates);
        setcalendarViewDates(dates);
    }

    React.useEffect( () => {
        
        enumerateDaysBetweenDates(calendarDateRangeStart.format('YYYY-MM-DD'), calendarDateRangeEnd.format('YYYY-MM-DD'));

        // console.log(calendarDateRangeStart.format("YYYY-MM-DD"), calendarDateRangeEnd.format("YYYY-MM-DD"));
        if (calendarDateRangeStart && calendarDateRangeEnd) {
            roomReservationsListQuery.refetch();
        }

        return (() => {

        });

    },[calendarDateRangeStart, calendarDateRangeEnd]);

    const handleCalendarDateRangeChange = (dates) => {
        
        if (dates) {

            
            if (moment(dates[0]).isSame(moment(dates[1]))) {
                dates[1] = moment(dates[0]).add(1,'days');
            } else if (moment.duration(moment(dates[1]).diff(dates[0])).asDays() >= 9) {
                dates[1] = moment(dates[0]).add(10,'days');
                message.info(`Max calendar days range is 10 days. End date is now ${dates[1].format('MMM D, YYYY')}.`);
            }

            setcalendarDateRangeStart(dates[0]);
            setcalendarDateRangeEnd(dates[1]);            
        }

    }

    const CalendarDateRange = () => {
        return (
            <div className="py-4">
                <DatePicker.RangePicker onOpenChange={resizeBookingPane} onChange={handleCalendarDateRangeChange} value={[calendarDateRangeStart, calendarDateRangeEnd]}/>
                <div className="ml-3"><small>Calendar date range</small></div>
            </div>
        )
    }

    const ItemListMemo = React.memo( () => <ItemList {...props} 
            calendarViewDates={calendarViewDates}
            roomReservationsListQuery={roomReservationsListQuery}
            calendarDateRangeStart={calendarDateRangeStart}

            items={roomListQuery.data &&
                    roomListQuery.data
                        .filter(i => selectedTags.length ? _.includes(selectedTags, i.code) : true)
                        .filter(i => checkedProperties.length ? _.includes(checkedProperties, i.property_code) : true)
            }
        />)

    return (
        <div style={{minWidth: '100%'}}>
            <Row gutter={[16,16]}>
                <Col xl={4}>
                    <CalendarDateRange/>
                    <Select style={{width:'100%'}} mode="multiple" onChange={(e)=>handleCheckedPropertiesChange(e)} defaultValue={checkedProperties}>
                        {
                            _.uniq(_.map(roomListQuery.data, i => i.property_code))
                            .map((item, key) => (
                                <Select.Option key={key} value={item}>{_.find(roomListQuery.data, i=>i.property_code == item)['property_name']}</Select.Option>
                            ))
                        }
                    </Select>
                </Col>
                <Col xl={5}>
                    <Card>
                        <Statistic title={<>Hotel guest arrival today</>} value={roomReservationDashboardQuery.data && roomReservationDashboardQuery.data.total_arrival_guest_count} />
                    </Card>
                </Col>
                <Col xl={5}>
                    <Card>
                        <Statistic title={<>Checked-in guests</>} value={roomReservationDashboardQuery.data && roomReservationDashboardQuery.data.checked_in_guests_count} />
                    </Card>
                </Col>
                <Col xl={5}>
                    <Card>
                        <Statistic title="Hotel guest departure today" value={roomReservationDashboardQuery.data && roomReservationDashboardQuery.data.total_departure_guest_count} />
                    </Card>
                </Col>
            </Row>
        <Row gutter={[8,8]}>
            <Col xl={2}>Room
                <Popover
                    content={
                        <div style={{width: 200}}>
                            <Row gutter={[12,12]}>
                                {
                                    tagsData.map((tag, key) => (
                                        <Col xl={12} key={key}>
                                            <CheckableTag
                                                key={tag}
                                                checked={selectedTags.indexOf(tag) > -1}
                                                onChange={checked => handleChange(tag, checked)}
                                            >
                                                {tag}
                                            </CheckableTag>
                                        </Col>
                                    ))
                                }
                            </Row>
                        </div>
                    }
                    title="Filter room type" trigger="click">
                    <FilterFilled className={`ml-2 ${selectedTags.length ? 'text-primary' : ''}`}/>
                </Popover>
            </Col>
            <Col xl={2}><small>Room status</small></Col>
            <Col xl={20} className="room-reservation-table">
                {
                    calendarViewDates.map( (date, i) => {
                        return (
                            <div key={date} style={{textAlign:'center', background: (_.includes(['Sat', 'Sun'], moment(date).format('ddd')) ? '#EBEDEF' : 'transparent'), width: selectableWidth, borderRight: i+1 < calendarViewDates.length ? 'solid 1px #efefef' : 'none'}}>
                                <small style={{fontSize:'0.6rem'}}>{moment(date).format('MMM D, YYYY')}</small>
                                <div style={{display:'flex', flexWrap:'wrap'}}>
                                    <small style={{flexGrow: 1}}>AM</small>
                                    <small style={{flexGrow: 1}}>PM</small>
                                </div>
                            </div>
                        )
                    })
                }
            </Col>
        </Row>
        { !roomListQuery.isLoading &&
            <DataProvider>
                <Suspense fallback={<Loading/>}><ItemListMemo /></Suspense>
                {/* <ItemListMemo /> */}
            </DataProvider>
        }
        </div>
    )
}

export default Page;