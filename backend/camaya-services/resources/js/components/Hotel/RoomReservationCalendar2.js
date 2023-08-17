import React, {useState, useEffect} from 'react'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');
import RoomService from 'services/Hotel/Room'
import RoomReservationService from 'services/Hotel/RoomReservation'
import RoomAllocationService from 'services/Hotel/RoomAllocationService'
import RoomTypeService from 'services/Hotel/RoomType'
import ViewBookingComponent from 'components/Booking/View'
import {queryCache} from 'react-query'

import { DatePicker, message, Dropdown, Menu, Modal, Form, Row, Col, Input, Button, Statistic, Select, Card, Popover, Tag, Tooltip, Descriptions, Typography, Radio } from 'antd'
const { CheckableTag } = Tag;
import { PrinterOutlined, LoginOutlined, LogoutOutlined, FilterFilled, CloseOutlined, CalendarOutlined, EnterOutlined } from '@ant-design/icons'

const paneBgColor = {
    'pending': 'orange',
    'confirmed': 'limegreen',
    'checked_out': 'gainsboro',
    'checked_in': 'dodgerblue',
    'blackout': 'black',
}

const roomStatusColor = {
    clean: '#5b8c00',
    clean_inspected: '#7cb305',
    dirty: '#873800',
    dirty_inspected: '#873800',
    pickup: '#597ef7',
    sanitized: '#36cfc9',
    inspected: '#4096ff',
    'out-of-service': '#434343',
    'out-of-order': '#262626',
    'none':'gray'
}

