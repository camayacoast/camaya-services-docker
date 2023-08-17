import React from 'react'
import moment from 'moment-timezone'
import DashboardService from 'services/Booking/DashboardService'
import { Line } from 'react-chartjs-2'
import Loading from 'common/Loading'
import ViewBookingComponent from 'components/Booking/View'

import { Row, Col, Card, Typography, Statistic, DatePicker, Table, Button, Modal, Input, message, Form, notification, InputNumber } from 'antd'
import { DashboardOutlined, LoadingOutlined, EditOutlined } from '@ant-design/icons'

const options = {
scales: {
    yAxes: [
    {
        ticks: {
        beginAtZero: true,
        },
    },
    ],
},
}

export default function Page(props) {

    const [selectedDate, setselectedDate] = React.useState(moment());

    const [selectedDates, setselectedDates] = React.useState({ start_date: moment(), end_date: moment() });
    const [corregidorBookings, setcorregidorBookings] = React.useState([]);
    const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);
    const [bookingToView, setbookingToView] = React.useState(null);
    const [editDailyGuestModalVisible, setEditDailyGuestModalVisible] = React.useState(false);

    const dashboardDataQuery = DashboardService.data(selectedDate);

    const [updateDailyLimitQuery, {isLoading: updateDailyLimitQueryIsLoading}] = DashboardService.updateDailyLimit();
    const [updateFerryPassengersLimitQuery, {isLoading: updateFerryPassengersLimitQueryIsLoading}] = DashboardService.updateFerryPassengersLimit();

    const [corregidorBookingsQuery, { isLoading: corregidorBookingsQueryIsLoading, reset: corregidorBookingsQueryReset}] = DashboardService.corregidorBookings();
    const [editDailyGuestPerDayQuery, { isLoading: editDailyGuestPerDayQueryIsLoading, reset: editDailyGuestPerDayQueryReset }] = DashboardService.editDailyGuestPerDay();

    const [editDailyGuestForm] = Form.useForm();

    const handleDailyLimitChange = (value, type) => {
        console.log(value, type);

        if (updateDailyLimitQueryIsLoading) return false;

        updateDailyLimitQuery({
            type: type,
            value: parseInt(value),
            selected_date: selectedDate.format('YYYY-MM-DD')
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success("Update daily limit successful!");
                dashboardDataQuery.refetch();
            },
            onError: (e) => {
                console.log(e);
                message.error(e.error)
            }
        })
    }

    const handleFerryPassengersLimitChange = (value, type) => {
        console.log(value, type);

        if (updateFerryPassengersLimitQueryIsLoading) return false;

        updateFerryPassengersLimitQuery({
            type: type,
            value: parseInt(value),
            selected_date: selectedDate.format('YYYY-MM-DD')
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success("Update ferry passengers limit successful!");
                dashboardDataQuery.refetch();
            },
            onError: (e) => {
                console.log(e);
                message.error(e.error)
            }
        })
    }

    // console.log(guestsCache);

    React.useEffect(()=> {
        // console.log(dashboardDataQuery.data);
        if (selectedDate) {
            dashboardDataQuery.refetch();
        }
    },[selectedDate]);

    React.useEffect(()=> {
        loadCorregidorBookings();
    },[selectedDates.start_date.format('YYYY-MM-DD'), selectedDates.end_date.format('YYYY-MM-DD')]);

    React.useEffect(()=> {
        // console.log(dashboardDataQuery.data);
        loadCorregidorBookings();
    },[]);

    const loadCorregidorBookings = () => {
        if (selectedDates && selectedDates.start_date && selectedDates.end_date) {
            // console.log(selectedDates.start_date.format('YYYY-MM-DD'), selectedDates.end_date.format('YYYY-MM-DD'));
            corregidorBookingsQuery(
                {
                    start_date: selectedDates.start_date.format('YYYY-MM-DD'),
                    end_date: selectedDates.end_date.format('YYYY-MM-DD'),
                },
                {
                    onSuccess: (res) => {
                        // console.log(res)
                        setcorregidorBookings(res.data);
                    },
                    onError: (e) => console.log(e)
                }
            )
        }
    }

    const handleEditDailyGuestLimit = (records) => {
        // console.log(date, records)
        setEditDailyGuestModalVisible(true);

        editDailyGuestForm.setFieldsValue({
            date: moment(selectedDate).format('YYYY-MM-DD'),
            admin: _.find(records, i => i.category == 'Admin')?.limit ?? null,
            commercial: _.find(records, i => i.category == 'Commercial')?.limit ?? null,
            sales: _.find(records, i => i.category == 'Sales')?.limit ?? null,
            remarks: dashboardDataQuery.data && dashboardDataQuery.data.daily_guest_limit_note ? dashboardDataQuery.data.daily_guest_limit_note.note : ''
        });

    }

    const viewBooking = (refno) => {
        setbookingToView(refno);
        setviewBookingModalVisible(true);
    }

    if (dashboardDataQuery.isLoading) {
        return <Loading/>;
    }

    const data = {
        labels: _.map(dashboardDataQuery.data.guest_forecast, i => moment(i.date).format('MMM D, Y')),
        datasets: [
          {
            label: '# of guest arrival',
            data: _.map(dashboardDataQuery.data.guest_forecast, 'count'),
            fill: false,
            backgroundColor: 'red',
            borderColor: 'rgba(200, 200, 200, 0.2)',
          },
        ],
      }

    return (
        <div>
            <Typography.Title level={2}><DashboardOutlined className="mr-2"/>Dashboard</Typography.Title>
            <Typography.Text>Today is {moment().format('dddd, MMMM D, YYYY')}</Typography.Text>

            <Col xl={24}>
                Jump to date: <DatePicker allowClear={false} value={selectedDate} onChange={(e)=>setselectedDate(e)} className="mx-2" />
                {
                    dashboardDataQuery.isFetching &&
                    <><LoadingOutlined className="ml-2" /> Loading data. Please wait...</>
                }
            </Col>

            <Row gutter={[48,48]} className="mt-2 mb-4">
                <Col xl={6}>
                    <Card size="small" bordered={false} style={{borderLeft: 'solid 5px limegreen'}} className="card-shadow">
                        <Statistic title="Arriving guests" value={dashboardDataQuery.data.total_arriving_guests} />
                    </Card>
                </Col>
                <Col xl={6}>
                    <Card size="small" bordered={false} style={{borderLeft: 'solid 5px limegreen'}} className="card-shadow">
                        <Statistic title="Day tour guests" value={dashboardDataQuery.data.total_day_tour_guests} />
                    </Card>
                </Col>
                <Col xl={6}>
                    <Card size="small" bordered={false} style={{borderLeft: 'solid 5px limegreen'}} className="card-shadow">
                        <Statistic title="Overnight guests" value={dashboardDataQuery.data.total_overnight_guests} />
                    </Card>
                </Col>
                <Col xl={6}>
                    <Card size="small" bordered={false} style={{borderLeft: 'solid 5px limegreen'}} className="card-shadow">
                        <Statistic title="All guests with stay-overs" value={dashboardDataQuery.data.total_guests_with_stayovers} />
                    </Card>
                </Col>
            </Row>

            <Row gutter={[48,48]}>

                <Col xl={12}>
                    <Card title="Guest arrival forecast" size="large" bordered={false} className="card-shadow">
                        <Line data={data} options={options} />
                    </Card>

                    
                </Col>

                {/* <Col xl={12}>
                    <Card title="Hotel occupancy &amp; Room availability" size="large" bordered={false} className="card-shadow">
                        ...
                    </Card>
                </Col>
                <Col xl={12}>
                    <Card title="Hotel occupancy per allocation" size="large" bordered={false} className="card-shadow">
                        ...
                    </Card>
                </Col> */}

                <Col xl={12}>
                    <Row gutter={[48,48]}>
                        <Col xl={24}>
                            <Card title={<>Daily Guest Limit <Button disabled={moment(selectedDate).isBefore(moment().format('YYYY-MM-DD'))} onClick={()=>handleEditDailyGuestLimit(dashboardDataQuery.data.daily_guest_limit_per_day)} icon={<EditOutlined/>} size="small" style={{float: 'right'}} /></>} size="large" bordered={false} className="card-shadow">
                            <>
                                {/* <div className="mb-4">
                                    <Typography.Title level={4}>Land Allocations</Typography.Title>
                                    <Space>
                                        <Button icon={<PlusOutlined/>} onClick={()=>setnewLandAllocationModalVisible(true)}>Add allocation</Button>
                                        <Button icon={<ReloadOutlined />} onClick={() => landAllocationListQuery.refetch()} />
                                    </Space>
                                </div> */}

                                <Table
                                    loading={dashboardDataQuery.isFetching}
                                    size="large"
                                    dataSource={dashboardDataQuery.data && dashboardDataQuery.data.daily_guest_limit_per_day}
                                    // dataSource={landAllocationListQuery.data && landAllocationListQuery.data.filter(i => (dateFilter ? moment(i.date).format('YYYY-MM-DD') == moment(dateFilter).format('YYYY-MM-DD') : true))}
                                    rowKey="id"
                                    columns={[
                                        {
                                            title: 'Category',
                                            dataIndex: 'category',
                                            key: 'category',
                                        },
                                        {
                                            title: 'Used / Limit',
                                            render: (text, record) => {

                                                let used = 0;

                                                switch(record.category) {
                                                    case 'Admin': 
                                                        used = dashboardDataQuery.data.admin_daily_used;
                                                        break;
                                                    case 'Sales': 
                                                        used = dashboardDataQuery.data.sales_daily_used;
                                                        break;
                                                    case 'Commercial': 
                                                        used = dashboardDataQuery.data.commercial_daily_used;
                                                        break;
                                                }

                                                return <>{used} / {record.limit}</>
                                            }
                                        },
                                    ]}
                                />
                                <div>Remarks: <Typography.Paragraph style={{width: 200}} ellipsis>{dashboardDataQuery.data && dashboardDataQuery.data.daily_guest_limit_note ? dashboardDataQuery.data.daily_guest_limit_note.note : ''}</Typography.Paragraph></div>
                                </>
                            </Card>
                        </Col>
                    </Row>
                </Col>

                <Col xl={12}>

                    <Row gutter={[48,48]}>
                        <Col xl={12}>
                            <Card title="Default Daily Guests Limit" size="large" bordered={false} className="card-shadow">
                                <Form>
                                    <Form.Item name="admin_daily_limit" label="Admin" style={{marginBottom: 0, paddingBottom: 0}}>
                                        <Typography.Text editable={{ onChange: (record) =>  handleDailyLimitChange(record, 'ADMIN_DAILY_LIMIT') }}> {dashboardDataQuery.data.admin_daily_used} / {dashboardDataQuery.data.admin_daily_limit ? dashboardDataQuery.data.admin_daily_limit : ''}</Typography.Text>
                                    </Form.Item>
                                    {/* <small style={{marginTop: 0, paddingTop: 0}}>HOA: 1,400 <span><EditOutlined className="mr-2"/></span> | Others: {dashboardDataQuery.data.admin_daily_limit - 1400} </small> */}

                                    <Form.Item name="sales_daily_limit" label="Sales" className="mt-4">
                                        <Typography.Text editable={{ onChange: (record) =>  handleDailyLimitChange(record, 'SALES_DAILY_LIMIT') }}> {dashboardDataQuery.data.sales_daily_used} / {dashboardDataQuery.data.sales_daily_limit ? dashboardDataQuery.data.sales_daily_limit : ''}</Typography.Text>
                                    </Form.Item>

                                    <Form.Item name="commercial_daily_limit" label="Commercial">
                                        <Typography.Text editable={{ onChange: (record) =>  handleDailyLimitChange(record, 'COMMERCIAL_DAILY_LIMIT') }}> {dashboardDataQuery.data.commercial_daily_used} / {dashboardDataQuery.data.commercial_daily_limit ? dashboardDataQuery.data.commercial_daily_limit : ''}</Typography.Text>
                                    </Form.Item>

                                    <Typography.Text><strong>TOTAL: {dashboardDataQuery.data.admin_daily_used + dashboardDataQuery.data.sales_daily_used + dashboardDataQuery.data.commercial_daily_used} / {parseInt(dashboardDataQuery.data.admin_daily_limit) + parseInt(dashboardDataQuery.data.sales_daily_limit) + parseInt(dashboardDataQuery.data.commercial_daily_limit)}</strong></Typography.Text>
                                </Form>
                            </Card>
                        </Col>
                        <Col xl={12}>
                            <Card title="Ferry Passenger's Limit" size="large" bordered={false} className="card-shadow">
                                <Form>
                                    {/* <Form.Item name="daily_limit_total" label="Adult:">
                                        <Typography.Text>{ (parseInt(dashboardDataQuery.data.admin_daily_limit) + parseInt(dashboardDataQuery.data.sales_daily_limit) + parseInt(dashboardDataQuery.data.commercial_daily_limit)) - (parseInt(dashboardDataQuery.data.trip_kid_max) + parseInt(dashboardDataQuery.data.trip_infant_max)) }</Typography.Text>
                                    </Form.Item> */}
                                    <Form.Item name="trip_adult_max" label="Adult:">
                                        <Typography.Text editable={{ onChange: (record) =>  handleFerryPassengersLimitChange(record, 'TRIP_ADULT_MAX') }}> {dashboardDataQuery.data.trip_adult_max ? dashboardDataQuery.data.trip_adult_max : ''}</Typography.Text>
                                    </Form.Item>
                                    <Form.Item name="trip_kid_max" label="Kid:">
                                        <Typography.Text editable={{ onChange: (record) =>  handleFerryPassengersLimitChange(record, 'TRIP_KID_MAX') }}> {dashboardDataQuery.data.trip_kid_max ? dashboardDataQuery.data.trip_kid_max : ''}</Typography.Text>
                                    </Form.Item>
                                    <Form.Item name="trip_infant_max" label="Infant:">
                                        <Typography.Text editable={{ onChange: (record) =>  handleFerryPassengersLimitChange(record, 'TRIP_INFANT_MAX') }}> {dashboardDataQuery.data.trip_infant_max ? dashboardDataQuery.data.trip_infant_max : ''}</Typography.Text>
                                    </Form.Item>
                                    <Typography.Text><strong>TOTAL: {parseInt(dashboardDataQuery.data.trip_adult_max) + parseInt(dashboardDataQuery.data.trip_kid_max) + parseInt(dashboardDataQuery.data.trip_infant_max)}</strong></Typography.Text>
                                </Form>
                            </Card>
                        </Col>

                    </Row>

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

                <Col xl={12}>
                    <Card title={<>Corregidor Bookings <DatePicker.RangePicker allowClear={false} value={[selectedDates.start_date, selectedDates.end_date]} onChange={(e)=>setselectedDates({start_date: e[0], end_date: e[1]})}/></>} size="large" bordered={false} className="card-shadow">
                    <Table
                        rowKey="id"
                        dataSource={corregidorBookings ? corregidorBookings : []}
                        columns={[
                            {
                                title: "Booking Ref #",
                                render: (text, record) => {
                                    return <Button onClick={()=>viewBooking(record.reference_number)} style={{color: record.status == 'confirmed' ? 'limegreen' : 'orange'}}>{record.reference_number}</Button>
                                }
                            },
                            {
                                title: "Date of visit",
                                render: (text, record) => {
                                    return moment(record.start_datetime).format('YYYY-MM-DD')+" ~ "+moment(record.end_datetime).format('YYYY-MM-DD')
                                }
                            },
                            {
                                title: "Adult pax",
                                render: (text, record) => {
                                    return record.adult_pax
                                }
                            },
                            {
                                title: "Kid pax",
                                render: (text, record) => {
                                    return record.kid_pax
                                }
                            },
                            {
                                title: "Infant pax",
                                render: (text, record) => {
                                    return record.infant_pax
                                }
                            },
                        ]}
                    />
                </Card>
                </Col>

            </Row>

            <Modal
                title="Edit Daily Guest Limit"
                visible={editDailyGuestModalVisible}
                onCancel={() => setEditDailyGuestModalVisible(false)}
                footer={null}
                width={500}
            >
                <>
                    <Form
                        form={editDailyGuestForm}
                        layout="vertical"
                        onFinish={(values) => {
                            if (editDailyGuestPerDayQueryIsLoading) return false;

                            let ans = confirm("Are you sure you want to update daily guest limit?");

                            if (!ans) {
                                return false;
                            }

                            editDailyGuestPerDayQuery(values, {
                                onSuccess: (res) => {
                                    console.log(res);

                                    editDailyGuestForm.resetFields();
                                    setEditDailyGuestModalVisible(false);
                                    dashboardDataQuery.refetch()
                                
                                    if (res.data) {
                                        notification.success({
                                            message: `Updated daily guest limit for ${values.date}`,
                                            description:
                                                ``
                                        });
                                    }

                                },
                                onError: (e) => {
                                    console.log(e)
                                    editDailyGuestPerDayQueryReset();
                                    message.warning(e.message);
                                }
                            });
                        }}
                    >
                        <Form.Item label="Date" name='date'>
                            <Input readOnly />
                        </Form.Item>

                        <Row gutter={[12, 12]}>
                            <Col xl={8}>
                                <Form.Item label="Admin" name='admin' rules={[{required: true}, {type: 'number', min: 0}]}>
                                    <InputNumber />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Commercial" name='commercial' rules={[{required: true}, {type: 'number', min: 0}]}>
                                    <InputNumber />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Sales" name='sales' rules={[{required: true}, {type: 'number', min: 0}]}>
                                    <InputNumber />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Form.Item label="Remarks" name='remarks'>
                            <Input.TextArea style={{borderRadius: 6}} rows={5} />
                        </Form.Item>

                        <Button htmlType='submit'>Save</Button>
                    </Form>
                </>
            </Modal>
        </div>
        
    )
}