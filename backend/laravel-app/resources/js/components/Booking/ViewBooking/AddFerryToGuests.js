import React, { useEffect, useState } from 'react'
import moment from 'moment-timezone'

import BookingService from 'services/Booking'
import ScheduleService from 'services/Transportation/ScheduleService'
import LocationService from 'services/Transportation/Location'

import { List, Row, Col, Card, Typography, Table, Button, message, Tag, Divider, Checkbox, Select } from 'antd'
import { CheckCircleFilled, ArrowRightOutlined } from '@ant-design/icons'

function Page(props) {
    // console.log(props.booking);

    const [guests, setGuests] = useState([...props.booking.adult_guests, ...props.booking.kid_guests, ...props.booking.infant_guests]);
    const [availableTripsByBookingDate, setAvailableTripsByBookingDate] = useState({
        first_trip: [],
        second_trip: [],
    });
    const [firstTrip, setFirstTrip] = useState(null);
    const [secondTrip, setSecondTrip] = useState(null);

    const [totalTicketCost, setTotalTicketCost] = React.useState(0);

    // Get available trips
    const [getAvailableTripsByBookingDateQuery, { isLoading: getAvailableTripsByBookingDateQueryIsLoading, reset: getAvailableTripsByBookingDateQueryReset}] = ScheduleService.getAvailableTripsByBookingDate();
    const [addFerryToGuestsQuery, { isLoading: addFerryToGuestsQueryIsLoading, reset: addFerryToGuestsQueryReset}] = BookingService.addFerryToGuests();
    // const locations = LocationService.list();

    useEffect( () => {
        
        console.log(guests);

        _.each( guests, (item) => _.assign(item, { selected: false }));

        getAvailableTripsByBookingDateQuery(
                {
                    arrival_date: props.booking.start_datetime,
                    departure_date: props.booking.end_datetime,
                    trip_type: 'roundtrip',
                    // origin: 1,
                    // destination: 2,
                    trip_data: props.booking.trip_data,
                    type: 'add_ferry_to_guests'
                },
                {
                    onSuccess: (res) => {
                        // console.log(res);
                        setAvailableTripsByBookingDate(res.data);
                    },
                    onError: (e) => console.log(e),
                }
        );

    }, []);

    React.useEffect( () => {
        updateTotalTicketCost();
    }, [firstTrip, secondTrip]);

    const handleItemClick = (key) => {
        // console.log(key);

        setGuests( prev => {
            
            prev[key].selected = !prev[key].selected; 

            if (firstTrip) {
                if (_.sumBy(prev, (i) => i.selected ? 1 : 0) > firstTrip.available) {
                    setFirstTrip(null);
                }
            }
    
            if (secondTrip) {
                if (_.sumBy(prev, (i) => i.selected ? 1 : 0) > secondTrip.available) {
                    setSecondTrip(null);
                }
            }

            return [...prev];

        })
    }

    const SelectAllGuests = () => {
        // _.each( guests, (item) => _.assign(item, { selected: true }));
    }

    const resetStates = () => {
        setAvailableTripsByBookingDate([]);

        setFirstTrip(null);
        setSecondTrip(null);

        setTotalTicketCost(0);

        _.each( guests, (item) => _.assign(item, { selected: false }));
    }

    const updateTotalTicketCost = () => {

        let total = 0;

        total = total + (_.sumBy(guests, (i) => (i.selected && i.type != 'infant') ? 1 : 0)) * (firstTrip ? firstTrip.rate : 0);
        total = total + (_.sumBy(guests, (i) => (i.selected && i.type != 'infant') ? 1 : 0)) * (secondTrip ? secondTrip.rate : 0);


        setTotalTicketCost(total);
    }

    const handleAddFerryToBookingClick = () => {
        // console.log(firstTrip, secondTrip);

        if (addFerryToGuestsQueryIsLoading) {
            return false;
        }

        addFerryToGuestsQuery(
            {
                booking_reference_number: props.booking.reference_number,
                trip_type: 'roundtrip',
                first_trip: firstTrip,
                second_trip: secondTrip,
                guests: guests,
            },
            {
                onSuccess: (res) => {
                    // console.log(res);
                    message.success("Added ferry to guests!");
                    props.setAddFerryToGuestsModalVisible(false);
                    props.refreshViewBooking();
                    resetStates();
                },
                onError: (e) => {
                    console.log(e);
                    message.danger("Adding ferry to guests failed!");
                    resetStates();
                },
            }
        );

    }

    return <>
        <Typography.Title>Guests</Typography.Title>

        <Row gutter={[16,16]}>
            <Col xl={8}>
                <List
                    header={<div style={{display: 'flex', justifyContent: 'space-between'}}>Select guest(s) <span>{ _.sumBy(guests, (i) => i.selected ? 1 : 0) ?  _.sumBy(guests, (i) => i.selected ? 1 : 0) : 0 } selected</span></div>}

                    // On-going
                    // header={<div style={{display: 'flex', justifyContent: 'space-between'}}>
                    //             <div>{<Checkbox onClick={SelectAllGuests()}></Checkbox>} Select all guest(s)</div>
                    //             <span>{ _.sumBy(guests, (i) => i.selected ? 1 : 0) ?  _.sumBy(guests, (i) => i.selected ? 1 : 0) : 0 } selected</span>
                    //         </div>}

                    dataSource={guests}
                    renderItem={(item, key) => (
                        <List.Item onClick={() => handleItemClick(key)}>
                            <Card size="small" hoverable style={{cursor:'pointer', width: '100%', border: item.selected ? 'solid 1px limegreen' : ''}}>
                                <div style={{display: 'flex', justifyContent: 'space-between'}}>
                                    <div>{ item.selected ? <CheckCircleFilled className="mr-2 text-success"/> : <CheckCircleFilled className="mr-2" style={{opacity: 0}} />}
                                    <Typography.Text mark>[{item.reference_number}]</Typography.Text> <span style={{textTransform: 'uppercase'}}>{item.first_name} {item.last_name}</span></div> <span className="text-secondary">{item.age} | {item.type} | {item.nationality}</span>
                                </div>
                                <Divider className="my-2"/>
                                <div>Existing guest trips: {(item.trip_bookings && item.trip_bookings.length) ? item.trip_bookings.map( (i, key) => <Tag key={key}>{i.trip_number}</Tag>) : ''}</div>
                            </Card>
                        </List.Item>
                    )}
                />
            </Col>
            <Col xl={16}>
                <Card className="mb-2">
                    <>
                        <Table
                            title={ () => <>First trip <Button size="small" style={{float: 'right'}} onClick={()=>setFirstTrip(null)}>Clear first trip</Button></> }
                            dataSource={availableTripsByBookingDate.first_trip && availableTripsByBookingDate.first_trip}
                            rowKey="id"
                            size="small"
                            loading={getAvailableTripsByBookingDateQueryIsLoading}
                            rowSelection={
                                {
                                    type: 'radio',
                                    onSelect: (record, selected, selectedRows, nativeEvent) => {
                                        // console.log(record);
                                        
                                        setFirstTrip(record);

                                        // updateTotalTicketCost();
                                    },
                                    getCheckboxProps: (record) => ({
                                    disabled: ((parseInt(record.available) - _.sumBy(guests, (i) => i.selected ? 1 : 0) ) < 0 || _.sumBy(guests, (i) => i.selected ? 1 : 0) <= 0), // Column configuration not to be checked
                                    //   name: record.trip_number,
                                    }),
                                    preserveSelectedRowKeys: false,
                                    selectedRowKeys: (firstTrip && firstTrip.available) < _.sumBy(guests, (i) => i.selected ? 1 : 0) || _.sumBy(guests, (i) => i.selected ? 1 : 0) <= 0 ? [] : [firstTrip && firstTrip.id],
                                }
                            }
                            columns={[
                                {
                                    title: 'Trip #',
                                    dataIndex: 'trip_number',
                                    key: 'trip_number',
                                    render: (text, record) => {
                                        return <span className={
                                            (parseInt(record.available) - _.sumBy(guests, (i) => i.selected ? 1 : 0)) < 0 ? 'text-danger' : 'text-success'
                                        }>{text}</span>
                                    }
                                },
                                {
                                    title: 'Selected / Available',
                                    dataIndex: 'available',
                                    key: 'available',
                                    render: (text, record) => {
                                        return <span className="text-primary"> <span className="text-warning">{_.sumBy(guests, (i) => i.selected ? 1 : 0)}</span>/{parseInt(text)}</span>
                                    }
                                },
                                {
                                    title: 'Trip',
                                    render: (text, record) => {
                                        return <>
                                            <div className="text-primary">{record.origin_code} <ArrowRightOutlined/> {record.destination_code}</div>
                                            <strong>{moment(record.trip_date).format('MMM D, YYYY')}</strong><br/>
                                            {moment(record.trip_date+" "+record.departure_time).format('h:mm A')} ~ {moment(record.trip_date+" "+record.estimated_arrival_time).format('h:mm A')}
                                            <div>{record.transportation_name}</div>
                                        </>
                                    }
                                },
                                {
                                    title: 'Segment',
                                    dataIndex: 'name',
                                    key: 'name',
                                    render: (text, record) => <>({record.allocation_name}) {text}</>
                                },
                                {
                                    title: 'Trip link',
                                    dataIndex: 'trip_link',
                                    key: 'trip_link',
                                },
                                {
                                    title: 'Rate',
                                    dataIndex: 'rate',
                                    key: 'rate',
                                    render: (text) => <span className="text-success">&#8369; {text}</span>
                                },
                            ]}
                        />
                    </>
                </Card>
                <Card>
                    <>
                        <Table
                            title={ () => <>Second trip <Button size="small" style={{float: 'right'}} onClick={()=>setSecondTrip(null)}>Clear second trip</Button></> }
                            dataSource={availableTripsByBookingDate.second_trip && availableTripsByBookingDate.second_trip}
                            rowKey="id"
                            size="small"
                            loading={getAvailableTripsByBookingDateQueryIsLoading}
                            rowSelection={
                                {
                                    type: 'radio',
                                    onSelect: (record, selected, selectedRows, nativeEvent) => {
                                        // console.log(record);
                                        
                                        setSecondTrip(record);

                                        // updateTotalTicketCost();
                                    },
                                    getCheckboxProps: (record) => ({
                                    disabled: ((parseInt(record.available) - _.sumBy(guests, (i) => i.selected ? 1 : 0) ) < 0 || _.sumBy(guests, (i) => i.selected ? 1 : 0) <= 0), // Column configuration not to be checked
                                    //   name: record.trip_number,
                                    }),
                                    preserveSelectedRowKeys: false,
                                    selectedRowKeys: (secondTrip && secondTrip.available) < _.sumBy(guests, (i) => i.selected ? 1 : 0) || _.sumBy(guests, (i) => i.selected ? 1 : 0) <= 0 ? [] : [secondTrip && secondTrip.id],
                                }
                            }
                            columns={[
                                {
                                    title: 'Trip #',
                                    dataIndex: 'trip_number',
                                    key: 'trip_number',
                                    render: (text, record) => {
                                        return <span className={
                                            (parseInt(record.available) - _.sumBy(guests, (i) => i.selected ? 1 : 0)) < 0 ? 'text-danger' : 'text-success'
                                        }>{text}</span>
                                    }
                                },
                                {
                                    title: 'Selected / Available',
                                    dataIndex: 'available',
                                    key: 'available',
                                    render: (text, record) => {
                                        return <span className="text-primary"> <span className="text-warning">{_.sumBy(guests, (i) => i.selected ? 1 : 0)}</span>/{parseInt(text)}</span>
                                    }
                                },
                                {
                                    title: 'Trip',
                                    render: (text, record) => {
                                        return <>
                                            <div className="text-primary">{record.origin_code} <ArrowRightOutlined/> {record.destination_code}</div>
                                            <strong>{moment(record.trip_date).format('MMM D, YYYY')}</strong><br/>
                                            {moment(record.trip_date+" "+record.departure_time).format('h:mm A')} ~ {moment(record.trip_date+" "+record.estimated_arrival_time).format('h:mm A')}
                                            <div>{record.transportation_name}</div>
                                        </>
                                    }
                                },
                                {
                                    title: 'Segment',
                                    dataIndex: 'name',
                                    key: 'name',
                                    render: (text, record) => <>({record.allocation_name}) {text}</>
                                },
                                {
                                    title: 'Trip link',
                                    dataIndex: 'trip_link',
                                    key: 'trip_link',
                                },
                                {
                                    title: 'Rate',
                                    dataIndex: 'rate',
                                    key: 'rate',
                                    render: (text) => <span className="text-success">&#8369; {text}</span>
                                },
                            ]}
                        />
                    </>
                </Card>
            </Col>
        </Row>

        <div>Total Ticket Cost: <span className="text-success">P {totalTicketCost}</span></div>

        <div className="mt-4"><Button type="primary" onClick={() => handleAddFerryToBookingClick()} disabled={_.sumBy(guests, (i) => i.selected ? 1 : 0) <= 0 || (!firstTrip && !secondTrip)}>Add Ferry to Guest(s)</Button></div>
    </>
}

export default Page;