const BookingPane = (props) => {

    // States
    const [changeRoomReservationForm] = Form.useForm();

    // Post, Put
    const [updateRoomReservationStatusQuery, {isLoading: updateRoomReservationStatusQueryIsLoading, reset: updateRoomReservationStatusQueryReset}] = RoomReservationService.updateRoomReservationStatus();
    const [switchRoomQuery, {isLoading: switchRoomQueryIsLoading, reset: switchRoomQueryReset}] = RoomReservationService.switchRoom();
    const [cancelBlockingQuery, {isLoading: cancelBlockingQueryIsLoading, reset: cancelBlockingQueryReset}] = RoomReservationService.cancelBlocking();
    const [updateCheckInTimeQuery, { isLoading: updateCheckInTimeQueryIsLoading, reset: updateCheckInTimeQueryReset}] = RoomReservationService.updateCheckInTime();

    /**
     * Switch Room
     */
    const handleSwitchRoomClick = (room_reservation, room_id) => {
        // console.log(room_reservation, room_id);

        if (switchRoomQueryIsLoading) {
            return false;
        }

        switchRoomQuery({
            room_reservation: room_reservation,
            room_id: room_id,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Switch room success!');
                //   queryCache.setQueryData(["rooms-reservations"], prev => {
                //       return [
                //           ...prev.filter(i=>i.id != res.data.id),
                //           {...res.data}
                //       ];
                //   });
                props.roomReservationsListQuery.refetch();
                switchRoomQueryReset();
            },
            onError: (e) => {
                // console.log(e);
                switchRoomQueryReset();
                message.error(e.message);
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
                    // console.log(res);
                    message.success("Room blocking cancelled!");
                    props.roomReservationsListQuery.refetch();
                    props.allocationPerDateQuery.refetch();
                },
                onError: (e) => {
                    // console.log(e);
                }
            })

    }

    const handleRoomReservationChangeStatus = (room_reservation, reservation_status, formData = {}) => {

        if (updateRoomReservationStatusQueryIsLoading) return false;

        const newFormData = {
            ...formData,
            check_in_time: formData.check_in_time ? moment(formData.check_in_time).format('YYYY-MM-DD HH:mm:ss') : null,
            check_out_time: formData.check_out_time ? moment(formData.check_out_time).format('YYYY-MM-DD HH:mm:ss') : null,
        }

        // console.log(newFormData);
        // return false;

        updateRoomReservationStatusQuery({
            room_reservation: room_reservation,
            reservation_status: reservation_status,
            data: newFormData
        }, {
            onSuccess: (res) => {
                // console.log(res.data);
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
                // console.log(e);
              updateRoomReservationStatusQueryReset();
            }
        })
    }

    const handleChangeRoomReservationStatusClick = (room_reservation, reservation_status) => {
        // console.log(room_reservation, reservation_status);
  
        if (updateRoomReservationStatusQueryIsLoading) {
            return false;
        }

        if (reservation_status == 'cancelled') {
            Modal.confirm({
                title: 'Are you sure?',
                onOk: (close) => {
                    close();
                    handleRoomReservationChangeStatus(room_reservation, reservation_status);
                },
                onCancel: (close) => {
                    close();
                    return false;
                }
            })
        } else {
            console.log(room_reservation, reservation_status);
            Modal.info({
                icon: null,
                title: 'Change Room Reservation Status',
                onOk: () => handleRoomReservationChangeStatus(room_reservation, reservation_status, changeRoomReservationForm.getFieldsValue()),
                closable: true,
                okText: <>Change Status</>,
                content: <div className='mt-4'>
                    <div className='mb-2'>
                        <label className='mr-2'>Changing room status to</label>
                        <b>{reservation_status.toUpperCase().replace('_', ' ')}</b>
                    </div>

                    <Form form={changeRoomReservationForm}>
                    { reservation_status == 'checked_in' ?
                        <>
                            <div className='mt-2'>
                                <label className='mb-1'>Check-in time</label>
                                <div><Form.Item name="check_in_time" initialValue={moment()}><DatePicker allowClear={false} showTime placeholder="Check-in time" /></Form.Item></div>
                            </div>
                            <div className='mt-2'>
                                <label className='mb-1'>Room FO Status to</label>
                                <div>
                                    <Form.Item name="in_room_fo_status" initialValue={moment().format('YYYY-MM-DD') != room_reservation.date_of_arrival ? '' : 'occupied'}>
                                        <Radio.Group disabled={moment().format('YYYY-MM-DD') != room_reservation.date_of_arrival}>
                                            <Radio.Button value="vacant">Vacant</Radio.Button>
                                            <Radio.Button value="occupied">Occupied</Radio.Button>
                                        </Radio.Group>
                                    </Form.Item>
                                </div>
                            </div>
                        </>
                    :
                        <>
                            <div className='mt-2'>
                                <label className='mb-1'>Check-out time</label>
                                <div><Form.Item name="check_out_time" initialValue={moment()}><DatePicker allowClear={false} showTime placeholder="Check-out time" /></Form.Item></div>
                            </div>
                            <div className='mt-2'>
                                <label className='mb-1'>Room FO Status to</label>
                                <div>
                                    <Form.Item name="out_room_fo_status" initialValue={moment().format('YYYY-MM-DD') != room_reservation.date_of_departure ? '' : 'vacant'}>
                                        <Radio.Group disabled={moment().format('YYYY-MM-DD') != room_reservation.date_of_departure}>
                                            <Radio.Button value="vacant">Vacant</Radio.Button>
                                            <Radio.Button value="occupied">Occupied</Radio.Button>
                                        </Radio.Group>
                                    </Form.Item>
                                </div>
                            </div>
                        </>
                    }
                    </Form>
                </div>
            });
            // handleRoomReservationChangeStatus(room_reservation, reservation_status);
        }
    }

    // Handle Check in time change
    const handleUpdateCheckinTime = (id, time) => {
        // console.log(id, time);

        if (updateCheckInTimeQueryIsLoading) return false;

        updateCheckInTimeQuery({
            id: id,
            time: time,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Succesfully updated check-in time!');
                // viewBookingQuery.refetch();
                queryCache.setQueryData(["rooms-reservations"], prev => {

                    const i = _.findIndex(prev, item => item.id == id);
                    prev[i].check_in_datetime = time;

                    return [...prev];
                });
            },
            onError: (e) => message.danger(e.error)
        });
    }


    const handleViewBookingDetailsClick = (refno) => {
        props.setBookingToView(refno);
        props.setviewBookingModalVisible(true);
    }

    const handleTransferRoomClick = (room_reservation) => {
        // console.log(room_reservation);

        props.setTransferRoomModalVisible(true);
        props.setTransferRoomData(room_reservation);
    }

    // console.log(props);
    const duration = moment.duration(
        moment(props.roomReservation.date_of_departure+' '+props.roomReservation.check_out_time)
        .diff(
            moment(props.date+' '+props.roomReservation.check_in_time)
        )
    ).asHours();

    
    const size = duration < 12 ? 50 : Math.round(duration / 12) * 50;

    return (
        <Dropdown
            // onClick={(e) => console.log('...')}
            overlay={
                <Menu>
                    { props.roomReservation.status == 'blackout' ?
                        <>
                            <Menu.Item key={0} onClick={()=> handleCancelBlocking(props.roomReservation.id)}>Cancel blocking</Menu.Item>
                        </>
                        :
                        <>
                            <Menu.Item key={1} onClick={()=> handleViewBookingDetailsClick(props.roomReservation.booking_reference_number)}>View Booking Details ({props.roomReservation.booking_reference_number})</Menu.Item>
                            <Menu.Item key={2} onClick={()=> window.open(process.env.APP_URL+'/booking/view-booking/'+props.roomReservation.booking_reference_number,"_blank")}>Open Booking Details ({props.roomReservation.booking_reference_number})</Menu.Item>
                            <Menu.Item key={3} onClick={()=> window.open(process.env.APP_URL+'/hotel/guest-registration-form/'+props.roomReservation.booking_reference_number,"_blank")}><PrinterOutlined /> Print guest information sheet</Menu.Item>
                            <Menu.Divider/>
                            <Menu.Item key={4} className="nya" onClick={()=> message.info("Coming soon...")}>View Folio</Menu.Item>
                            <Menu.Divider/>
                            <Menu.SubMenu key={5} disabled={props.roomReservation.date_of_arrival != moment().format('YYYY-MM-DD') && props.roomReservation.status != 'checked_in'} title="Change Room Reservation Status">
                                { ['confirmed', 'pending', 'checked_out'].includes(props.roomReservation.status) && <Menu.Item key={6} onClick={()=>handleChangeRoomReservationStatusClick(props.roomReservation, 'checked_in')}><LoginOutlined/> Checked-in</Menu.Item> }
                                { ['checked_in'].includes(props.roomReservation.status) && <Menu.Item key={7} onClick={()=>handleChangeRoomReservationStatusClick(props.roomReservation, 'checked_out')}><LogoutOutlined/> Checked-out</Menu.Item> }
                                <Menu.Item key={8} onClick={()=>handleChangeRoomReservationStatusClick(props.roomReservation, 'cancelled')}><CloseOutlined/> Cancel</Menu.Item>
                            </Menu.SubMenu>
                            <Menu.Item key={9} hidden={props.roomReservation.status != 'checked_in'} disabled={props.roomReservation.status < moment().format('YYYY-MM-DD') && props.roomReservation.status != 'checked_in'} onClick={()=> Modal.info({ title: 'Change check-in time', content: <DatePicker onChange={e => handleUpdateCheckinTime(props.roomReservation.id, moment(e).format('YYYY-MM-DD HH:mm:ss'))} showTime defaultValue={moment(props.roomReservation.check_in_datetime)} allowClear={false} />})}>Change Check-in Time</Menu.Item>
                            {/* Modal.info({ title: 'Change check-in time', content: <DatePicker value={moment(props.roomReservation.check_in_time)} allowClear={false} />}) */}
                            {/* <Menu.Item onClick={()=> handleSwitchRoomClick(props.roomReservation)}>Switch to Room</Menu.Item> */}
                            <Menu.SubMenu key={10} title="Change Room to">
                                {
                                    props.roomReservation.available_rooms && props.roomReservation.available_rooms.map( (item, key) => {
                                        return (
                                            <Menu.Item key={`available-${key}`} onClick={()=>handleSwitchRoomClick(props.roomReservation, item.id)}>{item.number} {item.property.code} {item.type.name} ({item.room_status})</Menu.Item>
                                        )
                                    })
                                }
                            </Menu.SubMenu>
                            <Menu.Item key={11} onClick={()=> handleTransferRoomClick(props.roomReservation)}>Transfer Room</Menu.Item>
                            {/* <Menu.Item className="nya" onClick={()=> message.info("Coming soon...")}>Swap with Room</Menu.Item> */}
                        </>
                    }
                </Menu>
                }
            trigger={['click']}>
                {/* <Tooltip placement="topLeft" title={
                    <><small>{props.roomReservation.status}</small> <br/> {props.roomReservation.date_of_arrival} {props.roomReservation.check_in_time} ~ {props.roomReservation.date_of_departure} {props.roomReservation.check_out_time} (Adult:{props.roomReservation.adult_pax}|Kid:{props.roomReservation.kid_pax}|Infant:{props.roomReservation.infant_pax})
                </> */}

                <Tooltip placement="topLeft" title={
                    <><small style={{textTransform:'uppercase'}}>{props.roomReservation.status}</small> <br/> 
                    Guest: {props.roomReservation.customer_first_name} {props.roomReservation.customer_last_name} <br/>
                    Arrival: {moment(props.roomReservation.date_of_arrival).format('MMM D, YYYY')} {props.roomReservation.check_in_time} PM<br/> 
                    Departure: {moment(props.roomReservation.date_of_departure).format('MMM D, YYYY')} {props.roomReservation.check_out_time} AM<br/>
                    Adult: {props.roomReservation.adult_pax} / Kid: {props.roomReservation.kid_pax} / Infant: {props.roomReservation.infant_pax} <br/>
                    Created by: {props.roomReservation.booked_by?.first_name ?? ''} {props.roomReservation.booked_by?.last_name ?? ''} <br/>
                    {/* Room Category: <br/> */}
                    {/* {props.roomReservation.room_number} {props.roomReservation.room_type_name} <br/> */}
                    
                    {/* Market Segmentation: {props.roomReservation.market_segmentation} <br/> */}
                    {/* Rate:  <br/> */}
                    {/* Payment:  <br/> */}
                    {/* Balance:  <br/> */}
                </>

                }>
                    <div
                        className={`hotel-calendar-booking-pane p-2 room-reservation-booking-${props.roomReservation.booking_status}`}
                        style={{
                            width: size+'%',
                            background: paneBgColor[props.roomReservation.status],
                            color: props.roomReservation.status == 'checked_out' ? '#333' : '#fff',
                            fontSize: '0.7rem',
                        }}
                    >
                        { props.roomReservation.status == 'blackout' ?
                            <>[{props.roomReservation.booking_reference_number}] {props.roomReservation.description}</>
                            :
                            <>[{props.roomReservation.booking_reference_number}] <div>{props.roomReservation.customer_first_name} {props.roomReservation.customer_last_name} {(props.roomReservation.market_segmentation&&props.roomReservation.market_segmentation.length) ? <>({props.roomReservation.market_segmentation})</> : ''}</div></>
                        }
                    </div>
                </Tooltip>
                
        </Dropdown>
    )    
}

