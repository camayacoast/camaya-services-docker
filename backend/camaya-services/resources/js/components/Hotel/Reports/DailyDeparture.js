import React, { useState, useEffect } from 'react'
import moment from 'moment-timezone'
import { Typography, Table, Form, DatePicker, Button, Row, Col, Checkbox } from 'antd'
import { DownloadOutlined } from '@ant-design/icons'

import HotelReportService from 'services/Hotel/Report'

function Page(props) {
    const currentDate = moment()
    const [properties, setProperties] = useState({
        sands: true,
        af: true
    });
    const [formattedDate, setFormattedDate] = useState(currentDate);
    const reportQuery = HotelReportService.dailyDeparture(formattedDate.format('YYYY-MM-DD'), properties);
    const downloadReport = HotelReportService.dailyDepartureDownload;
    
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
            title: 'Remarks',
            render: (text, record) => record.booking?.remarks ?? '',
            className: 'border-bottom',
        },
        {
            title: 'Checkout Time',
            render: (text, record) => record.check_out_time ? moment(record.check_out_time).format('MMM D, YYYY h:mm:ss a') : '',
            className: 'border-bottom',
        },
        {
            title: 'Checked-out By',
            render: (text, record) => record.checked_out_by_details ? record.checked_out_by_details?.first_name + ' ' + record.checked_out_by_details?.last_name : '',
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
            <Typography.Title level={4}>Daily Departure Report</Typography.Title>

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
                <div style={{textAlign: 'right'}}>
                    <div><strong>Total No. of Pax: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.adult_pax + i.booking.kid_pax + i.booking.infant_pax)}</strong></div>
                    <div>
                        Adult: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.adult_pax)} |
                        <span className='ml-2'>Kid: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.kid_pax)}</span> | 
                        <span className='ml-2'>Infant: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.infant_pax)}</span>
                    </div>
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