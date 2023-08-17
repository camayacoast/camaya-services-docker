import React, { useState, useEffect } from 'react'
import moment from 'moment-timezone'
import { Typography, Table, Form, DatePicker, Button, Row, Col } from 'antd'
import { DownloadOutlined } from '@ant-design/icons'

import HotelReportService from 'services/Hotel/Report'

function Page(props) {
    const [form] = Form.useForm();
    const reportQuery = HotelReportService.guestHistory;
    const downloadReport = HotelReportService.guestHistoryDownload;
    const currentDate = moment(new Date())
    const [formattedDate, setFormattedDate] = useState(currentDate.format('YYYY-MM-DD'));
    const reportList = reportQuery(formattedDate);

    const columns = [
        {
            title: 'Guest Name',
            dataIndex: 'customer',
            key: 'customer',
            className: 'border-bottom',
            render: (text, record) => `${record.customer?.first_name} ${record.customer?.last_name}`,
        },
        {
            title: 'Arrival Date',
            dataIndex: 'start_datetime',
            key: 'start_datetime',
            className: 'border-bottom',
            render: (text, record) => `${moment(record.start_datetime).format('MMM D, YYYY')}`,
        },
        {
            title: 'Departure Date',
            dataIndex: 'end_datetime',
            key: 'end_datetime',
            className: 'border-bottom',
            render: (text, record) => `${moment(record.end_datetime).format('MMM D, YYYY')}`,
        },
        {
            title: 'Number of Pax',
            dataIndex: 'pax',
            key: 'pax',
            className: 'border-bottom',
            render: (text, record) => record.adult_pax + record.kid_pax + record.infant_pax,
        },
        {
            title: 'Room Number',
            dataIndex: 'description',
            key: 'description3',
            className: 'border-bottom',
        },
        {
            title: 'Room Type',
            dataIndex: 'description',
            key: 'description4',
            className: 'border-bottom',
        },
        {
            title: 'Nationality',
            dataIndex: 'nationality',
            key: 'nationality',
            className: 'border-bottom',
            render: (text, record) => record.customer?.nationality,
        },
        {
            title: 'Preferences',
            dataIndex: 'preferences',
            key: 'preferences',
            className: 'border-bottom',
        },
        {
            title: 'Billing Instruction',
            dataIndex: 'billing-instruction',
            key: 'billing-instruction',
            className: 'border-bottom',
        },
    ];

    const onFilter = (values) => {
        setFormattedDate(values.date.format('YYYY-MM-DD'));
    }

    const onDownload = () => {
        downloadReport(formattedDate);
    }

    useEffect(() => {
        form.setFieldsValue({
            date: currentDate
        });
    }, []);

    return (
        <>
            <Typography.Title level={4}>Guest History</Typography.Title>

            <Row justify="center" className="my-3">
                <Col>
                    <Form
                        form={form}
                        onFinish={onFilter}
                        layout="inline"
                    >
                        <Form.Item name="date" label="Select Arrival Date">
                            <DatePicker allowClear={false} />
                        </Form.Item>
                        <Form.Item>
                            <Button type="primary" htmlType="submit">
                                View Report
                            </Button>
                            <Button type="primary"
                                className="ml-3"
                                icon={<DownloadOutlined />}
                                onClick={onDownload}>
                                Download
                            </Button>
                        </Form.Item>
                    </Form>
                </Col>
            </Row>

            <Table
                loading={reportList.status === 'loading'}
                columns={columns}
                dataSource={reportList?.data || []}
                rowKey="id"
                rowKey="id"
                scroll={{ x: 'max-content' }}
                bordered
            />
        </>
    )
}

export default Page;