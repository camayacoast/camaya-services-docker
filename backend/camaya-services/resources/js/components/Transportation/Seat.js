import React from 'react'
import TransportationService from 'services/Transportation'
import TransportationSeatService from 'services/Transportation/Seat'
import { queryCache } from 'react-query'

import { Typography, Select, Table, Space, Button, Row, Col, Modal, Form, Input, notification, InputNumber, message } from 'antd'
import { PlusOutlined } from '@ant-design/icons';

function Page(props) {

    const [selectedTransportation, setselectedTransportation] = React.useState(null);
    const transportationListQuery = TransportationService.list();
    const [newSeatQuery, {isLoading: newSeatQueryIsLoading, error: newSeatQueryError}] = TransportationSeatService.create();
    const [updateSeatOrderQuery, {isLoading: updateSeatOrderQueryIsLoading, error: updateSeatOrderQueryError}] = TransportationSeatService.updateSeatOrder();

    const [updateAutoCheckInStatusQuery, {isLoading: updateAutoCheckInStatusQueryIsLoading, error: updateAutoCheckInStatusQueryError}] = TransportationSeatService.updateAutoCheckInStatus();
    const [updateSeatStatusQuery, {isLoading: updateSeatStatusQueryIsLoading, error: updateSeatStatusQueryError}] = TransportationSeatService.updateSeatStatus();
    // const [updateSeatOrderQuery, {isLoading: updateSeatOrderQueryIsLoading, error: updateSeatOrderQueryError}] = TransportationSeatService.updateSeatOrder();
    // const [updateSeatOrderQuery, {isLoading: updateSeatOrderQueryIsLoading, error: updateSeatOrderQueryError}] = TransportationSeatService.updateSeatOrder();
    

    const [newSeatForm] = Form.useForm();

    const handleSeatOrderChange = (id, order) => {
        console.log(id, order);

        updateSeatOrderQuery({
            id:id,
            order: order
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success("Update seat order success!");
                // queryCache.setQueryData(["transportations"], prev => {

                //     let transportations = [...prev];

                //     const index = _.findIndex(transportations, i => i.id == selectedTransportation.id);

                //     const seatIndex = _.findIndex(transportations[index]['seats'], i => i.id == res.data.id);

                //     let seats = [...transportations[index]['seats']];
                //     seats[seatIndex] = res.data;

                //     transportations[index] = {
                //         ...transportations[index],
                //         seats: seats
                //     };

                //     return [...transportations];
                // })
            },
            onError: (e) => {
                console.log(e);
            }
        });
    }

    
    const handleAutoCheckInStatusChange = (id, auto_check_in_status) => {
        console.log(id, auto_check_in_status);

        updateAutoCheckInStatusQuery({
            id:id,
            auto_check_in_status: auto_check_in_status
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success("Update seat auto check-in status success!");
            },
            onError: (e) => {
                console.log(e);
            }
        });
    }

    const handleSeatStatusChange = (id, seat_status) => {
        console.log(id, seat_status);

        updateSeatStatusQuery({
            id:id,
            status: seat_status
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success("Update seat status success!");
            },
            onError: (e) => {
                console.log(e);
            }
        });
    }

    const columns = [
        {
          title: 'Seat #',
          dataIndex: 'number',
          key: 'number',
        },
        {
            title: 'Type',
            dataIndex: 'type',
            key: 'type',
            render: (text, record) => <>
                    <Select defaultValue={record.type}>
                        <Select.Option value="economy">Economy</Select.Option>
                        <Select.Option value="business">Business</Select.Option>
                        <Select.Option value="isolation">Isolation</Select.Option>
                    </Select>
                </>
        },
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
            render: (text, record) => <>
                    <Select onChange={(e) => handleSeatStatusChange(record.id, e)} defaultValue={record.status}>
                        <Select.Option value="active">Active</Select.Option>
                        <Select.Option value="out-of-order">Out of order</Select.Option>
                    </Select>
                </>
        },
        {
            title: 'Auto check-in status',
            dataIndex: 'auto_check_in_status',
            key: 'auto_check_in_status',
            render: (text, record) => <>
                    <Select onChange={(e)=>handleAutoCheckInStatusChange(record.id, e)} defaultValue={record.auto_check_in_status}>
                        <Select.Option value="">None</Select.Option>
                        <Select.Option value="public">Public</Select.Option>
                        <Select.Option value="restricted">Restricted</Select.Option>
                        <Select.Option value="vip">VIP</Select.Option>
                    </Select>
                </>
        },
        {
            title: 'Order #',
            dataIndex: 'order',
            key: 'order',
            defaultSortOrder: 'descend',
            sorter: (a, b) => a.order - b.order,
            render: (text, record) => <Select onChange={(e)=>handleSeatOrderChange(record.id, e)} style={{width: '100%'}} defaultValue={record.order}>
                {
                    _.map(_.range(0, selectedTransportation.capacity + 1), (i, k) => {
                        return (
                            <Select.Option key={k} value={i}>{i}</Select.Option>
                        )
                    })
                }
            </Select>
        },
    ];

    const handleSelectTransportation = (e) => {

        const transportation = _.find(transportationListQuery.data, { id: e });
        setselectedTransportation(transportation);
        console.log(transportation);

    }

    const newSeatFormFinish = (values) => {
        // console.log(values);

        newSeatQuery(values, {
            onSuccess: (res) => {
                console.log(res.data);

                queryCache.setQueryData(['seats', { id: res.data.property_id }], {seats: res.data});

                setselectedTransportation(prev => {

                    const seats = prev.seats;

                    seats.push({...res.data});

                    return {...prev, seats: [...seats], updated: res.data.updated_at};
                });

                newSeatForm.resetFields();

                notification.success({
                    message: `New Seat - ${res.data.number} Added!`,
                    description:
                        ``,
                });
            },
            onError: (e) =>{
                notification.error({
                    message: e.error,
                    description:
                        ``,
                });
            }
        });

    }

    const SeatForm = () => (
            <>
                <Form.Item
                    name="transportation_id"
                    noStyle
                    hidden={true}
                    rules={[{ required: true } ]}
                >
                    <Input/>
                </Form.Item>
                <Row gutter={[12,12]} className="mt-4">
                    <Col xl={12}>
                        <Form.Item
                            name="number"
                            label="Seat number"
                            rules={[{ required: true } ]}
                        >
                            <Input placeholder="Seat number"/>
                        </Form.Item>
                    </Col>
                    <Col xl={12}>
                        <Form.Item
                            name="type"
                            label="Type"
                            rules={[{ required: true } ]}
                        >
                            <Select>
                                <Select.Option value="economy">Economy</Select.Option>
                                <Select.Option value="business">Business</Select.Option>
                                <Select.Option value="isolation">Isolation</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>

                    <Col xl={12}>
                        <Form.Item
                            name="status"
                            label="Status"
                            rules={[{ required: true } ]}
                        >
                            <Select>
                                <Select.Option value="active">Active</Select.Option>
                                <Select.Option value="out-of-order">Out of order</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>

                    <Col xl={12}>
                        <Form.Item
                            name="auto_check_in_status"
                            label="Auto check-in Status"
                            rules={[{ required: true } ]}
                        >
                            <Select>
                                <Select.Option value="public">Public</Select.Option>
                                <Select.Option value="restricted">Restricted</Select.Option>
                                <Select.Option value="vip">VIP</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>

                    <Col xl={12}>
                        <Form.Item
                            name="order"
                            label="Seat order"
                        >
                            <InputNumber min="1" />
                        </Form.Item>
                    </Col>
                </Row>
            </>
    )

    const showNewSeatModal = () => {

        Modal.confirm({
            title: 'Create new seat for '+ selectedTransportation.name,
            icon: null,
            content: (
                <Form
                    layout="vertical"
                    form={newSeatForm}
                    onFinish={newSeatFormFinish}
                >
                    <SeatForm/>
                </Form>
            ),
            onOk() {
                newSeatForm.submit();
            },
            onCancel() {
                console.log('Cancel');
            },
        });

        newSeatForm.resetFields();
        newSeatForm.setFieldsValue({
            transportation_id: selectedTransportation.id,
            type: 'economy',
            status: 'active',
            auto_check_in_status: 'public',
        })

    }


    return (
        <>
            <Typography.Title level={4}>Seats</Typography.Title>

            
            <Row justify="space-between" className="my-4">
                <Col xl={4} xs={24}>
                    <Select style={{minWidth: 200}} placeholder="Select property" onChange={handleSelectTransportation}>
                        { 
                            transportationListQuery.data && transportationListQuery.data.map( (item, key) => (
                                <Select.Option value={item.id} key={key}>{item.name}</Select.Option>
                            ))
                        }
                    </Select>
                </Col>
                <Col xl={10} xs={24} align="right">
                    <Space>
                        <Button disabled={!selectedTransportation} onClick={()=>message.info("Coming soon...")}>View Seat Plan</Button>
                        <Button type="primary" disabled={!selectedTransportation} onClick={showNewSeatModal}><PlusOutlined/> Add Seat</Button>
                    </Space>
                </Col>
            </Row>

            <Table 
                // loading={transportationListQuery.status === 'loading'}
                columns={columns}
                // dataSource={tableFilters && tableFilters.status ? myBookingsFiltered : myBookingsQuery.data ? myBookingsQuery.data : []}
                dataSource={selectedTransportation && selectedTransportation.seats}
                rowKey="id"
                rowClassName="table-row"
                size="small"
                // onChange={(pagination, filters, sorter) => handleTableChange(pagination, filters, sorter, 'all')}
            />
        </>
    )
}

export default Page;