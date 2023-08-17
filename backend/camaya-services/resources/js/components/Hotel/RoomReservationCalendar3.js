import React, {useEffect, useState} from 'react'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');
import HotelRoomReservationService from 'services/Hotel/RoomReservation'
import RoomService from 'services/Hotel/Room'
import { DatePicker, message, Checkbox, Dropdown, Menu, Modal } from 'antd';
import ViewBookingComponent from 'components/Booking/View'

const enumerateDaysBetweenDates = function(startDate, endDate) {
    var dates = [];

    var currDate = moment(startDate).startOf('day');
    var lastDate = moment(endDate).startOf('day');

    while (currDate.add(1, 'days').diff(lastDate) < 0) {
        // console.log(currDate.toDate());
        dates.push(currDate.clone().format('YYYY-MM-DD'));
    }

    return [moment(startDate).format('YYYY-MM-DD'), ...dates, moment(endDate).format('YYYY-MM-DD')];
};

const paneBgColor = {
    'pending': 'orange',
    'confirmed': 'limegreen',
    'checked_out': 'gainsboro',
    'checked_in': 'dodgerblue',
    'blackout': 'black',
}

const bookingStatusColor = {
    'pending' : 'orange',
    'confirmed' : 'limegreen',
    'cancelled' : 'red'
}


