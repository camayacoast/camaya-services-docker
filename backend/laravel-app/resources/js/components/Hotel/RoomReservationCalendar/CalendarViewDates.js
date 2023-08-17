import React, { useContext } from "react";
import DataContext from "components/Hotel/RoomReservationCalendar/DataContext"
import {useLocation} from "react-router-dom"
import { SelectableGroup, createSelectable } from 'react-selectable';
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');
import ViewBookingComponent from 'components/Booking/View'
import RoomReservationService from 'services/Hotel/RoomReservation'
import { queryCache } from 'react-query'

import { Row, Col, Tooltip, Dropdown, Menu, message, Modal, Button, DatePicker, Form, Input } from 'antd'
import { UserOutlined, LockOutlined, PrinterOutlined, LoginOutlined, LogoutOutlined } from '@ant-design/icons'

const SelectableComponent = createSelectable((props) => {
  return (
      <>
          <div style={{fontSize: '0.5rem', height: 24, textTransform:'lowercase', color: '#aaa', position: 'relative'}}>
            <small style={{position: 'absolute', left: 0}}>{props.locked && <LockOutlined style={{color:'crimson'}}/>}{props.shift == 'AM' ? `00:00` : '12:00' }</small>
          </div>
      </>
  )
});

const BookingPane = ({
        room_reservation,
        date,
        calendarDateRangeStart,
        selectableWidth,
        setBookingToView,
        setviewBookingModalVisible,
        roomReservationsListQuery
    }) => {

    // Post, Put
    const [updateRoomReservationStatusQuery, {isLoading: updateRoomReservationStatusQueryIsLoading, reset: updateRoomReservationStatusQueryReset}] = RoomReservationService.updateRoomReservationStatus();
    const [switchRoomQuery, {isLoading: switchRoomQueryIsLoading, reset: switchRoomQueryReset}] = RoomReservationService.switchRoom();
    const [cancelBlockingQuery, {isLoading: cancelBlockingQueryIsLoading, reset: cancelBlockingQueryReset}] = RoomReservationService.cancelBlocking();
        
    /**
     * Switch Room
     */
    const handleSwitchRoomClick = (room_reservation, room_id) => {
        console.log(room_reservation, room_id);

        if (switchRoomQueryIsLoading) {
            return false;
        }

        switchRoomQuery({
            room_reservation: room_reservation,
            room_id: room_id,
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success('Switch room success!');
                //   queryCache.setQueryData(["rooms-reservations"], prev => {
                //       return [
                //           ...prev.filter(i=>i.id != res.data.id),
                //           {...res.data}
                //       ];
                //   });
                roomReservationsListQuery.refetch();
                switchRoomQueryReset();
            },
            onError: (e) => {
                console.log(e);
                switchRoomQueryReset();
            }
        });

    }

    /**
     * 
     */
    const handleCancelBlocking = (id) => {

            cancelBlockingQuery({
                id: id
            }, {
                onSuccess: (res) => {
                    console.log(res);
                    message.success("Room blocking cancelled!");
                    roomReservationsListQuery.refetch();
                },
                onError: (e) => {
                    console.log(e);
                }
            })

    }

    const handleChangeRoomReservationStatusClick = (room_reservation, reservation_status) => {
        console.log(room_reservation, reservation_status);
  
        if (updateRoomReservationStatusQueryIsLoading) {
            return false;
        }
  
        updateRoomReservationStatusQuery({
            room_reservation: room_reservation,
            reservation_status: reservation_status,
        }, {
            onSuccess: (res) => {
                console.log(res.data);
                message.success('Changed room reservation status!');
                queryCache.setQueryData(["rooms-reservations"], prev => {
                    return [
                        ...prev.filter(i=>i.id != res.data.id),
                        {...res.data}
                    ];
                });
              // roomReservationsListQuery.refetch();
              updateRoomReservationStatusQueryReset();
            },
            onError: (e) => {
                console.log(e);
              updateRoomReservationStatusQueryReset();
            }
        })
    }

    const duration = moment.duration(
            moment(room_reservation.date_of_departure+' '+room_reservation.check_out_time)
            .diff(
                moment(date+' '+room_reservation.check_in_time)
            )
        ).asHours();
    
    // console.log(duration, room_reservation.booking_reference_number);

    const size = duration < 12 ? 1 : Math.round(duration / 12);

    const paneBgColor = {
        'pending': 'orange',
        'confirmed': 'limegreen',
        'checked_out': 'gainsboro',
        'checked_in': 'dodgerblue',
        'blackout': 'black',
    }

    const handleViewBookingDetailsClick = (refno) => {
        setBookingToView(refno);
        setviewBookingModalVisible(true);
    }

    let additional_size = 0;
    // console.log(room_reservation.booking_reference_number, moment(room_reservation.date_of_arrival).format('YYYY-MM-DD') == moment(date).format('YYYY-MM-DD'), moment.duration(moment(room_reservation.date_of_departure).diff(moment(date))).asHours());
    const start_date = moment(calendarDateRangeStart).format('YYYY-MM-DD')+" 00:00:00";
    
    if (moment(room_reservation.date_of_arrival+" "+room_reservation.check_in_time).isBefore(start_date)) {
        additional_size = Math.round(moment.duration(moment(room_reservation.date_of_departure+" "+room_reservation.check_out_time).diff(moment(start_date))).asHours() / 12);
    } else {
        additional_size = size;
    }


    if (room_reservation.status == 'blackout') {
        return (
            <Dropdown
            // onClick={(e) => console.log('...')}
            overlay={
                <Menu>
                    <Menu.Item onClick={()=> handleCancelBlocking(room_reservation.id)}>Cancel blocking</Menu.Item>
                </Menu>}
            trigger={['click']}>
            <Tooltip placement="topLeft" title={
                <><small>{room_reservation.status}</small> <br/> {room_reservation.date_of_arrival} {room_reservation.check_in_time} ~ {room_reservation.date_of_departure} {room_reservation.check_out_time}</>
                }>
            <div
                className={`room-reservation-booking-blackout`}
                id={"id"+_.random(0,1000)+"_"+room_reservation.id}
                style={{
                    position: 'absolute',
                    height: '100%',
                    zIndex: 1000,
                    top: 0,
                    left: 0,
                    // width: selectableWidth * size, moment.duration(end.diff(startTime))
                    width: selectableWidth * (additional_size),
                    background: paneBgColor[room_reservation.status],
                    color: room_reservation.status == 'checked_out' ? '#333' : '#fff',
                    display: 'flex',
                    justifyContent: 'flex-start',
                    alignItems: 'center',
                    minWidth: 0,
                    opacity: 1
                }}
            ><div style={{padding: '0px 4px'}}>{room_reservation.description}</div></div>
            </Tooltip>
            </Dropdown>
        )
    }
    
    return (
        <>
        <Dropdown
            // onClick={(e) => console.log('...')}
            overlay={
                <Menu>
                    <Menu.Item onClick={()=> handleViewBookingDetailsClick(room_reservation.booking_reference_number)}>View Booking Details ({room_reservation.booking_reference_number})</Menu.Item>
                    <Menu.Item onClick={()=> window.open(process.env.APP_URL+'/hotel/guest-registration-form/'+room_reservation.booking_reference_number,"_blank")}><PrinterOutlined /> Print guest information sheet</Menu.Item>
                    <Menu.Divider/>
                    <Menu.Item className="nya" onClick={()=> message.info("Coming soon...")}>View Folio</Menu.Item>
                    <Menu.Divider/>
                    <Menu.SubMenu title="Change Room Reservation Status">
                        { ['confirmed', 'pending', 'checked_out'].includes(room_reservation.status) && <Menu.Item onClick={()=>handleChangeRoomReservationStatusClick(room_reservation, 'checked_in')}><LoginOutlined/> Checked-in</Menu.Item> }
                        { ['checked_in'].includes(room_reservation.status) && <Menu.Item onClick={()=>handleChangeRoomReservationStatusClick(room_reservation, 'checked_out')}><LogoutOutlined/> Checked-out</Menu.Item> }
                    </Menu.SubMenu>
                    {/* <Menu.Item onClick={()=> handleSwitchRoomClick(room_reservation)}>Switch to Room</Menu.Item> */}
                    <Menu.SubMenu title="Change Room to">
                        {
                            room_reservation.available_rooms && room_reservation.available_rooms.map( (item, key) => {
                                return (
                                    <Menu.Item key={key} onClick={()=>handleSwitchRoomClick(room_reservation, item.id)}>{item.number} {item.property.code} {item.type.name} ({item.room_status})</Menu.Item>
                                )
                            })
                        }
                    </Menu.SubMenu>
                    <Menu.Item className="nya" onClick={()=> message.info("Coming soon...")}>Swap with Room</Menu.Item>
                </Menu>}
            trigger={['click']}>
        <Tooltip placement="topLeft" title={
        <>{room_reservation.booking_reference_number} {room_reservation.customer_first_name} {room_reservation.customer_last_name} <small>{room_reservation.status}</small> <br/> {room_reservation.date_of_arrival} {room_reservation.check_in_time} ~ {room_reservation.date_of_departure} {room_reservation.check_out_time}</>
        }>
            <div
                className={`room-reservation-booking-${room_reservation.booking_status}`}
                id={room_reservation.booking_reference_number+"_"+room_reservation.id}
                style={{
                    position: 'absolute',
                    height: '100%',
                    zIndex: 1000,
                    top: 0,
                    left: 0,
                    // width: selectableWidth * size,
                    width: selectableWidth * (additional_size),
                    // width: selectableWidth * (size + (moment(room_reservation.date_of_arrival).format('YYYY-MM-DD') == moment(date).format('YYYY-MM-DD') ? 0 : 0)),
                    // width: selectableWidth * (size + (moment(room_reservation.date_of_arrival).isBefore(moment(date)) ? 1 : 0)),
                    background: paneBgColor[room_reservation.status],
                    color: room_reservation.status == 'checked_out' ? '#333' : '#fff',
                    display: 'flex',
                    justifyContent: 'flex-start',
                    alignItems: 'center',
                    minWidth: 0,
                    opacity: 1
                }}
            >
                
                    <small style={{
                        textOverflow: 'ellipsis',
                        whiteSpace: 'nowrap',
                        overflow: 'hidden',
                    }}>
                        <UserOutlined className="mx-1"/> {room_reservation.customer_first_name} {room_reservation.customer_last_name} | {room_reservation.booking_reference_number}
                    </small>
            </div>
        </Tooltip>
        </Dropdown>
        </>
    )
  }

