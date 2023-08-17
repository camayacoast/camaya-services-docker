import React, { useState } from 'react'
import { Typography, Table, Form, DatePicker, Button, Row, Col, Space } from 'antd'
import { DownloadOutlined, ReloadOutlined } from '@ant-design/icons'
import { queryCache } from 'react-query'

import TranportationReportService from 'services/Transportation/Report'

function Page(props) {
    const [form] = Form.useForm();
    const reportQuery = TranportationReportService.ferrySeatsPerSD;
    const downloadReport = TranportationReportService.ferrySeatsPerSDDownload;
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const reportList = reportQuery(startDate, endDate);

    const columns = [
        {
            title: 'Sales Director',
            dataIndex: 'sales_director',
        },
        {
            title: 'Date',
            dataIndex: 'date',
            align: 'center',
        },
        {
            title: 'Transportation',
            dataIndex: 'name_of_ferry',
            align: 'center',
        },
        {
            title: 'ETD',
            dataIndex: 'etd',
            align: 'center',
        },
        {
            title: 'ETA',
            dataIndex: 'eta',
            align: 'center',
        },        
        {
            title: 'Number of Pax Occupied (Booked by POC Agent)',
            dataIndex: 'total_pax_booked',
            align: 'center',
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

        queryCache.invalidateQueries(['reports', 'ferry-seats-per-sd', startDate, endDate]);
    }

    return (
        <>
            <Typography.Title level={4}>Ferry Seats Per Sales Director</Typography.Title>

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