const RoomList = (props) => {

    // console.log(props);

    // States
    // const [targetRef, setTargetRef] = React.useState(null);
    // const [selectableWidth, setselectableWidth] = React.useState(0);
    const [roomReservations, setRoomReservations] = React.useState(props.roomReservationsListQuery.data);
    const [bookingToView, setBookingToView] = React.useState(null);
    const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);
    // const [setDatesToBook, setDatesToBook] = React.useState([]);
    const [roomBlockingModalVisible, setroomBlockingModalVisible] = React.useState(false);
    const [lastAvailableReservationDate, setlastAvailableReservationDate] = React.useState({});
    const [transferRoomModalVisible, setTransferRoomModalVisible] = React.useState(false);
    const [transferRoomData, setTransferRoomData] = React.useState({});
    const [availableRoomsForDate, setAvailableRoomsForDate] = React.useState([]);

    // Get 
    const roomListQuery = RoomService.list(true);

    // Put
    const [roomBlockingQuery, {isLoading: roomBlockingQueryIsLoading, reset: roomBlockingQueryReset}] = RoomReservationService.roomBlocking();
    const [getLastAvailableReservationDateQuery, {isLoading: getLastAvailableReservationDateQueryIsLoading, reset: getLastAvailableReservationDateQueryReset}] = RoomReservationService.getLastAvailableReservationDate();
    const [getAvailableRoomsForDateQuery, {isLoading: getAvailableRoomsForDateQueryIsLoading, reset: getAvailableRoomsForDateQueryReset}] = RoomReservationService.getAvailableRoomsForDate();
    const [roomTransferQuery, {isLoading: roomTransferQueryIsLoading, reset: roomTransferQueryReset}] = RoomReservationService.roomTransfer();

    // Form
    const [roomBlockingForm] = Form.useForm();

    React.useEffect( () => {
        if (props.roomReservationsListQuery.data) {
            // console.log(props.roomReservationsListQuery.data);
            setRoomReservations(props.roomReservationsListQuery.data);
        }
    },[props.roomReservationsListQuery.data]);

    React.useEffect( () => {

        if (transferRoomModalVisible == false) {
            setTransferRoomData({});
        }

    }, [transferRoomModalVisible]);

    const roomBlockingFormOnFinish = (values) => {
        // console.log(values);
    
        roomBlockingQuery(values, {
            onSuccess: (res) => {
                // console.log(res);
                message.success("Room blocking successful!");
                props.roomReservationsListQuery.refetch();
                setroomBlockingModalVisible(false);
            },
            onError: (e) => {
                // console.log(e)
                message.error(e.error);
            }
        })
    }

    /**
     * Date cell click
     */
    const handleDateCellClick = (date, room_id) => {
        // console.log(date, room_id);

        // console.log(moment(date).format('YYYY-MM-DD 12:00:00'));

        const start_date = moment(date).format('YYYY-MM-DD 12:00:00');
        const room = _.find(roomListQuery.data, i => i.room_id == room_id);

        /**
         * Get last available date
         */
        getLastAvailableReservationDateQuery({
            room_id: room_id,
            start_date: start_date,
        }, {
            onSuccess: (res) => {
                // console.log(res);

                if (res.data) {
                    setlastAvailableReservationDate({
                        room_id: res.data.room_id,
                        date: res.data.start_datetime
                    });
                }

                roomBlockingForm.setFieldsValue({
                    room_id: room_id,
                    room_number: room.number || null,
                    property_code: room.property_code || null,
                    dates: [moment(start_date), moment(start_date).add(12, 'hours')]
                })
        
                setroomBlockingModalVisible(true);
            },
            onError: (e) => {
                message.error(e.error);
            }
        });

    }

    const enumerateDaysBetweenDates = function(startDate, endDate) {
        var dates = [];
    
        var currDate = moment(startDate).startOf('day');
        var lastDate = moment(endDate).startOf('day');
    
        while(currDate.add(1, 'days').diff(lastDate) < 0) {
            // console.log(currDate.toDate());
            dates.push(currDate.clone().format('YYYY-MM-DD'));
        }
    
        return dates;
    };

    const handleSelectRoomTransferClick = (date, room_data) => {
        // console.log(room_data);

        const entity = room_data.market_segmentation[0];
        setAvailableRoomsForDate([]);

        if (getAvailableRoomsForDateQueryIsLoading) {
            return false;
        }

        getAvailableRoomsForDateQuery({
            date:date,
            entity:entity,
        }, {
            onSuccess: (res) => {
                // console.log(res)
                setAvailableRoomsForDate(res.data);
            },
            onError: (e) => message.danger(e.message)
        });

    }

    const handleSelectRoomTransferChange = (date, room_data, room_id) => {
        // console.log(date, room_data, room_id);

        if (roomTransferQueryIsLoading) {
            return false;
        }

        roomTransferQuery({
            date: date,
            room_data: room_data,
            room_id: room_id,
        }, {
            onSuccess: (res) => {
                message.success("Transfer room successful!");
                props.roomReservationsListQuery.refetch();
                setTransferRoomModalVisible(false);
            },
            onError: (e) => message.danger(e.error),
        })
    }
 
    const BookingPaneMemo = React.memo((props) => <BookingPane {...props} />)

    return <React.Fragment>
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

        {
            (transferRoomData && transferRoomModalVisible) &&
            <Modal
                style={{top:16}}
                visible={transferRoomModalVisible}
                width="100%"
                onCancel={()=>{ setTransferRoomModalVisible(false) }}
                footer={null}
            >
                <Typography.Title level={3}>Transfer Room</Typography.Title>
                
                <div className="mt-4">
                    <Descriptions>
                        <Descriptions.Item label="Room #">
                            {transferRoomData.room.number} ({transferRoomData.room.type.name})
                        </Descriptions.Item>
                        <Descriptions.Item label="Date of visit #">
                            {transferRoomData.date_of_arrival} ~ {transferRoomData.date_of_departure}
                        </Descriptions.Item>
                        <Descriptions.Item label="Booking reference #">
                            {transferRoomData.booking_reference_number}
                        </Descriptions.Item>
                    </Descriptions>
                </div>

                <Row gutter={[8,8]} className="mt-4">
                    {

                        [transferRoomData.date_of_arrival, ...enumerateDaysBetweenDates(transferRoomData.date_of_arrival, transferRoomData.date_of_departure)]
                        .map( (date, key) => {
                            return (
                                <Col key={key} xs={4} style={{border:'solid 1px gainsboro'}} className="p-2">
                                    <div style={{textAlign:'center'}}><CalendarOutlined/> {date}</div>
                                    <Select loading={getAvailableRoomsForDateQueryIsLoading} defaultValue={transferRoomData.room_id} onClick={(e) => handleSelectRoomTransferClick(date, transferRoomData)} onChange={(e) => handleSelectRoomTransferChange(date, transferRoomData, e)} style={{width: '100%'}}>
                                        <Select.Option value={transferRoomData.room_id}>{transferRoomData.room.number} ({transferRoomData.room.type.name}) ({transferRoomData.market_segmentation.length > 0 ? transferRoomData.market_segmentation[0] : ''})</Select.Option>
                                        {
                                            availableRoomsForDate ?
                                            availableRoomsForDate.map( (item, key) => {
                                                return <Select.Option disabled={item.remaining <= 0} key={key+date} value={item.room_id}><small>{item.room_number} {item.property_code}-{item.room_type_name} (rem.: {item.remaining})</small></Select.Option>
                                            })
                                            :''
                                        }
                                    </Select>
                                </Col>
                            )
                        })
                    }
                </Row>
                
            </Modal>
        }
        
            <Modal
                title="Room blocking"
                visible={roomBlockingModalVisible}
                onCancel={()=>setroomBlockingModalVisible(false)}
                footer={null}
            >
                <Form
                    layout="vertical"
                    form={roomBlockingForm}
                    onFinish={roomBlockingFormOnFinish}
                >
                    <Row>
                        <Col xl={24}>
                            <Form.Item name="dates" label="Dates" rules={[{required:true}]}>
                                <DatePicker.RangePicker disabled={[true, false]} style={{width:'100%'}} size="large"
                                // showTime={false}
                                showTime={{
                                    minuteStep: 30,
                                    secondStep: 30,
                                    hideDisabledOptions: true,
                                    disabledHours: () => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23],
                                    disabledMinutes: (selectedHour) => [30],
                                    disabledSeconds: (selectedHour, selectedMinute) => [30],
                                }}
                                disabledDate={
                                    (currentDate) => (roomBlockingForm.getFieldValue('dates')[0].format('YYYY-MM-DD') == currentDate.format('YYYY-MM-DD') || (currentDate > moment(lastAvailableReservationDate.date) &&  roomBlockingForm.getFieldValue('room_id') == lastAvailableReservationDate.room_id))
                                }
                                // showTime={{
                                    // minuteStep: 0,
                                    // secondStep: 30,
                                    // hideDisabledOptions: true,
                                    // disabledHours: () => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22],
                                    // disabledMinutes: (selectedHour) => selectedHour == 23 ? [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58] : [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59],
                                    // disabledSeconds: (selectedHour, selectedMinute) => [30],
                                // }}
                                />
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item name="property_code" label="Property code" rules={[{required:true}]}>
                                <Input readOnly />
                            </Form.Item>
                        </Col>
                        <Col xl={12}>                        
                            <Form.Item noStyle name="room_id" rules={[{required:true}]}>
                                <Input hidden />
                            </Form.Item>
                            <Form.Item name="room_number" label="Room #" rules={[{required:true}]}>
                                <Input readOnly />
                            </Form.Item>
                        </Col>
                        <Col xl={24}>
                            <Form.Item name="description" label="Description">
                                <Input />
                            </Form.Item>
                        </Col>
                        <Col xl={24} align="right"><Button htmlType="submit">Block room</Button></Col>
                        </Row>
                    </Form>
                </Modal>
        
        {
            roomListQuery.data && roomListQuery.data
                .filter(i => props.selectedTags.length ? _.includes(props.selectedTags, i.code) : true)
                .filter(i => props.checkedProperties.length ? _.includes(props.checkedProperties, i.property_code) : true)
                .map((room, key) => {

                let counter = 0;
                let booking_reference_number;                

                return (
                        <div className="d-table-row" key={key}>
                            <div className="d-table-cell" style={{color: room.enabled == 0 ? 'crimson' : 'inherit', width: 180}}>
                                <small><strong>{room.number}</strong> {room.property_code} {room.name}</small>
                            </div>
                            <div className="d-table-cell" style={{ color: roomStatusColor[room.room_status], textTransform:'capitalize', width: 80}}>
                                <small>{room.room_status}</small>
                            </div>
                            {
                                props.calendarViewDates && props.calendarViewDates.map((date, key) => {

                                    // const room_reservation = roomReservations && _.find(roomReservations, i => i.room_id == room.room_id && i.date_of_arrival == moment(date).format('YYYY-MM-DD'));

                                    const shift_date = moment(date).format('YYYY-MM-DD')+" 12:00:00";

                                    const room_reservation = _.find(roomReservations, (room_reservation) => {
                                        const arrival = room_reservation.date_of_arrival+" "+room_reservation.check_in_time;
                                        const departure = room_reservation.date_of_departure+" "+room_reservation.check_out_time;

                                        return moment(shift_date).isBetween(arrival, departure, 'hour', '[]') && room_reservation.room_id == room.room_id && room_reservation.status != 'cancelled';
                                    });

                                    // ++counter;

                                    let re = new RegExp(props.customerBookingCode, 'i');
                                    let highlight = false;

                                    if (room_reservation) {
                                        if (booking_reference_number != room_reservation.booking_reference_number) {
                                            counter = 0;
                                        }
    
                                        booking_reference_number = room_reservation.booking_reference_number

                                        // highlight search input in Hotel Calendar (Primary Customer Name, Booking Code)                                   
                                        if (props.customerBookingCode) {
                                            highlight = re.test(room_reservation.booking_reference_number) || re.test(`${room_reservation.customer_first_name} ${room_reservation.customer_last_name}`)
                                        }
                                    }                                    
                                    

                                    return (
                                        <div
                                            // ref={el => { setTargetRef(prev => el); setselectableWidth(el && el.offsetWidth); } }
                                            key={key}
                                            className={`d-table-cell p-2 hotel-calendar-date-cell ${highlight && ' highlight-calendar'}`}
                                            onClick={() => room_reservation ? null : handleDateCellClick(moment(date).format('YYYY-MM-DD'), room.room_id)}
                                        >
                                            <small style={{opacity:0.2}}>
                                                {/* {moment(date).format('YYYY-MM-DD')} */}
                                                {
                                                    (props.allocationPerDateQuery.data && props.allocationPerDateQuery.data[moment(date).format('YYYY-MM-DD')]) ?
                                                        _.filter(props.allocationPerDateQuery.data[moment(date).format('YYYY-MM-DD')], {room_type_id: room.room_type_id})
                                                        .map( (i,key) => {
                                                            return <div key={key} className="text-info">{i.entity} {i.allocation - i.used}</div>
                                                        })
                                                    : '-'
                                                }
                                            </small>
                                            { 
                                                (room_reservation && counter == 0) ?
                                                    <BookingPaneMemo
                                                        roomReservation={room_reservation}
                                                        date={moment(date).format('YYYY-MM-DD')}
                                                        setBookingToView={setBookingToView}
                                                        setviewBookingModalVisible={setviewBookingModalVisible}
                                                        roomReservationsListQuery={props.roomReservationsListQuery}
                                                        allocationPerDateQuery={props.allocationPerDateQuery}
                                                        setTransferRoomModalVisible={setTransferRoomModalVisible}
                                                        setTransferRoomData={setTransferRoomData}
                                                        customerBookingCode={props.customerBookingCode}
                                                    />
                                                    :
                                                    
                                                        props.roomReservationsListQuery && props.roomReservationsListQuery.isLoading &&
                                                        <>Loading...</>
                                                    
                                            }
                                        </div>
                                    )
                                })
                            }
                        </div>
                )
            })
        }
    </React.Fragment>

}

