import React from 'react'
import moment from 'moment-timezone'
import DashboardService from 'services/Booking/DashboardService'
import Loading from 'common/Loading'
import ViewBookingComponent from 'components/Booking/View'

import ReactExport from "react-export-excel";
const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;

const numberWithCommas = (x) => {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

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
    const [startDate, setStartDate] = React.useState(moment());
    const [endDate, setEndDate] = React.useState(moment());
    const [bookingToView, setbookingToView] = React.useState(null);
    const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);

    const dashboardDataQuery = DashboardService.dttRevenueReport(startDate, endDate);

    React.useEffect(()=> {
        // console.log(dashboardDataQuery.data);
        dashboardDataQuery.refetch();
    },[startDate, endDate]);

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

    const handleDateRangeChange = (dates) => {
        console.log(dates);

        if (dates && dates.length == 2) {
            setStartDate(dates[0]);
            setEndDate(dates[1]);
        }
    }

    return (
        <>
            <Typography.Title level={4}>DTT Revenue Report</Typography.Title>
            
            <Row gutter={[48,48]} className="mt-4">
                <Col xl={24}>
                    Jump to date: <DatePicker.RangePicker onChange={handleDateRangeChange} value={[startDate, endDate]} className="ml-2"/>
                </Col>

                <Col xl={6}>
                    <Card
                        title="DTT Guests Arrivals" 
                        headStyle={{background:'#1177fa', color: 'white'}}
                        size="large" bordered={false}
                        className="card-shadow"
                        >
                        <Row gutter={[16,16]}>
                            <Col xl={24}>
                                <Card><Statistic title="Arrival Guests" value={dashboardDataQuery.data && dashboardDataQuery.data.dtt_arriving_guests ? dashboardDataQuery.data.dtt_arriving_guests.length : 0} />
                                </Card>
                            </Col>
                        </Row>
                    </Card>
                </Col>
                
                <Col xl={24}>
                    <Typography.Title level={5}>
                        DTT Arrival Guests List
                        <ExcelFile filename={`DTT_Revenue_Report_${moment().format('YYYY-MM-DD HH:mm:ss')}`} element={<Button className="ml-2" size="small"><PrinterOutlined/></Button>}>
                            <ExcelSheet data={dashboardDataQuery.data.dtt_arriving_guests} name="dtt_arrival_guests">
                                <ExcelColumn label="Booking Ref #" value="booking_reference_number"/>
                                <ExcelColumn label="Booking Type" value={ col => col.booking.type == 'DT' ? 'Day tour' : 'Overnight'}/>
                                <ExcelColumn label="Booking Status" value={ col => col.booking.status}/>
                                <ExcelColumn label="Booking Tags" value={ col => (col.booking.tags ? col.booking.tags : []).map(elem => `${elem.name}`).join(', ')}/>
                                <ExcelColumn label="Guest Tags" value={ col => (col.guest_tags ? col.guest_tags : []).map(elem => `${elem.name}`).join(', ')}/>
                                <ExcelColumn label="Primary Customer" value={ col => `${col.booking.customer.first_name} ${col.booking.customer.last_name} ${col.booking.customer.user_type || ''}`}/>
                                <ExcelColumn label="Guest Name" value={col => `${col.first_name} ${col.last_name}` }/>
                                <ExcelColumn label="Guest Age" value="age"/>
                                <ExcelColumn label="Guest Type" value="type"/>
                                <ExcelColumn label="Mode of Transportation" value={ col => (!_.find(col.active_trips, i => i.destination_code == 'CMY' || i.destination_code == 'FTT') && col.booking.mode_of_transportation == 'camaya_transportation') ? 'undecided' : col.booking.mode_of_transportation}/>
                                <ExcelColumn label="Vehicle Details" value={ col => col.booking.guest_vehicles.map(elem => `${elem.model} - ${elem.plate_number}`).join(', ')}/>
                                <ExcelColumn label="Mode of Payment" value={ col => col.booking.booking_payments.map(item => item.mode_of_payment == "online_payment" ? item.provider : item.mode_of_payment).join(", ")}/>
                                <ExcelColumn label="Total" value=
                                    { 
                                        col => numberWithCommas('₱ '+_.sum(col.booking.booking_payments.map(item => item.status == 'confirmed' ? parseFloat(item.amount) : 0 )))
                                    }
                                />
                            </ExcelSheet>
                        </ExcelFile> 
                    </Typography.Title>

                    <Input style={{width: 500}} type="text" placeholder="Search by guest name, booking ref #, guest ref #" size="large" className="ml-2 my-3" onChange={(e) => handleSearch(e.target.value)} />
                    <Table
                        dataSource={
                            dashboardDataQuery.data && dashboardDataQuery.data.dtt_arriving_guests ? 
                            dashboardDataQuery.data.dtt_arriving_guests
                            .filter(item => {
                                if (item && searchString) {
                                    const searchValue =  item.first_name.toLowerCase() + ' ' + item.last_name.toLowerCase() + ' ' + item.reference_number.toLowerCase() + ' ' + item.booking_reference_number.toLowerCase();
                                    return searchString ? searchValue.indexOf(searchString) !== -1 : true;
                                }
                                return true;
                            })
                            :
                            []
                        }
                        scroll={{
                            x: '150vw'
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
                                render: (text, record) => (record.booking.type == 'DT' ? 'Day Tour' : 'Overnight'),
                                // filters: [
                                //     { text: 'Day tour', value: 'DT' },
                                //     { text: 'Overnight', value: 'ON' },
                                // ],
                                defaultFilteredValue: ['DT'],
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
                                title: 'Guest Tags',
                                render: (text, record) => record.guest_tags.map((i,key) => <Tag key={key}><small>{i.name}</small></Tag>),
                            },
                            {
                                title: 'Primary customer',
                                render: (text, record) => <>{record.booking.customer.first_name} {record.booking.customer.last_name} {record.booking.customer.user_type ? <Tag>{record.booking.customer.user_type}</Tag> : ''}</>
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
                                title: 'Mode of payment',
                                // render: (text, record) => record.booking.mode_of_payment
    
                                // render: (text, record) => <><span className="mr-2">{record.booking.mode_of_payment}</span>
                                //     {record.booking.booking_payments && record.booking.booking_payments.map(item => item.mode_of_payment).join(", ")}
                                // </>
    
                                render: (text, record) => <>{record.booking.booking_payments && record.booking.booking_payments.map(item => item.mode_of_payment == "online_payment" ? item.provider : item.mode_of_payment ).join(", ")}
                                </>
    
                                // render: (text, record) => <><span className="mr-2">{record.booking.booking_payments.map(item => item.mode_of_payment)}</span>
                                // </>
                            },
                            // {
                            //     title: 'Grand Total',
                            //     render: (text, record) => <>{record.booking.invoices.map( item => numberWithCommas(_.sumBy(Number((item.status != 'void' ? item.grand_total : 0) || 0)).toFixed(2)))}
                            //     </>

                            //     render: (text, record) => <>{record.booking.invoices.map( item => numberWithCommas('₱ '+item.grand_total))}
                            //     </>
                            // },
                            {
                                title: 'Amount Paid',
                                render: (text, record) => numberWithCommas('₱ '+_.sum(record.booking.booking_payments.map(item => {
                                                return item.status == 'confirmed' ? parseFloat(item.amount) : 0;
                                            }
                                        )))
                            },
                            // {
                            //     title: 'Balance',
                            //     render: (text, record) => <>{record.booking.invoices.map( item => numberWithCommas('₱ '+item.balance))}
                            //     </>
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
                </Col>
            </Row>
        </>
    )
}