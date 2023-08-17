import React from 'react'

import { Card, Row, Col, Statistic, Space, Select, Progress, Table } from 'antd'
import { BarChartOutlined, LineChartOutlined, FileTextOutlined, TeamOutlined } from '@ant-design/icons';

import SalesAdminPortalServices from 'services/SalesAdminPortal'

import { Bar } from 'react-chartjs-2';

export default function Page(props) {

    const [lotInventoryData, setlotInventoryData] = React.useState({
        labels: [],
        datasets: [
          {
            label: 'Total Reserved Inventory',
            data: [],
            backgroundColor: 'limegreen',
            // ],
            borderWidth: 1,
          },
        ],
      });

    const [dashboardQuery, {IsLoading: dashboardQueryIsLoading, reset: dashboardQueryReset}] = SalesAdminPortalServices.dashboard();
    const [collectionReceivablesQuery, {IsLoading: collectionReceivablesQueryIsLoading, reset: collectionReceivablesQueryReset}] = SalesAdminPortalServices.collectionReceivable();
    const [collectionRevenueQuery, {IsLoading: collectionRevenueQueryIsLoading, reset: collectionRevenueQueryReset}] = SalesAdminPortalServices.collectionRevenue();

    const [dashboardData, setDashboardData] = React.useState({});
    const [selectedReceivableTerm, setSelectedReceivableTerm] = React.useState('in_house');
    const [selectedReceivableYear, setSelectedReceivableYear] = React.useState(new Date().getFullYear());
    const [selectedRevenueMonth, setSelectedRevenueMonth] = React.useState(new Date().getMonth() + 1);
    const [selectedRevenueYear, setSelectedRevenueYear] = React.useState(new Date().getFullYear());
    const [totalAmountReceivables, setTotalAmountReceivables] = React.useState(0);
    const [totalAmountReceivablePaid, setTotalAmountReceivablePaid] = React.useState(0);
    const [totalAmountReceivablePaidPercentage, setTotalAmountReceivablePaidPercentage] = React.useState(0);
    const [totalAmountReceivableUnpaid, setTotalAmountReceivableUnpaid] = React.useState(0);
    const [totalAmountReceivableUnpaidPercentage, setTotalAmountReceivableUnpaidPercentage] = React.useState(0);
    const [receivablesGraph, setReceivablesGraph] = React.useState([]);

    const [dataRevenues, setDataRevenues] = React.useState([]);

    React.useEffect( () => {

        // Transacted accounts and sales clients
        dashboardQuery({},
            {
                onSuccess: (res) => {
                    console.log(res.data);
                    setDashboardData(res.data);
                },
                onError: (e) => {
                    console.log(e);
                }
            }
        );

        // Amount receivables
        setReceivableData({term: selectedReceivableTerm, year: selectedReceivableYear});

        // Total revenues
        setRevenueData({month: selectedRevenueMonth, year: selectedReceivableYear});

    }, []);

    const options = {
        responsive: true,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        stacked: false,
        plugins: {
          title: {
            display: true,
            text: 'Chart.js Line Chart - Multi Axis',
          },
        },
        scales: {
          xAxes: [{
            display: true,
                scaleLabel: {
                    display: true,
                    labelString: 'January - December ' + selectedReceivableYear
                }
            }],
          yAxes: [{
            display: true,
            ticks: {
                beginAtZero: true,
                steps: 10,
                stepValue: 5,
                // max: 58000000
                callback: (value, index, values) => {
                    if(parseInt(value) >= 1000){
                        return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    } else {
                        return value;
                    }
                }
            }
          }]
        },
      };
    
    const data = {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May','Jun', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'],
        datasets: receivablesGraph,
    }

    const getYearOptions = () => {
        let start_date = new Date().getFullYear() + 20;
        let options = [];
        for( let i = start_date; i > start_date - (start_date - 2009); i--  ) {
            options.push({value: i, label: i});
        }
        return options;
    }

    const setReceivableData = (params) => {

        if (collectionReceivablesQueryIsLoading) {
            return false;
        }

        setSelectedReceivableTerm(params.term);
        setSelectedReceivableYear(params.year);

        collectionReceivablesQuery({term: params.term, year: params.year},
            {
                onSuccess: (res) => {
                    let data = res.data;

                    let paidPercent = ((data.total_amount_receivables_paid / data.total_amount_receivables) * 100).toFixed(2);
                    let unpaidPercent = ((data.total_amount_receivables_unpaid / data.total_amount_receivables) * 100).toFixed(2);

                    setTotalAmountReceivables(data.total_amount_receivables);
                    setTotalAmountReceivablePaid(data.total_amount_receivables_paid);
                    setTotalAmountReceivablePaidPercentage(!isNaN(paidPercent) ? paidPercent : 0);
                    setTotalAmountReceivableUnpaid(data.total_amount_receivables_unpaid);
                    setTotalAmountReceivableUnpaidPercentage(!isNaN(unpaidPercent) ? unpaidPercent : 0);
                    setReceivablesGraph([
                        {
                          label: 'Paid',
                          data: _.map(data.monthly_amount_receivables.paid),
                          fill: false,
                          backgroundColor: 'rgba(17, 119, 250, 0.7)',
                          borderColor: 'rgba(200, 200, 200, 0.2)',
                        },
                        {
                          label: 'Unpaid',
                          data: _.map(data.monthly_amount_receivables.unpaid),
                          fill: false,
                          backgroundColor: 'rgba(250, 157, 17, 0.7)',
                          borderColor: 'rgba(200, 200, 200, 0.2)',
                        }, 
                        {
                            label: 'Total',
                            data: _.map(data.monthly_amount_receivables.total),
                            fill: false,
                            backgroundColor: 'rgba(11, 124, 4, 0.7)',
                            borderColor: 'rgba(200, 200, 200, 0.2)',
                        }, 
                    ]);
                },
                onError: (e) => {
                    collectionReceivablesQueryReset();
                    console.log(e);
                }
            }
        );
    };
    
    const columns = [{
        title: '',
        dataIndex: 'label',
        key: 'label',
        render: (text, record) => {
            let fw = (text == 'Total') ? 'bold' : 'normal';
            return <div style={{fontWeight: fw}}>{text}</div>;
        }
    }];
    columns.push({
        title: '',
        dataIndex: 'amount',
        key: 'amount',
        render: (amount, record) => {
            let fw = (record.label == 'Total') ? 'bold' : 'normal';
            return <div style={{fontWeight: fw}}>{amount}</div>;
        }
    });

    const setRevenueData = (params) => {

        if (collectionRevenueQueryIsLoading) {
            return false;
        }

        setSelectedRevenueMonth(params.month);
        setSelectedRevenueYear(params.year);

        collectionRevenueQuery({month: params.month, year: params.year},
            {
                onSuccess: (res) => {
                    let data = res.data;
                    setDataRevenues(data);
                },
                onError: (e) => {
                    collectionRevenueQueryReset();
                    console.log(e);
                }
            }
        );
    };

    return (
        <div className="mt-4">


            <Row gutter={[16,16]}>
                <Col xl={18} md={24}>
                    <Row gutter={[16,16]}>
                        <Col xl={12} md={12}>
                            <Card title={<><FileTextOutlined /> Transacted Accounts</>} headStyle={{background: '#1177fa', color: 'white'}}>
                                <Space size="large">
                                    {/* <Statistic title="For verification" value="14" /> */}
                                    {/* <Statistic title="Confirmed" value="33" /> */}
                                    <Statistic title="Pending" value={dashboardData?.reservations?.pending} />
                                    <Statistic title="Reviewed" value={dashboardData?.reservations?.reviewed} />
                                    <Statistic title="Approved" value={dashboardData?.reservations?.approved} />
                                    <Statistic title="Draft" value={dashboardData?.reservations?.draft} />
                                </Space>
                            </Card>
                        </Col>

                        <Col xl={12} md={12}>
                            <Card title={<><TeamOutlined /> Sales Clients</>} headStyle={{background: '#1177fa', color: 'white'}}>
                                <Space size="large">
                                    {/* <Statistic title="For verification" value="1" /> */}
                                    {/* <Statistic title="Active" value="100" /> */}
                                    <Statistic title="Total" value={dashboardData?.clients?.total} />
                                    <Statistic title="For review" value={dashboardData?.clients?.for_review} />
                                </Space>
                            </Card>
                        </Col>

                        <Col xl={24}>

                            <Card title={<><BarChartOutlined /> Amortization Amount Receivables</>} headStyle={{background: '#1177fa', color: 'white'}}>

                                <Select
                                    defaultValue='in_house'
                                    style={{ width: 120, marginRight: '10px' }}
                                    options = {[
                                        {value: 'in_house', label: 'In House'},
                                        {value: 'cash', label: 'Cash'}
                                    ]}
                                    onChange = {(e) => setReceivableData({term: e, year: selectedReceivableYear})}
                                />

                                <Select
                                    defaultValue={selectedReceivableYear}
                                    style={{ width: 120 }}
                                    options = {getYearOptions()}
                                    onChange = {(e) => setReceivableData({term: selectedReceivableTerm, year: e})}
                                />

                                <Row gutter={[16,16]}>

                                    <Col xl={16} md={24}>
                                        <Bar options={options} data={data} />
                                    </Col>

                                    <Col xl={8}>
                                        <Row gutter={[16,16]}>
                                            <Col xl={24} md={24}>
                                                <Row gutter={[16,16]}>
                                                    <Col xl={6}>
                                                        <Progress type='circle' width={100} percent={totalAmountReceivablePaidPercentage} strokeColor='rgba(17, 119, 250, 0.7)' status='active' />
                                                    </Col>
                                                    <Col xl={18}>
                                                        <Space size="large" style={{marginTop: '30px', marginLeft: '40px'}}>
                                                            <Statistic title="Total Amount Paid" value={totalAmountReceivablePaid} />
                                                        </Space>
                                                    </Col>
                                                </Row>
                                            </Col>
                                        </Row>
                                        <Row gutter={[16,16]}>
                                            <Col xl={24} md={24}>
                                                <Row gutter={[16,16]}>
                                                    <Col xl={6}>
                                                        <Progress type='circle' width={100} percent={totalAmountReceivableUnpaidPercentage} strokeColor='rgba(250, 157, 17, 0.7)' status='active' />
                                                    </Col>
                                                    <Col xl={18}>
                                                        <Space size="large" style={{marginTop: '30px', marginLeft: '40px'}}>
                                                            <Statistic title="Total Amount Unpaid" value={totalAmountReceivableUnpaid} />
                                                        </Space>
                                                    </Col>
                                                </Row>
                                            </Col>
                                        </Row>
                                        <Row gutter={[16,16]}>
                                            <Col xl={24} md={24}>
                                                <Space size="large" style={{marginTop: '30px', marginLeft: '20px', marginBottom: '10px'}}>
                                                    <Statistic title="Total Amount" value={totalAmountReceivables} />
                                                </Space>
                                            </Col>
                                        </Row>
                                    </Col>

                                </Row>

                            </Card>

                        </Col>
                    </Row>
                </Col>
                <Col xl={6} md={24}>
                    <Row gutter={[16,16]}>
                        <Col xl={24}>
                            <Card title={<><LineChartOutlined /> Revenues</>} headStyle={{background: '#1177fa', color: 'white'}}>
                                <Select
                                    defaultValue={selectedRevenueMonth}
                                    style={{ width: 120, marginRight: '10px' }}
                                    options = {[
                                        {value: 1, label: 'January'},
                                        {value: 2, label: 'February'},
                                        {value: 3, label: 'March'},
                                        {value: 4, label: 'April'},
                                        {value: 5, label: 'May'},
                                        {value: 6, label: 'June'},
                                        {value: 7, label: 'July'},
                                        {value: 8, label: 'August'},
                                        {value: 9, label: 'September'},
                                        {value: 10, label: 'October'},
                                        {value: 11, label: 'November'},
                                        {value: 12, label: 'December'},
                                    ]}
                                    onChange = {(e) => setRevenueData({month: e, year: selectedRevenueYear})}
                                />
                                <Select
                                    defaultValue={selectedRevenueYear}
                                    style={{ width: 120 }}
                                    options = {getYearOptions()}
                                    onChange = {(e) => setRevenueData({month: selectedRevenueMonth, year: e})}
                                />

                                <Row gutter={[16,16]}>
                                    <Col xl={24}>
                                        <Table dataSource={dataRevenues} columns={columns} pagination={false} style={{width: '100%'}} scroll={{ x: 300 }}/>
                                    </Col>
                                </Row>
                            </Card>
                        </Col>
                    </Row>
                </Col>
            </Row>
        </div>
    )
}