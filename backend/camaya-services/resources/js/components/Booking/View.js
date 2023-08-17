import React from 'react'

import CustomerService from 'services/Booking/Customer'
import BookingService from 'services/Booking'
import InvoiceService from 'services/Booking/Invoice'
import GuestService from 'services/Booking/GuestService'
import StubService from 'services/Booking/StubService'
import ProductService from 'services/Booking/Product'
import PackageService from 'services/Booking/Package'
import RoomReservationService from 'services/Hotel/RoomReservation'

import ScheduleService from 'services/Transportation/ScheduleService'
import LocationService from 'services/Transportation/Location'

import PassService from 'services/Booking/PassService'

// Components
import AddFerryToGuestsComponent from 'components/Booking/ViewBooking/AddFerryToGuests'

import moment from 'moment'
import { queryCache } from 'react-query'
import TicketIcon from 'assets/ticket-alt-solid.svg'
import FerryIcon from 'assets/ship-solid.svg'
import Loading from 'common/Loading'
import TeaLoading from 'common/TeaLoading'

import { QRCode } from 'react-qrcode-logo'

import { Switch, Alert, Badge, Row, Col, Card, Space, Button, Select, Input, DatePicker, Form, Divider, Typography, Tag, Popconfirm, notification, InputNumber, message, Modal, Table, Dropdown, Menu, Upload, Tooltip, Descriptions, TimePicker, Drawer, Checkbox, List} from 'antd'
import Icon, { UserOutlined, EyeOutlined, FileOutlined, FileSearchOutlined, CalendarOutlined, UploadOutlined, EllipsisOutlined, SendOutlined, QrcodeOutlined, EditOutlined, CopyOutlined, PlusOutlined, MinusOutlined, DeleteOutlined, LoadingOutlined, InfoCircleOutlined, ArrowRightOutlined, CheckOutlined, StopOutlined, DownloadOutlined } from '@ant-design/icons'

const { Option } = Select;

const fo_status = {
    'vacant': 'V',
    'occupied': 'O',
}

const room_status = {
    clean: 'C',
    clean_inspected: 'CI',
    dirty: 'D',
    dirty_inspected: 'DI',
    pickup: 'P',
    sanitized: 'S',
    inspected: 'I',
    'out-of-order': 'OO',
    'out-of-service': 'OS',
}

const numberWithCommas = (x) => {
    return String(x).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

const AddRoomFilter = (props) => {

    return (
        <div>
            {
                (props.roomTypes) &&
                props.roomTypes.map( (room_type, key) => {
                    return <Tag.CheckableTag
                                key={key}
                                checked={props.selectedRoomTypeFilter.indexOf(room_type.code) > -1}
                                onChange={checked => props.handleRoomTypeFilterChange(room_type, checked)}
                            >
                                {/* {room_type.property.code} {room_type.name} {room_type.room_allocations.map( (i,k) => <span key={k} className="mr-2 text-warning">({i.entity} {parseInt(i.available) - parseInt(i.taken)})</span>)} */}

                                {room_type.property.code} {room_type.name}
                            </Tag.CheckableTag>
                })
            }
        </div>
    )
}

const AddRoomModalContent = (props) => {

    const arrival_date = moment(props.booking.start_datetime).format('YYYY-MM-DD');
    const departure_date = moment(props.booking.end_datetime).format('YYYY-MM-DD');

    // Get
    const availableRoomListQuery = RoomReservationService.availableRoomList(arrival_date, departure_date);
    // Post
    const [addRoomToBookingQuery, { isLoading: addRoomToBookingQueryIsLoading, reset: addRoomToBookingQueryReset}] = RoomReservationService.addRoomToBooking();

    // states
    const [roomsToAdd, setRoomsToAdd] = React.useState([]);
    const [roomTypes, setRoomTypes] = React.useState([]);
    const [selectedRoomTypeFilter, setSelectRoomTypeFilter] = React.useState([]);
    const [handleAddRoomChangeIsLoading, setHandleAddRoomChangeIsLoading] = React.useState(false);

    React.useEffect( () => {
        // console.log(availableRoomListQuery.data);
    },[]);

    React.useEffect( () => {
        if (availableRoomListQuery.data&&availableRoomListQuery.data.room_types) {
            setRoomTypes(availableRoomListQuery.data.room_types);
        }
    },[availableRoomListQuery.data]);

    const handleAddRoomChange = (selected, room_id, room_type_id, room_rate_total) => {

        // console.log(selected, room_id);

        const updateRoomTypes = (newItems) => {
            setRoomTypes( prev => {

                prev.map( (room_type, key) => {

                    room_type.room_allocations = [...room_type.room_allocations].map( i => {

                        const _count = newItems.filter( room_to_add => room_to_add.allocation == i.entity && room_to_add.room_type_id == room_type.id);

                        return {...i, taken: _count.length};

                    });

                    return {...room_type};

                });

                return [...prev];
            });
        }

        if (selected) {
            setRoomsToAdd( prev => {
                const _prev = _.filter(prev, i => i.room_id != room_id);
                const newItems = [..._prev, {room_id: room_id, room_type_id: room_type_id, allocation: selected, room_rate_total: room_rate_total}];

                updateRoomTypes(newItems);

                return newItems;
             });

        } else {
            setRoomsToAdd( prev => {
                const newItems = _.filter(prev, i => i.room_id != room_id);

                updateRoomTypes(newItems);

                return newItems;
            });
        }

    }

    const handleRoomTypeFilterChange = (room_type, checked) => {
        const nextSelectedTags = checked ? [...selectedRoomTypeFilter, room_type.code] : selectedRoomTypeFilter.filter(t => t !== room_type.code);
        setSelectRoomTypeFilter(nextSelectedTags);
    }

    const handleAddRoomToBookingClick = () => {
        console.log(roomsToAdd);

        if (addRoomToBookingQueryIsLoading) return false;

        addRoomToBookingQuery({
            roomsToAdd: roomsToAdd,
            booking_reference_number: props.booking.reference_number,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                props.setAddRoomModalVisible(false);
                message.success('Succesfully added room(s) to booking!');
                props.viewBookingQuery.refetch();
            },
            onError: (e) => message.danger(e.error)
        });

    }


    return (
        <React.Fragment>
            <div>
                {roomsToAdd &&
                roomsToAdd.map( (item, key) => {
                    return <div key={key}>{item.room_id} : {item.allocation}</div>
                })}
            </div>

            <Space>
                <div>
                    Filter room type:
                    {
                        !handleAddRoomChangeIsLoading &&
                        <AddRoomFilter roomTypes={roomTypes} handleRoomTypeFilterChange={handleRoomTypeFilterChange} selectedRoomTypeFilter={selectedRoomTypeFilter}/>
                    }
                </div>
            </Space>

            {
                availableRoomListQuery.isFetching || availableRoomListQuery.isLoading ?
                <div className="my-4"><LoadingOutlined /> Loading available rooms...</div>
                : ''
            }

            {
                (availableRoomListQuery.data && availableRoomListQuery.data.available_rooms) &&

                Object.keys(availableRoomListQuery.data.available_rooms).filter( i => _.find(selectedRoomTypeFilter, j => i == j) || selectedRoomTypeFilter.length == 0).map( (room_type) => {
                    {
                        const _room_type = _.find(roomTypes, i => i.code == room_type);

                        return <React.Fragment key={room_type}>
                            <div className="mt-4 mb-2">{(roomTypes && _room_type) && _room_type.name}</div>
                            <Row gutter={[12,12]}>
                            {
                                availableRoomListQuery.data.available_rooms[room_type].map( (room, key) => {
                                return <Col xs={12} key={key}>
                                            <Card className="card-shadow" style={{boxShadow: _.find(roomsToAdd, i => i.room_id == room.id) ? 'inset 5px 0px 0px limegreen' : ''}}>
                                                <Row gutter={[8,8]}>
                                                    <Col xs={5}>
                                                        <div><img style={{width: '100%', borderRadius: 12}} src={room.type.images.length ? _.find(room.type.images, i => i.cover == 'yes').image_path : ''} /></div>
                                                    </Col>
                                                    <Col xs={8}>
                                                        <Typography.Title level={4} className="mb-0">Room {room.number}</Typography.Title>
                                                        <Typography.Text><span style={{fontWeight: 'normal'}}>{room.property.code}</span> {room.type.name}</Typography.Text>
                                                    </Col>
                                                    <Col xs={11} style={{textAlign:'right', display:'flex', justifyContent:'space-between', flexDirection:'column'}}>
                                                        <span style={{color: 'limegreen'}}>&#8369; {room.room_rate_total}</span>

                                                        <div>
                                                            <div>Room allocation:</div>
                                                            { room.room_allocations ?
                                                                <Select value={_.find(roomsToAdd, i => (i.room_type_id == room.room_type_id && i.room_id == room.id)) ? _.find(roomsToAdd, i => (i.room_type_id == room.room_type_id && i.room_id == room.id))['allocation'] : ''} style={{textAlign: 'left', width: '100%'}} onChange={e => handleAddRoomChange(e, room.id, room.room_type_id, room.room_rate_total)}>
                                                                    <Select.Option value=""></Select.Option>
                                                                    {/* <Select.Option value="BPO">BPO (5 remaining)</Select.Option>
                                                                    <Select.Option value="RE">RE (0 remaining)</Select.Option>
                                                                    <Select.Option value="HOA">HOA (0 remaining)</Select.Option> */}
                                                                    {
                                                                        room.room_allocations.map( (i,k) => {
                                                                            const rm = _.find(roomTypes, room_type => room_type.id == room.room_type_id);
                                                                            // console.log(rm);
                                                                            if (rm) {
                                                                                const room_allocation = _.find(rm.room_allocations, r => (r.entity == i.entity));
                                                                                // console.log(room_allocation);
                                                                                return <Select.Option key={k} className="mr-2" disabled={!i.isAvailable || (parseInt(i.available) - parseInt(room_allocation && room_allocation['taken'] ? room_allocation['taken'] : 0) <= 0)} value={i.entity}>{i.entity} ({parseInt(i.available) - parseInt(room_allocation && room_allocation['taken'] ? room_allocation['taken'] : 0)} remaining)</Select.Option>
                                                                            }
                                                                        })
                                                                    }
                                                                </Select> : 'No room allocation set.'
                                                            }
                                                        </div>
                                                    </Col>
                                                    <Col xs={24}>
                                                        <small className="text-secondary">Description:</small>
                                                        <p>{room.type.description}</p>
                                                        <p>{room.description}</p>
                                                    </Col>
                                                </Row>
                                            </Card>
                                        </Col>
                                })
                            }
                            </Row>
                        </React.Fragment>
                    }
                })
            }
            <div style={{display:'flex', justifyContent:'space-between', alignItems:'flex-end'}}>
                <Typography.Title level={4} className="mt-4">
                    Total additional room cost: <span style={{color: 'limegreen'}}> &#8369; {_.sumBy(roomsToAdd, 'room_rate_total')}</span>
                </Typography.Title>
                <Popconfirm title="Are you sure？" okText="Yes" cancelText="No" onConfirm={handleAddRoomToBookingClick}>
                    <Button type="primary" disabled={!roomsToAdd.length} style={{alignSelf: 'flex-end'}}>Add room{roomsToAdd.length > 1 ? 's':''} to booking</Button>
                </Popconfirm>
            </div>
        </React.Fragment>
    )
}

