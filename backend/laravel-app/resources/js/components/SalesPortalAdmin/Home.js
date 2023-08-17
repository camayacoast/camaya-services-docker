import React from 'react'

import { Card, Row, Col, Statistic, Space } from 'antd'

import { Bar } from 'react-chartjs-2';

import SalesAdminPortalServices from 'services/SalesAdminPortal'

const data = {
    labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
    datasets: [
      {
        label: 'Total Reserved Inventory',
        data: [12, 19, 3, 5, 2, 3],
        backgroundColor: 'limegreen',
        // backgroundColor: [
        //   'rgba(255, 99, 132, 0.2)',
        //   'rgba(54, 162, 235, 0.2)',
        //   'rgba(255, 206, 86, 0.2)',
        //   'rgba(75, 192, 192, 0.2)',
        //   'rgba(153, 102, 255, 0.2)',
        //   'rgba(255, 159, 64, 0.2)',
        // ],
        // borderColor: [
        //   'rgba(255, 99, 132, 1)',
        //   'rgba(54, 162, 235, 1)',
        //   'rgba(255, 206, 86, 1)',
        //   'rgba(75, 192, 192, 1)',
        //   'rgba(153, 102, 255, 1)',
        //   'rgba(255, 159, 64, 1)',
        // ],
        borderWidth: 1,
      },
    ],
  };


const options = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      yAxes: [
        {
          ticks: {
            beginAtZero: true,
            min: 0,
            max: 100
          },
        },
      ],
    },
  };

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

    const [condoInventoryData, setCondoLotInventoryData] = React.useState({
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

    const [lotInventoryDashboardQuery, {IsLoading: lotInventoryDashboardQueryIsLoading, reset: lotInventoryDashboardQueryReset}] = SalesAdminPortalServices.lotInventoryDashboard();
    const [dashboardQuery, {IsLoading: dashboardQueryIsLoading, reset: dashboardQueryReset}] = SalesAdminPortalServices.dashboard();

    const [dashboardData, setDashboardData] = React.useState({});

    React.useEffect( () => {

        lotInventoryDashboardQuery({},
            {
                onSuccess: (res) => {
                    const lot_percentage_per_subdivision = res.data.lot.map( i => {
                        return (i.reserved / i.total * 100).toFixed(2);
                    })

                    setlotInventoryData( prev => {
                        return {
                            ...prev,
                            labels: res.data.lot.map( i => i.subdivision+" (Total: "+i.total+")"),
                            datasets: [{
                                label: '% of Total Reserved Inventory',
                                data: lot_percentage_per_subdivision,
                                backgroundColor: 'limegreen',
                                borderWidth: 1,
                            }]
                        }
                    })

                    const condo_percentage_per_subdivision = res.data.condo.map( i => {
                        return (i.reserved / i.total * 100).toFixed(2);
                    });

                    setCondoLotInventoryData( prev => {
                        return {
                            ...prev,
                            labels: res.data.condo.map( i => i.subdivision+" (Total: "+i.total+")"),
                            datasets: [{
                                label: '% of Total Reserved Inventory',
                                data: condo_percentage_per_subdivision,
                                backgroundColor: 'limegreen',
                                borderWidth: 1,
                            }]
                        }
                    })
                },
                onerror: (e) => console.log(e),
            }
        );


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

    }, []);

    return (
        <div className="mt-4">
            <Row gutter={[16,16]}>

                <Col xl={8}>
                    <Card title="Reservation Documents" headStyle={{background: '#1177fa', color: 'white'}}>
                        <Space size="large">
                            {/* <Statistic title="For verification" value="14" /> */}
                            {/* <Statistic title="Confirmed" value="33" /> */}
                            <Statistic title="Pending" value={dashboardData?.reservations?.pending} />
                            <Statistic title="Reviewed" value={dashboardData?.reservations?.reviewed} />
                            <Statistic title="Approved" value={dashboardData?.reservations?.approved} />
                        </Space>
                    </Card>
                </Col>

                <Col xl={8}>
                    <Card title="Sales Clients" headStyle={{background: '#1177fa', color: 'white'}}>
                        <Space size="large">
                            {/* <Statistic title="For verification" value="1" /> */}
                            {/* <Statistic title="Active" value="100" /> */}
                            <Statistic title="Total" value={dashboardData?.clients?.total} />
                            <Statistic title="For review" value={dashboardData?.clients?.for_review} />
                        </Space>
                    </Card>
                </Col>

                <Col xl={24}>
                    <Card title="Lot inventory" 
                        headStyle={{background: '#1177fa', color: 'white'}}
                        bodyStyle={{height: '500px'}}
                    >
                        {/* <Space size="large"> */}
                            {/* <Statistic title="For verification" value="14" /> */}
                            {/* <Statistic title="Confirmed" value="33" /> */}
                            {/* <Statistic title="Count" value="-" /> */}
                            <Bar data={lotInventoryData} options={options} style={{width: '100%'}} />
                        {/* </Space> */}
                    </Card>
                </Col>

                <Col xl={24}>
                    <Card title="Condominium inventory" 
                        headStyle={{background: '#1177fa', color: 'white'}}
                        bodyStyle={{height: '500px'}}
                    >
                        {/* <Space size="large"> */}
                            {/* <Statistic title="For verification" value="14" /> */}
                            {/* <Statistic title="Confirmed" value="33" /> */}
                            {/* <Statistic title="Count" value="-" /> */}
                            <Bar data={condoInventoryData} options={options} style={{width: '100%'}} />
                        {/* </Space> */}
                    </Card>
                </Col>
            </Row>
        </div>
    )
}