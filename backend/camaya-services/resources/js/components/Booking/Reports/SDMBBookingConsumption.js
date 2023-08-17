import React, { useState, useEffect } from 'react'
import moment from 'moment-timezone'
import { Typography, Table, Form, DatePicker, Button, Row, Col, Space } from 'antd'
import { DownloadOutlined, ReloadOutlined } from '@ant-design/icons'
import { queryCache } from 'react-query'

import BookingReportService from 'services/Booking/ReportService'

function Page(props) {
    const [form] = Form.useForm();
    const reportQuery = BookingReportService.sdmbBookingConsumption;
    const downloadReport = BookingReportService.sdmbBookingConsumptionDownload;
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const reportList = reportQuery(startDate, endDate);

    const numberWithCommas = (x) => {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    const columns = [
        {
            title: 'Sales Director',
            dataIndex: 'sd_name',
            className: 'border-bottom',
        },
        {
            title: 'Land',
            // dataIndex: 'land_total_sales',
            render: (rec) => numberWithCommas('₱ '+ (rec.land_total_sales)),
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Ferry',
            render: (rec) => numberWithCommas('₱ '+ (rec.ferry_total_sales)),
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Total',
            render: (rec) => numberWithCommas('₱ '+ ((rec.land_total_sales) + (rec.ferry_total_sales))),
            className: 'border-bottom',
            align: 'center',
        },
    ];

    const summary = () => {
        if (! reportList?.data?.length) {
            return;
        }

        let summaryLandTotalSales = 0;
        let summaryFerryTotalSales = 0;
        

        reportList.data.forEach(data => {
            summaryLandTotalSales += data.land_total_sales;
            summaryFerryTotalSales += data.ferry_total_sales;
        });

        return (
            <>
                <Table.Summary.Row>
                    <Table.Summary.Cell><Typography.Title level={4}>Grand Total</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>₱ {numberWithCommas(summaryLandTotalSales)}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>₱ {numberWithCommas(summaryFerryTotalSales)}</Typography.Title></Table.Summary.Cell>

                    <Table.Summary.Cell align="center"><Typography.Title level={4}>₱ {numberWithCommas(summaryLandTotalSales + summaryFerryTotalSales)}</Typography.Title></Table.Summary.Cell>
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

        queryCache.invalidateQueries(['reports', 'sdmb-booking-consumption', startDate, endDate]);
    }

    return (
        <>
            <Typography.Title level={4}>Booking Consumption Report</Typography.Title>

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