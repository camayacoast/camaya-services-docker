import React, { useState, useEffect } from 'react'
import moment from 'moment-timezone'
import { Typography, Table, Form, DatePicker, Button, Row, Col, Checkbox, Tag } from 'antd'
import { DownloadOutlined } from '@ant-design/icons'

import HotelReportService from 'services/Hotel/Report'

function Page(props) {
    const currentDate = moment()
    const [properties, setProperties] = useState({
        sands: true,
        af: true
    });
    const [formattedDate, setFormattedDate] = useState(currentDate);
    const reportQuery = HotelReportService.inHouseGuestList(formattedDate.format('YYYY-MM-DD'), properties);
    const downloadReport = HotelReportService.inHouseGuestListDownload;
    
    const [form] = Form.useForm();

    const columns = [
        {
            title: 'Room No',
            className: 'border-bottom',
            render: (text, record) => 
            <>
                {/* {record.booking.room_reservations_no_filter.map(elem => `${elem.room.number}`).join(', ')} */}
                {record.booking.room_reservations_no_filter.map(elem => <div>{elem.room.number}</div>)}
            </>
        },
        {
            title: 'Room Type',
            className: 'border-bottom',
            render: (text, record) => 
            <>
                {record.booking.room_reservations_no_filter.map(elem => <div>{elem.room_type.code}</div>)}
            </>
        },
        {
            title: 'Booking Code',
            className: 'border-bottom',
            dataIndex: 'booking_reference_number',
            key: 'booking_reference_number',
            render: (text, record) => record.booking_reference_number
        },
        // {
        //     title: 'Primary Customer',
        //     className: 'border-bottom',
        //     render: (text, record) => <>{record.booking.customer.first_name} {record.booking.customer.last_name} {record.booking.customer.user_type ? <Tag>{record.booking.customer.user_type}</Tag> : ''}</>,
        // },
        {
            title: 'Last Name',
            className: 'border-bottom',
            render: (text, record) => <strong>{record.last_name}</strong>
        },
        {
            title: 'Guest Name',
            className: 'border-bottom',
            render: (text, record) => <strong>{record.first_name}</strong>
        },
        {
            title: 'Type',
            className: 'border-bottom',
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
            title: 'Age',
            className: 'border-bottom',
            render: (text, record) => <strong>{record.age}</strong>
        },
        {
          title: 'Arrival Date',
          className: 'border-bottom',
          key: 'start_datetime',
          render: (text, record) => `${moment(record.booking.start_datetime).format('D-MMM-YY')}`,
        },
        {
            title: 'Departure Date',
            className: 'border-bottom',
            key: 'end_datetime',
            render: (text, record) => `${moment(record.booking.end_datetime).format('D-MMM-YY')}`,
        },
        {
            title: 'Remarks',
            render: (text, record) => record.booking.remarks ?? '',
            className: 'border-bottom',
        },
    ];


    const onDownload = () => {
        downloadReport(formattedDate.format('YYYY-MM-DD'), properties);
    }

    useEffect(() => {
        reportQuery.refetch();
    }, [properties]);

    return (
        <>
            <Typography.Title level={4}>In-House Guest List</Typography.Title>

            <Row justify="center" className="my-3">
                <Col>
                    <Form
                        form={form}
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
                    Filter: <Checkbox checked={properties.sands} onChange={(e) => setProperties({...properties, sands: !properties.sands})}>Camaya Sands</Checkbox><Checkbox checked={properties.af} onChange={(e) => setProperties({...properties, af: !properties.af})}>Aqua Fun</Checkbox>
                </div>

                <div style={{textAlign: 'right'}}>
                    <div><strong>Total No. of Pax: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.adult_pax + i.booking.kid_pax + i.booking.infant_pax)}</strong></div>
                    <div>
                        Adult: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.adult_pax)} |
                        <span className='ml-2'>Kid: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.kid_pax)}</span> | 
                        <span className='ml-2'>Infant: {_.sumBy(_.uniqBy(reportQuery?.data?.room_reservations, 'booking_reference_number'), i => i.booking.infant_pax)}</span>
                    </div>
                </div>

                <div>
                    <div><strong>Total Rooms: 
                        <span className='ml-2'>{reportQuery?.data?.room_reservations.length || 0}</span>
                    </strong></div>
                </div>
            </div>

            <Table
                loading={reportQuery.isFetching}
                columns={columns}
                dataSource={reportQuery?.data?.inHouse_guests || []}
                rowKey="id"
                scroll={{ x: 'max-content' }}
                bordered
            />
        </>
    )
}

export default Page;