function CalendarViewDates({
    items,
    roomReservationsListQuery,
    item,
    locked,
    calendarDateRangeStart,
  }) {
  
  const [targetRef, setTargetRef] = React.useState(null);
  const [selectableWidth, setselectableWidth] = React.useState(0);
  const [selectedRoomReservationDates, setselectedRoomReservationDates] = React.useState([]);
  const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);
  const [bookingToView, setBookingToView] = React.useState(null);
  const [datesToBook, setDatesToBook] = React.useState([]);
  const [roomBlockingModalVisible, setroomBlockingModalVisible] = React.useState(false);

  // Put
  const [roomBlockingQuery, {isLoading: roomBlockingQueryIsLoading, reset: roomBlockingQueryReset}] = RoomReservationService.roomBlocking();

  // Form
  const [roomBlockingForm] = Form.useForm();
    
  const resizeBookingPane = () => {
    if (targetRef) {
        // console.log('resizing...', targetRef.offsetWidth);
        setselectableWidth(targetRef.offsetWidth);
    }
  };

  React.useLayoutEffect(() => {
      resizeBookingPane();
  }, [targetRef]);

  const [data, setData,
              //selectItem
        ] = useContext(DataContext);

  React.useEffect( () => {
    // setData(items);
    setData(items);
  },[]);


  const handleSelecting = (e) => {
    console.log(e);
    setselectedRoomReservationDates(e);

    // Check if date is booked
    roomBlockingForm.resetFields();

    // setselectedRoomReservationDates([]);

    if (_.find(e, item => typeof item === 'string')) {
        setselectedRoomReservationDates([]);
        return false;
    }
}