const AddFerryModalContent = (props) => {

    // console.log(props.booking);
    // States
    const [availableTripsByBookingDate, setAvailableTripsByBookingDate] = React.useState([]);

    const [tripType, setTripType] = React.useState(null);
    const [origin, setOrigin] = React.useState(null);
    const [destination, setDestination] = React.useState(null);

    const [firstTrip, setFirstTrip] = React.useState(null);
    const [secondTrip, setSecondTrip] = React.useState(null);

    const [totalTicketCost, setTotalTicketCost] = React.useState(0);

    // Get available trips
    const [getAvailableTripsByBookingDateQuery, { isLoading: getAvailableTripsByBookingDateQueryIsLoading, reset: getAvailableTripsByBookingDateQueryReset}] = ScheduleService.getAvailableTripsByBookingDate();
    const [addFerryToBookingQuery, { isLoading: addFerryToBookingQueryIsLoading, reset: addFerryToBookingQueryReset}] = BookingService.addFerryToBooking();
    const locations = LocationService.list();

    React.useEffect( () => {

        console.log(locations.data);

        if (tripType && origin && destination) {
            getAvailableTripsByBookingDateQuery(
                    {
                        arrival_date: props.booking.start_datetime,
                        departure_date: props.booking.end_datetime,
                        trip_type: tripType,
                        origin: origin,
                        destination: destination,
                    },
                    {
                        onSuccess: (res) => {
                            // console.log(res);
                            setAvailableTripsByBookingDate(res.data);
                        },
                        onError: (e) => console.log(e),
                    }
            );

        }

        setFirstTrip(null);
        setSecondTrip(null);

    },[tripType, origin, destination]);

    React.useEffect( () => {
        updateTotalTicketCost();
    }, [firstTrip, secondTrip]);

    // return <>{props.booking.reference_number}</>

    const resetStates = () => {
        setAvailableTripsByBookingDate([]);

        setTripType(null);
        setOrigin(null);
        setDestination(null);

        setFirstTrip(null);
        setSecondTrip(null);

        setTotalTicketCost(0);
    }

    const handleAddFerryToBookingClick = () => {
        console.log(firstTrip, secondTrip);

        if (tripType == 'one_way' && !firstTrip) {
            message.warning('Please select a trip.');
            return false;
        }

        if (tripType == 'roundtrip' && (!firstTrip || !secondTrip)) {
            message.warning('Please select a round trip.');
            return false;
        }

        if (addFerryToBookingQueryIsLoading) {
            return false;
        }

        addFerryToBookingQuery(
            {
                booking_reference_number: props.booking.reference_number,
                trip_type: tripType,
                first_trip: firstTrip,
                second_trip: secondTrip,
            },
            {
                onSuccess: (res) => {
                    // console.log(res);
                    message.success("Added ferry to guests!");
                    props.setAddFerryModalVisible(false);
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

    const updateTotalTicketCost = () => {

        let total = 0;

        if (firstTrip) {
            total = total + (props.booking.adult_pax + props.booking.kid_pax) * firstTrip.rate;
        }

        if (secondTrip) {
            total = total + (props.booking.adult_pax + props.booking.kid_pax) * secondTrip.rate;
        }


        setTotalTicketCost(total);
    }

    return <>

        <Row gutter={[8,8]} className="mb-4">
            <Col xs={3}>
                <Select style={{width: '100%'}} placeholder="Trip type" onChange={(e) => setTripType(e)}>
                    <Select.Option value="one_way">One-way</Select.Option>
                    <Select.Option value="roundtrip">Roundtrip</Select.Option>
                </Select>
            </Col>
            <Col xs={6}>
                <Select style={{width: '100%'}} placeholder="Origin" onChange={(e) => setOrigin(e)}>
                    { locations.data &&
                        locations.data.map( (item, key) => {
                            return <Select.Option key={key} value={item.id}>[{item.code}] {item.name}</Select.Option>
                        })
                    }
                </Select>
            </Col>
            <Col xs={2} style={{textAlign:'center'}}>
                <ArrowRightOutlined/>
            </Col>
            <Col xs={6}>
                <Select style={{width: '100%'}} placeholder="Destination" onChange={(e) => setDestination(e)}>
                    { locations.data &&
                        locations.data.map( (item, key) => {
                            return <Select.Option key={key} value={item.id}>[{item.code}] {item.name}</Select.Option>
                        })
                    }
                </Select>
            </Col>
        </Row>

        { (tripType && origin && destination) ?
                <>
                    <Table
                        title={ () => <>First trip</> }
                        dataSource={availableTripsByBookingDate.first_trip}
                        rowKey="id"
                        rowSelection={
                            {
                                type: 'radio',
                                onSelect: (record, selected, selectedRows, nativeEvent) => {
                                    // console.log(record);
                                    setFirstTrip(record);

                                    updateTotalTicketCost();
                                },
                                getCheckboxProps: (record) => ({
                                  disabled: ((parseInt(record.available) - (props.booking.adult_pax + props.booking.kid_pax)) < 0), // Column configuration not to be checked
                                //   name: record.trip_number,
                                }),
                                preserveSelectedRowKeys: false,
                                selectedRowKeys: (firstTrip && firstTrip.available) < (parseInt(props.booking.adult_pax || 0)+parseInt(props.booking.kid_pax || 0)) ? [] : [firstTrip.id],
                            }
                        }
                        columns={[
                            {
                                title: 'Trip #',
                                dataIndex: 'trip_number',
                                key: 'trip_number',
                                render: (text, record) => {
                                    return <span className={
                                        (parseInt(record.available) - (props.booking.adult_pax + props.booking.kid_pax)) < 0 ? 'text-danger' : 'text-success'
                                    }>{text}</span>
                                }
                            },
                            {
                                title: 'Available',
                                dataIndex: 'available',
                                key: 'available',
                                render: (text, record) => {
                                    return <span className="text-primary">{parseInt(text)}  <small className="text-warning">({(props.booking.adult_pax + props.booking.kid_pax)} pax to allocate)</small></span>
                                }
                            },
                            {
                                title: 'Route',
                                render: (text, record) => {
                                    return <>{record.origin_code} <ArrowRightOutlined/> {record.destination_code}</>
                                }
                            },
                            {
                                title: 'Schedule',
                                render: (text, record) => {
                                    return <>
                                        <strong>{moment(record.trip_date).format('MMM D, YYYY')}</strong><br/>
                                        {moment(record.trip_date+" "+record.departure_time).format('h:mm A')} ~ {moment(record.trip_date+" "+record.estimated_arrival_time).format('h:mm A')}
                                    </>
                                }
                            },
                            {
                                title: 'Transportation',
                                dataIndex: 'transportation_name',
                                key: 'transportation_name',
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
                                render: (text) => <span className="text-success">P {text}</span>
                            },
                        ]}
                    />

                    {
                        tripType === 'roundtrip' ?
                        <Table
                        title={ () => <>Second trip</> }
                            dataSource={availableTripsByBookingDate.second_trip}
                            rowKey="id"
                            rowSelection={
                                {
                                    type: 'radio',
                                    onSelect: (record, selected, selectedRows, nativeEvent) => {
                                        // console.log(record);
                                        setSecondTrip(record);

                                        updateTotalTicketCost();
                                    },
                                    getCheckboxProps: (record) => ({
                                      disabled: ((parseInt(record.available) - (props.booking.adult_pax + props.booking.kid_pax)) < 0), // Column configuration not to be checked
                                    //   name: record.trip_number,
                                    }),
                                    preserveSelectedRowKeys: false,
                                    selectedRowKeys: (secondTrip && secondTrip.available) < (parseInt(props.booking.adult_pax || 0)+parseInt(props.booking.kid_pax || 0)) ? [] : [secondTrip.id],
                                }
                            }
                            columns={[
                                {
                                    title: 'Trip #',
                                    dataIndex: 'trip_number',
                                    key: 'trip_number',
                                    render: (text, record) => {
                                        return <span className={
                                            (parseInt(record.available) - (props.booking.adult_pax + props.booking.kid_pax)) < 0 ? 'text-danger' : 'text-success'
                                        }>{text}</span>
                                    }
                                },
                                {
                                    title: 'Available',
                                    dataIndex: 'available',
                                    key: 'available',
                                    render: (text, record) => {
                                        return <span className="text-primary">{parseInt(text)}  <small className="text-warning">({(props.booking.adult_pax + props.booking.kid_pax)} pax to allocate)</small></span>
                                    }
                                },
                                {
                                    title: 'Route',
                                    render: (text, record) => {
                                        return <>{record.origin_code} <ArrowRightOutlined/> {record.destination_code}</>
                                    }
                                },
                                {
                                    title: 'Schedule',
                                    render: (text, record) => {
                                        return <>
                                            <strong>{moment(record.trip_date).format('MMM D, YYYY')}</strong><br/>
                                            {moment(record.trip_date+" "+record.departure_time).format('h:mm A')} ~ {moment(record.trip_date+" "+record.estimated_arrival_time).format('h:mm A')}
                                        </>
                                    }
                                },
                                {
                                    title: 'Transportation',
                                    dataIndex: 'transportation_name',
                                    key: 'transportation_name',
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
                                    render: (text) => <span className="text-success">P {text}</span>
                                },
                            ]}
                        /> : ''
                    }
                </>
                :
                'Select trip type, origin and destination to view available trips.'
        }
        <div style={{width:'100%', marginTop: 24}}>
            <div style={{float:'left'}}>Total Ticket Cost: <span className="text-success">P {totalTicketCost}</span></div>
            <Button style={{float: 'right'}} type="primary" onClick={() =>  handleAddFerryToBookingClick()}>Add ferry to booking</Button>
            <div style={{clear: 'both'}}></div>
        </div>
    </>

}

const ViewLogModalContent = (props) => {

    const bookingLogsQuery = BookingService.getLogs(props.booking.reference_number);

    return (<>
        <Table
            size="small"
            dataSource={bookingLogsQuery.data}
            rowKey="id"
            columns={[
                {
                    dataIndex: 'created_at',
                    rowKey: 'created_at',
                    title: 'Date',
                    render: (text, record) => <>{moment(record.created_at).format('YYYY-MM-DD h:mm:ss A')}</>
                },
                {
                    dataIndex: 'action',
                    rowKey: 'action',
                    title: 'Action',
                },
                {
                    dataIndex: 'description',
                    rowKey: 'description',
                    title: 'Description',
                },
                {
                    dataIndex: 'causer',
                    rowKey: 'causer',
                    title: 'Made by',
                    render: (text, record) => <>{record.causer ? <>({record.causer.id}) {record.causer.first_name} {record.causer.last_name}</> : ''}</>
                }
            ]}
        />
    </>)

}

const AddInclusionModalContent = (props) => {

    // States
    const [inclusionCart, setInclusionCart] = React.useState([]);

    // Get
    const productListQuery = ProductService.list();
    const packageListQuery = PackageService.list();
    // Post, Put
    const [addInclusionsToBookingQuery, {isLoading: addInclusionsToBookingQueryIsLoading, reset: addInclusionsToBookingQueryReset}] = BookingService.addInclusionsToBooking();

    const addToCart = (inclusion, type) => {

        const exists = _.find(inclusionCart, i => i.id == inclusion.id);

        inclusion['inclusion_type'] = type;

        if (exists) {

            // removeToCart(inclusion)

        } else {

            setInclusionCart( prev => {
                return [...prev, {...inclusion, quantity:1}];
            });

        }

    }

    const removeToCart = (inclusion) => {

        setInclusionCart( prev => {
            return [...prev.filter(i => i.id != inclusion.id)];
        });

    }

    const handleQuantityChange = (id, value) => {
        console.log(value);

        let inclusionsCart = [...inclusionCart];

        const index = _.findIndex(inclusionsCart, i => i.id == id);

        inclusionsCart[index] = {
            ...inclusionsCart[index],
            quantity: value
        };

        setInclusionCart(inclusionsCart);

    }

    /**
     * Add to inclusion to booking
     */
    const handleAddInclusionToBooking = () => {
        // console.log(inclusionCart);

        if (addInclusionsToBookingQueryIsLoading) return false;

        addInclusionsToBookingQuery({
            booking_reference_number: props.booking.reference_number,
            inclusions: [...inclusionCart]
        }, {
            onSuccess: (res) => {
                // console.log(res);

                notification.success({
                    message: 'Inclusions added to booking.',
                    description:
                      `Invoice #: ${res.data.reference_number}-${res.data.batch_number} | Total: ₱ ${numberWithCommas(parseFloat(res.data.grand_total || 0).toFixed(2))}`,
                });

                setInclusionCart([]);
                props.setAddInclusionModalVisible(false);
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    return (
        <React.Fragment>
            <Row gutter={[24,24]}>
                <Col xl={12}>
                    <Row>
                        <Col xl={24}>
                        <Typography.Title level={4}>Select products</Typography.Title>
                        <Table
                            size="small"
                            rowKey="id"
                            rowClassName={()=> 'row-clickable'}
                            dataSource={productListQuery.data && productListQuery.data
                                        .filter((item) => item.status != 'retired')
                                        .filter( item => {

                                            if (item.availability == 'for_dtt_and_overnight') {
                                                return true;
                                            }
                                            // console.log(props.booking.type);
                                            if (props.booking.type == 'DT' && item.availability == 'for_dtt') {
                                                return true;
                                            }

                                            if (props.booking.type == 'ON' && item.availability == 'for_overnight') {
                                                return true;
                                            }

                                            return false;

                                        })
                            }
                            expandable={{
                                expandedRowRender: record => <Space>
                                            <div>{record.walkin_price ? <>Walk-in price: &#8369; {numberWithCommas(parseFloat(record.walkin_price || 0).toFixed(2))} | </> : '-'}</div>
                                            <div>{record.kid_price ? <>Kid price: &#8369; {numberWithCommas(parseFloat(record.kid_price || 0).toFixed(2))} | </> : '-'}</div>
                                            <div>{record.infant_price ? <>Infant price: &#8369; {numberWithCommas(parseFloat(record.infant_price || 0).toFixed(2))}</> : '-'}</div>
                                        </Space>,
                            }}
                            columns={[
                                {
                                    title: 'Product',
                                    dataIndex: 'name',
                                    key: 'name',
                                    render: (text) => <strong>{text}</strong>
                                },
                                {
                                    title: 'Code',
                                    dataIndex: 'code',
                                    key: 'code',
                                },
                                {
                                    title: 'Type',
                                    dataIndex: 'type',
                                    key: 'type',
                                    render: (text) => <small>{text}</small>,
                                    filters: [
                                        { text: 'Per guest', value: 'per_guest' },
                                        { text: 'Per booking', value: 'per_booking' },
                                    ],
                                    defaultFilteredValue: ['per_guest', 'per_booking'],
                                    onFilter: (value, record) => record.type.includes(value),

                                },
                                {
                                    title: 'Price',
                                    dataIndex: 'price',
                                    key: 'price',
                                    align: 'right',
                                    render: (text, record) => <span className="text-success">&#8369; {numberWithCommas(parseFloat(text || 0).toFixed(2))}</span>
                                },
                            ]}
                            onRow={(record, rowIndex) => {
                                return {
                                    onClick: () => addToCart(record, 'product')
                                }
                            }}
                        />
                        </Col>
                        <Col xl={24}>
                            <Typography.Title level={4}>Select packages</Typography.Title>
                            <Table
                                size="small"
                                rowKey="id"
                                rowClassName={()=> 'row-clickable'}
                                dataSource={packageListQuery.data && packageListQuery.data
                                    .filter((item) => item.status == 'published')
                                    .filter( item => {

                                        if (item.availability == 'for_dtt_and_overnight') {
                                            return true;
                                        }
                                        if (props.booking.type == 'DT' && item.availability == 'for_dtt') {
                                            return true;
                                        }

                                        if (props.booking.type == 'ON' && item.availability == 'for_overnight') {
                                            return true;
                                        }

                                        return false;

                                    })
                                }
                                expandable={{
                                    expandedRowRender: record => <Space>
                                                <div>{record.walkin_price ? <>Walk-in price: &#8369; {numberWithCommas(parseFloat(record.walkin_price || 0).toFixed(2))} | </> : '-'}</div>
                                                <div>{record.regular_price ? <>Regular price: &#8369; {numberWithCommas(parseFloat(record.regular_price || 0).toFixed(2))}</> : '-'}</div>
                                            </Space>,
                                }}
                                columns={[
                                    {
                                        title: 'Package',
                                        dataIndex: 'name',
                                        key: 'name',
                                        render: (text) => <strong>{text}</strong>
                                    },
                                    {
                                        title: 'Code',
                                        dataIndex: 'code',
                                        key: 'code',
                                    },
                                    {
                                        title: 'Type',
                                        dataIndex: 'type',
                                        key: 'type',
                                        render: (text) => <small>{text}</small>,
                                        filters: [
                                            { text: 'Per guest', value: 'per_guest' },
                                            { text: 'Per booking', value: 'per_booking' },
                                        ],
                                        defaultFilteredValue: ['per_guest', 'per_booking'],
                                        onFilter: (value, record) => record.type.includes(value),

                                    },
                                    // {
                                    //     title: 'Price',
                                    //     dataIndex: 'selling_price',
                                    //     key: 'selling_price',
                                    //     align: 'right',
                                    //     render: (text, record) => <span className="text-success">&#8369; {numberWithCommas(parseFloat(text || 0).toFixed(2))}</span>
                                    // },
                                    {
                                        title: 'Price',
                                        align: 'right',
                                        render: (text, record) => <small style={{whiteSpace: 'nowrap'}} className="text-success">Weekday: &#8369; {numberWithCommas(parseFloat(record.weekday_rate || 0).toFixed(2))}<br/>Weekend: &#8369; {numberWithCommas(parseFloat(record.weekend_rate || 0).toFixed(2))}</small>
                                    },
                                ]}
                                onRow={(record, rowIndex) => {
                                    return {
                                        onClick: () => addToCart(record, 'package')
                                    }
                                }}
                            />
                        </Col>
                        <Col xl={24}>
                            <Typography.Title level={4}>Select room (coming soon)</Typography.Title>
                        </Col>
                    </Row>
                </Col>
                <Col xl={12}>
                    <Typography.Title level={4}>Inclusions to be added</Typography.Title>
                    <Table
                        size="small"
                        rowKey="id"
                        dataSource={inclusionCart && inclusionCart}
                        pagination={{
                            defaultPageSize: 20,
                        }}
                        columns={[
                            {
                                title: 'Inclusion',
                                dataIndex: 'name',
                                key: 'name',
                                render: (text) => <strong>{text}</strong>
                            },
                            {
                                title: 'Code',
                                dataIndex: 'code',
                                key: 'code',
                            },
                            // {
                            //     title: 'Price',
                            //     render: (text, record) => {

                            //         let total_pax_less_infant = 0;

                            //         total_pax_less_infant = parseInt(props.booking.adult_pax+props.booking.kid_pax) * record.selling_price || 0;

                            //         return <small>
                            //             {
                            //                 record.type == 'per_guest' ?
                            //                     <Space direction="vertical">

                            //                         { record.inclusion_type == 'product' ?
                            //                             <div>
                            //                                 Adult: {props.booking.adult_pax} x { numberWithCommas(record.price)} = &#8369; {numberWithCommas((parseInt(props.booking.adult_pax) * parseFloat(record.price)).toFixed(2))}
                                                        
                            //                                 {props.booking.kid_pax ? <div>Kid: {props.booking.kid_pax} x {numberWithCommas(record.kid_price != null ? record.kid_price : record.price)} = &#8369; {numberWithCommas((parseInt(props.booking.kid_pax) * parseFloat(record.kid_price != null ? record.kid_price : record.price)).toFixed(2))}</div> : ''}
                                                    
                            //                                 {props.booking.infant_pax ? <div>Infant: {props.booking.infant_pax} x {numberWithCommas(record.infant_price != null ? record.infant_price : 0)} = &#8369; {numberWithCommas((parseInt(props.booking.infant_pax) * parseFloat(record.infant_price != null ? record.infant_price : 0)).toFixed(2))}</div> : ''}
                            //                             </div>
                            //                         :
                            //                             <div>
                            //                                 No. of Pax: {props.booking.adult_pax + props.booking.kid_pax} x { numberWithCommas(record.selling_price)} = &#8369; {numberWithCommas(parseFloat((total_pax_less_infant) || 0).toFixed(2))}<br/>
                            //                                 <small><i><span class="text-danger">LESS:</span> Infant</i></small>
                            //                             </div>
                            //                         }
                            //                     </Space>
                            //                 :
                            //                 <>
                            //                 <Select defaultValue={1} onChange={(e)=>handleQuantityChange(record.id, e)}>
                            //                     {
                            //                         _.map(_.range(1,11), (item, key) => {
                            //                             return <Select.Option key={key} value={item}>{item}</Select.Option>
                            //                         })
                            //                     }
                            //                 </Select>
                            //                 { record.inclusion_type == 'product' ?
                            //                     <span> x &#8369; {numberWithCommas((parseFloat(record.price || 0)))} </span>
                            //                     :
                            //                     <span> &#8369; {numberWithCommas((parseFloat(record.selling_price || 0)))}</span>
                            //                 }
                            //                 </>
                            //             }
                            //         </small>
                            //     }
                            // },
                            // {
                            //     title: 'Total Price',
                            //     align: 'right',
                            //     render: (text, record) => {

                            //         let adult_price_total = 0;
                            //         let kid_price_total = 0;
                            //         let infant_price_total = 0;
                            //         let total_pax_less_infant = 0;

                            //         adult_price_total = parseInt(props.booking.adult_pax) * parseFloat(record.price) || 0;
                            //         kid_price_total = parseInt(props.booking.kid_pax) * parseFloat(record.kid_price != null ? record.kid_price : record.price) || 0;

                            //         infant_price_total = parseInt(props.booking.infant_pax) * parseFloat(record.infant_price != null ? record.infant_price : 0);

                            //         total_pax_less_infant = parseInt(props.booking.adult_pax+props.booking.kid_pax) * record.selling_price || 0;

                            //         return (
                            //             record.type == 'per_guest' ?

                            //                 <div className="text-success">&#8369; 
                            //                     { record.inclusion_type == 'product' ?
                            //                         (numberWithCommas(parseFloat((adult_price_total+kid_price_total+infant_price_total) || 0).toFixed(2)))
                            //                     :
                            //                         (numberWithCommas(parseFloat((total_pax_less_infant) || 0).toFixed(2)))
                            //                     }
                            //                 </div>

                            //             :
                                            
                            //                 <div className="text-success">&#8369; 
                            //                     { record.inclusion_type == 'product' ?
                            //                         (numberWithCommas((parseFloat(record.price || 0) * parseInt(record.quantity)).toFixed(2)))
                            //                     :
                            //                         (numberWithCommas((parseFloat(record.selling_price || 0) * parseInt(record.quantity)).toFixed(2)))
                            //                     }
                            //                 </div>
                            //         )
                            //     }
                            // },
                            {
                                title: 'Action',
                                render: (record) => <Button onClick={()=>removeToCart(record)} size="small" icon={<MinusOutlined/>} />
                            }
                        ]}
                        footer={
                            (currentPageData) => {

                                let $prod_total_price = 0;
                                let $pkg_total_price = 0;

                                _.map(currentPageData, (record, key) => {

                                    // PRODUCT
                                    const adult_price_total = parseInt(props.booking.adult_pax) * parseFloat(record.price) || 0;
                                    const kid_price_total = parseInt(props.booking.kid_pax) * parseFloat(record.kid_price != null ? record.kid_price : record.price) || 0;
                                    const infant_price_total = parseInt(props.booking.infant_pax) * parseFloat(record.infant_price != null ? record.infant_price : 0);

                                    // PACKAGE
                                    const total_pax_less_infant = parseInt(props.booking.adult_pax+props.booking.kid_pax) * record.selling_price || 0;

                                    if (record.type == 'per_guest') {
                                            if (record.inclusion_type == 'product') {
                                                $prod_total_price  = parseFloat($prod_total_price) + parseFloat(adult_price_total) + parseFloat(kid_price_total) + parseFloat(infant_price_total);
                                            } else {
                                                $pkg_total_price  = parseFloat($pkg_total_price) + parseFloat(total_pax_less_infant);
                                            }
                                        
                                    } else {
                                            if (record.inclusion_type == 'product') {
                                                $prod_total_price  = parseFloat($prod_total_price) + (parseFloat(record.price) * record.quantity);
                                            } else {
                                                $pkg_total_price  = parseFloat($pkg_total_price) + (parseFloat(record.selling_price) * record.quantity);
                                            }
                                    }
                                })

                                return <div style={{display:'flex', justifyContent:'flex-end'}}>
                                    <div style={{textAlign:'right'}}>
                                        {/* <Typography.Title level={4} className="text-success mb-0">&#8369; {numberWithCommas((parseFloat(total_price || 0).toFixed(2)))}</Typography.Title> */}

                                        {/* HIDE UNTIL FIXED */}
                                        {/* <Typography.Title level={4} className="text-success mb-0">&#8369; {numberWithCommas((parseFloat($prod_total_price+$pkg_total_price || 0).toFixed(2)))}</Typography.Title> */}
                                        <small className='text-danger'>(Under maintenance, please compute manually.)</small>
                                        <p>Total</p>
                                    </div>
                                </div>
                            }
                        }
                    />
                </Col>
                <Col xl={24} align="right">
                    <Button type="primary" onClick={() => handleAddInclusionToBooking()}>Add Inclusions to Booking</Button>
                </Col>
            </Row>
        </React.Fragment>
    )
}

const AddAccessPassForm = (props) => {

    const stubListQuery = StubService.list();
    const [addGuestPassQuery, {isLoading: addGuestPassQueryIsLoading}] = GuestService.addGuestPass();

    const [addAccessPassForm] = Form.useForm();
    const [selectedStub, setselectedStub] = React.useState({});

    if (stubListQuery.isLoading) {
        return <Loading isHeightFull={false}/>
    }

    const onFinish = (values) => {

        if (addGuestPassQueryIsLoading) return false;

        const newValues = {
            ...values,
            guest_reference_number: props.guestRefNo,
        }
        console.log(values);
        addGuestPassQuery(newValues, {
            onSuccess: (res) => {
                // console.log(res);

                message.success("Passes added to guest.");

                addAccessPassForm.resetFields();

                Modal.destroyAll();

            },
            onError: (e) => {
                message.info(e.message);
            }
        })
    }

    return <>
            <Typography.Title level={5}>Add Access Pass to {props.guest} </Typography.Title>

            <Form
                layout="vertical"
                form={addAccessPassForm}
                onFinish={onFinish}
                initialValues={{
                    starttime: moment('00:00:00', 'HH:mm:ss'),
                    endtime: moment('23:59:00', 'HH:mm:ss'),
                }}
            >
                <Row gutter={[8,8]}>
                <Col xl={24}>
                    <Form.Item name="stub" label="Pass stub" rules={[{required: true}]}>
                        <Select onChange={(e)=>setselectedStub(_.find(stubListQuery.data, {id:e}))}>
                            {
                                stubListQuery.data
                                    .filter(i => !_.includes(_.map(props.existingPasses, 'type'), i.type))
                                    .map((item, key) => {
                                        return <Select.Option value={item.id} key={key}>{item.type}</Select.Option>
                                    })
                            }
                        </Select>
                    </Form.Item>
                    {
                        (selectedStub && selectedStub.id) &&
                        <Descriptions bordered size="small" column={1} className="mb-2">
                            <Descriptions.Item label="Type">{selectedStub.type}</Descriptions.Item>
                            <Descriptions.Item label="Interfaces">{selectedStub.interfaces}</Descriptions.Item>
                            <Descriptions.Item label="Mode">{selectedStub.mode}</Descriptions.Item>
                            <Descriptions.Item label="Category">{selectedStub.category}</Descriptions.Item>
                        </Descriptions>
                    }
                </Col>
                <Col xl={24}>
                    <Form.Item label="Count" name="count" rules={[{required: true}]}>
                        <InputNumber min={0} />
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item label="Start time" name="starttime">
                        <TimePicker />
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item label="End time" name="endtime">
                        <TimePicker />
                    </Form.Item>
                </Col>
                <Col xl={24} align="right">
                    <Button htmlType="submit">Save</Button>
                </Col>
                </Row>
            </Form>
        </>
}

const ViewGuestPassModalContent = (props) => {

    const [record, setRecord] = React.useState(props.record);

    // Post, Put
    const [updateUsableAtQuery, {isLoading: updateUsableAtQueryIsLoading}] = PassService.updateUsableAt();
    const [updateExpiresAtQuery, {isLoading: updateExpiresAtQueryIsLoading}] = PassService.updateExpiresAt();
    const [deletePassQuery, {isLoading: deletePassQueryIsLoading, reset: deletePassQueryReset}] = PassService.deletePass();

    const handlePassesUsableAtChange = (id, date) => {
        console.log(id, date)

        if (updateUsableAtQueryIsLoading) return false;

        updateUsableAtQuery({
            id: id,
            date: date,
        },{
            onSuccess: (res) => {
                // console.log(res)
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handlePassesExpiresAtChange = (id, date) => {
        console.log(id, date)

        if (updateExpiresAtQueryIsLoading) return false;

        updateExpiresAtQuery({
            id: id,
            date: date,
        },{
            onSuccess: (res) => {
                // console.log(res)
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    /**
     * handleDeletePassClick
     */

    const handleDeletePassClick = (pass_id) => {
        console.log(pass_id);

        if (deletePassQueryIsLoading) return false;

        deletePassQuery({
            pass_id: pass_id
        }, {
            onSuccess: (res) => {
                // console.log(res);
                setRecord(res.data);

                message.success("Guest pass deleted.");
            },
            onError: (e) => {
                message.error(e.error);
            }
        })
    }

    return <div>
                <div style={{display: 'flex', flexDirection:'row', justifyContent: 'space-between', alignItems: 'center'}}>
                    <QRCode size={100} value={record.reference_number} logoWidth={25} logoImage={process.env.APP_URL+"/images/camaya-logo.jpg"} />
                    <strong style={{fontSize: '2rem'}}>{record.reference_number}</strong>
                    <div style={{fontSize: '2rem'}}>{record.first_name} {record.last_name}</div>
                </div>
                <div className="mt-5">
                    <Typography.Title level={4}>Guest access passes</Typography.Title>
                    {
                        record.passes && record.passes.map( (pass, key) => {
                            return  <Card size="small" key={key}
                                        extra={
                                            <Popconfirm
                                                title="Are you sure you want to delete?" okText="Yes" cancelText="No"
                                                onConfirm={(e) => handleDeletePassClick(pass.id)}
                                            >
                                                <Button danger icon={<DeleteOutlined/>} />
                                            </Popconfirm>
                                        }
                                        hoverable={true} className="mb-2 card-shadow" headStyle={{background: pass.trip ? 'indigo':'#1177fa', color: 'white'}} title={<span style={{textTransform:'capitalize'}}><Icon component={TicketIcon} className="mr-2" />{pass.type.replace(/_/g, ' ')}</span>}>
                                        <Row gutter={[32, 32]} className="m-0">
                                            <Col xl={8}>
                                                {pass.pass_code}
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Access Code</small></div>
                                            </Col>
                                            <Col xl={8}>
                                                {/* {pass.status} */}
                                                <Select defaultValue={pass.status} onChange={(e) => console.log(e)}>
                                                    <Select.Option value="created">created</Select.Option>
                                                    <Select.Option value="consumed">consumed</Select.Option>
                                                    <Select.Option value="used">used</Select.Option>
                                                    <Select.Option value="voided">voided</Select.Option>
                                                </Select>
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Status</small></div>
                                            </Col>

                                            <Col xl={8}>
                                                <span className="text-success">{pass.count >= 0 ? pass.count : <>&#8734;</>}</span>
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Remaining count</small></div>
                                            </Col>

                                            <Col xl={6}>
                                                {pass.category}
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Category</small></div>
                                            </Col>
                                            <Col xl={6}>
                                                {pass.interfaces.join(', ')}
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Interfaces</small></div>
                                            </Col>
                                            <Col xl={6}>
                                                {/* {pass.usable_at} */}
                                                <DatePicker defaultValue={moment(pass.usable_at)} allowClear={false} showTime onChange={(e) => handlePassesUsableAtChange(pass.id, e)} onOk={(e) => console.log(e)} />
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Usable at</small></div>
                                            </Col>
                                            <Col xl={6}>
                                                {/* {pass.expires_at} */}
                                                <DatePicker defaultValue={moment(pass.expires_at)} allowClear={false} showTime onChange={(e) => handlePassesExpiresAtChange(pass.id, e)} onOk={(e) => console.log(e)} />
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Expires at</small></div>
                                            </Col>
                                        </Row>
                                        {
                                            pass.trip &&
                                            <>
                                            <Divider orientation="left">Boarding details <Button size="small" onClick={()=>message.info("Coming soon...")}>View seat plan</Button></Divider>
                                            <Descriptions className="m-3" bordered>
                                                <Descriptions.Item label="Seat #">{pass.trip.seat_number}</Descriptions.Item>
                                                <Descriptions.Item label="Status">{pass.trip.status}</Descriptions.Item>
                                            </Descriptions>
                                            </>
                                        }
                                        </Card>
                        })
                    }
                    <Card
                        bordered={true}
                        // hoverable={true}
                        size="small"
                        // onClick={()=>setNewProductDrawerVisible(true)}
                        className="card-add-button"
                        onClick={()=> {
                            Modal.info({
                                    icon: null,
                                    width: 500,
                                    okType: 'link',
                                    // onOk: ()=> console.log('test'),
                                    okText: 'Close',
                                    content: <AddAccessPassForm existingPasses={record.passes} guestRefNo={record.reference_number} guest={`${record.first_name} ${record.last_name}`} />
                                })
                            }
                        }
                    >
                        <Button type="link"><PlusOutlined/> Add Access Pass</Button>
                    </Card>
                </div>
            </div>

}

function Page(props) {

    // console.log(props);

    const tagColor = {
        draft: 'purple',
        pending: 'orange',
        confirmed: 'green',
        cancelled: 'red',
    };

    const invoiceStatusColor = {
        sent: 'orange',
        paid: 'green',
        partial: 'blue',
        overdue: 'red',
    }

    const tagsStatusFilterData = [
        'paid', 'overdue', 'sent', 'partial', 'void'
    ];

    // Queries
    // const customerListQuery = CustomerService.list(props.isTripping);
    const viewBookingQuery = BookingService.viewBooking(props.referenceNumber);
    const productListQuery = ProductService.list();
    // const packageListQuery = PackageService.list();
    // const invoiceListQuery = InvoiceService.list(props.referenceNumber);

    // Post, Put
    const [cancelBookingQuery, {isLoading: cancelBookingQueryIsLoading, error: cancelBookingQueryError, reset: cancelBookingQueryReset}] = BookingService.cancelBooking();
    const [pendingBookingQuery, {isLoading: pendingBookingQueryIsLoading, error: pendingBookingQueryError}] = BookingService.pendingBooking();
    const [confirmBookingQuery, {isLoading: confirmBookingQueryIsLoading, error: confirmBookingQueryError}] = BookingService.confirmBooking();
    const [updateGuestQuery, {isLoading: updateGuestQueryIsLoading, error: updateGuestQueryError, reset: updateGuestQueryReset}] = BookingService.updateGuest();
    const [updateVehicleQuery, {isLoading: updateVehicleQueryIsLoading, error: updateVehicleQueryError}] = BookingService.updateVehicle();
    const [addVehicleQuery, {isLoading: addVehicleQueryIsLoading, reset: addVehicleQueryReset}] = BookingService.addVehicle();
    const [customerListQuery2, {isLoading: customerListQuery2IsLoading, reset: customerListQuery2Reset}] = CustomerService.list2(props.isTripping);
    const [updateCheckInTimeQuery, { isLoading: updateCheckInTimeQueryIsLoading, reset: updateCheckInTimeQueryReset}] = RoomReservationService.updateCheckInTime();

    const [downloadBoardingPassOnePDFQuery, {isLoading: downloadBoardingPassOnePDFQueryIsLoading, reset: downloadBoardingPassOnePDFQueryReset}] = BookingService.downloadBoardingPassOnePDF();

    const [downloadBookingConfirmationQuery, {isLoading: downloadBookingConfirmationQueryIsLoading, reset: downloadBookingConfirmationQueryReset}] = BookingService.downloadBookingConfirmation();

    // const [updateBookingLabelQuery, {isLoading: updateBookingLabelQueryIsLoading, error: updateBookingLabelQueryError}] = BookingService.updateBookingLabel();
    const [updateBookingLabelQuery, {isLoading: updateBookingLabelQueryIsLoading}] = BookingService.updateBookingLabel();
    const [updateRemarksQuery, {isLoading: updateRemarksQueryIsLoading}] = BookingService.updateRemarks();
    const [updateBillingInstructionsQuery, {isLoading: updateBillingInstructionsQueryIsLoading, reset: updateBillingInstructionsQueryReset}] = BookingService.updateBillingInstructions();

    const [resendBookingConfirmationQuery, {isLoading: resendBookingConfirmationQueryIsLoading, reset: resendBookingConfirmationQueryReset}] = BookingService.resendBookingConfirmation();
    const [updateGuestStatusQuery, {isLoading: updateGuestStatusQueryIsLoading}] = GuestService.updateStatus();
    const [newNoteQuery, {isLoading: newNoteQueryIsLoading, reset: newNoteQueryReset}] = BookingService.newNote();
    const [newPaymentQuery, {isLoading: newPaymentQueryIsLoading, error: newPaymentQueryError}] = InvoiceService.newPayment();
    const [updateInvoiceDiscountQuery, {isLoading: updateInvoiceDiscountQueryIsLoading}] = InvoiceService.updateDiscount();
    const [updateInclusionDiscountQuery, {isLoading: updateInclusionDiscountQueryIsLoading}] = InvoiceService.updateInclusionDiscount();
    const [addGuestQuery, {isLoading: addGuestQueryIsLoading, reset: addGuestQueryReset}] = BookingService.addGuest();
    const [removeInclusionQuery, {isLoading: removeInclusionQueryIsLoading, reset: removeInclusionQueryReset}] = BookingService.removeInclusion();
    const [updateAdditionalEmailsQuery, {isLoading:updateAdditionalEmailsQueryIsLoading, reset: updateAdditionalEmailsQueryReset}] = BookingService.updateAdditionalEmails();
    const [updateBookingTagsQuery, {isLoading:updateBookingTagsQueryIsLoading, reset: updateBookingTagsQueryReset}] = BookingService.updateBookingTags();
    const [voidPaymentQuery, {isLoading: voidPaymentQueryIsLoading, reset: voidPaymentQueryReset}] = InvoiceService.voidPayment();
    const [deleteGuestQuery, {isLoading: deleteGuestQueryIsLoading, reset: deleteGuestQueryReset}] = GuestService.deleteGuest();
    const [deleteVehicleQuery, {isLoading: deleteVehicleQueryIsLoading, reset: deleteVehicleQueryReset}] = BookingService.deleteVehicle();
    const [updatePrimaryCustomer, {isLoading: updatePrimaryCustomerIsLoading, reset: updatePrimaryCustomerReset}] = BookingService.updatePrimaryGuest();
    const [updateAutoCancelDate, {isLoading: updateAutoCancelDateIsLoading, reset: updateAutoCancelDateReset}] = BookingService.updateAutoCancelDate();
    const [updateBookingDate, {isLoading: updateBookingDateIsLoading, isSuccess: updateBookingDateIsSuccess, reset: updateBookingDateReset}] = BookingService.updateBookingDate();

    // States
    const [activeTabKey, setactiveTabKey] = React.useState([]);
    const [selectedStatusFilterTags, setselectedStatusFilterTags] = React.useState(tagsStatusFilterData);
    const [viewGuestPasses, setViewGuestPasses] = React.useState({});
    const [customers, setCustomers] = React.useState([]);
    const [searchCustomer, setSearchCustomer] = React.useState('');
    // Modal states
    const [viewPaymentsModalVisible, setviewPaymentsModalVisible] = React.useState(false);
    const [addNoteDrawerVisible, setaddNoteDrawerVisible] = React.useState(false);
    const [addGuestModalVisible, setaddGuestModalVisible] = React.useState(false);
    const [viewAttachmentsModalVisible, setviewAttachmentsModalVisible] = React.useState(false);
    const [viewGuestModalVisible, setViewGuestModalVisible] = React.useState(false);
    const [changePrimaryCustomerModalOpen, setChangePrimaryCustomerModalOpen] = React.useState(false);

    const [addVehicleModalVisible, setAddVehicleModalVisible] = React.useState(false);
    const [editVehicleModalVisible, setEditVehicleModalVisible] = React.useState(false);

    // const [editLabelModalVisible, setEditLabelModalVisible] = React.useState(false); 

    const [addInclusionModalVisible, setAddInclusionModalVisible] = React.useState(false);
    const [viewGuestPassModalVisible, setViewGuestPassModalVisible] = React.useState(false);
    const [addRoomModalVisible, setAddRoomModalVisible] = React.useState(false);
    const [viewLogsModalVisible, setviewLogsModalVisible] = React.useState(false);
    const [addFerryModalVisible, setAddFerryModalVisible] = React.useState(false);
    // Add Guest States
    const [addGuestWalkin, setaddGuestWalkin] = React.useState(false);
    const [addGuestInclusions, setaddGuestInclusions] = React.useState([]);
    // Add Ferry to guests
    const [addFerryToGuestsModalVisible, setAddFerryToGuestsModalVisible] = React.useState(false);
    const [modeOfPayment, setModeOfPayment] = React.useState('');
    const [paymentModalVisible, setPaymentModalVisible] = React.useState(false);
    const [invoiceIdForPayment, setInvoiceIdForPayment] = React.useState(null);
    // Show Datepicker
    const [showDatePicker, setShowDatePicker] = React.useState(false);
    const [newBookingDate, setNewBookingDate] = React.useState('');

    // Forms
    const [viewGuestForm] = Form.useForm();
    const [newPaymentForm] = Form.useForm();
    const [addGuestForm] = Form.useForm();
    const [viewBookingForm] = Form.useForm();
    const [newNoteForm] = Form.useForm();
    const [editVehicleForm] = Form.useForm();
    const [addVehicleForm] = Form.useForm();

    // const [editLabelForm] = Form.useForm();

    // console.log(viewBookingQuery.data);

    React.useEffect( () => {
        if (viewPaymentsModalVisible) {
            // invoiceListQuery.refetch();
        }
    }, [viewPaymentsModalVisible]);

    React.useEffect( () => {
        if (viewGuestPassModalVisible == false) {
            setViewGuestPasses({});
        }
    }, [viewGuestPassModalVisible]);

    React.useEffect( () => {

        if (viewBookingQuery.data) {

        }

    }, [viewBookingQuery.data]);

    React.useEffect( () => {
        if (changePrimaryCustomerModalOpen) {
            setCustomers([]);
            setSearchCustomer('');
        }
    }, [changePrimaryCustomerModalOpen]);

    React.useEffect( () => {

        setaddGuestInclusions([]);

    }, [addGuestWalkin]);    

    React.useEffect( () => {

        if (updateBookingDateIsSuccess) {
            viewBookingQuery.refetch();
            setShowDatePicker(false);
        }

    }, [updateBookingDateIsSuccess]);  

    if (viewBookingQuery.isLoading) {
        return <div style={{marginTop: 90, marginBottom: 90, display: 'flex', justifyContent: 'center', flexDirection:'column', alignItems:'center'}}>
                    <div className='mb-24'><TeaLoading /></div>
                    <div style={{textAlign:'center', fontSize: '1.2rem', paddingTop: 4, marginTop: 4}}><b>{props.referenceNumber}</b> loading booking details.<br/>Please wait...</div>
                </div>
    }

    const handleStatusFilterTagsChange = (tag, checked) => {
        const nextSelectedTags = checked ? [...selectedStatusFilterTags, tag] : selectedStatusFilterTags.filter(t => t !== tag);
        setselectedStatusFilterTags(nextSelectedTags);
    }

    /**
     * Change Booking Status
     */
    const handleCancelBooking = (reference_number) => {
        console.log(reference_number);

        if (cancelBookingQueryIsLoading) {
            message.info("You already sent request to cancel this booking.");
            return false;
        }

        cancelBookingQuery({reference_number: reference_number}, {
            onSuccess: (res) => {

                // console.log(res);

                viewBookingQuery.refetch();

                queryCache.setQueryData(['bookings', { reference_number: res.data.reference_number }], res.data);

                notification.success({
                    message: 'Booking Cancelled '+reference_number ,
                    description:
                        `Changed booking status of ${reference_number} to cancelled.`,
                });

                // Reset Forms
                cancelBookingQueryReset();

            },
            onError: (res) => {
                // console.log(res);
                cancelBookingQueryReset();
            }
        });
    }

    const handlePendingBooking = (reference_number) => {
        console.log(reference_number);

        if (pendingBookingQueryIsLoading) return false;

        pendingBookingQuery({reference_number: reference_number}, {
            onSuccess: (res) => {

                // console.log(res);

                viewBookingQuery.refetch();

                queryCache.setQueryData(['bookings', { reference_number: res.data.reference_number }], res.data);

                notification.success({
                    message: 'Booking Pending '+reference_number ,
                    description:
                        `Changed booking status of ${reference_number} to pending.`,
                });

                // Reset Forms

            },
            onError: (res) => {
                // console.log(res);
                message.error(res.error);
            }
        });
    }

    const handleConfirmBooking = (reference_number) => {
        // console.log(reference_number);

        if (confirmBookingQueryIsLoading) {
            message.info('Confirming booking...');
            return false;
        }

        confirmBookingQuery({reference_number: reference_number}, {
            onSuccess: (res) => {

                // console.log(res);

                viewBookingQuery.refetch();

                queryCache.setQueryData(['bookings', { reference_number: res.data.reference_number }], res.data);

                notification.success({
                    message: 'Booking Confirmed '+reference_number ,
                    description:
                        `Changed booking status of ${reference_number} to confirmed.`,
                });

                // Reset Forms

                // Update tabs
                // props.updateBookingTabs({reference_number: res.data.reference_number, status: res.data.status}, 'update');
                props.bookingPaneEdit(res.data.reference_number, 'update', res.data.status);


            },
            onError: (res) => {
                console.log(res);
            }
        });
    }

    // const onChange = (value) => {
    //     console.log(`selected ${value}`);
    // }

    // const onBlur = () => {
    //     console.log('blur');
    // }

    // const onFocus = () => {
    //     console.log('focus');
    // }

    const onSearch = (val) => {
        console.log('search:', val);
    }

    const handleSearchCustomer = (search) => {

        if (customerListQuery2IsLoading) {
            return false;
        }

        if (search.length < 3) {
            message.info("Search must be atleast 3 characters.");
            return false;
        }

        customerListQuery2({
            search: search,
            v: Math.random()
        }, {
            onSuccess: (res) => {
                // console.log(res);
                setCustomers(res.data);
            },
            onError: (e) => {
                customerListQuery2Reset();
                message.info("Error loading customers");
            }
        })
    }

    const children = [];

    for (let i = 10; i < 36; i++) {
        children.push(<Option key={i.toString(36) + i}>{i.toString(36) + i}</Option>);
    }

    const tabList = ({inclusions, payments}) => [
        {
            key: 'invoice',
            tab: <span style={{fontSize: '14px'}}><FileOutlined/>Invoice</span>,
        },
        {
            key: 'inclusions',
            tab: <Badge size="small"  count={inclusions.length || 0}>
                    Inclusions
                </Badge>,
        },
        {
            key: 'payments',
            tab: <Badge size="small" offset={[1, -1]} count={payments.length || 0}>
                    Payments
                </Badge>,
        },
    ];

    const Toolbar = () => {
        {/* toolbar */}

        if (viewBookingQuery.isLoading) {
            return <Loading isHeightFull={false}/>
        }

        return (
            <Row className="my-3">
                <Col xl={16}>
                    <Space size="large">
                        <div>
                                <Tag
                                    onClick={()=>Modal.confirm({
                                                    icon: null,
                                                    content:
                                                        <div style={{display: 'flex', flexDirection:'column', justifyContent: 'center', alignItems: 'center'}}>
                                                            <QRCode size={200} value={viewBookingQuery.data && viewBookingQuery.data.reference_number} logoWidth={50} logoImage={process.env.APP_URL+"/images/camaya-logo.jpg"} />
                                                            <strong>{viewBookingQuery.data && viewBookingQuery.data.reference_number}</strong>
                                                        </div>
                                                })
                                    }
                                    style={{fontSize: '1rem'}}
                                    color={tagColor[viewBookingQuery.data ? viewBookingQuery.data.status : '']}
                                >{props.referenceNumber}</Tag>
                                <br/><small className="text-secondary">Booking Reference Number</small></div>
                        <div><UserOutlined/> {(viewBookingQuery.data && viewBookingQuery.data.booked_by) ? `${viewBookingQuery.data.booked_by.first_name} ${viewBookingQuery.data.booked_by.last_name}` : <>{viewBookingQuery.data.customer.first_name} {viewBookingQuery.data.customer.last_name}</>}<br/><small className="text-secondary">Creator</small></div>
                        <div><span className={(viewBookingQuery.data && viewBookingQuery.data.balance <= 0) ? 'text-success' : 'text-warning'}>Php {viewBookingQuery.data && (viewBookingQuery.data.balance || 0)}</span><Button onClick={()=>setviewPaymentsModalVisible(true)} size="small" className="ml-2" icon={<EyeOutlined />}>View invoices &amp; payments</Button><br/><small className="text-secondary">Balance</small></div>
                        <div><span><FileOutlined/> {viewBookingQuery.data && viewBookingQuery.data.attachments.length} Files</span><Button size="small" className="ml-2" onClick={()=>setviewAttachmentsModalVisible(true)} icon={<FileSearchOutlined />}>View attachments</Button><br/><small className="text-secondary">Attachments</small></div>
                        <div><Button size="small" className="ml-2" onClick={()=>setviewLogsModalVisible(true)} icon={<FileSearchOutlined />}>View logs</Button><br/><small className="text-secondary">Activity logs</small></div>
                    </Space>
                </Col>
                <Col xl={8}>
                    <div style={{display:'flex', alignItems:'flex-end', justifyContent:'flex-end'}}>
                        <Space size="small">
                            {
                                (viewBookingQuery.data && (viewBookingQuery.data.status == 'pending' || viewBookingQuery.data.status == 'confirmed')) &&
                                <Dropdown className="ml-1 p-2" overlay={viewBookingMenu(props.referenceNumber, viewBookingQuery.data.mode_of_transportation)}>
                                    <Button><EllipsisOutlined/></Button>
                                </Dropdown>
                            }
                            {
                                (viewBookingQuery.data && (viewBookingQuery.data.status == 'pending' || viewBookingQuery.data.status == 'confirmed')) &&
                                <Popconfirm title="Are you sure？" okText="Yes" cancelText="No" onConfirm={()=>handleCancelBooking(props.referenceNumber)}>
                                    <Button type="dashed" loading={cancelBookingQueryIsLoading} disabled={cancelBookingQueryIsLoading} danger>Cancel Booking</Button>
                                </Popconfirm>
                            }
                            {
                                (viewBookingQuery.data && (viewBookingQuery.data.status == 'draft')) &&
                                <>
                                    <Popconfirm title="Are you sure？" okText="Yes" cancelText="No" onConfirm={()=>handleCancelBooking(props.referenceNumber)}>
                                        <Button type="dashed" loading={cancelBookingQueryIsLoading} disabled={cancelBookingQueryIsLoading} danger>Cancel Draft</Button>
                                    </Popconfirm>
                                    <Popconfirm title="Are you sure？" okText="Yes" cancelText="No" onConfirm={()=>handlePendingBooking(props.referenceNumber)}>
                                        <Button>Pending Booking</Button>
                                    </Popconfirm>
                                </>
                            }
                            {
                                (viewBookingQuery.data && (viewBookingQuery.data.status == 'cancelled' && viewBookingQuery.data.mode_of_transportation != 'camaya_transportation'  && viewBookingQuery.data.type == 'DT')) &&
                                <>
                                    <Popconfirm title="Are you sure？" okText="Yes" cancelText="No" onConfirm={()=>handlePendingBooking(props.referenceNumber)}>
                                        <Button size="small">Return Booking Status to Pending</Button>
                                    </Popconfirm>
                                </>
                            }
                            {
                                (viewBookingQuery.data && (viewBookingQuery.data.status == 'pending' || viewBookingQuery.data.status == 'draft')) &&
                                <Popconfirm title="Are you sure？" okText="Yes" cancelText="No" onConfirm={()=>handleConfirmBooking(props.referenceNumber)}>
                                    <Button type="primary">Confirm Booking</Button>
                                </Popconfirm>
                            }
                        </Space>
                    </div>
                </Col>
            </Row>
        )
    }

    const showPaymentModal = (invoice_id) => {

        setPaymentModalVisible(true);

        setInvoiceIdForPayment(invoice_id);

        // const modal = Modal.confirm();

        // modal.update({
        //     icon: <MoneyCollectOutlined />,
        //     title: 'New Payment',
        //     content: <Content/>,
        //     onOk(close) {
        //         // console.log(newPaymentForm.submit());
        //     },
        //     onCancel() {
        //         console.log('Cancel');
        //     },
        // })

        // update();
    }

    const newPaymentFormFinish = (values) => {

        const newValues = {
            ...values,
            invoice_id: invoiceIdForPayment
        }

        if (newPaymentQueryIsLoading) {
            message.info("New payment in progress...");
            return false;
        }

        newPaymentQuery(newValues, {
            onSuccess: (res) => {
                // console.log(res);
                newPaymentForm.resetFields();

                notification.success({
                    message: 'New Payment made',
                    description:
                        `Successfully added new payment to Invoice ID: ${invoiceIdForPayment}`,
                });

                setInvoiceIdForPayment(null);
                setPaymentModalVisible(false);
                viewBookingQuery.refetch();
            },
            onError: (e) => {
                message.warning(e.message);
            }
        });
    }

    const PaymewntModalContent = (props) => (
        <Form
            layout="vertical"
            labelCol={{ span: 24 }}
            wrapperCol={{ span: 24 }}
            form={newPaymentForm}
            onFinish={newPaymentFormFinish}
        >
            <Form.Item name="mode_of_payment" label="Mode of payment" rules={[
                {
                    required: true
                }
            ]}>
                <Select placeholder="Select payment mode" onChange={(e)=>{ setModeOfPayment(e)}}>
                    <Select.Option value="cash">Cash</Select.Option>
                    <Select.Option value="voucher">Voucher</Select.Option>
                    <Select.Option value="foc_cashier">FOC - Cashier</Select.Option>
                    <Select.OptGroup key="online_payment" label="Online Payment">
                        <Select.Option value="online_payment_paypal">Paypal</Select.Option>
                        <Select.Option value="online_payment_dragonpay">DragonPay</Select.Option>
                        <Select.Option value="online_payment_paymaya">Paymaya</Select.Option>
                    </Select.OptGroup>
                    <Select.OptGroup key="bank" label="Bank">
                        <Select.Option value="bank_deposit">Bank Deposit</Select.Option>
                        <Select.Option value="bank_transfer">Bank Transfer</Select.Option>
                    </Select.OptGroup>
                    <Select.OptGroup key="cards_and_checks" label="Cards and Checks">
                        <Select.Option value="credit_card">Credit Card</Select.Option>
                        <Select.Option value="debit_card">Debit Card</Select.Option>
                        <Select.Option value="check_payments">Check payments</Select.Option>
                    </Select.OptGroup>
                    {/* <Select.Option value="city_ledger">City Ledger</Select.Option> */}
                    <Select.OptGroup label="City Ledger">
                        <Select.Option value="city_ledger_ESLCC">City Ledger - ESLCC</Select.Option>
                        <Select.Option value="city_ledger_ESTLC">City Ledger - ESTLC</Select.Option>
                        <Select.Option value="city_ledger_ESTVC">City Ledger - ESTVC</Select.Option>
                        <Select.Option value="city_ledger_DEV1">City Ledger - DEV1</Select.Option>
                        <Select.Option value="city_ledger_1BATAAN">City Ledger - 1BATAAN</Select.Option>
                        <Select.Option value="city_ledger_MAGIC_LEAF">City Ledger - MAGIC LEAF</Select.Option>
                        <Select.Option value="city_ledger_SLA">City Ledger - SLA</Select.Option>
                        <Select.Option value="city_ledger_ORION_SKY">City Ledger - Orion Sky</Select.Option>
                    </Select.OptGroup>
                </Select>
            </Form.Item>


            <Form.Item name="amount" min={1} label={modeOfPayment == 'voucher' ? 'Amount (disabled for voucher)' : 'Amount'} rules={[
                { required: modeOfPayment == 'voucher' ? false : true }
            ]}>
                <InputNumber
                    disabled={modeOfPayment == 'voucher'}
                    size="large"
                    min={1}
                    style={{width: 180}}
                    steps={0.1}
                    formatter={value => `₱ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                    parser={value => value.replace(/\₱\s?|(,*)/g, '')}
                />
            </Form.Item>
            {
                modeOfPayment == 'voucher' &&
                <Form.Item name="voucher" min={1} label="Voucher Code" rules={[
                    { required: modeOfPayment == 'voucher' ? true : false }
                ]}>
                    <Input
                        disabled={modeOfPayment != 'voucher'}
                        size="large"
                        style={{width: 180}}
                    />
                </Form.Item>
            }

            <Form.Item name="remarks" min={1} label="Remarks">
                <Input.TextArea style={{borderRadius: 12}}/>
            </Form.Item>

            <Button htmlType="submit">Save</Button>
        </Form>
    )

    const handleResendBookingConfirmation = (ref_no) => {

        if (resendBookingConfirmationQueryIsLoading) {
            message.info('Sending request to resend booking confirmation. Please wait...');
            return false;
        }

        resendBookingConfirmationQuery({booking_reference_number: ref_no }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Resending booking confirmation email success!');
                resendBookingConfirmationQueryReset();
            },
            onError: (e) => {
                // console.log(e);
                message.danger('Failed to resend booking confirmation.');
                resendBookingConfirmationQueryReset();
            }
        });
    }

    const invoiceMenu = ({id: invoice_id, balance}) => (
        <Menu>
            <Menu.Item disabled={balance <= 0} key="1" onClick={()=>showPaymentModal(invoice_id)}>New payment</Menu.Item>
            <Menu.Item className="nya" disabled={balance <= 0} key="2" onClick={()=>message.info("This feature will be here soon...")}>Mark as paid</Menu.Item>
            {/* <Menu.Item key="3" onClick={()=>handleApplyInvoiceDiscount(invoice_id)}>Apply invoice discount</Menu.Item> */}
            <Menu.Divider/>
            <Menu.Item className="nya" key="4" onClick={()=>message.info("This feature will be here soon...")}><SendOutlined/> Resend Invoice Email</Menu.Item>
            <Menu.Item className="nya" key="5" onClick={()=>message.info("This feature will be here soon...")}>Apply existing payment per payment ref # </Menu.Item>
        </Menu>
    )

    const viewBookingMenu = (booking_reference_number, mode_of_transportation = null) => {

        const handleDownloadBoardingPassOnePDF = (booking_reference_number) => {

            if (downloadBoardingPassOnePDFQueryIsLoading) {
                message.info("Download in progress...");
                return false;
            }

            downloadBoardingPassOnePDFQuery({
                booking_reference_number: booking_reference_number
            }, {
                onSuccess: (res) => {
                    const file = new Blob(
                        [res.data], 
                        {type: 'application/pdf'});
                    //Build a URL from the file
                    const fileURL = URL.createObjectURL(file);
                    //Download fileURL
                    var a = document.createElement("a");
                    a.href = fileURL;
                    a.download = `Boarding Pass - ${booking_reference_number}`;
                    a.click();
                    window.URL.revokeObjectURL(fileURL);

                    message.success("Download complete!");
                },
                onError: (e) => {
                    console.log(e);
                    downloadBoardingPassOnePDFQueryReset();
                }
            })
            // window.open(`${process.env.APP_URL}/api/booking/download-boarding-pass-one-pdf/${booking_reference_number}`);
        }

        const handleDownloadBookingConfirmation = (ref_no) => {

            if (downloadBookingConfirmationQueryIsLoading) {
                message.info("Download in progress...");
                return false;
            }

            downloadBookingConfirmationQuery({
                booking_reference_number: ref_no
            }, {
                onSuccess: (res) => {
                    const file = new Blob(
                        [res.data], 
                        {type: 'application/pdf'});
                    //Build a URL from the file
                    const fileURL = URL.createObjectURL(file);
                    //Download fileURL
                    var a = document.createElement("a");
                    a.href = fileURL;
                    a.download = `Booking Confirmation - ${ref_no}`;
                    a.click();
                    window.URL.revokeObjectURL(fileURL);

                    message.success("Download complete!");
                },
                onError: (e) => {
                    console.log(e);
                    downloadBookingConfirmationQueryReset();
                }
            })
        }
        
        return <Menu>
            <Menu.Item className="nya" key="1" onClick={()=>message.info("This feature will be here soon...")}>Rebook</Menu.Item>
            <Menu.Item key="2" onClick={()=>handleResendBookingConfirmation(booking_reference_number)}><SendOutlined/> Resend booking confirmation</Menu.Item>
            <Menu.Item key="3" onClick={()=>handleDownloadBookingConfirmation(booking_reference_number)}><DownloadOutlined/> Download booking confirmation</Menu.Item>
            { mode_of_transportation == 'camaya_transportation' ?
                <Menu.Item key="4" onClick={()=>handleDownloadBoardingPassOnePDF(booking_reference_number)}><DownloadOutlined/> Download Boarding Pass One PDF</Menu.Item>
                : ''
            }
            <Divider style={{margin: '8px 0px 8px 0px'}} />
            <Menu.Item key="5" onClick={()=>Modal.info({
                icon: '',
                width: 600,
                content: (<>
                        <Typography.Title level={4}>Copy Maya link</Typography.Title>
                        <Typography.Paragraph copyable>{`${process.env.APP_URL}/api/booking/public/payment/paymaya/request/${booking_reference_number}`}</Typography.Paragraph>
                    </>
                )
            })}><CopyOutlined/> Maya / GCash online payment link</Menu.Item>
            <Menu.Item key="6" onClick={()=>Modal.info({
                icon: '',
                width: 600,
                content: (<>
                        <Typography.Title level={4}>Copy PayPal link</Typography.Title>
                        <Typography.Paragraph copyable>{`${process.env.APP_URL}/api/booking/public/payment/paypal/request/${booking_reference_number}`}</Typography.Paragraph>
                    </>
                )
            })}><CopyOutlined/> PayPal online payment link</Menu.Item>
            <Menu.Item className="nya" key="7" onClick={()=>message.info("This feature will be here soon...")}><CopyOutlined/> DragonPay online payment link</Menu.Item>
        </Menu>
    }

    const addVehicleModal = (record) => {
        addVehicleForm.resetFields();
        setAddVehicleModalVisible(true);
    }

    const onAddVehicleFormFinish = (values) => {

        if (addVehicleQueryIsLoading) return false;

        addVehicleQuery({
            booking_reference_number: props.referenceNumber,
            model: values.model,
            plate_number: values.plate_number,
        }, {
            onSuccess: (res) => {
                message.success("Added vehicle successfully!");
                // console.log(res);
                addVehicleForm.resetFields();
                setAddVehicleModalVisible(false);
                viewBookingQuery.refetch();
            },
            onError: (e) => {
                console.log(e);
            }
        })

    }

    const editVehicleModal = (record) => {
        editVehicleForm.resetFields();
        editVehicleForm.setFieldsValue(record);
        setEditVehicleModalVisible(true);
        viewBookingQuery.refetch(record);
    }

    const onEditVehicleFormFinish = (values) => {
        if (updateVehicleQueryIsLoading) {
            message.warning('Saving in progress...');
            return false;
        }

        updateVehicleQuery(values, {
            onSuccess: (res) => {
                // console.log(res.data);
                message.success("Vehicle Details Updated!");
                
                if (res.data.mode_of_transportation === 'own_vehicle') {
                    const newData = [...viewBookingQuery.data.guest_vehicles];
                    const index = newData.findIndex(item => res.data.id === item.id);
                    const item = newData[index];
                    newData.splice(index, 1, {
                        ...item,
                        ...res.data,
                    });
                    viewBookingQuery.data.guest_vehicles = newData;
                }

                setEditVehicleModalVisible(false);
                viewBookingQuery.refetch(values);
                
                // notification.success({
                //     message: `Vehicle Details Updated!`,
                //     description:
                //         ``,
                // });

            },
        });
    }

    const handleBookingLabelChange = (value, ref_no) => {
        console.log(value, ref_no);

        if (updateBookingLabelQueryIsLoading) return false;

        updateBookingLabelQuery({
            booking_reference_number: ref_no,
            value: value,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success("Change booking label successful!");
                queryCache.setQueryData(['view-booking', res.data.reference_number], prev => {
                    return {
                        ...prev,
                        label: res.data.label
                    }
                });
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handleRemarksChange = (value, ref_no) => {
        // console.log(value, ref_no);

        if (updateRemarksQueryIsLoading) return false;

        updateRemarksQuery({
            booking_reference_number: ref_no,
            value: value,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success("Change booking remarks successful!");
                queryCache.setQueryData(['view-booking', res.data.reference_number], prev => {
                    return {
                        ...prev,
                        remarks: res.data.remarks
                    }
                });
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handleBillingInstructionChange = (value, ref_no) => {
        // console.log(value, ref_no);

        if (updateBillingInstructionsQueryIsLoading) return false;

        updateBillingInstructionsQuery({
            booking_reference_number: ref_no,
            value: value,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success("Change booking billing instructions successful!");
                queryCache.setQueryData(['view-booking', res.data.reference_number], prev => {
                    return {
                        ...prev,
                        billing_instructions: res.data.billing_instructions
                    }
                });
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const viewGuestModal = (record) => {
        viewGuestForm.resetFields();
        viewGuestForm.setFieldsValue(record);
        setViewGuestModalVisible(true);
    }

    const onViewGuestFormFinish = (values) => {
        if (updateGuestQueryIsLoading) {
            message.warning('Saving in progress...');
            return false;
        }

        updateGuestQuery(values, {
            onSuccess: (res) => {
                // console.log(res.data);

                if (res.data.type === 'adult') {
                    const newData = [...viewBookingQuery.data.adult_guests];
                    const index = newData.findIndex(item => res.data.id === item.id);
                    const item = newData[index];
                    newData.splice(index, 1, {
                        ...item,
                        ...res.data,
                    });

                    viewBookingQuery.data.adult_guests = newData;
                } else if (res.data.type === 'kid') {
                    const newData = [...viewBookingQuery.data.kid_guests];
                    const index = newData.findIndex(item => res.data.id === item.id);
                    const item = newData[index];
                    newData.splice(index, 1, {
                        ...item,
                        ...res.data,
                    });

                    viewBookingQuery.data.kid_guests = newData;
                } else if (res.data.type === 'infant') {
                    const newData = [...viewBookingQuery.data.infant_guests];
                    const index = newData.findIndex(item => res.data.id === item.id);
                    const item = newData[index];
                    newData.splice(index, 1, {
                        ...item,
                        ...res.data,
                    });

                    viewBookingQuery.data.infant_guests = newData;
                }

                setViewGuestModalVisible(false);

                notification.success({
                    message: `Guest Updated!`,
                    description:
                        ``,
                });
            },
            onError: (e) => {
                message.error(e.error);
                updateGuestQueryReset();
            }
        });
    }

    /**
     * Change invoice discount
     */
    const handleInvoiceDiscountChange = (id, amount, record) => {
        // console.log(refno, amount);

        let _amount = amount;

        if (_amount < 0.00) {
            message.error("Invalid amount");
            return false;
        }

        if (amount > parseFloat(record.total_cost)) {
            _amount = parseFloat(record.total_cost);
        }

        if (updateInvoiceDiscountQueryIsLoading) return false;

        function isInt(n){
            return Number(n) === n && n % 1 === 0;
        }

        function isFloat(n){
            return Number(n) === n && n % 1 !== 0;
        }

        if (isInt(_amount) || isFloat(_amount)) {
            // console.log(rate);
            updateInvoiceDiscountQuery({
                id: id,
                amount: _amount,
            }, {
                onSuccess: (res) => {
                    // console.log(res);
                    // viewBookingQuery.refetch();
                    const newData = res.data;
                    const prev = queryCache.getQueryData(['view-booking', res.data.reference_number ]);
                    queryCache.setQueryData(['view-booking', res.data.reference_number ], {
                        ...prev,
                        ...newData
                    });

                    message.success("Updated invoice discount!");
                },
                onError: (e) => {
                    console.log(e);
                }
            })
        }
    }

    const handleInclusionDiscountChange = (id, amount, record) => {
        console.log(id, amount);

        let _amount = amount;

        if (_amount < 0.00) {
            message.error("Invalid amount");
            return false;
        }

        if (amount > (parseFloat(record.price) * parseInt(record.quantity))) {
            _amount = parseFloat(record.price) * parseInt(record.quantity);
        }

        if (updateInclusionDiscountQueryIsLoading) return false;

        function isInt(n){
            return Number(n) === n && n % 1 === 0;
        }

        function isFloat(n){
            return Number(n) === n && n % 1 !== 0;
        }

        if (isInt(_amount) || isFloat(_amount)) {
            // console.log(rate);
            updateInclusionDiscountQuery({
                id: id,
                amount: _amount,
            }, {
                onSuccess: (res) => {
                    // console.log(res);
                    // viewBookingQuery.refetch();

                    const newData = res.data;
                    const prev = queryCache.getQueryData(['view-booking', res.data.reference_number ]);
                    queryCache.setQueryData(['view-booking', res.data.reference_number ], {
                        ...prev,
                        ...newData
                    });

                    // console.log(queryCache.getQueryData(['view-booking', res.data.reference_number ]));

                    message.success("Updated inclusion discount!");
                },
                onError: (e) => {
                    console.log(e);
                    message.error(e.error);
                }
            })
        }
    }


    /**
     * Change guest status
     */
    const handleChangeGuestStatus = (id, status) => {
        console.log(id, status);

        if (updateGuestStatusQueryIsLoading) return false;

        updateGuestStatusQuery({
            guest_id:id,
            guest_status:status
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Updated guest status!');
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    /**
     * New note
     */
    const onNewNoteFormFinish = (values) => {
        console.log(values);

        if (newNoteQueryIsLoading) {
            return false;
        }

        newNoteQuery({
            booking_reference_number: viewBookingQuery.data.reference_number,
            message: values.message,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                queryCache.setQueryData(['view-booking', res.data.booking_reference_number], prev => {
                    return {
                        ...prev,
                        notes: [
                            res.data,
                            ...prev.notes,
                        ]
                    }
                });
                message.success("Added new note successful!");
                setaddNoteDrawerVisible(false);
                newNoteForm.resetFields();
                newNoteQueryReset();
            },
            onError: (e) => {
                console.log(e);
                newNoteQueryReset();
            }
        });
    }

    const addGuestOnFinish = (values) => {

        if (addGuestQueryIsLoading) return false;

        addGuestQuery({
            booking_reference_number: props.referenceNumber,
            first_name: values.first_name,
            last_name: values.last_name,
            age: values.age,
            nationality: values.nationality,
            addGuestInclusions: addGuestInclusions,
            addGuestWalkin: addGuestWalkin,
        }, {
            onSuccess: (res) => {
                message.success("Added guest successfully!");
                // console.log(res);
                addGuestForm.resetFields();
                setaddGuestModalVisible(false);
                setaddGuestInclusions([]);
                setaddGuestWalkin([]);
                viewBookingQuery.refetch();
            },
            onError: (e) => {
                console.log(e);
                message.info(e.message);
            }
        })

    }

    /**
     * Add inclusion
     */
    const handleAddInclusionClick = () =>{
        // console.log(props.referenceNumber);

        setAddInclusionModalVisible(true);
    }

    const handleAddRoomClick = () => {
        setAddRoomModalVisible(true);
    }

    /**
     * Remove inclusion
     */

    const handleRemoveInclusion = (id) => {
        // console.log(id);

        if (removeInclusionQueryIsLoading) return false;

        removeInclusionQuery({
            id:id
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success("Inclusion removed!");

                viewBookingQuery.refetch();
            },
            onError: (e) => {
                console.log(e);
            }
        });

    }

    /**
     * Update additional emails
     */
    const handleAdditionalEmailChange = (refno, emails) => {

        if (updateAdditionalEmailsQueryIsLoading) return false;

        console.log(refno, emails);
        const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        _.remove(emails, e => !re.test(e));

        updateAdditionalEmailsQuery({
            booking_reference_number: refno,
            emails: emails
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success("Updated additional emails!");
            },
            onError: (e) => {
                console.log(e);
                message.error(e.error);
            }
        })
    }

    /**
     * Update booking tags
     */
    const handleBookingTagsChange = (refno, tags) => {
        // console.log(refno, tags);

        if (updateBookingTagsQueryIsLoading) return false;

        updateBookingTagsQuery({
            booking_reference_number: refno,
            tags: tags
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success("Updated booking tags!");
            },
            onError: (e) => {
                console.log(e);
                message.error(e.error);
            }
        })
    }

    /**
     * Handle void payment click
     */
    const handleVoidPaymentClick = (id) => {
        // console.log(id);
        Modal.confirm({
            title: 'Are you sure?',
            content: (
                <>Void payment</>
            ),
            onOk: (close) => {

                if (voidPaymentQueryIsLoading) {
                    return false;
                }

                voidPaymentQuery({
                    id: id
                }, {
                    onSuccess: (res) => {
                        message.success('Payment voided');
                        voidPaymentQueryReset();
                        viewBookingQuery.refetch();
                    },
                    onError: (e) => {
                        message.error(e.error);
                        voidPaymentQueryReset();
                    }
                });

                close();
            },
            onCancel: (close) => {
                close();
            }
        })
    }

    /**
     * handleDeleteGuest
     */
    const handleDeleteGuest = (guest_id) => {
        console.log(guest_id);

        if (deleteGuestQueryIsLoading) return false;

        deleteGuestQuery({
            guest_id: guest_id
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success("Guest deleted.");
                viewGuestForm.resetFields();
                viewBookingQuery.refetch();
                setViewGuestModalVisible(false);
            },
            onError: (e) => {
                message.error(e.error);
            }
        })
    }

    const handleDeleteVehicle = (booking_reference_number) => {
        console.log(booking_reference_number);

        if (deleteVehicleQueryIsLoading) return false;

        deleteVehicleQuery({
            booking_reference_number: booking_reference_number
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success("Vehicle deleted.");
                editVehicleForm.resetFields();
                viewVehicleQuery.refetch();
                setEditVehicleModalVisible(false);
            },
            onError: (e) => {
                message.error(e.error);
            }
        })
    }

    const handleGuestQRClick = (record) => {
        // setViewGuestPasses([]);

        setViewGuestPassModalVisible(true);
        setViewGuestPasses(record);
    }

    const handlePrimaryCustomerChange = (e) => {
        // console.log(e);

        if (updatePrimaryCustomerIsLoading) {
            return false;
        }

        updatePrimaryCustomer({
            booking_reference_number: props.referenceNumber,
            primary_customer_id: e,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Updated primary customer to: '+ res.data.email);
                setChangePrimaryCustomerModalOpen(false);
                viewBookingQuery.refetch();
            },
            onError: (e) => {
                message.danger(e.message);
            }
        })
    }

    /**
     * Auto cancel date update
     */
    const handleAutoCancelDateChange = (e) => {
        console.log(e);

        const date = e ? e : null;

        updateAutoCancelDate({
            booking_reference_number: props.referenceNumber,
            date: date,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Updated auto-cancel date to: '+ (date ? date.format('YYYY-MM-DD H:mm:ss A') : 'none'));
            },
            onError: (e) => {
                message.danger(e.message);
            }
        })
    }


    const handleModeOfTranspoChange = (e, prev) => {
        // console.log(e, prev);

        if (e === 'camaya_transportation') {
            // console.log("Ferry");
            setAddFerryModalVisible(true);
        }

        if (prev === 'camaya_transportation' && e !== 'camaya_transportation') {
            // Ask if they want to cancel the ferry booking
            Modal.confirm({
                icon: <InfoCircleOutlined className="text-danger" />,
                title: 'Are you sure you want to change the mode of transportation to "'+e+'"?',
                content: <>
                            This will remove/cancel the ferry of the guest(s).&nbsp;
                            <span className="text-danger">Not yet implemented.</span>
                        </>,
                onOk: (close) => close(),
                onCancel: (close) => {
                    close();


                },
            });
        }
    }

    // Handle Add Guest Inclusion Change
    const handleAddGuestInclusionChange = (checked, code, price) => {
        // console.log(checked, code, price);

        setaddGuestInclusions( prev => {

            prev = prev.filter(i => i.code != code);

            return [...prev, {
                code: code,
                price: price,
                checked: checked
            }];
        })
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
                viewBookingQuery.refetch();
            },
            onError: (e) => message.danger(e.error)
        });
    }

    return (
        <div className="fadeIn">
            <div className="mb-5">
                <Toolbar />

                <Modal
                    title="New Payment"
                    visible={paymentModalVisible}
                    onCancel={() => setPaymentModalVisible(false)}
                    footer={null}
                    width={500}
                >
                    <PaymewntModalContent/>
                </Modal>

                <Modal
                    title="Change Primary Customer"
                    visible={changePrimaryCustomerModalOpen}
                    onCancel={() => setChangePrimaryCustomerModalOpen(false)}
                    footer={null}
                    width={900}
                >
                    <Input value={searchCustomer} style={{width: '100%', marginBottom: 8}} type="search" placeholder="Search customer name or email here" onChange={ (e) => setSearchCustomer(e.target.value) } />
                    <Button block size="small" className="mb-4" onClick={() => handleSearchCustomer(searchCustomer)}>Search</Button>

                    
                    {
                        customerListQuery2IsLoading ?
                            <>Searching customer. Please wait...</>
                        :
                            customers &&
                            <>
                                <h4>Search result ({customers.length}):</h4>
                                {
                                    customers.map( (item, key) => (
                                        <React.Fragment key={key}>
                                            <div>{`[${item.user_type || 'guest'}] ${item.first_name} ${item.last_name} ${item.email}`}<Button disabled={updatePrimaryCustomerIsLoading} loading={updatePrimaryCustomerIsLoading} className="mb-2" style={{float: 'right'}} size="small" onClick={() => handlePrimaryCustomerChange(item.id)}>Select</Button></div>
                                            <hr style={{border: 'none', borderTop: 'solid 1px gainsboro'}} />
                                        </React.Fragment>
                                    ))
                                }
                            </>
                    }
                </Modal>

                <Modal
                    title="Add Ferry"
                    visible={addFerryModalVisible}
                    onCancel={() => setAddFerryModalVisible(false)}
                    footer={null}
                    width="100%"
                >
                    <AddFerryModalContent booking={viewBookingQuery.data} setAddFerryModalVisible={setAddFerryModalVisible} refreshViewBooking={() => viewBookingQuery.refetch()}/>
                </Modal>

                {
                addFerryToGuestsModalVisible &&
                    <Modal
                        title="Add ferry to guest(s)"
                        visible={addFerryToGuestsModalVisible}
                        onCancel={() => setAddFerryToGuestsModalVisible(false)}
                        footer={null}
                        width="100%"
                    >
                        <AddFerryToGuestsComponent booking={viewBookingQuery.data} setAddFerryToGuestsModalVisible={setAddFerryToGuestsModalVisible} refreshViewBooking={() => viewBookingQuery.refetch()}/>
                    </Modal>
                }

                <Modal
                    title="View logs"
                    visible={viewLogsModalVisible}
                    onCancel={() => setviewLogsModalVisible(false)}
                    footer={null}
                    width="100%"
                >
                    <ViewLogModalContent booking={viewBookingQuery.data} setviewLogsModalVisible={setviewLogsModalVisible}/>
                </Modal>

                <Modal
                    title="Add inclusion"
                    visible={addInclusionModalVisible}
                    onCancel={() => setAddInclusionModalVisible(false)}
                    footer={null}
                    width="100%"
                >
                    <AddInclusionModalContent booking={viewBookingQuery.data} setAddInclusionModalVisible={setAddInclusionModalVisible}/>
                </Modal>

                { addRoomModalVisible &&
                    <Modal
                        title="Add room"
                        visible={addRoomModalVisible}
                        onCancel={() => setAddRoomModalVisible(false)}
                        footer={null}
                        width="900px"
                        forceRender={true}
                    >
                        <AddRoomModalContent viewBookingQuery={viewBookingQuery} booking={viewBookingQuery.data} setAddRoomModalVisible={setAddRoomModalVisible}/>
                    </Modal>
                }
                { viewGuestPassModalVisible &&
                    <Modal
                        title="View Guest Passes"
                        visible={viewGuestPassModalVisible}
                        onCancel={() => { setViewGuestPassModalVisible(false); }}
                        footer={null}
                        width={1000}
                        >
                            <ViewGuestPassModalContent record={viewGuestPasses} />
                    </Modal>
                }
                <Modal
                    title="Add guest"
                    visible={addGuestModalVisible}
                    onCancel={() => setaddGuestModalVisible(false)}
                    footer={null}
                    >
                        <Form
                            layout="vertical"
                            form={addGuestForm}
                            footer={null}
                            onFinish={addGuestOnFinish}
                        >
                            <Row>
                                <Col xl={24}>
                                    <Form.Item name="first_name" label="First name" rules={[{ required: true }]}>
                                        <Input />
                                    </Form.Item>
                                </Col>
                                <Col xl={24}>
                                    <Form.Item name="last_name" label="Last name" rules={[{ required: true }]}>
                                        <Input />
                                    </Form.Item>
                                </Col>
                                <Col xl={12}>
                                    <Form.Item name="age" label="Age" rules={[{ required: true }]}>
                                        <InputNumber />
                                    </Form.Item>
                                </Col>
                                <Col xl={12}>
                                    <Form.Item name="nationality" label="Nationality">
                                        <Input />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <div>
                                <Typography.Title level={5}>Guest Inclusion</Typography.Title>
                                <Alert message={<><strong>Notice!</strong> Day tour guest is recommended to have a DTT inclusion.</>} type="info" />

                                <Form.Item name="walkin" valuePropName="checked" label="Walk-in?" className="mt-4">
                                    <Switch checked={addGuestWalkin} onChange={e => setaddGuestWalkin(e)} />
                                </Form.Item>

                                { viewBookingQuery.data &&
                                    <List
                                        header={<strong>Per Guest Products</strong>}
                                        footer={<div style={{textAlign:'right'}}>Total: <span className="text-success">&#8369; {_.sumBy(addGuestInclusions, (i) => i.checked == true && parseFloat(i.price))}</span></div>}
                                        bordered
                                        size="small"
                                        pagination={
                                            {
                                                defaultPageSize: 25,

                                            }
                                        }
                                        dataSource={productListQuery.data &&
                                            productListQuery.data
                                                .filter( item => item.type == 'per_guest')
                                                .filter( item => {
                                                    if (item.availability == 'for_dtt' && viewBookingQuery.data.type == 'DT') {
                                                        return true;
                                                    } else if (item.availability == 'for_overnight' && viewBookingQuery.data.type == 'ON') {
                                                        return true;
                                                    } else if (item.availability == 'for_dtt_and_overnight') {
                                                        return true;
                                                    } else {
                                                        return false;
                                                    }
                                                }
                                            )
                                        }
                                        renderItem={item => (
                                            <List.Item>
                                                <div style={{display: 'flex', justifyContent:'space-between', width:'100%'}}>
                                                    <div><Checkbox checked={_.find(addGuestInclusions, i => i.code == item.code) ? _.find(addGuestInclusions, i => i.code == item.code).checked : false} onChange={e => handleAddGuestInclusionChange(e.target.checked, item.code, addGuestWalkin ? (item.walkin_price ? item.walkin_price : item.price) : item.price)} className="mr-2" />{item.code} - {item.name}</div>
                                                    <Space>
                                                        <div className="text-success">&#8369; {addGuestWalkin ? (item.walkin_price ? item.walkin_price : item.price) : item.price}</div>
                                                    </Space>
                                                </div>
                                            </List.Item>
                                        )}
                                    />
                                }
                            </div>

                            <Button htmlType="submit" block className="mt-4">Add guest</Button>

                        </Form>
                    </Modal>

                <Modal
                    visible={viewPaymentsModalVisible}
                    onCancel={()=>setviewPaymentsModalVisible(false)}
                    width={1000}
                    footer={null}
                >
                    <Typography.Title level={4} className="mb-4">Invoices &amp; Payments</Typography.Title>

                    <Typography.Title level={5}>Total Balance: <span className="text-success">&#8369; {numberWithCommas(_.sumBy(viewBookingQuery.data && viewBookingQuery.data.invoices, item => Number(item.balance || 0)).toFixed(2))}</span></Typography.Title>
                    <div style={{float:'right', width: 500, wordWrap:'break-word'}}><b>Billing Instructions: </b>{viewBookingQuery.data?.billing_instructions ?? ''}</div>

                    <div className="mt-4 mb-4">
                        <span className="mr-2">Status filter:</span>
                        {tagsStatusFilterData.map(tag => (
                            <Tag.CheckableTag
                                key={tag}
                                checked={selectedStatusFilterTags.indexOf(tag) > -1}
                                onChange={checked => handleStatusFilterTagsChange(tag, checked)}
                                color={invoiceStatusColor[tag]}
                                style={{textTransform:'capitalize'}}
                            >
                                {tag == 'sent' ? 'unpaid' : tag}
                            </Tag.CheckableTag>
                        ))}
                    </div>


                {
                    !viewBookingQuery.isLoading ? viewBookingQuery.data.invoices.filter((item) => _.includes(selectedStatusFilterTags, item.status)).map( (item, key) => {

                        const activeTab = _.find(activeTabKey, { id: item.id }) || { id: item.id, key: 'invoice' };

                        return (
                            <Card
                                key={key}
                                hoverable={true}
                                bordered={false}
                                className={`card-shadow mb-2`}
                                size="small"
                                tabList={tabList(item)}
                                tabBarExtraContent={
                                    <div style={{display:'flex', alignItems: 'center'}}>
                                        <div><Tag color={invoiceStatusColor[item.status]} style={{textTransform:'capitalize'}}>{item.status == 'sent' ? 'unpaid' : item.status}</Tag> {item.paid_at ? '- '+item.paid_at : ''}</div>
                                        <Dropdown className="ml-1 p-2" overlay={invoiceMenu(item)}>
                                            <Button><EllipsisOutlined/></Button>
                                        </Dropdown>
                                    </div>
                                }
                                activeTabKey={activeTab.key}
                                onTabChange={key => {
                                    setactiveTabKey( prev => [..._.filter(prev, i => i.id != item.id), {id: item.id, key: key}]);
                                }}
                            >
                                { activeTab.key == 'invoice' &&
                                    <div>
                                        <Row gutter={[32, 32]} className="m-0">
                                            <Col xl={6}>
                                                {item.reference_number}-{item.batch_number}
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Invoice #</small></div>
                                            </Col>
                                            <Col xl={5}>
                                                {/* {item.due_datetime || '-no due date-'} */}
                                                <DatePicker showTime defaultValue={item.due_datetime} />
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary"><CalendarOutlined/> Due date</small></div>
                                            </Col>
                                            <Col xl={3}>
                                                <span className="text-success">&#8369;
                                                    {numberWithCommas(item.discount)} ({item.total_cost > 0 ? (item.discount/item.total_cost*100).toFixed(2):0}%)
                                                    {/* <InputNumber onChange={(e)=>handleInvoiceDiscountChange(item.id, e)} min={0} max={item.total_cost} defaultValue={item.discount || 0} /> */}
                                                    &#8369; <Typography.Text editable={{ onChange: (e) => handleInvoiceDiscountChange(item.id, parseFloat(e), item) }}>{updateInvoiceDiscountQueryIsLoading ? 'saving..' : item.discount}</Typography.Text>
                                                </span>
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Discount</small></div>
                                            </Col>
                                            <Col xl={3}>
                                                <span className="text-success">&#8369; {numberWithCommas(item.grand_total)}</span>
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Grand total</small></div>
                                            </Col>
                                            <Col xl={4}>
                                                <span className="text-success">&#8369; {numberWithCommas(item.total_payment)}</span>
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Total payment</small></div>
                                            </Col>
                                            <Col xl={3}>
                                                <span className="text-success">&#8369; {numberWithCommas(item.balance)}</span>
                                                <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Balance</small></div>
                                            </Col>
                                        </Row>
                                    </div>
                                }

                                { activeTab.key == 'inclusions' &&
                                    <div>
                                        <Table
                                            loading={viewBookingQuery.isFetching}
                                            rowKey="id"
                                            size="small"
                                            dataSource={item.inclusions}
                                            scroll={{x: true}}
                                            expandable={{
                                                expandedRowRender: record =>
                                                    <Table
                                                        rowKey="id"
                                                        dataSource={record.package_inclusions}
                                                        size="small"
                                                        columns={
                                                            [
                                                                {
                                                                title: 'Item',
                                                                dataIndex: 'item',
                                                                key: 'item_inclusion',
                                                                },
                                                                {
                                                                    title: 'Guest',
                                                                    dataIndex: 'guest',
                                                                    key: 'item',
                                                                    render: (text, record) => (
                                                                        <>{ record.guest_id && record.guest_inclusion.first_name+' '+record.guest_inclusion.last_name}</>
                                                                    )
                                                                },
                                                                {
                                                                    title: 'Type',
                                                                    dataIndex: 'type',
                                                                    key: 'type',
                                                                },
                                                                {
                                                                    title: 'Quantity',
                                                                    dataIndex: 'quantity',
                                                                    key: 'quantity',
                                                                },
                                                                {
                                                                    title: 'Original price',
                                                                    dataIndex: 'original_price',
                                                                    key: 'original_price',
                                                                    render: (text, record) => {
                                                                        return <span className="text-success">&#8369; {numberWithCommas(text)}</span>
                                                                    }
                                                                },
                                                                {
                                                                    title: 'Price',
                                                                    dataIndex: 'price',
                                                                    key: 'price',
                                                                    render: (text, record) => {
                                                                        return <span className="text-success">&#8369; {numberWithCommas(text)}</span>
                                                                    }
                                                                },
                                                                {
                                                                    title: 'Total price',
                                                                    dataIndex: 'total_price',
                                                                    render: (text, record) => {
                                                                        return <span className="text-success">&#8369; {numberWithCommas(parseFloat(record.price) * parseInt(record.quantity))}</span>
                                                                    }
                                                                },
                                                            ]
                                                        }
                                                    />,
                                                rowExpandable: record => record.package_inclusions.length !== 0,
                                            }}
                                            columns={
                                             [
                                                {
                                                  title: 'Item',
                                                  dataIndex: 'item',
                                                  key: 'item',
                                                    render: (text, record) => <span style={{textDecoration: !record.deleted_at ? '' : 'line-through'}}>{text}</span>
                                                },
                                                {
                                                    title: 'Guest',
                                                    dataIndex: 'guest',
                                                    key: 'item',
                                                    render: (text, record) => (
                                                        <span style={{textDecoration: !record.deleted_at ? '' : 'line-through'}}>{ record.guest_id && record.guest_inclusion.first_name+' '+record.guest_inclusion.last_name}</span>
                                                    )
                                                },
                                                {
                                                    title: 'Type',
                                                    dataIndex: 'type',
                                                    key: 'type',
                                                    render: (text, record) => <span style={{textDecoration: !record.deleted_at ? '' : 'line-through'}}>{text}</span>
                                                },
                                                {
                                                    title: 'Quantity',
                                                    dataIndex: 'quantity',
                                                    key: 'quantity',
                                                    render: (text, record) => <span style={{textDecoration: !record.deleted_at ? '' : 'line-through'}}>{text}</span>
                                                },
                                                {
                                                    title: 'Price',
                                                    dataIndex: 'price',
                                                    key: 'price',
                                                    render: (text, record) => {
                                                        return <span className="text-success" style={{textDecoration: !record.deleted_at ? '' : 'line-through'}}>&#8369; {numberWithCommas(text)}</span>
                                                    }
                                                },
                                                {
                                                    title: 'Discount',
                                                    dataIndex: 'discount',
                                                    key: 'discount',
                                                    render: (text, record) => {
                                                        return <>
                                                            <span className="text-success" style={{textDecoration: !record.deleted_at ? '' : 'line-through'}}>
                                                                {/* &#8369; {numberWithCommas(text || 0)} */}
                                                                {/* <InputNumber disabled={record.deleted_at} onChange={(e)=>handleInclusionDiscountChange(record.id, e)} min={0} max={parseFloat(record.price) * parseInt(record.quantity)} defaultValue={record.discount || 0} /> */}
                                                                &#8369; <Typography.Text editable={{ onChange: (e) => handleInclusionDiscountChange(record.id, parseFloat(e), record) }}>{updateInclusionDiscountQueryIsLoading ? 'saving..' : record.discount}</Typography.Text>
                                                            </span>
                                                        </>
                                                    }
                                                },
                                                {
                                                    title: 'Total price',
                                                    dataIndex: 'total_price',
                                                    render: (text, record) => {
                                                        return <span style={{textDecoration: !record.deleted_at ? '' : 'line-through'}} className="text-success">&#8369; {numberWithCommas((parseFloat(record.price) * parseInt(record.quantity)) - parseFloat(record.discount || 0))}</span>
                                                    }
                                                },
                                                {
                                                    title: 'Action',
                                                    render: (text, record) =>
                                                    <>
                                                        { !record.deleted_at ?
                                                            <Popconfirm
                                                                title="Are you sure to delete this inclusion?"
                                                                onConfirm={()=>handleRemoveInclusion(record.id)}
                                                                okText="Yes"
                                                                cancelText="No"
                                                            >
                                                                <Button size="small" type="danger" icon={<DeleteOutlined />}/>
                                                            </Popconfirm>
                                                            : <Tag>deleted by {record.deleted_by_user.first_name}</Tag>
                                                        }
                                                    </>
                                                },
                                            ]
                                            }
                                            // footer={(currentPageData) => {
                                            //     // console.log(currentPageData);
                                            //     return (
                                            //         <div style={{textAlign:'right'}}>Total: <span className="text-success">&#8369; {numberWithCommas(0)}</span></div>
                                            //     )
                                            // }}
                                        />
                                    </div>
                                }

                                { activeTab.key == 'payments' &&
                                    <div>
                                        <Table
                                            rowKey="id"
                                            size="small"
                                            dataSource={item.payments}
                                            scroll={{x: 1000}}
                                            columns={
                                             [
                                                {
                                                  title: 'Payment reference #',
                                                  dataIndex: 'payment_reference_number',
                                                  key: 'payment_reference_number',
                                                },
                                                {
                                                    title: 'Paid at',
                                                    dataIndex: 'paid_at',
                                                    key: 'paid_at',
                                                    render: (text, record) => record.paid_at ? moment(record.paid_at).format('YYYY-MM-DD HH:mm:ss') : ''
                                                },
                                                {
                                                    title: 'Amount',
                                                    dataIndex: 'amount',
                                                    key: 'amount',
                                                    render: (text, record) => <span className="text-success">&#8369; {numberWithCommas(text)}</span>
                                                },
                                                {
                                                    title: 'Mode of payment',
                                                    dataIndex: 'mode_of_payment',
                                                    key: 'mode_of_payment',
                                                },
                                                {
                                                    title: 'Status',
                                                    dataIndex: 'status',
                                                    key: 'status',
                                                },
                                                {
                                                    title: 'Provider',
                                                    dataIndex: 'provider',
                                                    key: 'provider',
                                                },
                                                {
                                                    title: 'Provider reference #',
                                                    dataIndex: 'provider_reference_number',
                                                    key: 'provider_reference_number',
                                                },
                                                {
                                                    title: 'Action',
                                                    render: (text, record) => <>
                                                        <Dropdown overlay={
                                                            <Menu>
                                                                <Menu.Item>
                                                                    <a target="_blank" className="nya">
                                                                        Set payment to refunded status
                                                                    </a>
                                                                </Menu.Item>
                                                                <Menu.Item disabled={record.status == 'voided'} danger onClick={()=>handleVoidPaymentClick(record.id)}>Void payment</Menu.Item>
                                                            </Menu>
                                                        }>
                                                            <Button icon={<EllipsisOutlined/>} />
                                                        </Dropdown>
                                                    </>
                                                },
                                            ]
                                            }
                                            footer={(currentPageData) => {
                                                // console.log(currentPageData);
                                                // return (
                                                //     <div style={{textAlign:'right'}}>Total: <span className="text-success">&#8369; {item.grand_total}</span></div>
                                                // )
                                            }}
                                        />
                                    </div>
                                }
                            </Card>
                        )
                    })
                    : <Loading isHeightFull={false}/>
                }
                </Modal>
                <Modal
                    title={<Typography.Title level={4}>Attachments</Typography.Title>}
                    visible={viewAttachmentsModalVisible}
                    onCancel={()=>setviewAttachmentsModalVisible(false)}
                    width={800}
                    footer={null}
                >
                    <Upload
                        action={`${process.env.APP_URL}/api/booking/add-attachment`}
                        showUploadList={{
                            showRemoveIcon: false
                        }}
                        data={(file) => {
                            return {
                                booking_reference_number: props.referenceNumber,
                                file_type: file.type,
                                file_size: file.size,
                                file_name: file.name,
                            }
                        }}
                        headers={
                            {
                                Authorization: `Bearer ${localStorage.getItem('token')}`,
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        }
                        onChange={({file}) => {
                            if (file.status !== 'uploading') {
                                // viewBookingQuery.data.attachments.push(file.response);
                                if (file.status !== 'removed') {
                                    queryCache.setQueryData(['view-booking', file.response.booking_reference_number], (prev) => {
                                        // console.log(prev);
                                        return {...prev, attachments: [{...file.response, justAdded: true}, ...prev.attachments]}
                                    })
                                }
                            }
                        }}
                        listType="picture"
                    >
                        <Button icon={<UploadOutlined/>}>Add Attachment</Button>
                    </Upload>

                    <div className="mt-4">
                        <Table
                            dataSource={viewBookingQuery.data && viewBookingQuery.data.attachments}
                            loading={viewBookingQuery.isLoading}
                            size="small"
                            rowKey="id"
                            columns={[
                                {
                                    title: 'File',
                                    dataIndex: 'file',
                                    key: 'file',
                                    render: (text, record) => {
                                        return <a href={record.file_path} target="_blank">{ _.includes(['image/png', 'image/jpeg'], record.content_type) ? <img style={{width: 25}} src={record.file_path} /> : <FileOutlined/> }</a>
                                    }
                                },
                                {
                                    title: 'File name',
                                    dataIndex: 'file_name',
                                    key: 'file_name',
                                    render: (text, record) => {
                                        return <a href={record.file_path} style={record.justAdded ? {fontWeight: 'bold'} : {}} target="_blank">{record.file_name}{record.justAdded && <Tag color="blue" className="ml-2">new</Tag> }</a>
                                    }
                                },
                                {
                                    title: 'File size',
                                    dataIndex: 'file_size',
                                    key: 'file_size',
                                    render: (text, record) => {
                                        return <small>{_.round(text / Math.pow(1024,1))} KB</small>
                                    }
                                },
                                {
                                    title: 'Uploaded at',
                                    dataIndex: 'created_at',
                                    key: 'created_at',
                                    render: (text, record) => {
                                        return <Tooltip title={moment(record.created_at).format('YYYY-MM-DD HH:mm:ss')}><small>{moment(record.created_at).fromNow()}</small></Tooltip>
                                    }
                                },
                                {
                                    title: 'Uploaded by',
                                    dataIndex: 'uploader',
                                    key: 'uploader',
                                    render: (text, record) => {
                                        return <>{record.uploader.first_name} {record.uploader.last_name}</>
                                    }
                                },
                            ]}
                        />
                    </div>
                </Modal>
            </div>

            <Form
                layout="vertical"
                onFinish={e => onFinish(e)}
                form={viewBookingForm}
                autoComplete="off"
                // initialValues={}
            >

                <Row gutter={[48,48]}>
                    <Col xl={24} xs={24}>
                        {!viewBookingQuery.isLoading &&
                            <Descriptions
                                    bordered
                                    title="Booking Details"
                                    size="small"
                                    // extra={<Button type="primary">Edit</Button>}
                                    column={3}
                                >
                                <Descriptions.Item label="Customer">
                                    {/* <Card size="small" className="mt-2"> */}
                                        {/* Contact number: {(customerListQuery.data && _.find(customerListQuery.data, i => i.id == viewBookingQuery.data.customer_id)) && _.find(customerListQuery.data, i => i.id == viewBookingQuery.data.customer_id)['contact_number']}<br/> */}

                                        {/* Address: {_.find(customerListQuery.data, i => i.id == viewBookingQuery.data.customer_id)['address']}<br/> */}

                                        {/* Address: {(customerListQuery.data && _.find(customerListQuery.data, i => i.id == viewBookingQuery.data.customer_id)) && _.find(customerListQuery.data, i => i.id == viewBookingQuery.data.customer_id)['address']} */}

                                        <strong><UserOutlined/> {viewBookingQuery.data.customer.first_name} {viewBookingQuery.data.customer.last_name} {viewBookingQuery.data.customer.user_type}</strong>
                                        <div className="mb-2">
                                            <Button size="small" onClick={()=>setChangePrimaryCustomerModalOpen(true)}>Change primary customer</Button>
                                        </div>
                                        Email: {(viewBookingQuery.data.customer && viewBookingQuery.data.customer.email)}
                                        <br/>
                                        Contact number: {(viewBookingQuery.data.customer && viewBookingQuery.data.customer.contact_number)}
                                        <br/>
                                        Address: {(viewBookingQuery.data.customer && viewBookingQuery.data.customer.address)}
                                    {/* </Card> */}
                                </Descriptions.Item>
                                <Descriptions.Item label="Date of visit">                                
                                    { viewBookingQuery.data.type === 'DT' && viewBookingQuery.data.mode_of_transportation === 'own_vehicle' &&
                                        <>
                                            { showDatePicker 
                                            ? <Space direction="vertical">
                                                <DatePicker onChange={(date) => setNewBookingDate(date)} defaultValue={moment(viewBookingQuery.data.start_datetime)} />
                                                <Space direction="horizontal" style={{width: '100%', justifyContent: 'center'}}>
                                                    <Button size="small" type="primary" loading={updateBookingDateIsLoading} onClick={() => {
                                                        const param = {
                                                            booking_reference_number: viewBookingQuery.data.reference_number,
                                                            start_datetime: newBookingDate.format('YYYY-MM-DD'),
                                                            end_datetime: newBookingDate.format('YYYY-MM-DD'),
                                                        }
                                                        updateBookingDate(param);
                                                    }}>
                                                        <CheckOutlined />
                                                    </Button>
                                                    <Button size="small" type="default" onClick={() => setShowDatePicker(false)}><StopOutlined /></Button>
                                                </Space>
                                            </Space>
                                            : <>
                                                { moment(viewBookingQuery.data.start_datetime).format('MMM D, YYYY') }
                                                <Button type="link" onClick={() => setShowDatePicker(true)}><EditOutlined /></Button>
                                            </> } 
                                        </>
                                    ||                                 
                                        <>
                                            {moment(viewBookingQuery.data.start_datetime).format('MMM D, YYYY')} {!moment(viewBookingQuery.data.start_datetime).isSame(viewBookingQuery.data.end_datetime) ? "~ "+moment(viewBookingQuery.data.end_datetime).format('MMM D, YYYY') : ''}
                                        </>
                                    }
                                    {/* {moment(viewBookingQuery.data.start_datetime).format('MMM D, YYYY')} {!moment(viewBookingQuery.data.start_datetime).isSame(viewBookingQuery.data.end_datetime) ? "~ "+moment(viewBookingQuery.data.end_datetime).format('MMM D, YYYY') : ''} */}
                                </Descriptions.Item>
                                <Descriptions.Item label="Pax">adult: {viewBookingQuery.data.adult_pax} | kid: {viewBookingQuery.data.kid_pax} | infant: {viewBookingQuery.data.infant_pax}</Descriptions.Item>

                                <Descriptions.Item label="Booking label">
                                    <Typography.Paragraph editable={{ onChange: (record) =>  handleBookingLabelChange(record, viewBookingQuery.data.reference_number) }}>{viewBookingQuery.data.label ? viewBookingQuery.data.label : ''}</Typography.Paragraph>
                                </Descriptions.Item>

                                <Descriptions.Item label="Booking tags" span={2}>
                                    <Select mode="multiple" onChange={e => handleBookingTagsChange(props.referenceNumber, e)} defaultValue={_.map(viewBookingQuery.data.tags, 'name')} style={{ width: '100%' }} placeholder="Tags" tokenSeparators={[',',';']}>
                                        <Select.OptGroup label={<small className="text-primary">RE tags</small>}>
                                            <Select.Option value="ESLCC - Sales Agent">ESLCC - Sales Agent</Select.Option>
                                            <Select.Option value="ESLCC - Sales Client">ESLCC - Sales Client</Select.Option>
                                            <Select.Option value="RE - Golf">RE - Golf</Select.Option>
                                            <Select.Option value="SDMB - Sales Director Marketing Budget">SDMB - Sales Director Marketing Budget</Select.Option>
                                            <Select.Option value="Thru Agent - Paying">Thru Agent - Paying</Select.Option>
                                            <Select.Option value="Walk-in - Sales Agent">Walk-in - Sales Agent</Select.Option>
                                            <Select.Option value="Walk-in - Sales Client">Walk-in - Sales Client</Select.Option>
                                        </Select.OptGroup>
                                        <Select.OptGroup label={<small className="text-primary">Homeowner tags</small>}>
                                            <Select.Option value="ESLCC - AFV">ESLCC - AFV</Select.Option>
                                            <Select.Option value="ESLCC - CSV">ESLCC - CSV</Select.Option>
                                            <Select.Option value="ESLCC - HOA">ESLCC - HOA</Select.Option>
                                            <Select.Option value="HOA">HOA</Select.Option>
                                            <Select.Option value="HOA - Access Stub">HOA - Access Stub</Select.Option>
                                            <Select.Option value="HOA - AF Unit Owner">HOA - AF Unit Owner</Select.Option>
                                            <Select.Option value="HOA - Client">HOA - Client</Select.Option>
                                            <Select.Option value="HOA – Gate Access">HOA – Gate Access</Select.Option>
                                            <Select.Option value="HOA - Golf">HOA - Golf</Select.Option>
                                            <Select.Option value="HOA - Member">HOA - Member</Select.Option>
                                            <Select.Option value="HOA - Paying Promo">HOA - Paying Promo</Select.Option>
                                            <Select.Option value="HOA - Voucher">HOA - Voucher</Select.Option>
                                            <Select.Option value="HOA - Walk-in">HOA - Walk-in</Select.Option>
                                            <Select.Option value="HOA - Sales Director Marketing Budget">HOA - Sales Director Marketing Budget</Select.Option>
                                            <Select.Option value="Property Owner (Non-Member)">Property Owner (Non-Member)</Select.Option>
                                            <Select.Option value="Property Owner (HOA Member)">Property Owner (HOA Member)</Select.Option>
                                            <Select.Option value="Property Owner (Dependents)">Property Owner (Dependents)</Select.Option>
                                            <Select.Option value="Property Owner (Guests)">Property Owner (Guests)</Select.Option>
                                        </Select.OptGroup>
                                        <Select.OptGroup label={<small className="text-primary">Commercial tags</small>}>
                                            <Select.Option value="Commercial">Commercial</Select.Option>
                                            <Select.Option value="Commercial - Admin">Commercial - Admin</Select.Option>
                                            <Select.Option value="Commercial - Corre ">Commercial - Corre</Select.Option>
                                            <Select.Option value="Commercial - Golf ">Commercial - Golf</Select.Option>
                                            <Select.Option value="Commercial - Promo">Commecial - Promo</Select.Option>
                                            <Select.Option value="Commercial - Promo (Luventure)">Commercial - Promo (Luventure)</Select.Option>
                                            <Select.Option value="Commercial - Promo (Camaya Summer)">Commercial - Promo (Camaya Summer)</Select.Option>
                                            <Select.Option value="Commercial - Promo (Save Now, Travel Later)">Commercial - Promo (Save Now, Travel Later)</Select.Option>
                                            <Select.Option value="Commercial - Promo (12.12)">Commercial - Promo (12.12)</Select.Option>
                                            <Select.Option value="Commercial - Walk-in">Commercial - Walk-in</Select.Option>
                                            <Select.Option value="Commercial - Website">Commercial - Website</Select.Option>
                                            <Select.Option value="Corporate FIT">Corporate FIT</Select.Option>
                                            <Select.Option value="Corporate Sales">Corporate Sales</Select.Option>
                                            <Select.Option value="CVoucher">CVoucher</Select.Option>
                                            <Select.Option value="DTT - Walk-in">DTT - Walk-in</Select.Option>
                                            <Select.Option value="OTA - Klook">OTA - Klook</Select.Option>
                                            <Select.Option value="Paying - Walk-in">Paying - Walk-in</Select.Option>
                                        </Select.OptGroup>
                                        <Select.OptGroup label={<small className="text-primary">Employee tags</small>}>
                                            <Select.Option value="1Bataan ITS - Employee">1Bataan ITS - Employee</Select.Option>
                                            <Select.Option value="DEV 1 - Employee">DEV 1 - Employee</Select.Option>
                                            {/* <Select.Option value="DEV1 - Employee">DEV1 - Employee</Select.Option> */}
                                            <Select.Option value="ESLCC - Employee">ESLCC - Employee</Select.Option>
                                            <Select.Option value="ESLCC - Employee/Guest">ESLCC - Employee/Guest</Select.Option>
                                            <Select.Option value="ESTLC - Employee">ESTLC - Employee</Select.Option>
                                            <Select.Option value="ESTVC - Employee">ESTVC - Employee</Select.Option>
                                            <Select.Option value="Orion Sky - Employee">Orion Sky - Employee</Select.Option>
                                            <Select.Option value="People Plus - Employee">People Plus - Employee</Select.Option>
                                            <Select.Option value="SLA - Employee">SLA - Employee</Select.Option>
                                            <Select.Option value="DS18 - Employee">DS18 - Employee</Select.Option>
                                            <Select.Option value="DS18 - Events Guest">DS18 - Events Guest</Select.Option>
                                        </Select.OptGroup>
                                        <Select.OptGroup label={<small className="text-primary">Other tags</small>}>
                                            <Select.Option value="DEV 1 - Event/Guest">DEV 1 - Event/Guest</Select.Option>
                                            <Select.Option value="ESLCC - GC">ESLCC - GC</Select.Option>
                                            <Select.Option value="ESLCC - Guest">ESLCC - Guest</Select.Option>
                                            <Select.Option value="ESLCC - Event/Guest">ESLCC - Event/Guest</Select.Option>
                                            <Select.Option value="ESLCC - FOC">ESLCC - FOC</Select.Option>
                                            <Select.Option value="ESTLC - Guest">ESTLC - Guest</Select.Option>
                                            <Select.Option value="ESTLC - Event/Guest">ESTLC - Guest</Select.Option>
                                            <Select.Option value="ESTVC - GC">ESTVC - GC</Select.Option>
                                            <Select.Option value="ESTVC - Guest">ESTVC - Guest</Select.Option>
                                            <Select.Option value="ESTVC - Event/Guest">ESTVC - Event/Guest</Select.Option>
                                            <Select.Option value="Golf Member">Golf Member</Select.Option>
                                            <Select.Option value="House Use">House Use</Select.Option>
                                            <Select.Option value="Magic Leaf - Event/Guest">Magic Leaf - Event/Guest</Select.Option>
                                            <Select.Option value="TA - Rates">TA - Rates</Select.Option>
                                            <Select.Option value="Orion Sky">Orion Sky</Select.Option>
                                            <Select.Option value="Orion Sky - Guest">Orion Sky - Guest</Select.Option>
                                            <Select.Option value="SLA - Event/Guest">SLA - Event/Guest</Select.Option>
                                            <Select.Option value="VIP Guest">VIP Guest</Select.Option>
                                            <Select.Option value="Camaya Golf Voucher">Camaya Golf Voucher</Select.Option>
                                            <Select.Option value="Walkin">Walkin</Select.Option>
                                        </Select.OptGroup>
                                        <Select.Option value="Ferry Only" disabled>Ferry Only</Select.Option>
                                    </Select>
                                </Descriptions.Item>
                                <Descriptions.Item label="Source of booking">
                                    <Select style={{ width: '100%' }} placeholder="Source" defaultValue={viewBookingQuery.data.source}>
                                        <Option value="call">Call</Option>
                                        <Option value="viber">Viber</Option>
                                        <Option value="facebook_page">Facebook</Option>
                                        <Option value="other">Other</Option>
                                    </Select>
                                </Descriptions.Item>
                                <Descriptions.Item label="Mode of transportation">
                                    <Select defaultValue={viewBookingQuery.data.mode_of_transportation} style={{ width: '100%' }} placeholder="Transportation" onChange={e => handleModeOfTranspoChange(e, viewBookingQuery.data.mode_of_transportation)}>
                                        <Option value="undecided">Undecided</Option>
                                        <Option value="own_vehicle">Own vehicle</Option>
                                        <Option value="camaya_transportation">Camaya transportation</Option>
                                        { viewBookingQuery.data.mode_of_transportation != 'camaya_transportation' && <Option value="camaya_vehicle">Camaya vehicle</Option> }
                                        { viewBookingQuery.data.mode_of_transportation != 'camaya_transportation' && <Option value="van_rental">Van rental</Option> }
                                        { viewBookingQuery.data.mode_of_transportation != 'camaya_transportation' && <Option value="company_vehicle">Company vehicle</Option> }
                                    </Select>
                                </Descriptions.Item>
                                <Descriptions.Item label="Estimated time of arrival">
                                        <Select defaultValue={viewBookingQuery.data.eta} style={{ width: '100%' }} placeholder="ETA">
                                                {/* <Option value="6:00 AM">6AM</Option> */}
                                                <Option value="7:00 AM">7AM</Option>
                                                <Option value="8:00 AM">8AM</Option>
                                                <Option value="9:00 AM">9AM</Option>
                                                <Option value="10:00 AM">10AM</Option>
                                                <Option value="11:00 AM">11AM</Option>
                                                <Option value="12:00 PM">12PM</Option>
                                                <Option value="13:00 PM">1PM</Option>
                                                <Option value="14:00 PM">2PM</Option>
                                                <Option value="15:00 PM">3PM</Option>
                                                <Option value="16:00 PM">4PM</Option>
                                                <Option value="17:00 PM">5PM</Option>
                                                <Option value="18:00 PM">6PM</Option>
                                        </Select>
                                </Descriptions.Item>
                                {/* <Descriptions.Item label="Pay until">--pay until---</Descriptions.Item> */}

                                {/* <Descriptions.Item label="Remarks">{viewBookingQuery.data.remarks}</Descriptions.Item> */}

                                <Descriptions.Item label="Remarks">
                                    <Typography.Paragraph editable={{ onChange: (record) =>  handleRemarksChange(record, viewBookingQuery.data.reference_number) }}>{viewBookingQuery.data.remarks ? viewBookingQuery.data.remarks : ''}</Typography.Paragraph>
                                </Descriptions.Item>

                                <Descriptions.Item label="Additional emails" span={2}>
                                        {/* {_.map(viewBookingQuery.data.additional_emails, 'email').join(', ')} */}
                                        <Select mode="tags" defaultValue={_.map(viewBookingQuery.data.additional_emails, 'email')} tokenSeparators={[',',';', ' ']} style={{ width: '100%' }} placeholder="Additional email addresses" onChange={e => handleAdditionalEmailChange(props.referenceNumber, e)}/>
                                </Descriptions.Item>
                                
                                <Descriptions.Item label="Guest vehicle details">
                                    {
                                        (viewBookingQuery.data.guest_vehicles && viewBookingQuery.data.guest_vehicles.length > 0) ?
                                            <Button size="small" onClick={()=> {
                                                Modal.info({
                                                    icon: '',
                                                    title:'Guest vehicles',
                                                    content: (
                                                        viewBookingQuery.data.guest_vehicles &&
                                                        <>
                                                            <Table
                                                                size="small"
                                                                rowKey="id"
                                                                dataSource={viewBookingQuery.data.guest_vehicles}
                                                                columns={[
                                                                    {
                                                                        title: 'Model',
                                                                        dataIndex: 'model',
                                                                        key: 'model',
                                                                    },
                                                                    {
                                                                        title: 'Plate #',
                                                                        dataIndex: 'plate_number',
                                                                        key: 'plate_number',
                                                                    },
                                                                    {
                                                                        title: 'Action',
                                                                        dataIndex: 'action',
                                                                        key: 'action',
                                                                        render: (text, record) => ( <Button onClick={()=>editVehicleModal(record)} icon={<EditOutlined/>}/> )
                                                                    },
                                                                ]}
                                                            />
                                                            <Space>
                                                                <Button size="small" onClick={()=>addVehicleModal()}>Add vehicle</Button>
                                                            </Space>
                                                        </>
                                                    )
                                                });
                                        }}>{viewBookingQuery.data.guest_vehicles.length} vehicles</Button>
                                        : '-- no details --' 
                                    }

                                    {/* Edit Vehicle */}
                                    <Modal
                                        title={<Typography.Title level={4}>Edit vehicle</Typography.Title>}
                                        visible={editVehicleModalVisible}
                                        width={400}
                                        onOk={() => editVehicleForm.submit()}
                                        onCancel={() => setEditVehicleModalVisible(false)}
                                    >
                                        <Form
                                            form={editVehicleForm}
                                            onFinish={onEditVehicleFormFinish}
                                            layout="vertical"
                                            scrollToFirstError={true}
                                        >

                                            <Row>
                                                <Form.Item name="id" noStyle>
                                                    <Input type="hidden" />
                                                </Form.Item>
                                                <Col xl={24}>
                                                    <Form.Item
                                                        name="model"
                                                        label="Model"
                                                        rules={[
                                                            {
                                                                required: true
                                                            }
                                                        ]}
                                                    >
                                                        <Input/>
                                                    </Form.Item>
                                                </Col>
                                                <Col xl={24}>
                                                    <Form.Item
                                                        name="plate_number"
                                                        label="Plate Number"
                                                        rules={[
                                                            {
                                                                required: true
                                                            }
                                                        ]}
                                                    >
                                                        <Input/>
                                                    </Form.Item>
                                                </Col>
                                            </Row>
                                        </Form>
                                        <Space>
                                            <Popconfirm title="Are you sure?" onConfirm={()=>handleDeleteVehicle(editVehicleForm.getFieldValue('id'))} disabled>
                                                <Button size="small" danger icon={<DeleteOutlined/>} disabled>Delete vehicle</Button>
                                            </Popconfirm>
                                        </Space>
                                    </Modal>

                                    {/* Add Vehicle */}
                                    <Modal
                                        title={<Typography.Title level={4}>Add vehicle</Typography.Title>}
                                        visible={addVehicleModalVisible}
                                        width={400}
                                        onOk={() => addVehicleForm.submit()}
                                        onCancel={() => setAddVehicleModalVisible(false)}
                                    >
                                        <Form
                                            form={addVehicleForm}
                                            onFinish={onAddVehicleFormFinish}
                                            layout="vertical"
                                            scrollToFirstError={true}
                                        >

                                            <Row>
                                                <Form.Item name="id" noStyle>
                                                    <Input type="hidden" />
                                                </Form.Item>
                                                <Col xl={24}>
                                                    <Form.Item
                                                        name="model"
                                                        label="Model"
                                                        rules={[
                                                            {
                                                                required: true
                                                            }
                                                        ]}
                                                    >
                                                        <Input/>
                                                    </Form.Item>
                                                </Col>
                                                <Col xl={24}>
                                                    <Form.Item
                                                        name="plate_number"
                                                        label="Plate Number"
                                                        rules={[
                                                            {
                                                                required: true
                                                            }
                                                        ]}
                                                    >
                                                        <Input/>
                                                    </Form.Item>
                                                </Col>
                                            </Row>
                                        </Form>
                                    </Modal>
                                </Descriptions.Item>
                                <Descriptions.Item span={2} label="Billing instructions">
                                    <Typography.Paragraph editable={{ onChange: (update) =>  handleBillingInstructionChange(update, viewBookingQuery.data.reference_number) }}>{updateBillingInstructionsQueryIsLoading ? 'saving...' : (viewBookingQuery.data.billing_instructions ?? '')}</Typography.Paragraph>
                                </Descriptions.Item>

                                <Descriptions.Item label="Booking date">
                                    {moment(viewBookingQuery.data.created_at).format('MMM D, YYYY, h:mm:ss A')}
                                </Descriptions.Item>
                                <Descriptions.Item span={2} label="Auto cancel">
                                    <div><DatePicker allowClear onChange={e => handleAutoCancelDateChange(e)} showTime className="mr-2" defaultValue={viewBookingQuery.data.auto_cancel_at ? moment(viewBookingQuery.data.auto_cancel_at) : null} /></div>
                                    <Tooltip title={moment(viewBookingQuery.data.auto_cancel_at).format('YYYY-MM-DD HH:mm:ss')}>{viewBookingQuery.data.auto_cancel_at ? moment(viewBookingQuery.data.auto_cancel_at).fromNow() : ''}</Tooltip>
                                </Descriptions.Item>
                                {
                                        viewBookingQuery.data.cancelled_at &&
                                        <Descriptions.Item label="Date cancelled">
                                        <Button type="link" onClick={() => Modal.info({
                                            title: 'Cancel history log',
                                            width: 500,
                                            content: (
                                                <Descriptions size="small" bordered column={1} className="mt-4">
                                                    <Descriptions.Item label="Date cancelled">{moment(viewBookingQuery.data.cancelled_at).format('MMM D, YYYY, h:mm:ss A')}</Descriptions.Item>
                                                    <Descriptions.Item label="Cancelled by">{ viewBookingQuery.data.cancelled_by ? <>{viewBookingQuery.data.cancelled_by.first_name} {viewBookingQuery.data.cancelled_by.last_name}</>:'System' }</Descriptions.Item>
                                                    <Descriptions.Item label="Reason for cancellation">{viewBookingQuery.data.reason_for_cancellation}</Descriptions.Item>
                                                </Descriptions>
                                            )
                                        })}>{moment(viewBookingQuery.data.cancelled_at).format('MMM D, YYYY, h:mm:ss A')} - (view history)</Button>
                                        </Descriptions.Item>
                                }
                                { (viewBookingQuery.data.agent || viewBookingQuery.data.sales_director) &&
                                    <Descriptions.Item span={3} label="Sales Agent &amp; Director">
                                        Sales Agent: {viewBookingQuery.data.agent?.first_name+" "+viewBookingQuery.data.agent?.last_name}<br/>
                                        Sales Director: {viewBookingQuery.data.sales_director_id ? viewBookingQuery.data.sales_director?.first_name+" "+viewBookingQuery.data.sales_director?.last_name : ''}
                                    </Descriptions.Item>
                                }
                            </Descriptions>
                        }
                    </Col>

                    <Col xl={24} xs={24}>
                        <Typography.Title level={5}>Notes
                            <Button style={{float: 'right'}} onClick={()=>setaddNoteDrawerVisible(true)}>
                                <PlusOutlined/> Add note
                            </Button>
                        </Typography.Title>
                        <Table
                            size="small"
                            rowKey="id"
                            dataSource={viewBookingQuery.data && viewBookingQuery.data.notes}
                            columns={[
                                {
                                    title:'Author',
                                    render: (text, record) => <><UserOutlined className="mr-2"/>{record.author_details.first_name} {record.author_details.last_name}</>
                                },
                                {
                                    title:'Date',
                                    render: (text, record) => <><CalendarOutlined className="mr-2"/>{moment(record.created_at).format('MMM D, YYYY h:mm:ss A')}</>
                                },
                                {
                                    title: 'Message',
                                    render: (text, record) => <Typography.Paragraph ellipsis style={{width: 800}}>{record.message}</Typography.Paragraph>
                                }
                            ]}
                            // rowKey="id"
                            onRow={(record, rowIndex) => {
                                return {
                                onClick: (event) => Modal.info({
                                        icon: '',
                                        width: 500,
                                        title: <>{`${record.author_details.first_name} ${record.author_details.last_name}`}</>,
                                        content: (
                                            <>
                                                <small>Written: {moment(record.created_at).format('MMM D, YYYY h:mm:ss A')}</small><br/>
                                                <Typography.Paragraph className="mt-4" style={{whiteSpace:'pre-wrap'}}>{record.message}</Typography.Paragraph>
                                            </>
                                        ),
                                }),
                                onDoubleClick: event => {}, // double click row
                                onContextMenu: event => {}, // right button click row
                                onMouseEnter: event => {}, // mouse enter row
                                onMouseLeave: event => {}, // mouse leave row
                                };
                            }}
                        />
                    </Col>

                    <Col xl={24} xs={24}>
                        <Typography.Title level={5}>Guests
                        {/* { viewBookingQuery.data && viewBookingQuery.data.type == "ON" && <Button className="ml-2" onClick={()=>setaddGuestModalVisible(true)}>Add guest</Button> } */}
                            <Button className="ml-2" size="small" onClick={()=>setaddGuestModalVisible(true)}>Add guest</Button>
                            <Button className="ml-2" size="small" onClick={()=>setAddFerryToGuestsModalVisible(true)}>Add ferry to guest(s)</Button>
                        </Typography.Title>

                        <Typography.Text>Adult guests ({viewBookingQuery.data ? viewBookingQuery.data.adult_guests.length : ''})</Typography.Text>
                        { !viewBookingQuery.isLoading &&
                            <Table
                                size="small"
                                rowKey="id"
                                dataSource={viewBookingQuery.data.adult_guests}
                                expandable={{
                                    expandedRowRender: record =>
                                        <>
                                            <Typography.Text strong className="mr-2">Guest tags:</Typography.Text>
                                            <Space>
                                                    {
                                                        record.guest_tags.map((item, key) => {
                                                            return <Tag color="blue" key={key}>{item.name}</Tag>
                                                        })
                                                    }
                                            </Space>

                                            { record.tee_time &&
                                                <div>
                                                    <Typography.Text strong className="mr-2">Guest tee time:</Typography.Text>
                                                    <Space>
                                                            {
                                                                record.tee_time.map((item, key) => {
                                                                    return <Tag key={key}>{moment(moment(item.schedule.date).format('YYYY-MM-DD')+' '+item.schedule.time).format('D MMM YYYY h:mm A')}</Tag>
                                                                })
                                                            }
                                                    </Space>
                                                </div>
                                            }
                                        </>,
                                    rowExpandable: record => record.guest_tags.length !== 0 || record.tee_time.length !== 0,
                                }}
                                columns={[
                                    {
                                        title: 'QR',
                                        dataIndex: 'qr',
                                        key: 'qr',
                                        render: (text, record) => {
                                            return <QrcodeOutlined style={{fontSize:'2rem'}} onClick={()=>{ handleGuestQRClick(record) }}/>
                                        }
                                    },
                                    {
                                        title: 'Guest ref. #',
                                        dataIndex: 'reference_number',
                                        key: 'reference_number',
                                    },
                                    {
                                        title: 'Trips',
                                        render: (text, record) => {

                                            if (record.trip_bookings?.length > 0) {
                                                return <div style={{display: 'flex'}}>{record.trip_bookings.map( (i, key) => <div key={key} style={{ textAlign: 'center', lineHeight: 1, padding: 4, border: 'solid 1px gainsboro', borderRadius: 6}}><div><Icon component={FerryIcon} /></div><small style={{fontSize: '0.4rem'}}>to {i.destination_code}</small></div>)}</div>;
                                            } else {
                                                return <></>
                                            }
                                        }
                                    },
                                    {
                                        title: 'First name',
                                        dataIndex: 'first_name',
                                        key: 'first_name',
                                    },
                                    {
                                        title: 'Last name',
                                        dataIndex: 'last_name',
                                        key: 'last_name',
                                    },
                                    {
                                        title: 'Age',
                                        dataIndex: 'age',
                                        key: 'age',
                                    },
                                    {
                                        title: 'Nationality',
                                        dataIndex: 'nationality',
                                        key: 'nationality',
                                    },
                                    {
                                        title: 'Status',
                                        dataIndex: 'status',
                                        key: 'status',
                                        render: (text, record) =>  {
                                            let disabled = (viewBookingQuery.data.status === 'cancelled' || viewBookingQuery.data.status === 'pending') ? true : false;
                                            return  <Select defaultValue={record.status} style={{width:'100%'}} onChange={(e) => handleChangeGuestStatus(record.id, e)} disabled={disabled}>
                                                    <Select.Option value="arriving">Arriving</Select.Option>
                                                    <Select.Option value="on_premise">On Premise</Select.Option>
                                                    <Select.Option value="checked_in">Checked-in</Select.Option>
                                                    <Select.Option value="no_show">No show</Select.Option>
                                                    <Select.Option value="booking_cancelled">Booking cancelled</Select.Option>
                                                    <Select.Option disabled={viewBookingQuery.data.type == 'DT'} value="room_checked_in">Room checked-in</Select.Option>
                                                    <Select.Option disabled={viewBookingQuery.data.type == 'DT'} value="room_checked_out">Room checked-out</Select.Option>
                                                </Select>
                                            }
                                    },
                                    // {
                                    //     title: 'Inclusions',
                                    //     dataIndex: 'guest_inclusions',
                                    //     key: 'guest_inclusions',
                                    //     render: (text, record) => {
                                    //         return <Select
                                    //             disabled={true}
                                    //             style={{width: '100%'}}
                                    //             mode="multiple"
                                    //             maxTagCount={4}
                                    //             defaultValue={_.map(record.guest_inclusions, i => i.code)}>
                                    //             {
                                    //                 record.guest_inclusions.map( (i, key) => {
                                    //                     return <Select.Option key={key} value={i.code}>{i.code}</Select.Option>
                                    //                 })
                                    //             }
                                    //             </Select>
                                    //     }
                                    // },
                                    {
                                        title: 'Action',
                                        dataIndex: 'action',
                                        key: 'action',
                                        render: (text, record) => (<Button onClick={()=>viewGuestModal(record)} icon={<EditOutlined/>} />)
                                    },
                                ]}
                            />
                        }

                        { viewBookingQuery.isLoading ?
                            <Loading /> :
                            <>
                            { (viewBookingQuery.data && viewBookingQuery.data.kid_guests && viewBookingQuery.data.kid_guests.length) ?
                                <>
                                <Typography.Text>Kid guests ({viewBookingQuery.data.kid_guests.length})</Typography.Text>
                                <Table
                                    size="small"
                                    rowKey="id"
                                    dataSource={viewBookingQuery.data.kid_guests}
                                    expandable={{
                                        expandedRowRender: record =>
                                            <>
                                                <Typography.Text strong className="mr-2">Guest tags:</Typography.Text>
                                                <Space>
                                                        {
                                                            record.guest_tags.map((item, key) => {
                                                                return <Tag color="blue" key={key}>{item.name}</Tag>
                                                            })
                                                        }
                                                </Space>

                                                { record.tee_time &&
                                                    <div>
                                                        <Typography.Text strong className="mr-2">Guest tee time:</Typography.Text>
                                                        <Space>
                                                                {
                                                                    record.tee_time.map((item, key) => {
                                                                        return <Tag key={key}>{moment(moment(item.schedule.date).format('YYYY-MM-DD')+' '+item.schedule.time).format('D MMM YYYY h:mm A')}</Tag>
                                                                    })
                                                                }
                                                        </Space>
                                                    </div>
                                                }

                                            </>,
                                        rowExpandable: record => record.guest_tags.length !== 0 || record.tee_time.length !== 0,
                                    }}
                                    columns={[
                                        {
                                            title: 'QR',
                                            dataIndex: 'qr',
                                            key: 'qr',
                                            render: (text, record) => {
                                                return <QrcodeOutlined style={{fontSize:'2rem'}} onClick={()=>{ handleGuestQRClick(record) }}/>
                                            }

                                        },
                                        {
                                            title: 'Guest ref #',
                                            dataIndex: 'reference_number',
                                            key: 'reference_number',
                                        },
                                        {
                                            title: 'Trips',
                                            render: (text, record) => {
        
                                                if (record.trip_bookings?.length > 0) {
                                                    return <div style={{display: 'flex'}}>{record.trip_bookings.map( (i, key) => <div key={key} style={{ textAlign: 'center', lineHeight: 1, padding: 4, border: 'solid 1px gainsboro', borderRadius: 6}}><div><Icon component={FerryIcon} /></div><small style={{fontSize: '0.4rem'}}>to {i.destination_code}</small></div>)}</div>;
                                                } else {
                                                    return <></>
                                                }
                                            }
                                        },
                                        {
                                            title: 'First name',
                                            dataIndex: 'first_name',
                                            key: 'first_name',
                                        },
                                        {
                                            title: 'Last name',
                                            dataIndex: 'last_name',
                                            key: 'last_name',
                                        },
                                        {
                                            title: 'Age',
                                            dataIndex: 'age',
                                            key: 'age',
                                        },
                                        {
                                            title: 'Nationality',
                                            dataIndex: 'nationality',
                                            key: 'nationality',
                                        },
                                        {
                                            title: 'Status',
                                            dataIndex: 'status',
                                            key: 'status',
                                            render: (text, record) => {
                                                let disabled = (viewBookingQuery.data.status === 'cancelled' || viewBookingQuery.data.status === 'pending') ? true : false;
                                                return <Select defaultValue={record.status} style={{width:'100%'}} onChange={(e) => handleChangeGuestStatus(record.id, e)} disabled={disabled}>
                                                    <Select.Option value="arriving">Arriving</Select.Option>
                                                    <Select.Option value="on_premise">On Premise</Select.Option>
                                                    <Select.Option value="checked_in">Checked-in</Select.Option>
                                                    <Select.Option value="no_show">No show</Select.Option>
                                                    <Select.Option value="booking_cancelled">Booking cancelled</Select.Option>
                                                    <Select.Option disabled={viewBookingQuery.data.type == 'DT'} value="room_checked_in">Room checked-in</Select.Option>
                                                    <Select.Option disabled={viewBookingQuery.data.type == 'DT'} value="room_checked_out">Room checked-out</Select.Option>
                                                </Select>
                                            }
                                        },
                                        // {
                                        //     title: 'Inclusions',
                                        //     dataIndex: 'guest_inclusions',
                                        //     key: 'guest_inclusions',
                                        //     render: (text, record) => {
                                        //         return <Select
                                        //             disabled={true}
                                        //             style={{width: '100%'}}
                                        //             mode="multiple"
                                        //             defaultValue={_.map(record.guest_inclusions, i => i.code)}>
                                        //             {
                                        //                 record.guest_inclusions.map( (i, key) => {
                                        //                     return <Select.Option key={key} value={i.code}>{i.code}</Select.Option>
                                        //                 })
                                        //             }
                                        //             </Select>
                                        //     }
                                        // },
                                        {
                                            title: 'Action',
                                            dataIndex: 'action',
                                            key: 'action',
                                            render: (text, record) => (<Button onClick={()=>viewGuestModal(record)} icon={<EditOutlined/>} />)
                                        },
                                    ]}
                                />
                                </>
                                :''
                            }
                            </>
                        }

                        { viewBookingQuery.isLoading ?
                            <Loading /> :
                            <>
                            { (viewBookingQuery.data && viewBookingQuery.data.infant_guests && viewBookingQuery.data.infant_guests.length) ?
                                <>
                                <Typography.Text>Infant guests ({viewBookingQuery.data.infant_guests.length})</Typography.Text>
                                <Table
                                    size="small"
                                    rowKey="id"
                                    dataSource={viewBookingQuery.data.infant_guests}
                                    expandable={{
                                        expandedRowRender: record =>
                                            <>
                                                <Typography.Text strong className="mr-2">Guest tags:</Typography.Text>
                                                <Space>
                                                        {
                                                            record.guest_tags.map((item, key) => {
                                                                return <Tag color="blue" key={key}>{item.name}</Tag>
                                                            })
                                                        }
                                            </Space>
                                            </>,
                                        rowExpandable: record => record.guest_tags.length !== 0,
                                    }}
                                    columns={[
                                        {
                                            title: 'QR',
                                            dataIndex: 'qr',
                                            key: 'qr',
                                            render: (text, record) => {
                                                return <QrcodeOutlined style={{fontSize:'2rem'}} onClick={()=>{ handleGuestQRClick(record) }}/>
                                            }
                                        },
                                        {
                                            title: 'Guest ref #',
                                            dataIndex: 'reference_number',
                                            key: 'reference_number',
                                        },
                                        {
                                            title: 'Trips',
                                            render: (text, record) => {
        
                                                if (record.trip_bookings?.length > 0) {
                                                    return <div style={{display: 'flex'}}>{record.trip_bookings.map( (i, key) => <div key={key} style={{ textAlign: 'center', lineHeight: 1, padding: 4, border: 'solid 1px gainsboro', borderRadius: 6}}><div><Icon component={FerryIcon} /></div><small style={{fontSize: '0.4rem'}}>to {i.destination_code}</small></div>)}</div>;
                                                } else {
                                                    return <></>
                                                }
                                            }
                                        },
                                        {
                                            title: 'First name',
                                            dataIndex: 'first_name',
                                            key: 'first_name',
                                        },
                                        {
                                            title: 'Last name',
                                            dataIndex: 'last_name',
                                            key: 'last_name',
                                        },
                                        {
                                            title: 'Age',
                                            dataIndex: 'age',
                                            key: 'age',
                                        },
                                        {
                                            title: 'Nationality',
                                            dataIndex: 'nationality',
                                            key: 'nationality',
                                        },
                                        {
                                            title: 'Status',
                                            dataIndex: 'status',
                                            key: 'status',
                                            render: (text, record) => {
                                                let disabled = (viewBookingQuery.data.status === 'cancelled' || viewBookingQuery.data.status === 'pending') ? true : false;
                                                return <Select defaultValue={record.status} style={{width:'100%'}} onChange={(e) => handleChangeGuestStatus(record.id, e)} disabled={disabled}>
                                                    <Select.Option value="arriving">Arriving</Select.Option>
                                                    <Select.Option value="on_premise">On Premise</Select.Option>
                                                    <Select.Option value="checked_in">Checked-in</Select.Option>
                                                    <Select.Option value="no_show">No show</Select.Option>
                                                    <Select.Option value="booking_cancelled">Booking cancelled</Select.Option>
                                                    <Select.Option disabled={viewBookingQuery.data.type == 'DT'} value="room_checked_in">Room checked-in</Select.Option>
                                                    <Select.Option disabled={viewBookingQuery.data.type == 'DT'} value="room_checked_out">Room checked-out</Select.Option>
                                                </Select>
                                            }
                                        },
                                        // {
                                        //     title: 'Inclusions',
                                        //     dataIndex: 'guest_inclusions',
                                        //     key: 'guest_inclusions',
                                        //     render: (text, record) => {
                                        //         return <Select
                                        //             disabled={true}
                                        //             style={{width: '100%'}}
                                        //             mode="multiple"
                                        //             defaultValue={_.map(record.guest_inclusions, i => i.code)}>
                                        //             {
                                        //                 record.guest_inclusions.map( (i, key) => {
                                        //                     return <Select.Option key={key} value={i.code}>{i.code}</Select.Option>
                                        //                 })
                                        //             }
                                        //             </Select>
                                        //     }
                                        // },
                                        {
                                            title: 'Action',
                                            dataIndex: 'action',
                                            key: 'action',
                                            render: (text, record) => (<Button onClick={()=>viewGuestModal(record)} icon={<EditOutlined/>} />)
                                        },
                                    ]}
                                />
                                </>
                                :''
                            }
                            </>
                        }

                        <Modal
                            title={<Typography.Title level={4}>Edit Guest Details</Typography.Title>}
                            visible={viewGuestModalVisible}
                            width={400}
                            onOk={() => viewGuestForm.submit()}
                            onCancel={()=>setViewGuestModalVisible(false)}
                        >
                            <Form
                                form={viewGuestForm}
                                onFinish={onViewGuestFormFinish}
                                layout="vertical"
                                scrollToFirstError={true}
                                // initialValues={}
                            >
                                <Row>
                                    <Form.Item name="id" noStyle>
                                        <Input type="hidden" />
                                    </Form.Item>
                                    <Col xl={24}>
                                        <Form.Item
                                            name="first_name"
                                            label="First Name"
                                            rules={[
                                                {
                                                    required: true
                                                }
                                            ]}
                                        >
                                            <Input/>
                                        </Form.Item>
                                    </Col>
                                    <Col xl={24}>
                                        <Form.Item
                                            name="last_name"
                                            label="Last Name"
                                            rules={[
                                                {
                                                    required: true
                                                }
                                            ]}
                                        >
                                            <Input/>
                                        </Form.Item>
                                    </Col>
                                    <Col xl={24}>
                                        <Form.Item
                                            name="age"
                                            label="Age"
                                            rules={[
                                                {
                                                    required: true
                                                }
                                            ]}
                                        >
                                            <Input/>
                                        </Form.Item>
                                    </Col>
                                    <Col xl={24}>
                                        <Form.Item 
                                            name="nationality"
                                            label="nationality"
                                        >
                                            <Select
                                                showSearch
                                                style={{ width: '100%' }}
                                                optionFilterProp="children"
                                                onSearch={onSearch}
                                                filterOption={(input, option) =>
                                                    option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                                }
                                                placeholder="Nationality"
                                            >
                                                <Select.Option value="Afghan">Afghan</Select.Option>
                                                <Select.Option value="Albanian">Albanian</Select.Option>
                                                <Select.Option value="Algerian">Algerian</Select.Option>
                                                <Select.Option value="American">American</Select.Option>
                                                <Select.Option value="Andorran">Andorran</Select.Option>
                                                <Select.Option value="Angolan">Angolan</Select.Option>
                                                <Select.Option value="Anguillan">Anguillan</Select.Option>
                                                <Select.Option value="Citizen of Antigua and Barbuda">Citizen of Antigua and Barbuda</Select.Option>
                                                <Select.Option value="Argentine">Argentine</Select.Option>
                                                <Select.Option value="Armenian">Armenian</Select.Option>
                                                <Select.Option value="Australian">Australian</Select.Option>
                                                <Select.Option value="Austrian">Austrian</Select.Option>
                                                <Select.Option value="Azerbaijani">Azerbaijani</Select.Option>

                                                <Select.Option value="Bahamian">Bahamian</Select.Option>
                                                <Select.Option value="Bahraini">Bahraini</Select.Option>
                                                <Select.Option value="Bangladeshi">Bangladeshi</Select.Option>
                                                <Select.Option value="Barbadian">Barbadian</Select.Option>
                                                <Select.Option value="Belarusian">Belarusian</Select.Option>
                                                <Select.Option value="Belgian">Belgian</Select.Option>
                                                <Select.Option value="Belizean">Belizean</Select.Option>
                                                <Select.Option value="Beninese">Beninese</Select.Option>
                                                <Select.Option value="Bermudian">Bermudian</Select.Option>
                                                <Select.Option value="Bhutanese">Bhutanese</Select.Option>
                                                <Select.Option value="Bolivian">Bolivian</Select.Option>
                                                <Select.Option value="Citizen of Bosnia and Herzegovina">Citizen of Bosnia and Herzegovina</Select.Option>
                                                <Select.Option value="Botswanan">Botswanan</Select.Option>
                                                <Select.Option value="Brazilian">Brazilian</Select.Option>
                                                <Select.Option value="British">British</Select.Option>
                                                <Select.Option value="British Virgin Islander">British Virgin Islander</Select.Option>
                                                <Select.Option value="Bruneian">Bruneian</Select.Option>
                                                <Select.Option value="Bulgarian">Bulgarian</Select.Option>
                                                <Select.Option value="Burkinan">Burkinan</Select.Option>
                                                <Select.Option value="Burmese">Burmese</Select.Option>
                                                <Select.Option value="Burundian">Burundian</Select.Option>

                                                <Select.Option value="Cambodian">Cambodian</Select.Option>
                                                <Select.Option value="Cameroonian">Cameroonian</Select.Option>
                                                <Select.Option value="Canadian">Canadian</Select.Option>
                                                <Select.Option value="Cape Verdean">Cape Verdean</Select.Option>
                                                <Select.Option value="Cayman Islander">Cayman Islander</Select.Option>
                                                <Select.Option value="Central African">Central African</Select.Option>
                                                <Select.Option value="Chadian">Chadian</Select.Option>
                                                <Select.Option value="Chilean">Chilean</Select.Option>
                                                <Select.Option value="Chinese">Chinese</Select.Option>
                                                <Select.Option value="Colombian">Colombian</Select.Option>
                                                <Select.Option value="Comoran">Comoran</Select.Option>
                                                <Select.Option value="Congolese (Congo)">Congolese (Congo)</Select.Option>
                                                <Select.Option value="Congolese (DRC)">Congolese (DRC)</Select.Option>
                                                <Select.Option value="Cook Islander">Cook Islander</Select.Option>
                                                <Select.Option value="Costa Rican">Costa Rican</Select.Option>
                                                <Select.Option value="Croatian">Croatian</Select.Option>
                                                <Select.Option value="Cuban">Cuban</Select.Option>
                                                <Select.Option value="Cymraes">Cymraes</Select.Option>
                                                <Select.Option value="Cymro">Cymro</Select.Option>
                                                <Select.Option value="Cypriot">Cypriot</Select.Option>
                                                <Select.Option value="Czech">Czech</Select.Option>

                                                <Select.Option value="Danish">Danish</Select.Option>
                                                <Select.Option value="Djiboutian">Djiboutian</Select.Option>
                                                <Select.Option value="Dominican">Dominican</Select.Option>
                                                <Select.Option value="Citizen of the Dominican Republic">Citizen of the Dominican Republic</Select.Option>
                                                <Select.Option value="Dutch">Dutch</Select.Option>

                                                <Select.Option value="East Timorese">East Timorese</Select.Option>
                                                <Select.Option value="Ecuadorean">Ecuadorean</Select.Option>
                                                <Select.Option value="Egyptian">Egyptian</Select.Option>
                                                <Select.Option value="Emirati">Emirati</Select.Option>
                                                <Select.Option value="English">English</Select.Option>
                                                <Select.Option value="Equatorial Guinean">Equatorial Guinean</Select.Option>
                                                <Select.Option value="Eritrean">Eritrean</Select.Option>
                                                <Select.Option value="Estonian">Estonian</Select.Option>
                                                <Select.Option value="Ethiopian">Ethiopian</Select.Option>

                                                <Select.Option value="Faroese">Faroese</Select.Option>
                                                <Select.Option value="Fijian">Fijian</Select.Option>
                                                <Select.Option value="Filipino">Filipino</Select.Option>
                                                <Select.Option value="Finnish">Finnish</Select.Option>
                                                <Select.Option value="French">French</Select.Option>

                                                <Select.Option value="Gabonese">Gabonese</Select.Option>
                                                <Select.Option value="Gambian">Gambian</Select.Option>
                                                <Select.Option value="Georgian">Georgian</Select.Option>
                                                <Select.Option value="German">German</Select.Option>
                                                <Select.Option value="Ghanaian">Ghanaian</Select.Option>
                                                <Select.Option value="Gibraltarian">Gibraltarian</Select.Option>
                                                <Select.Option value="Greek">Greek</Select.Option>
                                                <Select.Option value="Greenlandic">Greenlandic</Select.Option>
                                                <Select.Option value="Grenadian">Grenadian</Select.Option>
                                                <Select.Option value="Guamanian">Guamanian</Select.Option>
                                                <Select.Option value="Guatemalan">Guatemalan</Select.Option>
                                                <Select.Option value="Citizen of Guinea-Bissau">Citizen of Guinea-Bissau</Select.Option>
                                                <Select.Option value="Guinean">Guinean</Select.Option>
                                                <Select.Option value="Guyanese">Guyanese</Select.Option>

                                                <Select.Option value="Haitian">Haitian</Select.Option>
                                                <Select.Option value="Honduran">Honduran</Select.Option>
                                                <Select.Option value="Hong Konger">Hong Konger</Select.Option>
                                                <Select.Option value="Hungarian">Hungarian</Select.Option>

                                                <Select.Option value="Icelandic">Icelandic</Select.Option>
                                                <Select.Option value="Indian">Indian</Select.Option>
                                                <Select.Option value="Indonesian">Indonesian</Select.Option>
                                                <Select.Option value="Iranian">Iranian</Select.Option>
                                                <Select.Option value="Iraqi">Iraqi</Select.Option>
                                                <Select.Option value="Irish">Irish</Select.Option>
                                                <Select.Option value="Israeli">Israeli</Select.Option>
                                                <Select.Option value="Italian">Italian</Select.Option>
                                                <Select.Option value="Ivorian">Ivorian</Select.Option>

                                                <Select.Option value="Jamaican">Jamaican</Select.Option>
                                                <Select.Option value="Japanese">Japanese</Select.Option>
                                                <Select.Option value="Jordanian">Jordanian</Select.Option>

                                                <Select.Option value="Kazakh">Kazakh</Select.Option>
                                                <Select.Option value="Kenyan">Kenyan</Select.Option>
                                                <Select.Option value="Kittitian">Kittitian</Select.Option>
                                                <Select.Option value="Citizen of Kiribati">Citizen of Kiribati</Select.Option>
                                                <Select.Option value="Kosovan">Kosovan</Select.Option>
                                                <Select.Option value="Kuwaiti">Kuwaiti</Select.Option>
                                                <Select.Option value="Kyrgyz">Kyrgyz</Select.Option>

                                                <Select.Option value="Lao">Lao</Select.Option>
                                                <Select.Option value="Latvian">Latvian</Select.Option>
                                                <Select.Option value="Lebanese">Lebanese</Select.Option>
                                                <Select.Option value="Liberian">Liberian</Select.Option>
                                                <Select.Option value="Libyan">Libyan</Select.Option>
                                                <Select.Option value="Liechtenstein Citizen">Liechtenstein Citizen</Select.Option>
                                                <Select.Option value="Lithuanian">Lithuanian</Select.Option>
                                                <Select.Option value="Luxembourger">Luxembourger</Select.Option>

                                                <Select.Option value="Macanese">Macanese</Select.Option>
                                                <Select.Option value="Macedonian">Macedonian</Select.Option>
                                                <Select.Option value="Malagasy">Malagasy</Select.Option>
                                                <Select.Option value="Malawian">Malawian</Select.Option>
                                                <Select.Option value="Malaysian">Malaysian</Select.Option>
                                                <Select.Option value="Maldivian">Maldivian</Select.Option>
                                                <Select.Option value="Malian">Malian</Select.Option>
                                                <Select.Option value="Maltese">Maltese</Select.Option>
                                                <Select.Option value="Marshallese">Marshallese</Select.Option>
                                                <Select.Option value="Martiniquais">Martiniquais</Select.Option>
                                                <Select.Option value="Mauritanian">Mauritanian</Select.Option>
                                                <Select.Option value="Mauritian">Mauritian</Select.Option>
                                                <Select.Option value="Mexican">Mexican</Select.Option>
                                                <Select.Option value="Micronesian">Micronesian</Select.Option>
                                                <Select.Option value="Moldovan">Moldovan</Select.Option>
                                                <Select.Option value="Monegasque">Monegasque</Select.Option>
                                                <Select.Option value="Mongolian">Mongolian</Select.Option>
                                                <Select.Option value="Montenegrin">Montenegrin</Select.Option>
                                                <Select.Option value="Montserratian">Montserratian</Select.Option>
                                                <Select.Option value="Moroccan">Moroccan</Select.Option>
                                                <Select.Option value="Mosotho">Mosotho</Select.Option>
                                                <Select.Option value="Mozambican">Mozambican</Select.Option>

                                                <Select.Option value="Namibian">Namibian</Select.Option>
                                                <Select.Option value="Nauruan">Nauruan</Select.Option>
                                                <Select.Option value="Nepalese">Nepalese</Select.Option>
                                                <Select.Option value="New Zealander">New Zealander</Select.Option>
                                                <Select.Option value="Nicaraguan">Nicaraguan</Select.Option>
                                                <Select.Option value="Nigerian">Nigerian</Select.Option>
                                                <Select.Option value="Nigerien">Nigerien</Select.Option>
                                                <Select.Option value="Niuean">Niuean</Select.Option>
                                                <Select.Option value="North Korean">North Korean</Select.Option>
                                                <Select.Option value="Northern Irish">Northern Irish</Select.Option>
                                                <Select.Option value="Norwegian">Norwegian</Select.Option>

                                                <Select.Option value="Omani">Omani</Select.Option>

                                                <Select.Option value="Pakistani">Pakistani</Select.Option>
                                                <Select.Option value="Palauan">Palauan</Select.Option>
                                                <Select.Option value="Palestinian">Palestinian</Select.Option>
                                                <Select.Option value="Panamanian">Panamanian</Select.Option>
                                                <Select.Option value="Papua New Guinean">Papua New Guinean</Select.Option>
                                                <Select.Option value="Paraguayan">Paraguayan</Select.Option>
                                                <Select.Option value="Peruvian">Peruvian</Select.Option>
                                                <Select.Option value="Pitcairn Islander">Pitcairn Islander</Select.Option>
                                                <Select.Option value="Polish">Polish</Select.Option>
                                                <Select.Option value="Portuguese">Portuguese</Select.Option>
                                                <Select.Option value="Prydeinig">Prydeinig</Select.Option>
                                                <Select.Option value="Puerto Rican">Puerto Rican</Select.Option>

                                                <Select.Option value="Qatari">Qatari</Select.Option>

                                                <Select.Option value="Romanian">Romanian</Select.Option>
                                                <Select.Option value="Russian">Russian</Select.Option>
                                                <Select.Option value="Rwandan">Rwandan</Select.Option>

                                                <Select.Option value="Salvadorean">Salvadorean</Select.Option>
                                                <Select.Option value="Sammarinese">Sammarinese</Select.Option>
                                                <Select.Option value="Samoan">Samoan</Select.Option>
                                                <Select.Option value="Sao Tomean">Sao Tomean</Select.Option>
                                                <Select.Option value="Saudi Arabian">Saudi Arabian</Select.Option>
                                                <Select.Option value="Scottish">Scottish</Select.Option>
                                                <Select.Option value="Senegalese">Senegalese</Select.Option>
                                                <Select.Option value="Serbian">Serbian</Select.Option>
                                                <Select.Option value="Citizen of Seychelles">Citizen of Seychelles</Select.Option>
                                                <Select.Option value="Sierra Leonean">Sierra Leonean</Select.Option>
                                                <Select.Option value="Singaporean">Singaporean</Select.Option>
                                                <Select.Option value="Slovak">Slovak</Select.Option>
                                                <Select.Option value="Slovenian">Slovenian</Select.Option>
                                                <Select.Option value="Solomon Islander">Solomon Islander</Select.Option>
                                                <Select.Option value="Somali">Somali</Select.Option>
                                                <Select.Option value="South African">South African</Select.Option>
                                                <Select.Option value="South Korean">South Korean</Select.Option>
                                                <Select.Option value="South Sudanese">South Sudanese</Select.Option>
                                                <Select.Option value="Spanish">Spanish</Select.Option>
                                                <Select.Option value="Sri Lankan">Sri Lankan</Select.Option>
                                                <Select.Option value="St Helenian">St Helenian</Select.Option>
                                                <Select.Option value="St Lucian">St Lucian</Select.Option>
                                                <Select.Option value="Stateless">Stateless</Select.Option>
                                                <Select.Option value="Sudanese">Sudanese</Select.Option>
                                                <Select.Option value="Surinamese">Surinamese</Select.Option>
                                                <Select.Option value="Swazi">Swazi</Select.Option>
                                                <Select.Option value="Swedish">Nationality</Select.Option>
                                                <Select.Option value="Swiss">Swiss</Select.Option>
                                                <Select.Option value="Syrian">Syrian</Select.Option>

                                                <Select.Option value="Taiwanese">Taiwanese</Select.Option>
                                                <Select.Option value="Tajik">Tajik</Select.Option>
                                                <Select.Option value="Tanzanian">Tanzanian</Select.Option>
                                                <Select.Option value="Thai">Thai</Select.Option>
                                                <Select.Option value="Togolese">Togolese</Select.Option>
                                                <Select.Option value="Tongan">Tongan</Select.Option>
                                                <Select.Option value="Trinidadian">Trinidadian</Select.Option>
                                                <Select.Option value="Tristanian">Tristanian</Select.Option>
                                                <Select.Option value="Tunisian">Tunisian</Select.Option>
                                                <Select.Option value="Turkish">Turkish</Select.Option>
                                                <Select.Option value="Turkmen">Turkmen</Select.Option>
                                                <Select.Option value="Turks and Caicos Islander">Turks and Caicos Islander</Select.Option>
                                                <Select.Option value="Tuvaluan">Tuvaluan</Select.Option>

                                                <Select.Option value="Ugandan">Ugandan</Select.Option>
                                                <Select.Option value="Ukrainian">Ukrainian</Select.Option>
                                                <Select.Option value="Uruguayan">Uruguayan</Select.Option>
                                                <Select.Option value="Uzbek">Uzbek</Select.Option>

                                                <Select.Option value="Vatican Citizen">Vatican Citizen</Select.Option>
                                                <Select.Option value="Citizen of Vanuatu">Citizen of Vanuatu</Select.Option>
                                                <Select.Option value="Venezuelan">Venezuelan</Select.Option>
                                                <Select.Option value="Vietnamese">Vietnamese</Select.Option>
                                                <Select.Option value="Vincentian">Vincentian</Select.Option>

                                                <Select.Option value="Wallisian">Wallisian</Select.Option>
                                                <Select.Option value="Welsh">Welsh</Select.Option>

                                                <Select.Option value="Yemeni">Yemeni</Select.Option>

                                                <Select.Option value="Zambian">Zambian</Select.Option>
                                                <Select.Option value="Zimbabwean">Zimbabwean</Select.Option>
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                </Row>
                            </Form>
                            
                            <Space>
                                <Popconfirm title="Are you sure?" onConfirm={()=>handleDeleteGuest(viewGuestForm.getFieldValue('id'))}>
                                    <Button size="small" danger icon={<DeleteOutlined/>}>Delete guest</Button>
                                </Popconfirm>
                            </Space>
                            
                        </Modal>
                    </Col>
                    
                    <Col xl={24} xs={24}>
                        <div className="mb-4" style={{display: 'flex', justifyContent:'space-between'}}>
                            <Typography.Title level={5}>Inclusions</Typography.Title>
                            <Button onClick={()=>handleAddInclusionClick()}><PlusOutlined/> Add inclusion</Button>
                        </div>

                        {!viewBookingQuery.isLoading &&
                            <Table
                                rowKey="id"
                                dataSource={_.filter(viewBookingQuery.data.inclusions, i => i.parent_id == null)}
                                size="small"
                                expandable={{
                                    expandedRowRender: record =>
                                        <Table
                                            rowKey="id"
                                            dataSource={record.package_inclusions}
                                            size="small"
                                            columns={
                                                [
                                                    {
                                                    title: 'Inclusions',
                                                    dataIndex: 'item',
                                                    key: 'item_inclusion',
                                                    },
                                                    {
                                                        title: 'Guest',
                                                        dataIndex: 'guest',
                                                        key: 'item',
                                                        render: (text, record) => (
                                                            <>{ record.guest_id && record.guest_inclusion.first_name+' '+record.guest_inclusion.last_name}</>
                                                        )
                                                    },
                                                    {
                                                        title: 'Type',
                                                        dataIndex: 'type',
                                                        key: 'type',
                                                    },
                                                    {
                                                        title: 'Quantity',
                                                        dataIndex: 'quantity',
                                                        key: 'quantity',
                                                    },
                                                    // {
                                                    //     title: 'Walk-in Price',
                                                    //     dataIndex: 'walkin_price',
                                                    //     key: 'walkin_price',
                                                    //     render: (text, record) => {
                                                    //         return <span className="text-success">&#8369; {numberWithCommas(record.walkin_price && record.walkin_price ? record.walkin_price : '--')}</span>
                                                    //     }
                                                    // },
                                                    {
                                                        title: 'Rack Rate',
                                                        dataIndex: 'original_price',
                                                        key: 'original_price',
                                                        render: (text, record) => {
                                                            return <span>&#8369; {numberWithCommas(text)}</span>
                                                        }
                                                    },
                                                    {
                                                        title: 'Total Selling Price',
                                                        dataIndex: 'total_price',
                                                        render: (text, record) => {
                                                            return <span className="text-success">&#8369; {numberWithCommas(parseFloat(record.price) * parseInt(record.quantity))}</span>
                                                        }
                                                    },
                                                ]
                                            }
                                        />,
                                    rowExpandable: record => record.package_inclusions.length !== 0,
                                }}
                                columns={[
                                    {
                                        title: 'Item',
                                        dataIndex: 'item',
                                        key: 'item',
                                    },
                                    {
                                        title: 'Guest',
                                        dataIndex: 'guest',
                                        key: 'item',
                                        render: (text, record) => (
                                            <>{ record.guest_id && record.guest_inclusion.first_name+' '+record.guest_inclusion.last_name}</>
                                        )
                                    },
                                    {
                                        title: 'Type',
                                        dataIndex: 'type',
                                        key: 'type',
                                    },
                                    {
                                        title: 'Quantity',
                                        dataIndex: 'quantity',
                                        key: 'quantity',
                                    },
                                    {
                                        title: 'Rack Rate',
                                        dataIndex: 'original_price',
                                        key: 'original_price',
                                        render: (text, record) => {
                                            return <span>&#8369; {numberWithCommas(text)}</span>
                                        }
                                    },
                                    {
                                        title: 'Walk-in Price',
                                        dataIndex: 'walkin_price',
                                        key: 'walkin_price',
                                        render: (text, record) => {
                                            return <span className="text-success">&#8369; {numberWithCommas(record.walkin_price && record.walkin_price ? record.walkin_price : '0.00')}</span>
                                        }
                                    },
                                    {
                                        title: 'Price',
                                        key: 'selling_price',
                                        render: (text, record) => {
                                            return <span className="text-success">&#8369; {numberWithCommas(record.selling_price && record.selling_price ? record.selling_price : '0.00')} {record.selling_price_type}</span>
                                        }
                                    },
                                    {
                                        title: 'Total',
                                        dataIndex: 'total_price',
                                        render: (text, record) => {
                                            return <span className="text-success">&#8369; {numberWithCommas(parseFloat(record.price) * parseInt(record.quantity))}</span>
                                        }
                                    },
                                    {
                                        title: 'Action',
                                        dataIndex: 'action',
                                        key: 'action',
                                        // render: () => (<Button onClick={()=>message.info("This feature will be here soon...")} icon={<EditOutlined/>} />)
                                        render: (text, record) => {
                                            return <>
                                                { !record.deleted_at ?
                                                    <Popconfirm
                                                        title="Are you sure to delete this inclusion?"
                                                        onConfirm={()=>handleRemoveInclusion(record.id)}
                                                        okText="Yes"
                                                        cancelText="No"
                                                    >
                                                        <Button size="small" type="danger" icon={<DeleteOutlined />}/>
                                                    </Popconfirm>
                                                    : <Tag>deleted by {record.deleted_by_user.first_name}</Tag>
                                                }
                                            </>
                                        }
                                    },
                                ]}
                                footer={(currentPageData) => {
                                    // console.log(currentPageData);
                                    return (
                                        // <div style={{textAlign:'right'}}>Total: <span className="text-success">&#8369; {numberWithCommas(item.grand_total)}</span></div>
                                        <div style={{textAlign:'right'}}><Typography.Title level={5}>Grand Total: <span className="text-success">&#8369; {numberWithCommas(_.sumBy(viewBookingQuery.data && viewBookingQuery.data.invoices, item => Number((item.status != 'void' ? item.grand_total : 0) || 0)).toFixed(2))}</span></Typography.Title></div>
                                    )
                                }}
                            />
                        }
                    </Col>
                </Row>

            </Form>

            <div>
                <Drawer
                    visible={addNoteDrawerVisible}
                    onClose={()=>setaddNoteDrawerVisible(false)}
                    width={500}
                    title="New note"
                    >
                        <Form layout="vertical" form={newNoteForm} onFinish={onNewNoteFormFinish}>
                            <Form.Item name="message" label="Message" rules={[{
                                required: true
                            }]}>
                                <Input.TextArea rows={10} placeholder="Write your message / note here..." style={{borderRadius: '12px'}} />
                            </Form.Item>
                            <Button htmlType="submit">Save</Button>
                        </Form>
                </Drawer>

                    {
                        (viewBookingQuery.data && viewBookingQuery.data.room_reservations_no_filter && viewBookingQuery.data.type == 'ON') ?
                        <>
                            
                            <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center'}}>
                                <Typography.Title className="mb-4" level={5}>Room reservations ({viewBookingQuery.data.room_reservations_no_filter.length})</Typography.Title>
                                <Button onClick={()=>handleAddRoomClick()}><PlusOutlined/> Add room</Button>
                            </div>
                            <Row gutter={[8,8]}>
                                {
                                    viewBookingQuery.data.room_reservations_no_filter.map( (item, key) => {
                                        return <Col xs={4} key={key} style={{position:'relative'}}>
                                            <Space direction="vertical">
                                                {
                                                    (item.room_type.images && item.room_type.images.length > 0) &&
                                                    <div>
                                                        <img style={{width:'100%', borderRadius: 12}} src={item.room_type.images[0].image_path} />
                                                    </div>
                                                }
                                                <strong>Room {item.room.number}
                                                {/* ({fo_status[item.room.fo_status ? item.room.fo_status : 'none']}{room_status[item.room.room_status ? item.room.room_status : 'none']}) */}
                                                </strong>
                                                <strong>{item.room_type.property_id == '1' ? 'AF' : '' || item.room_type.property_id == '2' ? 'SANDS' : '' || item.room_type.property_id == '3' ? 'BV' : '' || item.room_type.property_id == '4' ? 'GH' : ''} {item.room_type.name}</strong>
                                                <small><CalendarOutlined/> {moment(item.start_datetime).format('YYYY-MM-DD')} ~ {moment(item.end_datetime).format('YYYY-MM-DD')}</small>

                                                <div style={{ textAlign: 'center', padding:4, borderRadius: 8, border:'solid 1px gainsboro'}}>
                                                    <small><CalendarOutlined/> Check-in: <br/>
                                                        {item.check_in_time ? <DatePicker onChange={e => handleUpdateCheckinTime(item.id, moment(e).format('YYYY-MM-DD HH:mm:ss'))} disabled={item.status != 'checked_in' || viewBookingQuery.isFetching} allowClear={false} showTime value={moment(item.check_in_time)} /> : ''}
                                                    </small>
                                                </div>
                                                <div style={{ textAlign: 'center', padding:4, borderRadius: 8, border:'solid 1px gainsboro'}}><small><CalendarOutlined/> Check-out: <br/>{item.check_out_time ? moment(item.check_out_time).format('YYYY-MM-DD h:mm:ss a') : ''}</small></div>
                                            </Space>
                                        </Col>
                                    })
                                }
                            </Row>
                        </>
                        :''

                    }
                
            </div>

            <Divider/>

            <div className="mt-5">
                <Toolbar/>
            </div>
        </div>

    )
}

export default Page;