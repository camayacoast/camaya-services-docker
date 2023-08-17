import React from 'react'
import img from 'assets/placeholder-1-1.jpg'
import TransportationService from 'services/Transportation'
import { queryCache } from 'react-query'

import { Typography, Row, Col, Card, Button, Grid, Form, Drawer, Input, Select, InputNumber, notification } from 'antd'
import { PlusOutlined } from '@ant-design/icons'

const { useBreakpoint } = Grid;

function Page(props) {

    const screen = useBreakpoint();

    const [newTransportationQuery, {isLoading: newTransportationQueryIsLoading, error: newTransportationQueryError}] = TransportationService.create();
    const transportationListQuery = TransportationService.list();

    const [newTransportationDrawerVisible, setnewTransportationDrawerVisible] = React.useState(false);
    const [viewTransportationDrawerVisible, setviewTransportationDrawerVisible] = React.useState(false);

    const [newTransportationForm] = Form.useForm();
    const [viewTransportationForm] = Form.useForm();

    // transportationListQuery = {
    //     data: [
    //         { name: 'Test', code: 'TEST', type: 'ferry', mode: 'sea', capacity: 100, description: 'Describe your transpo', max_infant: 0, status: 'available' },
    //         { name: 'Test2', code: 'TEST2' },
    //         { name: 'Test3', code: 'TEST3' },
    //     ]
    // };

    const onNewTransportationFormFinish = values => {
        console.log(values);

        newTransportationQuery(values, {
            onSuccess: (res) => {
                console.log(res.data);

                queryCache.setQueryData(['transportations', { id: res.data.id }], res.data);

                transportationListQuery.data.push({...res.data});

                newTransportationForm.resetFields();

                setnewTransportationDrawerVisible(false);

                notification.success({
                    message: `New Transportation - ${res.data.code} Added!`,
                    description:
                        ``,
                });
            },
        });
    }

    const onViewTransportationFormFinish = values => {
        console.log(values);
    }

    const ShowFormItemError = ({name}) => {
        if (newTransportationQueryError && newTransportationQueryError.errors && newTransportationQueryError.errors[name]) {
            return newTransportationQueryError.errors[name].map( (item, key) => (
                    <div role="alert" style={{color: '#ff4d4f'}} key={key}>{item}</div>
                ))
        }

        return <></>
    }

    const TransportationForm = () => {
        return (
            <>
                <Row gutter={[12,12]}>
                    <Col xl={12}>
                        <Form.Item
                            name="name"
                            label="Name"
                            extra={<ShowFormItemError name="name" />}
                            rules={[
                                {
                                    required: true
                                }
                            ]}
                        >
                            <Input/>
                        </Form.Item>
                    </Col>
                    <Col xl={12}>
                        <Form.Item name="code" label="Code"
                            extra={<ShowFormItemError name="code" />}
                            rules={[
                            {
                                pattern: /^\S*$/,
                                message: 'No spaces allowed. Use underscore (_) instead.'
                            },
                            {
                                required: true
                            }
                        ]}>
                            <Input style={{textTransform: 'uppercase'}}/>
                        </Form.Item>
                    </Col>

                    <Col xl={8}>
                        <Form.Item
                            name="type"
                            label="Type"
                            // extra={<ShowFormItemError name="name" />}
                            rules={[
                                {
                                    required: true
                                }
                            ]}
                        >
                            <Select>
                                <Select.Option value="bus">Bus</Select.Option>
                                <Select.Option value="ferry">Ferry</Select.Option>
                                <Select.Option value="sea-plane">Sea Plane</Select.Option>
                                <Select.Option value="plane">Plane</Select.Option>
                                <Select.Option value="van">Van</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>

                    <Col xl={8}>
                        <Form.Item
                            name="mode"
                            label="Mode"
                            // extra={<ShowFormItemError name="name" />}
                            rules={[
                                {
                                    required: true
                                }
                            ]}
                        >
                            <Select>
                                <Select.Option value="sea">Sea</Select.Option>
                                <Select.Option value="land">Land</Select.Option>
                                <Select.Option value="air">Air</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>
                    <Col xl={8}>
                        <Form.Item
                            name="capacity"
                            label="Capacity"
                            // extra={<ShowFormItemError name="name" />}
                            rules={[
                                {
                                    required: true
                                }
                            ]}
                        >
                            <InputNumber min="1"/>
                        </Form.Item>
                    </Col>
                    <Col xl={24}>
                        <Form.Item
                            name="description"
                            label="Description"
                            // extra={<ShowFormItemError name="name" />}
                        >
                            <Input.TextArea style={{borderRadius: 12}}/>
                        </Form.Item>
                    </Col>
                    
                    <Col xl={8}>
                        <Form.Item
                            name="max_infant"
                            label="Max infant"
                            // extra={<ShowFormItemError name="name" />}
                            // rules={[
                            //     {
                            //         required: true
                            //     }
                            // ]}
                        >
                            <InputNumber min="0"/>
                        </Form.Item>
                    </Col>
                    <Col xl={8}>
                        <Form.Item
                            name="status"
                            label="Status"
                            // extra={<ShowFormItemError name="name" />}
                            // rules={[
                            //     {
                            //         required: true
                            //     }
                            // ]}status (enum [unavailable, available, maintenance]
                        >
                            <Select>
                                <Select.Option value="unavailable">Unavailable</Select.Option>
                                <Select.Option value="available">Available</Select.Option>
                                <Select.Option value="maintenance">Maintenance</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>
                </Row>
            </>
        )
    }

    const handleViewTransportation = (item) => {
        viewTransportationForm.resetFields();
        viewTransportationForm.setFieldsValue(item);
        setviewTransportationDrawerVisible(true);
    }

    const onViewTransportationDrawerClose = () => {
        viewTransportationForm.resetFields();
        setviewTransportationDrawerVisible(false);
    }


    return (
        <>
            <Drawer
                    title="New Transportation"
                    width={ screen.xs == false ? 520 : '95%'}
                    closable={false}
                    visible={newTransportationDrawerVisible}
                    onClose={()=>setnewTransportationDrawerVisible(false)}
                    footer={<Button type="primary" style={{float:'right'}} onClick={()=>newTransportationForm.submit()}>Save</Button>}
                >
                    <Form
                        form={newTransportationForm}
                        onFinish={onNewTransportationFormFinish}
                        layout="vertical"
                        scrollToFirstError={true}
                        // initialValues={}
                    >
                        <TransportationForm/>
                    </Form>
            </Drawer>

            <Drawer
                    title="View Transportation"
                    width={ screen.xs == false ? 520 : '95%'}
                    closable={false}
                    visible={viewTransportationDrawerVisible}
                    onClose={()=>onViewTransportationDrawerClose()}
                    footer={<Button type="primary" style={{float:'right'}} onClick={()=>viewTransportationForm.submit()}>Save</Button>}
                >
                    <Form
                        form={viewTransportationForm}
                        onFinish={onViewTransportationFormFinish}
                        layout="vertical"
                        scrollToFirstError={true}
                        // initialValues={}
                    >
                        <TransportationForm/>
                    </Form>
            </Drawer>

            <Typography.Title level={4}>Transportation</Typography.Title>

            <Row gutter={[12,12]}>
                {
                    (transportationListQuery && transportationListQuery.data) && transportationListQuery.data.map( (item, key) => (
                        <Col xl={6} xs={12} key={key}>
                            <Card
                                bordered={false}
                                hoverable={true}
                                className="card-shadow"
                                size="small"
                                cover={<img src={img} style={{height: '100%'}} />}
                                onClick={()=>handleViewTransportation(item)}
                            >
                                <Card.Meta
                                    title={<><Typography.Title level={5} className="mb-1">{item.name}</Typography.Title><small><Typography.Text type="secondary">{item.code}</Typography.Text></small></>}
                                    // description={item.price}
                                />
                            </Card>
                        </Col>
                    ))
                }
                <Col xl={6} xs={12}>
                    <Card
                        bordered={true}
                        // hoverable={true}
                        size="small"
                        onClick={()=>setnewTransportationDrawerVisible(true)}
                        className="card-add-button"
                    >
                        <Button type="link"><PlusOutlined/> Add Transportation</Button>
                    </Card>
                </Col>
            </Row>
        </>
    )
}

export default Page;