const handleEndSelecting = (items, e) => {
    console.log(items);
    setselectedRoomReservationDates([]);
    setDatesToBook([]);

    if (_.find(items, item => typeof item === 'string')) {
        setselectedRoomReservationDates([]);
        message.info("Date already booked.");
        return false;
    } else {

        const dates = _.map(items, i => {
            return {
                date: `${i.date} ${i.shift == 'AM' ? '00:00:00' : '12:00:00'}`,
                property_code: i.property_code,
                room_number: i.room_number,
            }
        });

        roomBlockingForm.setFieldsValue({
            dates: dates.length ? [moment(dates[0]['date']), moment(dates[dates.length-1]['date'])] : [],
            property_code: dates.length ? dates[0]['property_code'] : '',
            room_number: dates.length ? dates[0]['room_number'] : '',
        });

        setDatesToBook(dates);
        setroomBlockingModalVisible(true);
    }

}

const handleNonItemClick = (e) => {
    // console.log(e);
}

const roomBlockingFormOnFinish = (values) => {
    console.log(values);

    roomBlockingQuery(values, {
        onSuccess: (res) => {
            console.log(res);
            message.success("Room blocking successful!");
            roomReservationsListQuery.refetch();
        },
        onError: (e) => {
            console.log(e)
        }
    })
}

  const BookingPaneMemo = React.memo((props) => <BookingPane
            calendarDateRangeStart={calendarDateRangeStart}
            selectableWidth={selectableWidth}
            setBookingToView={setBookingToView}
            setviewBookingModalVisible={setviewBookingModalVisible}
            roomReservationsListQuery={roomReservationsListQuery}
            {...props}
        />
    )

  let counter = 0;
    let booking_reference_number;
  
  return (
    <React.Fragment>
        <SelectableGroup
            className="room-reservation-table"
            onSelection={handleSelecting}
            onEndSelection={handleEndSelecting}
            onNonItemClick={handleNonItemClick}
        >
      {data && data.map((date, key) => {

        return (
          <React.Fragment key={`date-${key}`}>
                <div>
                    <div style={{display: 'flex', flexWrap: 'wrap', position: 'relative'}}>

                        { 
                            ['00:00','12:00'].map( (shift, key2) => {

                                const selected = _.find(selectedRoomReservationDates, {date : moment(date).format('YYYY-MM-DD'), property_code: item.property_code, room_number: item.number, shift: shift == '00:00' ? 'AM' : 'PM'});

                                const shift_date = moment(date).format('YYYY-MM-DD') + " " + shift + ":00";

                                let isBooked = false;

                                ++counter;

                                const room_reservation = _.find(roomReservationsListQuery.data, (room_reservation) => {
                                    const arrival = room_reservation.date_of_arrival+" "+room_reservation.check_in_time;
                                    const departure = room_reservation.date_of_departure+" "+room_reservation.check_out_time;

                                    return moment(shift_date).isBetween(arrival, departure, 'hour', '[]') && room_reservation.room_id == item.room_id;
                                });

                                if (room_reservation) {

                                    const arrival = room_reservation.date_of_arrival+" "+room_reservation.check_in_time;
                                    const departure = room_reservation.date_of_departure+" "+room_reservation.check_out_time;

                                    isBooked = moment(shift_date).isBetween(arrival, departure, 'hour', '[]');
                                    // if (item.number == '103') console.log(item.number, moment(date).format('YYYY-MM-DD'), isBooked);
                                    if (booking_reference_number != room_reservation.booking_reference_number) {
                                        counter = 0;
                                    }

                                    booking_reference_number = room_reservation.booking_reference_number

                                    // if (item.room_id == 5 && shift == '12:00') console.log(item.room_id, shift_date, isBooked);
                                }

                                return (
                                    <div 
                                        key={key2}
                                        ref={el => { if (shift == '00:00' ) { setTargetRef(prev => el); setselectableWidth(el && el.offsetWidth); } } }
                                        className={[isBooked == false ? 'selectable' : 'date-is-booked']}
                                        style={{background: selected ? 'red' : '', marginRight: shift == '00:00' ? 1 : 0}}
                                    >
                                        { (room_reservation && counter == 0) && <BookingPaneMemo room_reservation={room_reservation} date={moment(date).format('YYYY-MM-DD')} /> }
                                        {
                                            isBooked == false ?
                                                <SelectableComponent
                                                    selected={selected}
                                                    // selectableKey={`${moment(date).format('YYYY-MM-DD')}-${item.property_code}-${item.number}-AM`}
                                                    selectableKey={{
                                                        date: moment(date).format('YYYY-MM-DD'),
                                                        property_code: item.property_code,
                                                        room_number: item.number,
                                                        shift: shift == '00:00' ? 'AM' : 'PM'
                                                    }}
                                                    date={moment(date).format('YYYY-MM-DD')}
                                                    shift={shift == '00:00' ? 'AM' : 'PM'}
                                                    isBooked={false}
                                                    locked={locked}
                                                />
                                            :
                                            <SelectableComponent locked={locked} isBooked={true} selectableKey={`${moment(date).format('YYYY-MM-DD')}-${item.room_id}`} shift={shift == '00:00' ? 'AM' : 'PM'} />
                                            
                                        }
                                    </div>
                                )
                            })

                        }
                        
                    </div>
                </div>
          </React.Fragment>
        );
      })}
      </SelectableGroup>
      <Modal
            visible={viewBookingModalVisible}
            width="100%"
            onCancel={()=>setviewBookingModalVisible(false)}
            footer={null}
        >
            <ViewBookingComponent referenceNumber={bookingToView} />
        </Modal>
        <Modal
            title="Room blocking"
            visible={roomBlockingModalVisible}
            onCancel={()=>setroomBlockingModalVisible(false)}
            footer={null}
        >
            {/* <Button>Booking</Button> */}
            <Form
                layout="vertical"
                form={roomBlockingForm}
                onFinish={roomBlockingFormOnFinish}
                // initialValues={{
                //     dates: datesToBook.length ? [moment(datesToBook[0]['date']), moment(datesToBook[datesToBook.length-1]['date'])] : [],
                //     property_code: datesToBook.length ? datesToBook[0]['property_code'] : '',
                //     room_number: datesToBook.length ? datesToBook[0]['room_number'] : '',
                // }}
            >
                <Row>
            { datesToBook.length > 0 &&
                <>
                    <Col xl={24}>
                        <Form.Item name="dates" label="Dates">
                            <DatePicker.RangePicker style={{width:'100%'}} disabled size="large" showTime />
                        </Form.Item>
                    </Col>
                    <Col xl={12}>
                        <Form.Item name="property_code" label="Property code">
                            <Input readOnly />
                        </Form.Item>
                    </Col>
                    <Col xl={12}>
                        <Form.Item name="room_number" label="Room #">
                            <Input readOnly />
                        </Form.Item>
                    </Col>
                    <Col xl={24}>
                        <Form.Item name="description" label="Description">
                            <Input />
                        </Form.Item>
                    </Col>
                </>
            }
                <Col xl={24} align="right"><Button htmlType="submit">Block room</Button></Col>
                </Row>
            </Form>
        </Modal>
    </React.Fragment>
  );
}

const ItemListMemo = React.memo(CalendarViewDates);

export default ItemListMemo;