export default () => {

    const [calendarDateRangeStart, setcalendarDateRangeStart] = useState(moment());
    const [calendarDateRangeEnd, setcalendarDateRangeEnd] = useState(moment().add(7, 'days'));
    const [period, setPeriod] = useState([]);
    const [propertyFilter, setPropertyFilter] = useState(['AF']);
    const [roomTypeFilter, setRoomTypeFilter] = useState([]);
    const [bookingToView, setBookingToView] = React.useState(null);
    const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);
    
    const roomListQuery = RoomService.list(true);
    const roomReservations = HotelRoomReservationService.roomReservationCalendar(moment(calendarDateRangeStart).format('YYYY-MM-DD'), moment(calendarDateRangeEnd).format('YYYY-MM-DD'));

    useEffect( () => {

        setPeriod(enumerateDaysBetweenDates(moment(calendarDateRangeStart).format('YYYY-MM-DD'), moment(calendarDateRangeEnd).format('YYYY-MM-DD' )))

        console.log(roomReservations.data);
    }, [])

    useEffect(() => {
        
        setPeriod(enumerateDaysBetweenDates(moment(calendarDateRangeStart).format('YYYY-MM-DD'), moment(calendarDateRangeEnd).format('YYYY-MM-DD' )))

        roomReservations.refetch();

    },[calendarDateRangeStart, calendarDateRangeEnd])

    
    const handleCalendarDateRangeChange = (dates) => {
        
        if (dates) {

            
            if (moment(dates[0]).isSame(moment(dates[1]))) {
                dates[1] = moment(dates[0]).add(1,'days');
            } else if (moment.duration(moment(dates[1]).diff(dates[0])).asDays() >= 9) {
                dates[1] = moment(dates[0]).add(15,'days');
                message.info(`Max calendar days range is 15 days. End date is now ${dates[1].format('MMM D, YYYY')}.`);
            }

            setcalendarDateRangeStart(dates[0]);
            setcalendarDateRangeEnd(dates[1]);            
        }

    }

    const CalendarDateRange = () => {
        return (
            <div>
                <DatePicker.RangePicker size='large' onChange={handleCalendarDateRangeChange} value={[calendarDateRangeStart, calendarDateRangeEnd]}/>
            </div>
        )
    }

    const handlePropertyFilterChange = (checked, property) => {
        setPropertyFilter( prev => {
            return checked ? [...prev, property] : [...prev].filter( (i) => i !== property);
        })
    }

    const handleRoomTypeFilterChange = (checked, room_type) => {
        setRoomTypeFilter( prev => {
            return checked ? [...prev, room_type] : [...prev].filter( (i) => i !== room_type);
        })
    }

    const handleViewBookingDetailsClick = (refno) => {
        setBookingToView(refno);
        setviewBookingModalVisible(true);
    }

    return <>

            {
                (bookingToView && viewBookingModalVisible) &&
                <Modal
                    style={{top:16}}
                    visible={viewBookingModalVisible}
                    width="100%"
                    onCancel={()=>{ setviewBookingModalVisible(false); setBookingToView(null); }}
                    footer={null}
                >
                    <ViewBookingComponent referenceNumber={bookingToView} />
                </Modal>
            }
            <CalendarDateRange />

            <div className='my-2'>
                Property filter: {
                    roomListQuery.data && _.uniq(roomListQuery.data.map( (i) => i.property_code)).map( (i, key) => {
                        return <Checkbox checked={propertyFilter.includes(i)} key={key} onChange={(e) => handlePropertyFilterChange(e.target.checked, i)}>{i}</Checkbox>
                    })
                }
            </div>

            <div className='my-2'>
                Room type filter: {
                    roomListQuery.data && _.uniqBy(roomListQuery.data.map( (i) => {
                            return { code: i.code, property_code: i.property_code }
                        }), 'code')
                        .filter( item => propertyFilter.length ? propertyFilter.includes(item.property_code) : true)
                        .map( (i, key) => {
                            return <Checkbox checked={roomTypeFilter.includes(i.code)} key={key} onChange={(e) => handleRoomTypeFilterChange(e.target.checked, i.code)}>{i.code}</Checkbox>
                        })
                }
            </div>

            <div style={{position: 'relative'}}>
                <div className='room-reservation-calendar' style={{marginTop:36, borderRadius: 8, border:'solid 1px gainsboro'}}>
                    <table style={{width: '100%'}}>
                        <thead>
                            <tr>
                                <th className='sticky-header' style={{borderRight:'solid 1px gainsboro', borderBottom:'solid 1px gainsboro', minWidth: 150, width: 150}}>Room</th>
                                {
                                        period.map( (day, key) => {

                                            return <td key={key} valign="top" className="px-3 py-2" style={{borderRight:'solid 1px gainsboro', borderBottom:'solid 1px gainsboro', minWidth: 110, width: 110}}>
                                                        <div style={{position: 'relative'}}>
                                                            { moment().format('D MMM ddd') == moment(day).format('D MMM ddd') ? <div style={{position: 'absolute', top: 12, left: 0, color: 'green', fontSize: '0.7rem'}}><small>today</small></div> : <></>}
                                                            <small><b>{moment(day).format('D MMM ddd')}</b></small>
                                                        </div>
                                                    </td>
                                        })
                                }
                            </tr>
                        </thead>
                        <tbody>
                        {
                            roomListQuery.data && roomListQuery.data
                                .filter( item => propertyFilter.length ? propertyFilter.includes(item.property_code) : true)
                                .filter( item => roomTypeFilter.length ? roomTypeFilter.includes(item.code) : true)
                                .map((room, key) => {

                                return (
                                        <tr key={key}>
                                            <td valign="top" className="px-3 py-2 sticky-header" style={{borderRight:'solid 1px gainsboro', borderBottom:'solid 1px gainsboro'}}>
                                                <div>
                                                    <small><strong>{room.number}</strong> {room.property_code} {room.name}</small>
                                                </div>
                                                {/* <div>
                                                    <small>{room.room_status}</small>
                                                </div> */}
                                            </td>
                                            {
                                                period.map( (day, key) => {

                                                    const reservation = _.find(roomReservations.data ?? [], i => i.property_code == room.property_code && i.room_type == room.code && i.room_number == room.number && i.occupancy_dates.includes(moment(day).format('YYYY-MM-DD')))

                                                    return <td 
                                                                key={key} 
                                                                valign="top" 
                                                                className={`${reservation ? 'px-2 py-1 room-reservation-panel' : 'px-3 py-2'}`} 
                                                                style={{borderLeft: reservation ? `solid 2px ${bookingStatusColor[reservation.booking_status]}` : '', borderRight:'solid 1px gainsboro', borderBottom:'solid 1px gainsboro', backgroundColor: reservation ? paneBgColor[reservation.status] : 'inherit'}}
                                                            >
                                                                <div style={{position: 'relative'}}>
                                                                    {
                                                                        reservation ?
                                                                            <>
                                                                            <Dropdown
                                                                                overlay={
                                                                                    <Menu>
                                                                                        { reservation.status == 'blackout' ?
                                                                                            <>
                                                                                                <Menu.Item key={0} onClick={()=> console.log(reservation.id)}>Cancel blocking</Menu.Item>
                                                                                            </>
                                                                                            :
                                                                                            <>
                                                                                                <Menu.Item key={1} onClick={()=> handleViewBookingDetailsClick(reservation.booking_ref)}>View Booking Details ({reservation.booking_ref})</Menu.Item>
                                                                                            </>
                                                                                        }
                                                                                    </Menu>
                                                                                    }
                                                                                    trigger={['click']}>
                                                                                        <div style={{position: 'relative', zIndex: 10, color: 'white', fontSize: '0.75rem'}}>
                                                                                            { reservation.booking_ref }
                                                                                            <div style={{fontSize: '0.65rem'}}>{ reservation.customer_first_name } { reservation.customer_last_name }</div>
                                                                                            <div style={{fontSize: '0.65rem'}}>{ reservation.market_segmentation.length ? `(${reservation.market_segmentation.join(', ')})` : '' }</div>

                                                                                            { reservation.status == 'blackout' ? 
                                                                                                    <>
                                                                                                        <div style={{fontSize: '0.65rem'}}>{reservation.description}</div>
                                                                                                        <div style={{fontSize: '0.65rem'}}>{reservation.blocked_by}</div>
                                                                                                    </>
                                                                                                    : '' }
                                                                                        </div>
                                                                                </Dropdown>
                                                                            </>
                                                                        :
                                                                            <small style={{color: 'gainsboro', position: 'absolute', top: 2, left: 2, zIndex: 0}}>{moment(day).format('D MMM ddd')}</small>
                                                                    }
                                                                </div>
                                                            </td>
                                                })
                                            }
                                        </tr>
                                )
                            })
                        }
                        </tbody>
                    </table>
                </div>
            </div>
        </>
}