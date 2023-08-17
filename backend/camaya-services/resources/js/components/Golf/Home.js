import React from 'react'
import moment from 'moment-timezone'
import GolfService from 'services/Golf/GolfService'
import ViewBookingComponent from 'components/Booking/View'
import Loading from 'common/Loading'

import ReactExport from "react-export-excel";
const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;

import { Row, Col, Card, Statistic, Button, message, Space, Table, Typography, Input, Tag, DatePicker, Modal } from 'antd'

import { PrinterOutlined } from '@ant-design/icons'


export default function Page(props) {

    const guestStatusColor = {
        arriving: 'text-primary',
        checked_in: 'text-success',
        no_show: 'text-warning',
        cancelled: 'text-danger',
    }

    // States
    const [searchString, setSearchString] = React.useState(null);
    const [selectedDate, setselectedDate] = React.useState(moment());
    const [bookingToView, setbookingToView] = React.useState(null);
    const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);

    const arrivalSummaryQuery = GolfService.arrivalSummary(selectedDate);

    React.useEffect(()=> {
        // console.log(arrivalSummaryQuery.data);
        arrivalSummaryQuery.refetch();
    },[selectedDate]);

    React.useEffect(()=> {
        if (bookingToView) {
            setviewBookingModalVisible(true);
        }
    },[bookingToView]);

    if (arrivalSummaryQuery.isLoading) {
        return <Loading/>;
    }

    const handleSearch = (search) => {
        setSearchString(search.toLowerCase());
    }

    return (
        <Row gutter={[48,48]} className="mt-4">
            <Col xl={24}>
                Jump to date: <DatePicker allowClear={false} value={selectedDate} onChange={(e)=>setselectedDate(e)} className="mx-2" />
            </Col>
            <Col xl={24}>
            <Typography.Title level={5}>
                    Golf arrival guests list
                    <ExcelFile filename={`Golf_arrival_guests_today_${moment().format('YYYY-MM-DD HH:mm:ss')}`} element={<Button className="ml-2" size="small"><PrinterOutlined/></Button>}>
                        <ExcelSheet data={arrivalSummaryQuery.data} name="Arrival_guests_today">
                            <ExcelColumn label="Booking ref #" value="booking_reference_number"/>
                            <ExcelColumn label="Booking type" value={ col => col.booking.type == 'DT' ? 'Day tour' : 'Overnight'}/>
                            <ExcelColumn label="Tee time" value={ col => col.tee_time &&
                                                    col.tee_time.map( (item, key) => {
                                                        return moment(moment(item.schedule.date).format('YYYY-MM-DD')+' '+item.schedule.time).format('D MMM YYYY h:mm A')
                                                    }).join(', ')}/>
                            <ExcelColumn label="Booking status" value={ col => col.booking.status}/>
                            <ExcelColumn label="Primary customer" value={ col => `${col.booking.customer.first_name} ${col.booking.customer.last_name} ${col.booking.customer.user_type || ''}`}/>
                            <ExcelColumn label="Guest name" value={col => `${col.first_name} ${col.last_name}` }/>
                            <ExcelColumn label="Guest age" value="age"/>
                            <ExcelColumn label="Guest type" value="type"/>
                            <ExcelColumn label="Mode of transportation" value={ col => (!_.find(col.active_trips, i => i.destination_code == 'CMY' || i.destination_code == 'FTT') && col.booking.mode_of_transportation == 'camaya_transportation') ? 'undecided' : col.booking.mode_of_transportation}/>
                            <ExcelColumn label="Vehicle details" value={ col => col.booking.guest_vehicles.map(elem => `(${elem.model}, ${elem.plate_number})`).join(', ')}/>
                        </ExcelSheet>
                    </ExcelFile>                
                </Typography.Title>
                <Input style={{width: 500}} type="text" placeholder="Search by guest name, booking ref #, guest ref #" size="large" className="ml-2 my-3" onChange={(e) => handleSearch(e.target.value)} />
                <Table
                    dataSource={
                        arrivalSummaryQuery.data &&
                        arrivalSummaryQuery.data
                        .filter(item => {
                            if (item && searchString) {
                                const searchValue =  item.first_name.toLowerCase() + ' ' + item.last_name.toLowerCase() + ' ' + item.reference_number.toLowerCase() + ' ' + item.booking_reference_number.toLowerCase();
                                return searchString ? searchValue.indexOf(searchString) !== -1 : true;
                            }
                            return true;
                        })
                    }
                    scroll={{
                        x: '110vw'
                    }}
                    size="small"
                    rowKey="reference_number"
                    columns={[
                        {
                            title: '#',
                            render: (text, record, index) => (index+1)
                        },
                        {
                            title: 'Booking ref #',
                            dataIndex: 'booking_reference_number',
                            key: 'booking_reference_number',
                            render: (text, record) => <Button type="link" onClick={()=>setbookingToView(record.booking_reference_number)}>{text}</Button>
                        },
                        {
                            title: 'Booking type',
                            render: (text, record) => (record.booking.type == 'DT' ? 'Day tour' : 'Overnight'),
                            filters: [
                                { text: 'Day tour', value: 'DT' },
                                { text: 'Overnight', value: 'ON' },
                            ],
                            defaultFilteredValue: ['DT', 'ON'],
                            onFilter: (value, record) => record.booking.type.includes(value),
                        },
                        {
                            title: 'Booking status',
                            render: (text, record) => record.booking.status,
                            filters: [
                                { text: 'pending', value: 'pending' },
                                { text: 'confirmed', value: 'confirmed' },
                                { text: 'cancelled', value: 'cancelled' },
                            ],
                            defaultFilteredValue: ['pending', 'confirmed'],
                            onFilter: (value, record) => record.booking.status.includes(value),
                        },
                        {
                            title: 'Tags',
                            render: (text, record) => record.booking.tags.map((i,key) => <Tag key={key}><small>{i.name}</small></Tag>),
                        },
                        {
                            title: 'Primary customer',
                            render: (text, record) => <>{record.booking.customer.first_name} {record.booking.customer.last_name} {record.booking.customer.user_type ? <Tag>{record.booking.customer.user_type}</Tag> : ''}</>
                        },
                        {
                            title: 'Tee time',
                            render: (text, record) => <>{record.tee_time &&
                                                    record.tee_time.map( (item, key) => {
                                                        return <Tag key={key}>{moment(moment(item.schedule.date).format('YYYY-MM-DD')+' '+item.schedule.time).format('D MMM YYYY h:mm A')}</Tag>
                                                    })}</>
                        },
                        {
                            title: 'Guest status',
                            render: (text, record) => <span className={guestStatusColor[record.status]}>{record.status}</span>,
                            filters: [
                                { text: 'Arriving', value: 'arriving' },
                                { text: 'Checked-in', value: 'checked_in' },
                                { text: 'No show', value: 'no_show' },
                                { text: 'Cancelled', value: 'cancelled' },
                            ],
                            defaultFilteredValue: ['arriving', 'checked_in'],
                            onFilter: (value, record) => record.status.includes(value),
                        },
                        {
                            title: 'Guest reference number',
                            render: (text, record) => record.reference_number
                        },
                        {
                            title: 'Guest name',
                            render: (text, record) => <strong>{record.first_name} {record.last_name}</strong>
                        },
                        {
                            title: 'Guest age',
                            render: (text, record) => record.age
                        },
                        {
                            title: 'Guest type',
                            render: (text, record) => record.type,
                            filters: [
                                { text: 'Adult', value: 'adult' },
                                { text: 'Kid', value: 'kid' },
                                { text: 'Infant', value: 'infant' },
                            ],
                            defaultFilteredValue: ['adult', 'kid', 'infant'],
                            onFilter: (value, record) => record.type.includes(value),
                        },
                        {
                            title: 'Mode of transportation',
                            render: (text, record) => (!_.find(record.active_trips, i => i.destination_code == 'CMY' || i.destination_code == 'FTT') && record.booking.mode_of_transportation == 'camaya_transportation') ? 'undecided' : record.booking.mode_of_transportation,
                            filters: [
                                { text: 'Own vehicle', value: 'own_vehicle' },
                                { text: 'Van rental', value: 'van_rental' },
                                { text: 'Undecided', value: 'undecided' },
                                { text: 'Company vehicle', value: 'company_vehicle' },
                                { text: 'Camaya vehicle', value: 'camaya_vehicle' },
                                { text: 'Camaya transportation', value: 'camaya_transportation' },
                            ],
                            defaultFilteredValue: ['own_vehicle', 'van_rental', 'undecided', 'company_vehicle', 'camaya_vehicle', 'camaya_transportation'],
                            onFilter: (value, record) => record.booking.mode_of_transportation.includes(value),
                        },
                        {
                            title: 'Vehicle details',
                            render: (text, record) => record.booking.guest_vehicles.map(elem => `(${elem.model},${elem.plate_number})`).join(', ')
                        },
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
            </Col>
        </Row>
    )
}