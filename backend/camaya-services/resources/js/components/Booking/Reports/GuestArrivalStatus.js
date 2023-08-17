import React from 'react'
import moment from 'moment-timezone'
import DashboardService from 'services/Booking/DashboardService'
import Loading from 'common/Loading'
import ViewBookingComponent from 'components/Booking/View'
import { Row, Col, Card, Statistic, Button, message, Space, Table, Typography, Input, Tag, DatePicker, Modal } from 'antd'
import { PrinterOutlined } from '@ant-design/icons'
import ReactExport from "react-export-excel";

const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;

const numberWithCommas = (x) => {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

export default function Page(props) {

    const guestStatusColor = {
        arriving: 'text-primary',
        on_premise: 'text-success',
        checked_in: 'text-success',
        no_show: 'text-warning',
        booking_cancelled: 'text-danger',
    }

    const market_segmentation = { 'Commercial':'BPO',
        'Commercial-Promo':'BPO',
        'Commercial - Promo (Save Now, Travel Later)':'BPO', 
        'Commercial - Promo (Save Now Travel Later)':'BPO', 
        'Commercial - Promo (Luventure)':'BPO',
        'Commercial - Promo':'BPO',
        'Commercial - PROMO':'BPO',
        'Commercial - Promo (Camaya Summer)':'BPO',
        'Commercial - Promo 12.12':'BPO',
        'Commercial - Promo (12.12)':'BPO',
        'Commercial - Golf':'BPO',
        'Commercial - Website':'BPO',
        'Commercial - Walk-in':'BPO',
        'Commercial - Admin':'BPO',
        'Commercial (Admin)':'BPO',
        'Commercial (Website)':'BPO',
        'Commercial - Corre':'BPO',
        'ESLCC - Employee':'BPO',
        'ESLCC - Employee / Guest':'BPO',
        'ESLCC - Employee/Guest':'BPO',
        'ESLCC-Events/Guests':'BPO',
        'ESLCC - Events/Guests':'BPO',
        'ESLCC - Event/Guest':'BPO',
        'ESLCC - Guest':'BPO',
        'ESLCC - AFV':'BPO',
        'ESLCC - CSV':'BPO',
        'ESLCC - GC':'BPO',
        'ESLCC - HOA':'BPO',
        'ESLCC FOC':'BPO',
        'ESLCC - FOC':'BPO',
        'ESLCC GUEST':'BPO',
        'ESLCC-GUEST':'BPO',
        'ESLCC- EVENTS/ GUESTS':'BPO',
        'ESLCC-EVENTS/ GUESTS':'BPO',
        'ESTLC-Guest':'BPO',
        'ESTLC - Guest':'BPO',
        'ESTLC - Employee':'BPO',
        'ESTLC - Event/Guest':'BPO',
        'DEV 1':'BPO',
        'DEV 1 - Employee':'BPO',
        'DEV1 - Employee':'BPO',
        'DEV1 - Events/Guests':'BPO',
        'DEV1 - Event/Guest':'BPO',
        'SLA - Employee'  :'BPO',
        'SLA - Events/Guests':'BPO',
        'SLA - Event/Guest':'BPO',
        'Magic Leaf - Employee':'BPO',
        'Magic Leaf - Events/Guests' :'BPO',
        'Magic Leaf - Event/Guest' :'BPO',
        '1Bataan ITS - Employee':'BPO',
        '1Bataan ITS - Events/Guests':'BPO',
        'ESTVC - Employee':'BPO',
        'ESTVC-GUEST':'BPO',
        'ESTVC - Events/Guests':'BPO',
        'ESTVC-Guest/Events':'BPO',
        'ESTVC-Guest':'BPO',
        'ESTVC- Guest':'BPO',
        'ESTVC - Guest':'BPO',
        'ESTVC - Event/Guest':'BPO',
        'ESTVC - Employee' :'BPO',
        'ESTVC - GC' :'BPO',
        'ESTVC - EMP':'BPO',
        'TA-Rates':'BPO',
        'TA - Rates':'BPO',
        'PEOPLE PLUS-EMPLOYEE':'BPO',
        'Corporate Sales':'BPO',
        'Corporate FIT':'BPO',
        'House Use':'BPO',
        'Metrodeal':'BPO',
        'CVoucher':'BPO',
        'VIP Guest':'BPO',
        'DTT WALK-IN':'BPO',
        'DTT Walk-in':'BPO',
        'DTT - Walk-in':'BPO',
        'Paying - Walk-in':'BPO',
        'OTA - KLOOK':'BPO',
        'Walk-in Commercial Guest':'BPO',
        'Walk-in - Commercial':'BPO',
        'Walk-in - DTT':'BPO',
        'Walk-in - Paying':'BPO',
        'GOLF - Commercial':'BPO',
        'Orion Sky':'BPO',
        'Orion Sky - Guest':'BPO',
        'Orion Sky - Employee':'BPO',
        'Golf Member':'BPO',
        'Camaya Golf Voucher':'BPO',
        'DS18 - Employee':'BPO',
        'DS18 - Events Guest':'BPO',

        'HOA': 'HOA',
        'HOA ACCESS STUB': 'HOA',
        'HOA CLIENT': 'HOA',
        'HOA MEMBER': 'HOA',
        'HOA - Access Stub': 'HOA',
        'HOA - Client': 'HOA',
        'HOA - Member': 'HOA',
        'HOA - (Paying-promo)':'HOA',
        'HOA - Paying Promo':'HOA',
        'HOA - AF Unit Owner':'HOA',
        'HOA - Voucher':'HOA',
        'HOA – Gate Access': 'HOA',
        'HOA - Golf':'HOA',
        'HOA - Walk-in':'HOA',
        'ESLCC - AFV': 'HOA',
        'ESLCC - CSV': 'HOA',
        'ESLCC - HOA': 'HOA',
        'ESLCC HOA': 'HOA',
        'GOLF - HOA':'HOA',
        'Walk-in HOA':'HOA',
        'HOA - Sales Director Marketing Budget':'HOA',
        'Property Owner (Non-Member)':'HOA',
        'Property Owner (HOA Member)':'HOA',
        'Property Owner (Dependents)':'HOA',
        'Property Owner (Guests)':'HOA',

        'RE - Tripping':'RE',
        'RE-Tripping':'RE',
        'Thru Agent - Paying':'RE',
        'Thru Agent-Paying':'RE',
        'Thru Agent (Paying)':'RE',
        'Thru Agent (Paying-promo)':'RE',
        'ESLCC - Sales Client':'RE',
        'ESLCC - Sales Agent':'RE',
        'ESLCC- Sales Agent':'RE',
        'ESLCC - Unused Room by Sales':'RE',
        'SDMB - Sales Director Marketing Budget':'RE',
        'Walk-in Real Estate':'RE',
        'Walk-in - Sales Client':'RE',
        'Walk-in - Sales Agent':'RE',
        'GOLF - RE':'RE',
        'RE - Golf':'RE',

        'OTA (BOOKING.COM)':'OTA',
        'OTA (TRAVELOKA)':'OTA',
        'OTA (AGODA)':'OTA',
        'OTA (EXPEDIA)':'OTA',
        'OTA (KLOOK)':'OTA',
        'OTA - Klook':'OTA',
    }

    // States
    const [searchString, setSearchString] = React.useState(null);
    const [startDate, setStartDate] = React.useState(moment());
    const [endDate, setEndDate] = React.useState(moment());
    const [bookingToView, setbookingToView] = React.useState(null);
    const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);

    const dashboardDataQuery = DashboardService.guestArrivalStatusReport(startDate, endDate);

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
            <Typography.Title level={4}>Guest Arrival Status Report</Typography.Title>
            
            <Row gutter={[48,48]} className="mt-4">
                <Col xl={24}>
                    Jump to date: <DatePicker.RangePicker onChange={handleDateRangeChange} value={[startDate, endDate]} className="ml-2"/>
                </Col>
                <Col xl={24} lg={32} md={24}>
                    <Card
                        title="Guest Arrivals" 
                        headStyle={{background:'#1177fa', color: 'white'}}
                        size="large" bordered={false}
                        className="card-shadow"
                        >
                        <Row gutter={[16,16]}>
                            {/* <Col xl={6} lg={6} md={6}>
                                <Card><Statistic title="Total Bookings" value={dashboardDataQuery.data && dashboardDataQuery.data.arriving_guests ? dashboardDataQuery.data.arriving_guests.length : 0} /></Card>
                            </Col> */}
                            <Col xl={6} lg={6} md={6}>
                                <Card><Statistic title="Total Guests" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_active ? dashboardDataQuery.data.guest_status_active.length : 0} /></Card>
                            </Col>
                            <Col xl={6} lg={6} md={6}>
                                <Card><Statistic title="Arriving" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_arriving ? dashboardDataQuery.data.guest_status_arriving.length : 0} /></Card>
                            </Col>
                            <Col xl={6} lg={6} md={6}>
                                <Card><Statistic title="On Premise" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_onpremise ? dashboardDataQuery.data.guest_status_onpremise.length : 0} /></Card>
                            </Col>
                            <Col xl={6} lg={6} md={6}>
                                <Card><Statistic title="Checked-in" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_checkedin ? dashboardDataQuery.data.guest_status_checkedin.length : 0} /></Card>
                            </Col>
                            <Col xl={6} lg={6} md={6}>
                                <Card><Statistic title="No Show" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_noshow ? dashboardDataQuery.data.guest_status_noshow.length : 0} /></Card>
                            </Col>
                            <Col xl={6} lg={6} md={6}>
                                <Card><Statistic title="Cancelled" value={dashboardDataQuery.data && dashboardDataQuery.data.guest_status_cancelled ? dashboardDataQuery.data.guest_status_cancelled.length : 0} /></Card>
                            </Col>
                        </Row>
                    </Card>
                </Col>
                <Col xl={24}>
                    <Typography.Title level={5}>
                        
                        Arrival Guests List
                        <ExcelFile filename={`Guest_Arrival_Status_Report_${moment().format('YYYY-MM-DD HH:mm:ss')}`} element={<Button className="ml-2" size="small"><PrinterOutlined/></Button>}>
                            <ExcelSheet data={dashboardDataQuery.data.arriving_guests} name="guest_arrival_status">
                                <ExcelColumn label="Date" value={ col => moment(col.booking.start_datetime).format('MMM D, YYYY')} />
                                <ExcelColumn label="Booking Ref #" value="booking_reference_number"/>
                                <ExcelColumn label="Booking Type" value={ col => col.booking.type == 'DT' ? 'Day tour' : 'Overnight'}/>
                                <ExcelColumn label="Booking Status" value={ col => col.booking.status}/>
                                <ExcelColumn label="Market Segmentation" value={ col => (col.booking.tags ? col.booking.tags : []).map(elem => market_segmentation[elem.name] ? market_segmentation[elem.name] : 'none' ).join(', ')}/>
                                <ExcelColumn label="Guest Ref #" value="reference_number"/>
                                <ExcelColumn label="Guests" value={col => `${col.first_name} ${col.last_name}` }/>
                                <ExcelColumn label="Guest Type" value="type"/>
                                <ExcelColumn label="Guest Status" value="status"/>
                                <ExcelColumn label="Mode of Transportation" value={ col => (!_.find(col.active_trips, i => i.destination_code == 'CMY' || i.destination_code == 'FTT') && col.booking.mode_of_transportation == 'camaya_transportation') ? 'undecided' : col.booking.mode_of_transportation}/>
                                <ExcelColumn label="Rate" value= { col => _.sum(col.booking.invoices.map(item => parseFloat(item.total_cost)) )} />
                            </ExcelSheet>
                        </ExcelFile>  

                    </Typography.Title>
                    <Input style={{width: 500}} type="text" placeholder="Search by guest name, booking ref #, guest ref #" size="large" className="ml-2 my-3" onChange={(e) => handleSearch(e.target.value)} />
                    <Table
                        dataSource={
                            dashboardDataQuery.data && dashboardDataQuery.data.arriving_guests ? 
                            dashboardDataQuery.data.arriving_guests
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
                            x: '100vw'
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
                                title: 'Market Segmentation',
                                render: (text, record) => record.booking.tags.map((i,key) => <Typography key={key}> 
                                    {
                                        market_segmentation[i.name] ? market_segmentation[i.name] : 'no market segmentation'
                                    }
                                </Typography>),
                                filters: [
                                    { text: 'BPO', value: 'BPO' },
                                    { text: 'HOA', value: 'HOA' },
                                    { text: 'RE', value: 'RE' },
                                    { text: 'OTA', value: 'OTA' }
                                ],
                                defaultFilteredValue: [],
                                onFilter: (value, record) => record.booking.tags.map(i => { 
                                   return market_segmentation[i.name] == value ? market_segmentation[i.name] : null
                                }).includes(value)
                            },
                            {
                                title: 'Guest Ref #',
                                render: (text, record) => record.reference_number
                            },
                            {
                                title: 'Guests',
                                render: (text, record) => <strong>{record.first_name} {record.last_name}</strong>
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
                                title: 'Rate',
                                render: (text, record) => numberWithCommas('₱ '+_.sum(record.booking.invoices.map(item => {
                                                return parseFloat(item.total_cost);
                                            }
                                        )))
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
        </>
    )
}