function Page(props) {

    // States
    const [calendarViewDates, setcalendarViewDates] = React.useState([]);
    const [calendarDateRangeStart, setcalendarDateRangeStart] = React.useState(moment());
    const [calendarDateRangeEnd, setcalendarDateRangeEnd] = React.useState(moment().add(6, 'days'));
    const [customerBookingCode, setcustomerBookingCode] = React.useState('');

    // Get
    const roomReservationsListQuery = RoomReservationService.list(calendarDateRangeStart, calendarDateRangeEnd, customerBookingCode);
    const allocationPerDateQuery = RoomAllocationService.allocationPerDate(calendarDateRangeStart.format('YYYY-MM-DD'), calendarDateRangeEnd.format('YYYY-MM-DD'));
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

    React.useEffect( () => {
        
        enumerateDaysBetweenDates(calendarDateRangeStart.format('YYYY-MM-DD'), calendarDateRangeEnd.format('YYYY-MM-DD'));

        if (calendarDateRangeStart && calendarDateRangeEnd) {
            roomReservationsListQuery.refetch();
            allocationPerDateQuery.refetch();
        }


        return (() => {

        });

    },[calendarDateRangeStart, calendarDateRangeEnd]);

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

    /**
     * 
     */

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
            <div className="">
                <DatePicker.RangePicker onChange={handleCalendarDateRangeChange} value={[calendarDateRangeStart, calendarDateRangeEnd]}/>
                <div style={{textAlign: 'center'}}><small>Calendar date range</small></div>
            </div>
        )
    }

    return <>
        <div>
            <Row gutter={[16,16]}>
                <Col xl={4}>
                    <CalendarDateRange/>
                    <div className="">
                        <Input placeholder="Enter Customer Name or Booking Code" onChange={(e) => {
                            setcustomerBookingCode(e.target.value);
                        }} />
                        <div style={{textAlign: 'center'}}><small>Customer/Booking code</small></div>
                    </div>
                    <div className="">
                        <Select style={{width:'100%'}} mode="multiple" onChange={(e)=>handleCheckedPropertiesChange(e)} defaultValue={checkedProperties}>
                            {
                                _.uniq(_.map(roomListQuery.data, i => i.property_code))
                                .map((item, key) => (
                                    <Select.Option key={key} value={item}>{_.find(roomListQuery.data, i=>i.property_code == item)['property_name']}</Select.Option>
                                ))
                            }
                        </Select>
                    </div>
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
            <section className="d-table p-3" style={{width: '100%', border: 'solid 1px gainsboro', borderRadius: 12, overflow:'hidden'}}>
                <header className="d-table-row">
                    <div className="d-table-cell pb-2">
                        Room
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
                    </div>
                    <div className="d-table-cell pb-2">Status<br/>today</div>
                    {
                        calendarViewDates.map( (date, i) => {
                            return (
                                <div key={date} className="d-table-cell" style={{textAlign:'center', background: (_.includes(['Sat', 'Sun'], moment(date).format('ddd')) ? '#EBEDEF' : 'transparent'), borderRight: i+1 < calendarViewDates.length ? 'solid 1px #efefef' : 'none'}}>
                                    <Popover content={
                                            <div style={{textAlign:'left'}}>
                                                { 
                                                    (allocationPerDateQuery.data && allocationPerDateQuery.data[moment(date).format('YYYY-MM-DD')]) ?
                                                    allocationPerDateQuery.data[moment(date).format('YYYY-MM-DD')]
                                                    .map( (item, key) => {
                                                        return <Tag key={key}><small style={{fontSize:'0.6rem'}}>{item.room_type.name} {item.entity} ({item.used}/{item.allocation})</small></Tag>   
                                                    })
                                                    :'No room allocation'
                                                }
                                            </div>
                                    } title="Room allocation" trigger="hover">
                                        <small style={{fontSize:'0.6rem', color: allocationPerDateQuery.data && allocationPerDateQuery.data[moment(date).format('YYYY-MM-DD')] ? 'inherit' : 'darkorange'}}>{moment(date).format('MMM D, YYYY dddd')}</small>
                                    </Popover>
                                    <div style={{display:'flex', flexWrap:'wrap'}}>
                                        {/* <small style={{flexGrow: 1}}>AM</small> */}
                                        {/* <small style={{flexGrow: 1}}>PM</small> */}
                                    </div>
                                </div>
                            )
                        })
                    }
                </header>

                <RoomList
                    calendarViewDates={calendarViewDates}
                    calendarDateRangeStart={calendarDateRangeStart}
                    calendarDateRangeEnd={calendarDateRangeEnd}
                    roomReservationsListQuery={roomReservationsListQuery}
                    checkedProperties={checkedProperties}
                    selectedTags={selectedTags}
                    allocationPerDateQuery={allocationPerDateQuery}
                    customerBookingCode={customerBookingCode}
                />

            </section>
        </div>
    </>
}

export default Page;