import React, { useState, useEffect } from 'react'
import moment from 'moment-timezone'
import { Typography, Table, Form, DatePicker, Button, Row, Col, Space } from 'antd'
import { DownloadOutlined, ReloadOutlined } from '@ant-design/icons'
import { queryCache } from 'react-query'

import BookingReportService from 'services/Booking/ReportService'

function Page(props) {
    const [form] = Form.useForm();
    const reportQuery = BookingReportService.dailyBookingsPerSD;
    const downloadReport = BookingReportService.dailyBookingsPerSDDownload;
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const reportList = reportQuery(startDate, endDate);

    const columns = [
        {
            title: 'Sales Director',
            dataIndex: 'sd_name',
            className: 'border-bottom',
        },
        {
            title: 'No. of Guest in Land Bookings',
            dataIndex: 'counter_land_total_bookings',
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'No. of Guest in Ferry Bookings',
            dataIndex: 'counter_ferry_total_bookings',
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Guest Status (LAND)',
            className: 'border-bottom',
            children: [
                {
                    title: 'Arriving',
                    dataIndex: 'counter_land_arriving',
                    className: 'border-bottom',
                    align: 'center',

                },
                {
                    title: 'Arrived',
                    dataIndex: 'counter_land_arrived',
                    className: 'border-bottom',
                    align: 'center',

                },
                {
                    title: 'Cancelled',
                    dataIndex: 'counter_land_cancelled',
                    className: 'border-bottom',
                    align: 'center',
                },
                {
                    title: 'No Show',
                    dataIndex: 'counter_land_no_show',
                    className: 'border-bottom',
                    align: 'center',
                },
            ]
        },
        {
            title: 'Guest Status (FERRY)',
            dataIndex: 'status',
            className: 'border-bottom',
            children: [
                {
                    title: 'Arriving',
                    dataIndex: 'counter_ferry_arriving',
                    className: 'border-bottom',
                    align: 'center',

                },
                {
                    title: 'Arrived',
                    dataIndex: 'counter_ferry_arrived',
                    className: 'border-bottom',
                    align: 'center',

                },
                {
                    title: 'Cancelled',
                    dataIndex: 'counter_ferry_cancelled',
                    className: 'border-bottom',
                    align: 'center',
                },
                {
                    title: 'No Show',
                    dataIndex: 'counter_ferry_no_show',
                    className: 'border-bottom',
                    align: 'center',
                },
            ]
        },
    ];

    const summary = () => {
        if (! reportList?.data?.length) {
            return;
        }

        let summaryCounterFerryTotalBookings = 0;
        let summaryCounterFerryArrived = 0;
        let summaryCounterFerryArriving = 0;
        let summaryCounterFerryCancelled = 0;
        let summaryCounterFerryNoShow = 0;
        let summaryCounterLandTotalBookings = 0;
        let summaryCounterLandArrived = 0;
        let summaryCounterLandArriving = 0;
        let summaryCounterLandCancelled = 0;
        let summaryCounterLandNoShow = 0;

        reportList.data.forEach(data => {
            summaryCounterFerryTotalBookings += data.counter_ferry_total_bookings;
            summaryCounterFerryArriving += data.counter_ferry_arriving;
            summaryCounterFerryArrived += data.counter_ferry_arrived;
            summaryCounterFerryCancelled += data.counter_ferry_cancelled;
            summaryCounterFerryNoShow += data.counter_ferry_no_show;
            summaryCounterLandTotalBookings += data.counter_land_total_bookings;
            summaryCounterLandArriving += data.counter_land_arriving;
            summaryCounterLandArrived += data.counter_land_arrived;
            summaryCounterLandCancelled += data.counter_land_cancelled;
            summaryCounterLandNoShow += data.counter_land_no_show;
        });

        return (
            <>
                <Table.Summary.Row>
                    <Table.Summary.Cell><Typography.Title level={4}>TOTAL</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryCounterLandTotalBookings}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryCounterFerryTotalBookings}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryCounterLandArriving}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryCounterLandArrived}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryCounterLandCancelled}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryCounterLandNoShow}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryCounterFerryArriving}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryCounterFerryArrived}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryCounterFerryCancelled}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryCounterFerryNoShow}</Typography.Title></Table.Summary.Cell>
                </Table.Summary.Row>
            </>
        );
    }

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

        queryCache.invalidateQueries(['reports', 'daily-booking-per-sd', startDate, endDate]);
    }

    return (
        <>
            <Typography.Title level={4}>Daily Bookings Per Sales Director</Typography.Title>

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
                bordered
                columns={columns}
                dataSource={reportList?.data || []}
                loading={reportList.status === 'loading'}
                rowKey="id"
                scroll={{ x: 'max-content' }}
                summary={summary}
            />
        </>
    )
}

export default Page;