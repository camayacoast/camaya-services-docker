import React from 'react'
import moment from 'moment'
import DashboardService from 'services/Booking/DashboardService'
import Loading from 'common/Loading'
import ViewBookingComponent from 'components/Booking/View'

import ReactExport from "react-export-excel";
const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;

import { Row, Col, Card, Statistic, Button, Table, Typography, Input, Tag, DatePicker, Modal } from 'antd'

import { PrinterOutlined, LoadingOutlined } from '@ant-design/icons'


export default function Page(props) {

    const guestStatusColor = {
        arriving: 'text-primary',
        on_premise: 'text-success',
        checked_in: 'text-success',
        no_show: 'text-warning',
        booking_cancelled: 'text-danger',
    }

    // States
    const [searchString, setSearchString] = React.useState(null);
    const [selectedDate, setselectedDate] = React.useState(moment());
    const [bookingToView, setbookingToView] = React.useState(null);
    const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);

    const dashboardDataQuery = DashboardService.conciergeData(selectedDate);

    React.useEffect(()=> {
        // console.log(dashboardDataQuery.data);
        if (selectedDate) {
            dashboardDataQuery.refetch();
        }
    },[selectedDate]);

    React.useEffect(()=> {
        if (bookingToView) {
            setviewBookingModalVisible(true);
        }
    },[bookingToView]);

    if (dashboardDataQuery.isLoading) {
        return <Loading/>;
    }

    const handleSearch = (search) => {
        setSearchString(search.toLowerCase());
    }

    return (
        <Row gutter={[48,48]} className="mt-4">
            <Col xl={24}>
                Jump to date: <DatePicker allowClear={false} value={selectedDate} onChange={(e)=>setselectedDate(e)} className="mx-2" />
                {
                    dashboardDataQuery.isFetching &&
                    <><LoadingOutlined className="ml-2" /> Loading data. Please wait...</>
                }
            </Col>

            <Col xl={18} lg={32} md={24}>
                <Card
                    title="Guests Arrivals" 
                    headStyle={{background:'#1177fa', color: 'white'}}
                    size="large" bordered={false}
                    className="card-shadow"
                    >
                    <Row gutter={[16,16]}>
                        {/* <Col xl={4} lg={6} md={6}>
                            <Card><Statistic title="Total Bookings" value={dashboardDataQuery.data && dashboardDataQuery.data.arriving_guests ? dashboardDataQuery.data.arriving_guests : 0}/></Card>
                        </Col> */}
                        {
                            dashboardDataQuery.isFetching || dashboardDataQuery.isLoading ?
                            <Col xl={24} lg={24} md={24}>
                                <LoadingOutlined className="mr-2" />Loading...
                            </Col>
                            :
                            <>
                                <Col xl={4} lg={6} md={6}>
                                    <Card><Statistic title="Total Guests" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_active ? dashboardDataQuery.data.guest_status_active : 0} /></Card>
                                </Col>
                                <Col xl={4} lg={6} md={6}>
                                    <Card><Statistic title="Arriving" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_arriving ? dashboardDataQuery.data.guest_status_arriving : 0} /></Card>
                                </Col>
                                <Col xl={4} lg={6} md={6}>
                                    <Card><Statistic title="On Premise" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_onpremise ? dashboardDataQuery.data.guest_status_onpremise : 0} /></Card>
                                </Col>
                                <Col xl={4} lg={6} md={6}>
                                    <Card><Statistic title="Checked-in" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_checkedin ? dashboardDataQuery.data.guest_status_checkedin : 0} /></Card>
                                </Col>
                                <Col xl={4} lg={6} md={6}>
                                    <Card><Statistic title="No Show" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_noshow ? dashboardDataQuery.data.guest_status_noshow : 0} /></Card>
                                </Col>
                                <Col xl={4} lg={6} md={6}>
                                    <Card><Statistic title="Cancelled" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_cancelled ? dashboardDataQuery.data.guest_status_cancelled : 0} /></Card>
                                </Col>
                            </>
                        }
                    </Row>
                </Card>
            </Col>
                

            <Col xl={24}>
                <Typography.Title level={5}>
                    
                    Arrival guests list
                    <ExcelFile filename={`Arrival_Guests_Report_${moment(selectedDate).format('YYYY-MMM-DD HH:mm:ss')}`} element={<Button className="ml-2" size="small"><PrinterOutlined/></Button>}>
                        <ExcelSheet data={dashboardDataQuery.data.arriving_guests} name="arrival_guests_today">
                            <ExcelColumn label="Booking Ref #" value="booking_reference_number"/>
                            <ExcelColumn label="Booking Type" value={ col => col.booking.type == 'DT' ? 'Day tour' : 'Overnight'}/>
                            <ExcelColumn label="Booking Status" value={ col => col.booking.status}/>
                            <ExcelColumn label="Booking Tags" value={ col => (col.booking.tags ? col.booking.tags : []).map(elem => `${elem.name}`).join(', ')}/>
                            {/* <ExcelColumn label="Sales Director" value={ col => ( col.booking.booked_by?.user_type == 'agent' ? (col.booking.booked_by?.parent_team?.teamowner ? col.booking.booked_by?.parent_team?.teamowner?.first_name + ' ' + col.booking.booked_by?.parent_team?.teamowner?.last_name : '') : (col.booking.customer?.user?.parent_team?.teamowner ? col.booking.customer?.user?.parent_team?.teamowner?.first_name + ' ' + col.booking.customer?.user?.parent_team?.teamowner?.last_name : '') ) }/> */}
                            
                            <ExcelColumn label="Sales Director" value={ col => ( col.booking.booked_by?.user_type == 'agent' ? (col.booking.booked_by?.parent_team?.team?.teamowner ? col.booking.booked_by?.parent_team?.team?.teamowner?.first_name + ' ' + col.booking.booked_by?.parent_team?.team?.teamowner?.last_name : '') : (col.booking.sales_director_id ? col.booking.sales_director?.first_name+" "+col.booking.sales_director?.last_name : '') ) }/>

                            <ExcelColumn label="Booked By" value={ col => col.booking.booked_by ? col.booking.booked_by?.first_name + ' ' + col.booking.booked_by?.last_name : col.booking.customer?.first_name + ' ' + col.booking.customer?.last_name}/>
                            <ExcelColumn label="Guest Tags" value={ col => (col.guest_tags ? col.guest_tags : []).map(elem => `${elem.name}`).join(', ')}/>
                            <ExcelColumn label="Guest Status" value="status"/>
                            <ExcelColumn label="Guest Ref #" value="reference_number"/>
                            <ExcelColumn label="Primary Customer" value={ col => `${col.booking.customer.first_name} ${col.booking.customer.last_name} ${col.booking.customer.user_type || ''}`}/>
                            <ExcelColumn label="Guest Name" value={col => `${col.first_name} ${col.last_name}` }/>
                            <ExcelColumn label="Guest Age" value="age"/>
                            <ExcelColumn label="Nationality" value="nationality"/>
                            <ExcelColumn label="Guest Type" value="type"/>
                            <ExcelColumn label="Mode of Transportation" value={ col => (!_.find(col.active_trips, i => i.destination_code == 'CMY' || i.destination_code == 'FTT') && col.booking.mode_of_transportation == 'camaya_transportation') ? 'undecided' : col.booking.mode_of_transportation}/>
                            <ExcelColumn label="Vehicle Details" value={ col => col.booking.guest_vehicles.map(elem => `${elem.model} - ${elem.plate_number}`).join(', ')}/>
                            <ExcelColumn label="Notes" value={ col => (col.booking.notes ? col.booking.notes : []).map(elem => `${elem.message}`).join(', ')}/>
                        </ExcelSheet>
                    </ExcelFile>  

                </Typography.Title>
                <Input style={{width: 500}} type="text" placeholder="Search by guest name, booking ref #, guest ref #" size="large" className="ml-2 my-3" onChange={(e) => handleSearch(e.target.value)} />
                <Table
                    loading={dashboardDataQuery.isFetching || dashboardDataQuery.isLoading}
                    dataSource={
                        dashboardDataQuery.data.arriving_guests
                        .filter(item => {
                            if (item && searchString) {
                                const searchValue =  item.first_name.toLowerCase() + ' ' + item.last_name.toLowerCase() + ' ' + item.reference_number.toLowerCase() + ' ' + item.booking_reference_number.toLowerCase();
                                return searchString ? searchValue.indexOf(searchString) !== -1 : true;
                            }
                            return true;
                        })
                    }
                    scroll={{
                        x: '200vw'
                    }}
                    size="small"
                    rowKey="reference_number"
                    columns={[
                        {
                            title: '#',
                            render: (text, record, index) => (index+1)
                        },
                        {
                            title: 'Booking Ref #',
                            dataIndex: 'booking_reference_number',
                            key: 'booking_reference_number',
                            render: (text, record) => <Button type="link" onClick={()=>setbookingToView(record.booking_reference_number)}>{text}</Button>
                        },
                        {
                            title: 'Booking Type',
                            render: (text, record) => (record.booking.type == 'DT' ? 'Day tour' : 'Overnight'),
                            filters: [
                                { text: 'Day tour', value: 'DT' },
                                { text: 'Overnight', value: 'ON' },
                            ],
                            defaultFilteredValue: ['DT', 'ON'],
                            onFilter: (value, record) => record.booking.type.includes(value),
                        },
                        {
                            title: 'Booking Status',
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
                            title: 'Booking Tags',
                            render: (text, record) => record.booking.tags.map((i,key) => <Tag key={key}><small>{i.name}</small></Tag>),
                        },
                        {
                            title: 'Sales Director',
                            render: (text, record) => <>
                                { record.booking.booked_by?.user_type == 'agent' ?
                                    <>  
                                        {
                                            record.booking.booked_by?.parent_team?.team?.teamowner ? record.booking.booked_by?.parent_team?.team?.teamowner?.first_name + ' ' + record.booking.booked_by?.parent_team?.team?.teamowner?.last_name : ''
                                        }
                                    </> 
                                    :
                                    <>
                                        {
                                            // record.booking.customer?.user?.parent_team?.teamowner ? record.booking.customer?.user?.parent_team?.teamowner?.first_name + ' ' + record.booking.customer?.user?.parent_team?.teamowner?.last_name : ''

                                            record.booking.sales_director_id ? record.booking.sales_director?.first_name+" "+record.booking.sales_director?.last_name : ''
                                        }
                                    </>
                                }
                            </>
                        },
                        {
                            title: 'Booked By',
                            render: (text, record) => <>
                                { record.booking.booked_by ?
                                    <>
                                        {record.booking.booked_by.first_name} {record.booking.booked_by.last_name} { record.booking.booked_by.user_type ? <Tag className="ml-2">{record.booking.booked_by.user_type}</Tag> :  ''}
                                    </> :
                                    <>
                                        {record.booking.customer.first_name} {record.booking.customer.last_name} { record.booking.customer.user_type ? <Tag className="ml-2">{record.booking.customer.user_type}</Tag> :  ''}
                                    </>
                                }
                            </>
                        },
                        {
                            title: 'Guest Tags',
                            render: (text, record) => record.guest_tags.map((i,key) => <Tag key={key}><small>{i.name}</small></Tag>),
                        },
                        {
                            title: 'Primary Customer',
                            render: (text, record) => <>{record.booking.customer.first_name} {record.booking.customer.last_name} {record.booking.customer.user_type ? <Tag>{record.booking.customer.user_type}</Tag> : ''}</>
                        },
                        {
                            title: 'Guest Status',
                            render: (text, record) => <span className={guestStatusColor[record.status]}>{record.status}</span>,
                            filters: [
                                { text: 'Arriving', value: 'arriving' },
                                { text: 'On Premise', value: 'on_premise' },
                                { text: 'Checked-in', value: 'checked_in' },
                                { text: 'No show', value: 'no_show' },
                                { text: 'Cancelled', value: 'booking_cancelled' },
                            ],
                            defaultFilteredValue: ['arriving', 'on_premise', 'checked_in'],
                            onFilter: (value, record) => record.status.includes(value),
                        },
                        {
                            title: 'Guest Ref #',
                            render: (text, record) => record.reference_number
                        },
                        {
                            title: 'Guest Name',
                            render: (text, record) => <strong>{record.first_name} {record.last_name}</strong>
                        },
                        {
                            title: 'Guest Age',
                            render: (text, record) => record.age
                        },
                        // {
                        //     title: 'Nationality',
                        //     render: (text, record) => record.nationality
                        // },
                        {
                            title: 'Guest Type',
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
                            title: 'Mode of Transportation',
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
                            title: 'Vehicle Details',
                            render: (text, record) => record.booking.guest_vehicles.map(elem => `${elem.model} - ${elem.plate_number}`).join(', ')
                        },
                        {
                            title: 'Notes',
                            render: (text, record) => record.booking.notes.map((i,key) => <span key={key}>{i.message}<br></br></span>)
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