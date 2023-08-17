import React from 'react'
import { queryCache } from 'react-query'
import TransportationLocationService from 'services/Transportation/Location'
import TransportationRouteService from 'services/Transportation/Route'

import { Table, Space, Button, Typography, Form, Modal, Row, Col, Input, Select, message, notification } from 'antd'
import { PlusOutlined, ArrowRightOutlined } from '@ant-design/icons'

function Page(props) {

    const locationListQuery = TransportationLocationService.list();
    const routeListQuery = TransportationRouteService.list();

    const [newRouteQuery, {isLoading: newRouteQueryIsLoading, error: newRouteQueryError}] = TransportationRouteService.create();

    const [routeList, setrouteList] = React.useState(routeListQuery.data);

    const [newRouteForm] = Form.useForm();

    const columns = [
        // {
        //     title: 'ID',
        //     dataIndex: 'id',
        //     key: 'id',
        // },
        {
              title: 'Route',
              dataIndex: 'route',
              key: 'route',
              render: (text, record) => (
                <Row>
                    <Col xl={6}>{record.origin.name}<br/><small>{record.origin.code}</small></Col>
                    <Col xl={6} align="middle" justify="center"><ArrowRightOutlined /></Col>
                    <Col xl={6}>{record.destination.name}<br/><small>{record.destination.code}</small></Col>
                </Row>
              )
        },
        {
            title: 'Action',
            key: 'action',
            render: (text, record) => (
              <Space size="middle">
                <Button type="link" onClick={() => console.log(record.id, 'view')}>View</Button>
              </Space>
            ),
        },
    ];

    // const routeListQuery = {
    //     data: [
    //         {
    //             id: 1,
    //             origin: {
    //                 name: 'Esplanade Seaside Terminal',
    //                 code: 'EST',
    //             },
    //             destination: {  
    //                 name: 'Camaya Coast',
    //                 code: 'CMY',
    //             }
    //         }
    //     ]
    // }

    React.useEffect( () => {
        setrouteList(routeListQuery.data);
    }, [routeListQuery.data]);

    const handleOriginChange = (e) => {

        const destination = newRouteForm.getFieldValue('destination');

        if (e == destination) {
            message.warning('Your origin can not be the same as the destination.');
            newRouteForm.setFieldsValue({
                origin: null,
            });
        }

    }

    const handleDestinationChange = (e) => {
        
        const origin = newRouteForm.getFieldValue('origin');

        if (e == origin) {
            message.warning('Your destination can not be the same as the origin.');
            newRouteForm.setFieldsValue({
                destination: null,
            });
        }

    }

    const RouteForm = () => (
        <>
            <Row gutter={[12,12]} className="mt-4">
                <Col xl={24}>
                    <Form.Item
                        name="origin"
                        label="Origin"
                        rules={[{ required: true } ]}
                    >
                        <Select
                            onChange={handleOriginChange}
                        >
                            {
                                locationListQuery.data && locationListQuery.data.map( (item, key) => (
                                    <Select.Option key={key} value={item.id}>{item.name}</Select.Option>
                                ))
                            }
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={24}>
                    <Form.Item
                        name="destination"
                        label="Destination"
                        rules={[{ required: true } ]}
                    >
                        <Select
                            onChange={handleDestinationChange}
                        >
                            {
                                locationListQuery.data && locationListQuery.data.map( (item, key) => (
                                    <Select.Option key={key} value={item.id}>{item.name}</Select.Option>
                                ))
                            }
                        </Select>
                    </Form.Item>
                </Col>
                
            </Row>
        </>
)

    const showNewRouteModal = () => {

        Modal.confirm({
            title: 'Create new route',
            icon: null,
            content: (
                <Form
                    layout="vertical"
                    form={newRouteForm}
                    onFinish={newRouteFormFinish}
                >
                    <RouteForm/>
                </Form>
            ),
            onOk() {
                newRouteForm.submit();
            },
            onCancel() {
                console.log('Cancel');
                newRouteForm.resetFields();
            },
        });


    }

    const newRouteFormFinish = (values) => {
        console.log(values);

        newRouteQuery(values, {
            onSuccess: (res) => {
                console.log(res.data);

                queryCache.setQueryData(['routes', { id: res.data.id }], res.data);

                setrouteList(prev => [...prev, {...res.data}]);
                // routeListQuery.data.push({...res.data});

                newRouteForm.resetFields();

                notification.success({
                    message: `New Route - ${res.data.origin.name} -> ${res.data.destination.name} Added!`,
                    description:
                        ``,
                });
            },
            onError: (res) => {
                // console.log(res);
                notification.error({
                    message: `${res.error}`,
                    description:
                        ``,
                });

                newRouteForm.resetFields();
            }
        });
    }

    return (
        <>
            <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexDirection:'row'}}>
                <Typography.Title level={4}>Routes</Typography.Title>
                <Button type="primary" onClick={showNewRouteModal}><PlusOutlined/> Add Route</Button>
            </div>
            
            <Table 
                loading={routeListQuery.status === 'loading'}
                columns={columns}
                // dataSource={tableFilters && tableFilters.status ? myBookingsFiltered : myBookingsQuery.data ? myBookingsQuery.data : []}
                dataSource={routeList}
                rowKey="id"
                rowClassName="table-row"
                // size="small"
                // onChange={(pagination, filters, sorter) => handleTableChange(pagination, filters, sorter, 'all')}
            />
        </>
    )
}

export default Page;