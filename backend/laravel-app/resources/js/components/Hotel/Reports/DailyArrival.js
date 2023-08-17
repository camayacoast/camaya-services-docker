import React, { useState, useEffect } from 'react'
import moment from 'moment-timezone'
import { Typography, Table, Form, DatePicker, Button, Row, Col, Checkbox } from 'antd'
import { DownloadOutlined } from '@ant-design/icons'

import HotelReportService from 'services/Hotel/Report'


const fo_status = {
    vacant: 'V',
    occupied: 'O',
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

function Page(props) {
    const currentDate = moment()
    const [properties, setProperties] = useState({
        sands: true,
        af: true
    });
    const [formattedDate, setFormattedDate] = useState(currentDate);
    const reportQuery = HotelReportService.dailyArrival(formattedDate.format('YYYY-MM-DD'), properties);
    const downloadReport = HotelReportService.dailyArrivalDownload;
    
    const [form] = Form.useForm();

    const columns = [
        {
            title: 'Room No',
            className: 'border-bottom',
            render: (text, record) => record.room.number,
        },
        {
            title: 'Room Type',
            className: 'border-bottom',
            render: (text, record) => record.room_type.code,
        },
        {
            title: 'Booking Code',
            render: (text, record) => record.booking.reference_number,
            className: 'border-bottom',
        },
        {
            title: 'Check-in Time',
            render: (text, record) => record.check_in_time ? moment(record.check_in_time).format('MMM D, YYYY h:mm:ss a') : '',
            className: 'border-bottom',
        },
        {
            title: 'Room Status',
            render: (text, record) => `${record.room.fo_status ? fo_status[record.room.fo_status] : ''}${record.room.room_status ? room_status[record.room.room_status] : ''}`,
            className: 'border-bottom',
        },
        {
            title: 'Last Name',
            className: 'border-bottom',
            render: (text, record) => `${record.booking.customer?.last_name}`,
        },
        {
            title: 'Guest Name',
            className: 'border-bottom',
            render: (text, record) => `${record.booking.customer?.first_name}`,
        },
        {
            title: 'Adult',
            className: 'border-bottom',
            render: (text, record) => record.booking.adult_pax,
        },
        {
            title: 'Child',
            className: 'border-bottom',
            render: (text, record) => record.booking.kid_pax,
        },
        {
            title: 'Infant',
            className: 'border-bottom',
            render: (text, record) => record.booking.infant_pax,
        },
        {
            title: 'Total No. of Pax',
            className: 'border-bottom',
            render: (text, record) => record.booking.adult_pax + record.booking.kid_pax + record.booking.infant_pax,
        },
        {
            title: 'Billing Instructions',
            render: (text, record) => record.booking?.billing_instructions ?? '',
            className: 'border-bottom',
        },
        {
            title: 'Rate Code',
            render: (text, record) => "",
            className: 'border-bottom',
        },
        {
            title: 'Market Segmentation',
            render: (text, record) => record.booking.tags.length ? record.booking.tags.map( i => i.name).join(", ") : '',
            className: 'border-bottom',
        },
        {
          title: 'Arrival Date',
          className: 'border-bottom',
          render: (text, record) => `${moment(record.booking.start_datetime).format('D-MMM-YY')}`,
        },
        {
            title: 'Departure Date',
            className: 'border-bottom',
            render: (text, record) => `${moment(record.booking.end_datetime).format('D-MMM-YY')}`,
        },
        {
            title: 'Transportation Details',
            render: (text, record) => record.booking?.mode_of_transportation,
            className: 'border-bottom',
        },
        {
            title: 'Remarks',
            render: (text, record) => record.booking?.remarks ?? '',
            className: 'border-bottom',
        },
    ];

    // const onFilter = (values) => {
    //     setFormattedDate(values.date.format('YYYY-MM-DD'));
    // }

    const onDownload = () => {
        downloadReport(formattedDate.format('YYYY-MM-DD'), properties);
    }

    // useEffect(() => {
    //     form.setFieldsValue({
    //         date: currentDate
    //     });
    // }, []);

    useEffect(() => {
        // console.log(properties);
        reportQuery.refetch();
    }, [properties]);

    return (
        <>
            <Typography.Title level={4}>Daily Arrival Report</Typography.Title>

            <Row justify="center" className="my-3">
                <Col>
                    <Form
                        form={form}
                        // onFinish={onFilter}
                        layout="inline"
                    >
                        <Form.Item name="date" label="Select Date">
                            <DatePicker allowClear={false} defaultValue={moment()} onChange={e => setFormattedDate(moment(e))} />
                        </Form.Item>
                        <Form.Item>
                            <Button type="primary" onClick={() => reportQuery.refetch()}>
                                View Report
                            </Button>
                            <Button type="primary"
                                className="ml-3"
                                icon={<DownloadOutlined />}
                                onClick={() => onDownload()}>
                                Download
                            </Button>
                        </Form.Item>
                    </Form>
                </Col>
            </Row>

            <div className='p-4' style={{display: 'flex', justifyContent: 'space-between'}}>
                <div>
                    Filter: <Checkbox checked={properties.sands} onChange={(e) => setProperties({...properties, sands: !properties.sands})}>Camaya Sands</Checkbox><Checkbox checked={properties.af}  onChange={(e) => setProperties({...properties, af: !properties.af})}>Aqua Fun</Checkbox>
                </div>
                <div>
                    <div>Total Arrival Rooms: {reportQuery?.data?.room_reservations.length || 0}</div>
                    <div>Total No. of Pax: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.adult_pax + i.booking.kid_pax + i.booking.infant_pax)}</div>
                    <div>
                        Adult: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.adult_pax)} |
                        <span className='ml-2'>Kid: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.kid_pax)}</span> | 
                        <span className='ml-2'>Infant: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.infant_pax)}</span>
                    </div>
                </div>

                <div>
                    <div>Total Stay-over Rooms: {reportQuery?.data?.stayover_room_total || 0}</div>
                    <div>Total No. of Pax: {reportQuery?.data?.stayover_pax_total}</div>
                </div>

                <div>
                    <div>Total House Use: {reportQuery?.data?.houseuse_room_total || 0}</div>
                    <div>Total No. of Pax: {reportQuery?.data?.houseuse_pax_total}</div>
                </div>
            </div>

            <Table
                loading={reportQuery.status === 'loading'}
                columns={columns}
                dataSource={reportQuery?.data?.room_reservations || []}
                rowKey="id"
                scroll={{ x: 'max-content' }}
                bordered
            />
        </>
    )
}

export default Page;