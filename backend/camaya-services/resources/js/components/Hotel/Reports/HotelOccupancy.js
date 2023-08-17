import React, { useState } from 'react'
import { Typography, Table, Form, DatePicker, Button, Row, Col, Select, Space } from 'antd'
import { DownloadOutlined, ReloadOutlined } from '@ant-design/icons'
import { queryCache } from 'react-query'

import HotelReportService from 'services/Hotel/Report'

function Page(props) {
    const [form] = Form.useForm();
    const reportQuery = HotelReportService.hotelOccupancy;
    const downloadReport = HotelReportService.hotelOccupancyDownload;
    // const propertyLists = HotelPropertyService.list();

    // const [hotelId, setHotelId] = useState('');
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const reportList = reportQuery(startDate, endDate);

    const columns = [
        {
            title: 'Date',
            render: function(text, record) {
                return `${record.date_month} ${record.date_number}`;
            }
        }
    ];

    const expandedRowRender = (record) => {

        const expandColumn = [
            {
                title: '', // Room Type
                dataIndex: 'name',
            },
            {
                title: 'BPO',
                dataIndex: 'bpo',
                align: 'center',
            },
            {
                title: 'RE',
                dataIndex: 're',
                align: 'center',
            },
            {
                title: 'HOA',
                dataIndex: 'hoa',
                align: 'center',
            },
            {
                title: 'FOC',
                dataIndex: 'foc',
                align: 'center',
            },
            {
                title: 'Walk-in',
                dataIndex: 'walkIn',
                align: 'center',
            },
            {
                title: 'Occupancy',
                dataIndex: 'occupancy',
                align: 'center',
            },
            {
                title: '%',
                align: 'center',
                dataIndex: 'percent',
            },
        ];

        const expandDataSource = [
            {
                id: 1,
                name: `Deluxe Twin (${record.deluxe_twin_room_total})`,
                bpo: record.deluxe_twin_bpo,
                re: record.deluxe_twin_re,
                hoa: record.deluxe_twin_hoa,
                foc: record.deluxe_twin_foc,
                walkIn: record.deluxe_twin_walk_in,
                occupancy: record.deluxe_twin_occupancy,
                percent: record.deluxe_twin_percent,
            },
            {
                id: 2,
                name: `Family Suite (${record.family_suite_room_total})`,
                bpo: record.family_suite_bpo,
                re: record.family_suite_re,
                hoa: record.family_suite_hoa,
                foc: record.family_suite_foc,
                walkIn: record.family_suite_walk_in,
                occupancy: record.family_suite_occupancy,
                percent: record.family_suite_percent,
            },
            {
                id: 3,
                name: `Deluxe Queen (${record.deluxe_queen_room_total})`,
                bpo: record.deluxe_queen_bpo,
                re: record.deluxe_queen_re,
                hoa: record.deluxe_queen_hoa,
                foc: record.deluxe_queen_foc,
                walkIn: record.deluxe_queen_walk_in,
                occupancy: record.deluxe_queen_occupancy,
                percent: record.deluxe_queen_percent,
            },
            {
                id: 4,
                name: `Executive Suite (${record.executive_suite_room_total})`,
                bpo: record.executive_suite_bpo,
                re: record.executive_suite_re,
                hoa: record.executive_suite_hoa,
                foc: record.executive_suite_foc,
                walkIn: record.executive_suite_walk_in,
                occupancy: record.executive_suite_occupancy,
                percent: record.executive_suite_percent,
            },
            {
                id: 5,
                name: `Total: ${record.total_rooms} rooms`,
                bpo: record.total_bpo,
                re: record.total_re,
                hoa: record.total_hoa,
                foc: record.total_foc,
                walkIn: record.total_walk_in,
                occupancy: record.total_occupancy,
                percent: record.total_percent,
            },
        ]

        return <Table columns={expandColumn}
            dataSource={expandDataSource}
            pagination={false}
            rowKey="id"
        />;
    }

    const onFilter = (values) => {
        if (!values.date) {
            setStartDate('');
            setEndDate('');

            return;
        }

        // setHotelId(values.hotel);
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

        queryCache.invalidateQueries(['reports', 'hotel-occupancy', startDate, endDate]);
    }

    return (
        <>
            <Typography.Title level={4}>Hotel Occupancy</Typography.Title>

            <Row justify="center" className="my-3">
                <Col>
                    <Form
                        form={form}
                        onFinish={onFilter}
                        layout="inline"
                    >
                        <Row justify="center" align="middle">
                        <Col>
                            {/* <Form.Item name="hotel" label="Select Hotel">
                                <Select placeholder="Hotel">
                                    {propertyLists?.data?.map(property =>
                                        <Select.Option key={property.id} value={property.id}>
                                            {property.name}
                                        </Select.Option>)}
                                </Select>
                            </Form.Item> */}
                            <Form.Item name="date" label="Select Date">
                                <DatePicker.RangePicker />
                            </Form.Item>
                        </Col>
                        <Col>
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
                        </Col>
                        </Row>
                    </Form>
                </Col>
            </Row>

            <Table
                loading={reportList.status === 'loading'}
                columns={columns}
                dataSource={reportList?.data || []}
                expandable={{ expandedRowRender }}
                rowKey="id"
                scroll={{ x: 'max-content' }}
                bordered
            />
        </>
    )
}

export default Page;