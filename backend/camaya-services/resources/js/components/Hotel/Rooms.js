import React from 'react'
import PropertyService from 'services/Hotel/Property'
import RoomService from 'services/Hotel/Room'
import { queryCache } from 'react-query'

import { Typography, Select, Table, Space, Button, Row, Col, Modal, Form, Input, notification } from 'antd'
import { PlusOutlined } from '@ant-design/icons';

const RoomsForm = ({type, form, onFinish, selectedProperty}) => (
    <Form
        layout="vertical"
        form={form}
        onFinish={onFinish}
    >
        <Form.Item
            name="property_id"
            noStyle
            hidden={true}
            rules={[{ required: true } ]}
        >
            <Input/>
        </Form.Item>
        <Row gutter={[12,12]} className="mt-4">
            <Col xl={14}>
                <Form.Item
                    name="room_type_id"
                    label="Room type"
                    rules={[{ required: true } ]}
                >
                    <Select placeholder="Room type" style={{width: '100%'}}>
                        {
                            selectedProperty.room_types.map( (item, key) => (
                                <Select.Option key={key} value={item.id}>{item.name}</Select.Option>
                            ))
                        }
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={10}>
                <Form.Item
                    name="number"
                    label="Room number"
                    rules={[{ required: true } ]}
                >
                    <Input placeholder="Room number"/>
                </Form.Item>
            </Col>
            {type === 'view' && (
                <>
                    <Form.Item name="id" noStyle>
                        <Input type="hidden" />
                    </Form.Item>
                    <Form.Item name="enabled" noStyle>
                        <Input type="hidden" />
                    </Form.Item>
                    <Col xl={12}>
                        <Form.Item
                            name="room_status"
                            label="Room Status"
                        >
                            <Select placeholder="Room Status" style={{width: '100%'}}>
                                {['clean', 'clean_inspected','dirty','dirty_inspected','pickup','sanitized','inspected','out-of-service','out-of-order'].map((item, key) => (
                                    <Select.Option value={item} key={key}>{_.capitalize(item)}</Select.Option>
                                ))}
                            </Select>
                        </Form.Item>
                    </Col>
                    <Col xl={12}>
                        <Form.Item
                            name="fo_status"
                            label="FO Status"
                        >
                            <Select placeholder="Room Status" style={{width: '100%'}}>
                                {['vacant','occupied'].map((item, key) => (
                                    <Select.Option value={item} key={key}>{_.capitalize(item)}</Select.Option>
                                ))}
                            </Select>
                        </Form.Item>
                    </Col>
                </>
            )}
            <Col xl={24}>
                <Form.Item
                    name="description"
                    label="Description"
                    // rules={[{ required: true } ]}
                >
                    <Input.TextArea style={{borderRadius: '12px'}}/>
                </Form.Item>
            </Col>
        </Row>
    </Form>
);

