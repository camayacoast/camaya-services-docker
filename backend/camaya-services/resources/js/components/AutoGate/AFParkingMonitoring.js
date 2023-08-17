import React, { useEffect, useState } from 'react'
import { Row, Col, Card, Statistic, Button, Typography, Table, Switch, Tooltip } from 'antd'
import { QuestionCircleOutlined, ReloadOutlined } from '@ant-design/icons'

import DashboardService from 'services/AFParkingMonitoring/DashboardService'

export default function Page() {
    const dashboardDataQuery = DashboardService.data();
    const dashboardModeQuery = DashboardService.mode;
    const [mode, setMode] = useState("1");
    const [modeLoading, setModeLoading] = useState(false);
    const [tooltipTitle, setTooltipTitle] = useState('');

    const columns = [
        {
            title: '#',
            className: 'border-bottom',
            render: (text, record, index) => (index+1)
        },
        {
            title: 'Ref Number',
            className: 'border-bottom',
            dataIndex: 'reference_number',
        },
        {
            title: 'Name',
            className: 'border-bottom',
            render: (text, record) => <>{record.first_name} {record.last_name}</>
        },
        {
            title: 'Status',
            className: 'border-bottom',
            dataIndex: 'status',
        },
    ];

    useEffect(() => {
        if (dashboardDataQuery.isSuccess) {
            setMode(dashboardDataQuery.data?.data.mode || "1");
        }
    }, [dashboardDataQuery]);

    useEffect(() => {
        if (mode === "2") {
            setTooltipTitle('Reserved Parking: Will open park once guest checked-out in the hotel (make sure to configure entry/exit kiosk to mode 2)')
        } else {
            setTooltipTitle('First Come, First Serve Parking: Will open park once guest exited  (make sure to configure entry/exit kiosk to mode 1)')
        }
    }, [mode]);

    return (
        <>
            <Row gutter={[48,48]} className="mt-4">
                <Col xl={16} lg={24} md={24}>
                    <Card
                        title="Parking Slot"
                        extra={<Button icon={<ReloadOutlined />} onClick={() => dashboardDataQuery.refetch()} />}
                        headStyle={{background:'#1177fa', color: 'white'}}
                        size="large"
                        bordered={false}
                        className="card-shadow"
                        >
                        <Row gutter={[16,16]}>
                            <Col xl={6} lg={6} md={6}>
                                <Card style={{border: '1px solid black'}}>
                                    <Statistic title="Status" value={dashboardDataQuery.data?.data && dashboardDataQuery.data?.data.status || ''}/>
                                </Card>
                            </Col>
                            <Col xl={6} lg={6} md={6}>
                                <Card>
                                    <Statistic title="Total" value={dashboardDataQuery.data?.data && dashboardDataQuery.data?.data.total || '0'}/>
                                </Card>
                            </Col>
                            <Col xl={6} lg={6} md={6}>
                                <Card>
                                    <Statistic title="Used" value={dashboardDataQuery.data?.data && dashboardDataQuery.data?.data.used || '0'} />
                                </Card>
                            </Col>
                            <Col xl={6} lg={6} md={6}>
                                <Card>
                                    <Statistic title="Remaining" value={dashboardDataQuery.data?.data && dashboardDataQuery.data?.data.remaining || '0'} />
                                </Card>
                            </Col>
                        </Row>
                    </Card>
                </Col>
            </Row>
            <Row>
            <Col>
                <Typography.Title level={5}>
                    Guests Parked
                    &nbsp;
                    <Switch
                        disabled={dashboardDataQuery.data?.data.has_entry}
                        className="bg-green-6"
                        checked={mode === "1" ? false : true}
                        checkedChildren="Mode 2"
                        unCheckedChildren="Mode 1"
                        loading={dashboardDataQuery.isLoading || modeLoading}
                        onChange={(checked) => {
                            const question = 'Confirm changing mode?';
                            const response = confirm(question);

                            if (response) {
                                setModeLoading(true);
                                dashboardModeQuery(checked ? "2" : "1", function() {
                                    dashboardDataQuery.refetch();
                                    setTimeout(() => setModeLoading(false), 1500);
                                });
                            }
                        }}
                    />
                    &nbsp;
                    <Tooltip placement="right" title={tooltipTitle}>
                        <QuestionCircleOutlined />
                    </Tooltip>
                </Typography.Title>
                <Table
                    bordered
                    columns={columns}
                    dataSource={dashboardDataQuery.data?.data && dashboardDataQuery.data?.data.guests || []}
                    pagination={false}
                    rowKey="id"
                    className="mw-100"
                />
            </Col>
        </Row>
        </>
    )
}