import React from 'react'
import moment from 'moment-timezone'
import ScheduleService from 'services/Transportation/ScheduleService'
import PassengerService from 'services/Transportation/PassengerService'
import ViewBookingComponent from 'components/Booking/View'

import { Select, Table, Space, Tag, DatePicker, message, Input, Statistic, Row, Col, Card, Button, Modal } from 'antd';
import { ReloadOutlined } from '@ant-design/icons';

function Page(props) {

    const today = moment().format('YYYY-MM-DD');
    const tagColor = {
        checked_in: 'blue',
        boarded: 'purple',
        no_show: 'orange',
        cancelled: 'red',
    };

    // States
    const [date, setDate] = React.useState(today);
    const [selectedTrip, setSelectedTrip] = React.useState(null);
    const [searchString, setSearchString] = React.useState(null);
    const [tripPassengers, setTripPassengers] = React.useState([]);
    const [bookingToView, setbookingToView] = React.useState(null);
    const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);
    
    // Get
    const scheduleListQuery = ScheduleService.list(date);
    // const passengerListQuery = ScheduleService.passengerList(date);

    // Post, Put
    const [updatePassengerStatusQuery, {isLoading: updatePassengerStatusQueryIsLoading}] = PassengerService.updatePassengerStatus();
    const [getPassengerListByScheduleIdQuery, {isLoading: getPassengerListByScheduleIdQueryIsLoading, reset: getPassengerListByScheduleIdQueryReset}] = PassengerService.getPassengerListByScheduleId();
    
    React.useEffect( () => {
        setSelectedTrip(null);
        scheduleListQuery.refetch();
        // passengerListQuery.refetch();
    },[date]);

    React.useEffect(()=> {
        if (bookingToView) {
            setviewBookingModalVisible(true);
        }
    },[bookingToView]);

    const handlePassengerStatusChange = (id, status, passenger_status, booking_status) => {
        
        let answer = null;

        if (['no_show', 'cancelled'].includes(status)) {
            answer = confirm("Are you sure? We will release the seat allocation for this passenger and cannot be returned to pending, checked-in or boarded status. You can use the add ferry transporation on the guest booking details. This will make sure that it will check the available inventory before applying the seat allocation to avoid overbooking of passenger.");
        }

        if (['arriving', 'no_show', 'cancelled'].includes(passenger_status)) {
            if (booking_status != 'confirmed') {
                alert("Booking is not confirmed");
                return false;
            }
        }

        if (answer != false) {
            updatePassengerStatusQuery({
                id: id,
                new_status: status,
            },{
                onSuccess: (res) => {
                    // console.log(res);
                    passengerListQuery.refetch();
                    message.success("Updated passenger status!");
                    // handleRefresh();
                    setTripPassengers(res.data.passengerList);
                },
                onError: (e) => {
                    // console.log(e);
                    message.danger(e.error);
                }
            })
        }

        if (answer == false) {
            handleRefresh();
        }
    }

    const searchPassenger = (search) => {
        setSearchString(search.toLowerCase());
    }

    const handleRefresh = () => {
        // passengerListQuery.refetch();
        if (selectedTrip) {
            getPassengerListById(selectedTrip);
        }
    }

    const getPassengerListById = (schedule_id) => {
        if (schedule_id || !getPassengerListByScheduleIdQueryIsLoading) {
            getPassengerListByScheduleIdQuery(
                {
                    trip_number: schedule_id,
                },
                {
                    onSuccess: (res) => {
                        // console.log(res)
                        setTripPassengers(res.data);
                    },
                    onError: (e) => message.danger(e.error)
                }
            );
        }
    }

    const handleSelectTripChange = (trip_number) => {
        setSelectedTrip(trip_number);
        getPassengerListById(trip_number);
    }

    const getPassengerStats = (status_array = []) => {
        if (status_array.length > 1) {
            return Number(_.sumBy((tripPassengers ? tripPassengers : []).filter(item => item.trip_number == selectedTrip), i => _.includes(status_array, i.status)))
        } else if (status_array.length == 1) {
            return Number(_.sumBy((tripPassengers ? tripPassengers : []).filter(item => item.trip_number == selectedTrip), i => i.status == status_array[0]));
        }
    }

    return (
        <>
            <DatePicker allowClear={false} className="mr-2" value={moment(date)} onChange={ date => setDate(moment(date).format('YYYY-MM-DD')) } />
            <Select disabled={scheduleListQuery.isFetching} value={selectedTrip} onChange={(val) => handleSelectTripChange(val)} placeholder={scheduleListQuery.isFetching ? 'Loading trips...':'Select trip'} style={{width: 400}}>
                {
                    scheduleListQuery.data &&
                        Object.keys(_.groupBy(scheduleListQuery.data, i => i.transportation.name)).map( (vehicle) => {
                            return  <Select.OptGroup key={vehicle} label="">
                                    {
                                        _.groupBy(scheduleListQuery.data, i => i.transportation.name)[vehicle].map( (i, key) => {
                                            return <Select.Option key={vehicle+key} value={i.trip_number}>
                                                        <Space>
                                                            <strong>{i.transportation.name}</strong>
                                                            <strong className="text-primary">{i.start_time}</strong>
                                                            <div><strong>{i.origin} - {i.destination}</strong></div>
                                                            <Tag>{i.status}</Tag>
                                                        </Space>
                                                        <div>{i.trip_date}</div>
                                                        <span>{i.trip_number}</span>
                                                        
                                                    </Select.Option>
                                        })
                                    }
                                    </Select.OptGroup>
                        })
                }
            </Select>

            <Input style={{width: 400}} type="text" placeholder="Search passenger by name, guest or booking ref #" size="large" className="ml-2 my-3" onChange={(e) => searchPassenger(e.target.value)} />

            <Button style={{float: 'right'}} type="primary" onClick={() => handleRefresh() }><ReloadOutlined /></Button>

            <Row gutter={[16, 16]}>
                <Col xl={4}><Card size="small"><Statistic title="Total bookings" value={selectedTrip ? getPassengerStats(['arriving','boarded', 'pending', 'checked_in', 'no_show','cancelled']) : '-'} /></Card></Col>
                <Col xl={4}><Card size="small" style={{borderLeft: 'solid 3px limegreen'}}><Statistic title="Boarded" value={selectedTrip ? getPassengerStats(['boarded']) : '-'} /></Card></Col>
                <Col xl={4}><Card size="small" style={{borderLeft: 'solid 3px #6495ED'}}><Statistic title="Checked-in" value={selectedTrip ? getPassengerStats(['checked_in']) : '-'} /></Card></Col>
                <Col xl={4}><Card size="small" style={{borderLeft: 'solid 3px #6495ED'}}><Statistic title="Pending" value={selectedTrip ? getPassengerStats(['pending']) : '-'} /></Card></Col>
                <Col xl={4}><Card size="small" style={{borderLeft: 'solid 3px orange'}}><Statistic title="No show" value={selectedTrip ? getPassengerStats(['no_show']) : '-'} /></Card></Col>
                <Col xl={4}><Card size="small" style={{borderLeft: 'solid 3px red'}}><Statistic title="Cancelled" value={selectedTrip ? getPassengerStats(['cancelled']) : '-'} /></Card></Col>
            </Row>

            <Table
                loading={getPassengerListByScheduleIdQueryIsLoading || scheduleListQuery.isFetching}
                size="small"
                rowKey="id"
                scroll={{
                    x: '110vw'
                }}
                dataSource={
                    (selectedTrip && tripPassengers && !getPassengerListByScheduleIdQueryIsLoading) &&
                    tripPassengers
                    // .filter(item => item.trip_number == selectedTrip)
                    .filter(item => {
                        if (item && searchString) {
                            const searchValue =  item.passenger.first_name.toLowerCase() + ' ' + item.passenger.last_name.toLowerCase() + ' ' + item.guest_reference_number.toLowerCase() + ' ' + item.booking_reference_number.toLowerCase();
                            return searchString ? searchValue.indexOf(searchString) !== -1 : true;
                        }
                        return true;
                    })
                }
                columns={[
                    {
                        title: 'Status',
                        dataIndex: 'status',
                        key: 'status',
                        filters: [
                            { text: 'Confirmed', value: 'arriving' },
                            { text: 'Pending', value: 'pending' },
                            { text: 'Checked-in', value: 'checked_in' },
                            { text: 'Boarded', value: 'boarded' },
                            { text: 'No show', value: 'no_show' },
                            { text: 'Cancelled', value: 'cancelled' },
                        ],
                        defaultFilteredValue: ['pending', 'checked_in', 'boarded'],
                        onFilter: (value, record) => record.status.includes(value),
                        render: (text, record) => (
                            <Select  defaultValue={record.status} onChange={(val) => handlePassengerStatusChange(record.id, val, record.status, record.booking.status)}>
                                { !['no_show', 'cancelled'].includes(record.status) && <Select.Option value="arriving">Confirmed</Select.Option> }
                                { !['no_show', 'cancelled'].includes(record.status) && <Select.Option value="pending">Pending</Select.Option> }
                                { !['no_show', 'cancelled'].includes(record.status) && <Select.Option value="checked_in"><span className="text-primary">Checked-in</span></Select.Option> }
                                { (!['no_show', 'cancelled'].includes(record.status) && record.booking.status == 'confirmed') && <Select.Option value="boarded"><span className="text-success">Boarded</span></Select.Option> }
                                <Select.Option value="no_show"><span className="text-warning">No show</span></Select.Option>
                                <Select.Option value="cancelled"><span className="text-danger">Cancelled</span></Select.Option>
                            </Select>
                        ),
                    },
                    {
                        title: 'Boarded at',
                        key: 'boarded_at',
                        sorter: (a, b) => moment(a.boarded_at).unix() - moment(b.boarded_at).unix(),
                        render: (text, record) =>
                            <>{record.boarded_at ? moment(record.boarded_at).format('h:mm:sA') : '-'}</>
                    },
                    {
                        title: 'Booking ref no.',
                        dataIndex: 'booking_reference_number',
                        key: 'booking_reference_number',
                        render: (text, record) => <>
                            <Button type="link" onClick={()=>setbookingToView(record.booking_reference_number)} style={{ color: record.booking.status == 'confirmed' ? 'limegreen' : 'inherit' }}>{text}</Button>
                        </>
                    },
                    {
                        title: 'Guest ref no.',
                        dataIndex: 'guest_reference_number',
                        key: 'guest_reference_number',
                    },
                    {
                        title: 'Primary customer',
                        key: 'primary_customer',
                        render: (text, record) =>
                            <>{record.booking.customer.first_name} {record.booking.customer.last_name}</>
                    },
                    {
                        title: 'First name',
                        key: 'first_name',
                        render: (text, record) =>
                            <strong>{record.passenger.first_name}</strong>
                    },
                    {
                        title: 'Last name',
                        key: 'last_name',
                        render: (text, record) =>
                            <strong>{record.passenger.last_name}</strong>
                    },
                    {
                        title: 'Age',
                        key: 'age',
                        render: (text, record) =>
                            <>{record.passenger.age}</>
                    },
                    {
                        title: 'Seat #',
                        key: 'seat_number',
                        render: (text, record) =>
                            <span className="text-primary">{record.seat_number}</span>
                    },
                    {
                        title: 'Type',
                        key: 'type',
                        render: (text, record) =>
                            <>{record.passenger.type}</>
                    },
                    {
                        title: 'Contact number',
                        key: 'contact_number',
                        render: (text, record) =>
                            <>{record.booking.customer.contact_number}</>
                    },
                    {
                        title: 'Nationality',
                        key: 'nationality',
                        render: (text, record) =>
                            <>{record.passenger.nationality}</>
                    },
                    {
                        title: 'Booking type',
                        key: 'booking_type',
                        render: (text, record) =>
                            <>{record.booking.type == 'ON' ? 'Overnight' : 'Daytour'}</>
                    },
                    {
                        title: 'Vehicle',
                        key: 'vehicle',
                        render: (text, record) =>
                            <>{record.schedule.transportation.name}</>
                    },
                    {
                        title: 'Booking Tags',
                        render: (text, record) => record.booking.tags.map((i,key) => <Tag key={key}><small>{i.name}</small></Tag>),
                    },
                    {
                        title: 'Guest Tags',
                        render: (text, record) => record.passenger.guest_tags.map((i,key) => <Tag key={key}><small>{i.name}</small></Tag>)
                    },
                    // {
                    //     title: 'Trip date',
                    //     key: 'trip_date',
                    //     render: (text, record) =>
                    //         <>{record.schedule.trip_date}</>
                    // },
                    // {
                    //     title: 'Departure',
                    //     key: 'departure',
                    //     render: (text, record) =>
                    //         <>{record.schedule.start_time}</>
                    // },
                    // {
                    //     title: 'Arrival',
                    //     key: 'arrival',
                    //     render: (text, record) =>
                    //         <>{record.schedule.end_time}</>
                    // },
                    // {
                    //     title: 'Origin',
                    //     key: 'origin',
                    //     render: (text, record) =>
                    //         <>{record.schedule.route.origin.name}</>
                    // },
                    // {
                    //     title: 'Destination',
                    //     key: 'destination',
                    //     render: (text, record) =>
                    //         <>{record.schedule.route.destination.name}</>
                    // },
                ]}
            />
            { bookingToView && 
                <Modal
                    visible={viewBookingModalVisible}
                    width="100%"
                    style={{ top: 16 }}
                    onCancel={()=> { setviewBookingModalVisible(false); setbookingToView(null); }}
                    footer={null}
                >
                    <ViewBookingComponent referenceNumber={bookingToView} />
                </Modal>
            }
        </>
    )
}

export default Page;