function Page(props) {

    const [selectedProperty, setselectedProperty] = React.useState(null);
    const [selectedPropertyRooms, setselectedPropertyRooms] = React.useState([]);
    const propertyListQuery = PropertyService.list();
    const [newRoomQuery, {isLoading: newRoomQueryIsLoading, error: newRoomQueryError}] = RoomService.create();
    const [updateRoomQuery, {isLoading: updateRoomQueryIsLoading, error: updateRoomQueryError}] = RoomService.update();
    const [changeStatus, {isLoading: changeRoomStatusIsLoading, error: changeRoomStatusError}] = RoomService.changeStatus();

    const [roomForm] = Form.useForm();    

    const columns = [        
        {
          title: 'Room #',
          dataIndex: 'number',
          key: 'number',
        },
        {
            title: 'Room type',
            dataIndex: 'name',
            key: 'name',
            render: (text, record) => (
                <>{record.name} ({record.code})</>
            )
        },
        {
            title: 'Description',
            dataIndex: 'description',
            key: 'description',
        },
        {
            title: 'Status',
            dataIndex: 'enabled',
            key: 'enabled',
            render: (text, record) => (
                <Select defaultValue={record.enabled ? 'Enabled' : 'Disabled'} onChange={(e) => handleChangeRoomStatus(record, e)}>
                    <Select.Option value="1"><span className="text-warning">Enabled</span></Select.Option>
                    <Select.Option value="0"><span className="text-success">Disabled</span></Select.Option>
                </Select>
            ),
        },
        {
            title: 'Action',
            key: 'action',
            render: (text, record) => (
              <Space size="middle">
                <Button type="link" onClick={() => showViewRoomModal(record)}>View</Button>
              </Space>
            ),
        },
    ];

    const handleSelectProperty = (e) => {

        const property = _.find(propertyListQuery.data, { id: e });
        setselectedProperty(property);
        // setselectedPropertyRooms(property.rooms);

    }

    const newRoomFormFinish = (values) => {
        // console.log(values);

        newRoomQuery(values, {
            onSuccess: (res) => {
                console.log(res.data);

                queryCache.setQueryData(['properties', { id: res.data.property_id }], {rooms: res.data});

                setselectedProperty(prev => {

                    const rooms = prev.rooms;

                    rooms.push({...res.data});

                    return {...prev, rooms: [...rooms], updated: res.data.updated_at};
                });

                roomForm.resetFields();

                notification.success({
                    message: `New Room - ${res.data.number} Added!`,
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

    const showNewRoomModal = () => {

        Modal.confirm({
            title: 'Create new room for '+ selectedProperty.name,
            icon: null,
            content: (
                <RoomsForm 
                    type="new" 
                    form={roomForm} 
                    onFinish={newRoomFormFinish}
                    selectedProperty={selectedProperty} />
            ),
            onOk() {
                roomForm.submit();
            },
            onCancel() {
                console.log('Cancel');
            },
        });

        roomForm.resetFields();

        roomForm.setFieldsValue({
            property_id: selectedProperty.id
        });
    }    

    const showViewRoomModal = (record) => {

        console.log(record);        

        Modal.confirm({
          title: `Update Room ${record.number} of ${selectedProperty.name}`,
          icon: null,
          content: (
            <RoomsForm 
                type="view" 
                form={roomForm} 
                onFinish={viewRoomFormFinish}
                selectedProperty={selectedProperty}  />
          ),
          onOk() {
            roomForm.submit();
          },
          onCancel() {
            console.log('Cancel');
          },
        });

        roomForm.resetFields();

        roomForm.setFieldsValue({
            ...record,
            property_id: selectedProperty.id
        });
    }

    const viewRoomFormFinish = (values) => {
        // console.log(values);

        updateRoomQuery(values, {
            onSuccess: (res) => {
                console.log(res.data);

                queryCache.setQueryData(['properties', { id: res.data.property_id }], {rooms: res.data});

                setselectedProperty(prev => {

                    const rooms = prev.rooms;

                    rooms.forEach(function (room, index) {
                        if (room.id === res.data.id) {
                            rooms[index] = res.data;
                        }
                    });

                    return {...prev, rooms: [...rooms], updated: res.updated_at};
                });

                roomForm.resetFields();

                notification.success({
                    message: `Room - ${res.data.number} Updated!`,
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

    const handleChangeRoomStatus = ({id}, enabled) => {
        changeStatus({ id, enabled }, {
            onSuccess: (res) => {
                console.log(res);

                queryCache.setQueryData(['properties', { id: res.data.property_id }], {rooms: res.data});

                setselectedProperty(prev => {

                    const rooms = prev.rooms;

                    rooms.forEach(function (room, index) {
                        if (room.id === id) {
                            rooms[index] = res.data;
                        }
                    });

                    return {...prev, rooms: [...rooms], updated: res.updated_at};
                });
            
                notification.success({
                    message: `Room ${parseInt(enabled) ? 'Enabled' : 'Disabled'}`,
                    description:
                        ``,
                });
            },
            onError: (e) => {
                console.log(e)
                message.warning(e.message);
            }
        });
    }

    return (
        <>
            <Typography.Title level={4}>Rooms</Typography.Title>

            
            <Row justify="space-between" className="my-4">
                <Col xl={4} xs={24}>
                    <Select style={{minWidth: 200}} placeholder="Select property" onChange={handleSelectProperty}>
                        { 
                            propertyListQuery.data && propertyListQuery.data.map( (item, key) => (
                                <Select.Option value={item.id} key={key}>{item.name}</Select.Option>
                            ))
                        }
                    </Select>
                </Col>
                <Col xl={4} xs={24}>
                    <Button type="primary" block disabled={!selectedProperty} onClick={showNewRoomModal}><PlusOutlined/> Add Room</Button>
                </Col>
            </Row>

            <Table 
                // loading={propertyListQuery.status === 'loading'}
                columns={columns}
                // dataSource={tableFilters && tableFilters.status ? myBookingsFiltered : myBookingsQuery.data ? myBookingsQuery.data : []}
                dataSource={selectedProperty && selectedProperty.rooms}
                rowKey="id"
                rowClassName="table-row"
                size="small"
                // onChange={(pagination, filters, sorter) => handleTableChange(pagination, filters, sorter, 'all')}
            />
        </>
    )
}

export default Page;