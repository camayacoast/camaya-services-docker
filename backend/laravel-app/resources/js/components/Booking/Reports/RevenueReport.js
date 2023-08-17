import React, { useState, useEffect } from 'react'
import moment from 'moment-timezone'
import { Typography, Table, Form, DatePicker, Button, Row, Col, Space } from 'antd'
import { DownloadOutlined, ReloadOutlined } from '@ant-design/icons'
import { queryCache } from 'react-query'

import BookingReportService from 'services/Booking/ReportService'

function Page(props) {
    const [form] = Form.useForm();

    const [getRevenueReportQuery, { isLoading: getRevenueReportQueryIsLoading, reset: getRevenueReportQueryReset }] = BookingReportService.revenueReport();
    const downloadReport = BookingReportService.revenueReportDownload;
    
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [records, setRecords] = useState([]);

    const numberWithCommas = (x) => {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

    const columns = [
        {
            title: 'Promos',
            align: 'center',
            dataIndex: 'promo',
            className: 'border-bottom',
        },
        {
            title: 'BPO',
            className: 'border-bottom',
            children: [
                {
                    title: 'Weekday',
                    className: 'border-bottom',
                    align: 'center',
                    children: [
                        {
                            title: 'Pax',
                            render: (rec) => rec.bpo.wd.pax,
                            align: 'center',
                            className: 'border-bottom',
                        },
                        {
                            title: 'Sales',
                            // render: (rec) => rec.bpo.wd.sales,
                            render: (rec) => numberWithCommas('₱ '+ (rec.bpo.wd.sales)),
                            align: 'center',
                            className: 'border-bottom',
                        },
                    ]
                },
                {
                    title: 'Weekend',
                    className: 'border-bottom',
                    align: 'center',
                    children: [
                        {
                            title: 'Pax',
                            render: (rec) => rec.bpo.we.pax,
                            align: 'center',
                            className: 'border-bottom',
                        },
                        {
                            title: 'Sales',
                            render: (rec) => numberWithCommas('₱ '+ (rec.bpo.we.sales)),
                            align: 'center',
                            className: 'border-bottom',
                        },
                    ]
                },
            ]
        },
        {
            title: 'RE',
            dataIndex: 'status',
            className: 'border-bottom',
            children: [
                {
                    title: 'Weekday',
                    className: 'border-bottom',
                    align: 'center',
                    children: [
                        {
                            title: 'Pax',
                            render: (rec) => rec.re.wd.pax,
                            align: 'center',
                            className: 'border-bottom',
                        },
                        {
                            title: 'Sales',
                            render: (rec) => numberWithCommas('₱ '+ (rec.re.wd.sales)),
                            align: 'center',
                            className: 'border-bottom',
                        },
                    ]
                },
                {
                    title: 'Weekend',
                    className: 'border-bottom',
                    align: 'center',
                    children: [
                        {
                            title: 'Pax',
                            render: (rec) => rec.re.we.pax,
                            align: 'center',
                            className: 'border-bottom',
                        },
                        {
                            title: 'Sales',
                            render: (rec) => numberWithCommas('₱ '+ (rec.re.we.sales)),
                            align: 'center',
                            className: 'border-bottom',
                        },
                    ]
                },
            ]
        },
    ];

    const summary = () => {
        if (! records.length) {
            return;
        }

        let summaryBPO_WD_Pax = 0;
        let summaryBPO_WD_Sales = 0;
        let summaryBPO_WE_Pax = 0;
        let summaryBPO_WE_Sales = 0;
        let summaryRE_WD_Pax = 0;
        let summaryRE_WD_Sales = 0;
        let summaryRE_WE_Pax = 0;
        let summaryRE_WE_Sales = 0;

        records.forEach(data => {
            summaryBPO_WD_Pax += data.bpo.wd.pax;
            summaryBPO_WD_Sales += data.bpo.wd.sales;
            summaryBPO_WE_Pax += data.bpo.we.pax;
            summaryBPO_WE_Sales += data.bpo.we.sales;
            summaryRE_WD_Pax += data.re.wd.pax;
            summaryRE_WD_Sales += data.re.wd.sales;
            summaryRE_WE_Pax += data.re.we.pax;
            summaryRE_WE_Sales += data.re.we.sales;
        });

        return (
            <>
                <Table.Summary.Row>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>TOTAL</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryBPO_WD_Pax}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>₱ {numberWithCommas(summaryBPO_WD_Sales)}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryBPO_WE_Pax}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>₱ {numberWithCommas(summaryBPO_WE_Sales)}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryRE_WD_Pax}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>₱ {numberWithCommas(summaryRE_WD_Sales)}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>{summaryRE_WE_Pax}</Typography.Title></Table.Summary.Cell>
                    <Table.Summary.Cell align="center"><Typography.Title level={4}>₱ {numberWithCommas(summaryRE_WE_Sales)}</Typography.Title></Table.Summary.Cell>
                </Table.Summary.Row>
            </>
        );
    }

    React.useEffect(() => {
        if (endDate) {
            getRevenueReport();
        }
    },[endDate])

    const getRevenueReport = () => {
        if (getRevenueReportQueryIsLoading) {
            return false;
        }

        getRevenueReportQuery({
            start_date: startDate,
            end_date: endDate
        }, {
            onSuccess: (res) => setRecords(res.data),
            onError: (e) => console.log(e)
        })
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

        queryCache.invalidateQueries(['reports', 'revenue-report', startDate, endDate]);
    }

    return (
        <>
            <Typography.Title level={4}>Revenue Report</Typography.Title>

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
                                    disabled={records
                                        && records.length
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
                dataSource={records || []}
                loading={getRevenueReportQueryIsLoading}
                rowKey="promo"
                scroll={{ x: 'max-content' }}
                summary={summary}
            />
        </>
    )
}

export default Page;