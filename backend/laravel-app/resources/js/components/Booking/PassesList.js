import React from 'react'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');
import StubService from 'services/Booking/StubService'
import { Table, Button, Typography, Space, message, Popconfirm, Row, Col, Form, Modal, Input, Select, InputNumber, TimePicker } from 'antd'
import Icon, { EditOutlined, LinkOutlined } from '@ant-design/icons'
import {queryCache} from 'react-query'

const NewStubForm = (props) => (
    <Form {...props}>
        <Row gutter={[8,8]}>
            <Col xl={24}>
                <Form.Item label="Type" name="type" rules={[{required: true}]}>
                    <Input />
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item label="Interface" name="interfaces">
                    <Select>
                        <Select.Option value="">None</Select.Option>
                        <Select.Option value="commercial_gate">Commercial gate</Select.Option>
                        <Select.Option value="main_gate">Main gate</Select.Option>
                        <Select.Option value="parking_gate">Parking gate</Select.Option>
                        <Select.Option value="boarding_gate">Boarding gate</Select.Option>
                        <Select.Option value="snack_pack_redemption">Snack pack redemption</Select.Option>
                        <Select.Option value="aqua_fun_access">Aqua Fun Water Park Access</Select.Option>
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item label="Mode" name="mode" rules={[{required: true}]}>
                    <Select>
                        <Select.Option value="entry">Entry</Select.Option>
                        <Select.Option value="exit">Exit</Select.Option>
                        <Select.Option value="redeem">Redeem</Select.Option>
                        <Select.Option value="access">Access</Select.Option>
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item label="Count" name="count" rules={[{required: true}]}>
                    <InputNumber min={0} />
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item label="Category" name="category" rules={[{required: true}]}>
                    <Select>
                        <Select.Option value="consumable">Consumable</Select.Option>
                        <Select.Option value="reusable">Reusable</Select.Option>
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item label="Start time" name="starttime">
                    <TimePicker />
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item label="End time" name="endtime">
                    <TimePicker />
                </Form.Item>
            </Col>
            <Col xs={24} align="right">
                <Button htmlType="submit">Save</Button>
            </Col>
        </Row>
    </Form>
    //  $table->dateTime('starttime',0)->nullable();
    //  $table->dateTime('endtime',0)->nullable();
    //  $table->timestamps();
)

function Page(props) {


    const stubListQuery = StubService.list();

    const [newStubQuery, {isLoading: newStubQueryIsLoading}] = StubService.create();

    const [updateStubCategoryQuery, {isLoading: updateStubCategoryQueryIsLoading}] = StubService.updateStubCategory();

    const [stubModalVisible, setstubModalVisible] = React.useState(false);

    const [newStubForm] = Form.useForm();

    const newStubFormOnFinish = (values) => {
        
        newStubQuery(values, {
            onSuccess: (res) => {
                console.log(res);
                setstubModalVisible(false);
                message.success('Added stub successfully!');

                queryCache.setQueryData("stubs", prev => [...prev, res.data]);

                //Reset fields
                newStubForm.resetFields();
            },
            onError: (e) => {
                console.log(e);
                message.info(e.message);
            }
        })
    }

    const handleUpdateStubCategory = (id, category) => {
        console.log(id, category);

        updateStubCategoryQuery({
            stub_id:id,
            category:category
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success('Updated stub category!');
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    return (
        <>
            <Typography.Title level={5}>Pass stubs</Typography.Title>
            <Button onClick={()=>setstubModalVisible(true)}>New Pass Stub</Button>
            <Modal
                title="New pass stub"
                visible={stubModalVisible}
                onCancel={()=>setstubModalVisible(false)}
                footer={null}
            >
                <NewStubForm
                    form={newStubForm}
                    layout="vertical"
                    onFinish={newStubFormOnFinish}
                    initialValues={{
                        starttime: moment('00:00:00', 'HH:mm:ss'),
                        endtime: moment('23:59:00', 'HH:mm:ss'),
                    }}
                />
            </Modal>
            <Table
                size="small"
                dataSource={stubListQuery.data && stubListQuery.data}
                loading={stubListQuery.isLoading}
                rowKey="id"
                columns={[
                    // {
                    //     title: 'ID',
                    //     dataIndex: 'id',
                    //     key: 'id',
                    // },
                    {
                        title: 'Type',
                        dataIndex: 'type',
                        key: 'type',
                    },
                    {
                        title: 'Interfaces',
                        dataIndex: 'interfaces',
                        key: 'interfaces',
                    },
                    {
                        title: 'Mode',
                        dataIndex: 'mode',
                        key: 'mode',
                    },
                    {
                        title: 'Count',
                        dataIndex: 'count',
                        key: 'count',
                    },
                    {
                        title: 'Category',
                        dataIndex: 'category',
                        key: 'category',
                        render: (text, record) =>  {
                            return  <Select defaultValue={record.category} style={{width:'100%'}} onChange={(e) => handleUpdateStubCategory(record.id, e)}>
                                    <Select.Option value="reusable">Reusable</Select.Option>
                                    <Select.Option value="consumable">Consumable</Select.Option>
                                </Select>
                            }
                    },
                    {
                        title: 'Start time',
                        dataIndex: 'starttime',
                        key: 'starttime',
                        
                    },
                    {
                        title: 'End time',
                        dataIndex: 'endtime',
                        key: 'endtime',
                    },
                ]}
            />
        </>
    )
}

export default Page;