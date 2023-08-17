import React, { useState, useEffect } from 'react'
import moment from 'moment-timezone'
import { Typography, Table, Form, DatePicker, Button, Row, Col, Space } from 'antd'
import { DownloadOutlined, ReloadOutlined } from '@ant-design/icons'
import { queryCache } from 'react-query'
import _ from 'lodash'

import BookingReportService from 'services/Booking/ReportService'

function Page(props) {    
    const [form] = Form.useForm();
    const reportQuery = BookingReportService.commercialSales;
    const downloadReport = BookingReportService.commercialSalesDownload;
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const reportList = reportQuery(startDate, endDate);

    const columns = [        
        {
            title: 'Date of Visit',
            dataIndex: 'date',
            className: 'border-bottom',
        },
        {
            title: 'Date of Booking',
            dataIndex: 'date_created',
            className: 'border-bottom',
        },        
        {
            title: 'Primary Guest Name',
            dataIndex: 'customer',
            className: 'border-bottom',
            render: (customer) => `${customer.first_name} ${customer.last_name}`,          
        },
        {
            title: 'DT/ON',
            dataIndex: 'type',
            className: 'border-bottom',
        },
        {
            title: 'New Booking Code',
            dataIndex: 'reference_number',
            className: 'border-bottom',
        },
        {
            title: 'Market Segmentation',
            dataIndex: 'market_segmentation',
            className: 'border-bottom',
        },        
        {
            title: 'Package Promo',
            dataIndex: 'tags',
            className: 'border-bottom',
            render: (tags) => _.map(tags, 'name').join(', '),
        },
        {
            title: 'Promo Availed',
            dataIndex: 'tags',
            className: 'border-bottom',
            render: (tags, record) => record.label ?? record.remarks,
        },
        {
            title: 'Pax',
            dataIndex: 'description',
            className: 'border-bottom',
            children: [
                {
                    title: 'Adults',
                    dataIndex: 'adult_pax',
                    className: 'border-bottom',
                    
                },
                {
                    title: 'Kids',
                    dataIndex: 'kid_pax',
                    className: 'border-bottom',
                },
            ]            
        },
        {
            title: 'Check In',
            dataIndex: 'check_in',
            className: 'border-bottom',
        },       
        {
            title: 'Check Out',
            dataIndex: 'check_out',
            className: 'border-bottom',
        },
        {
            title: 'Number of Nights',
            dataIndex: 'no_of_nights',
            className: 'border-bottom',
        },
        {
            title: 'Hotel',
            dataIndex: 'hotel',
            className: 'border-bottom',
        },
        {
            title: 'Number of Rooms',
            dataIndex: 'no_of_rooms',
            className: 'border-bottom',
        },
        {
            title: 'Type of Rooms',
            dataIndex: 'room_type',
            className: 'border-bottom',
        },
        {
            title: 'Meal Arrangement',
            className: 'border-bottom',
        },
        {
            title: 'Mode of Transportation',
            dataIndex: 'mode_of_transportation',
            className: 'border-bottom',
        },
        {
            title: 'Amount',
            dataIndex: 'amount',
            className: 'border-bottom',
        },
        {
            title: 'Mode of Payment',
            dataIndex: 'mode_of_payment',
            className: 'border-bottom',
        },
        {
            title: 'Source of Sale',
            dataIndex: 'source',
            className: 'border-bottom',
        },
        {
            title: 'Contact Number',
            dataIndex: 'customer',
            className: 'border-bottom',
            render: (customer) => customer.contact_number,
        },
        {
            title: 'Email',
            dataIndex: 'customer',
            className: 'border-bottom',
            render: (customer) => customer.email,
        },
    ];
    

    const onFilter = (values) => {
        if (!values.date) {
            setStartDate('');
            setEndDate('');

            return;
        }

        setStartDate(values.date[0].format('YYYY-MM-DD'));
        setEndDate(values.date[1].format('YYYY-MM-DD'));
    }

    const onDownload = () => {        
        downloadReport(startDate, endDate);
    }    

    const onReload = () => {
        if (! startDate || ! endDate) {
            return;
        }

        queryCache.invalidateQueries(['reports', 'commercial-sales', startDate, endDate]);
    }

    return (
        <>
            <Typography.Title level={4}>Commercial Sales</Typography.Title>

            <Row justify="center" className="my-3">
                <Col>
                    <Form
                        form={form}
                        onFinish={onFilter}
                        layout="inline"
                    >
                        <Form.Item name="date" label="Select Date">
                            <DatePicker.RangePicker />
                        </Form.Item>
                        <Form.Item>
                            <Space>
                                <Button type="primary" htmlType="submit">
                                    View Report
                                </Button>
                                <Button type="primary" icon={<ReloadOutlined />} onClick={onReload} />
                                <Button type="primary"
                                    disabled={reportList?.data 
                                        && reportList?.data.length  
                                        ? false 
                                        : true
                                    }
                                    icon={<DownloadOutlined />}
                                    onClick={onDownload}>
                                    Download
                                </Button>
                            </Space>
                        </Form.Item>
                    </Form>
                </Col>
            </Row>

            <Table
                loading={reportList.status === 'loading'}
                columns={columns}
                dataSource={reportList?.data || []}
                rowKey="id"
                scroll={{ x: 'max-content' }}
                bordered
            />
        </>
    )
}

export default Page;