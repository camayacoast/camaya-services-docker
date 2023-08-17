import React from 'react'
import moment from 'moment-timezone'
import SettingService from 'services/Booking/SettingService'
import Loading from 'common/Loading'

import { Typography, Table, Form, Row, Col, Select, Modal, Button, Input } from 'antd'
import { SettingOutlined } from '@ant-design/icons'

const SettingForm = (props) => {

    const [newSettingForm] = Form.useForm();

    const [newSettingQuery, {isLoading: newSettingQueryIsLoading}] = SettingService.create();

    const onFinish = (values) => {
        console.log(values);

        if (newSettingQueryIsLoading) {
            return false;
        }

        newSettingQuery(values, {
            onSuccess: res => {
                console.log(res);
                newSettingForm.resetFields();

                props.setnewSettingModalVisible(false);
            },
            onError: e => {
                console.log(e);
            }
        })
    }
    

    return (
        <Form
            layout="vertical"
            form={newSettingForm}
            onFinish={onFinish}
        >
            <Row gutter={[8,8]}>
                <Col xl={12}>
                    <Form.Item label="Name" name="name" rules={[{required:true}]}>
                        <Input/>
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item label="Code" name="code" rules={[{required:true}]}>
                        <Input/>
                    </Form.Item>
                </Col>
                <Col xl={24}>
                    <Form.Item label="Description" name="description">
                        <Input/>
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item label="Type" name="type" rules={[{required:true}]}>
                        <Select>
                            <Select.Option value="string">String</Select.Option>
                            <Select.Option value="decimal">Decimal</Select.Option>
                            <Select.Option value="integer">Integer</Select.Option>
                            <Select.Option value="array">Array</Select.Option>
                            <Select.Option value="date">Date</Select.Option>
                            <Select.Option value="time">Time</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item label="Value" name="value" rules={[{required:true}]}>
                        <Input/>
                    </Form.Item>
                </Col>
                <Col xl={24} align="right">
                    <Button htmlType="submit">Save</Button>
                </Col>
            </Row>
        </Form>
    )
}


export default function Page(props) {

    const settingsDataQuery = SettingService.data();
    const [newSettingModalVisible, setnewSettingModalVisible] = React.useState(false);

    // console.log(guestsCache);

    React.useEffect(()=> {
        console.log(settingsDataQuery.data);
    },[]);

    if (settingsDataQuery.isLoading) {
        return <Loading/>;
    }

    return (
        <div>
            <Modal
                title="Setting"
                visible={newSettingModalVisible}
                footer={null}
                onCancel={()=>setnewSettingModalVisible(false)}
                >
                    <SettingForm setnewSettingModalVisible={setnewSettingModalVisible}/>
            </Modal>
            <Typography.Title level={2}><SettingOutlined className="mr-2"/>Settings</Typography.Title>
            <Button onClick={()=>setnewSettingModalVisible(true)}>Add setting</Button>
            <Table
                dataSource={settingsDataQuery.data}
                rowKey="id"
                columns={[
                    {
                        title:'ID',
                        dataIndex:'id',
                        key:'id',
                    },
                    {
                        title:'Name',
                        dataIndex:'name',
                        key:'name',
                    },
                    {
                        title:'Value',
                        dataIndex:'value',
                        key:'value',
                    }
                ]}
            />
        </div>
        
    )
}