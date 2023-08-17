import React from 'react'
import moment from 'moment-timezone'
import RoomRateService from 'services/Hotel/RoomRateService'
import RoomTypeService from 'services/Hotel/RoomType'
import RoleService from 'services/RoleService'
import { queryCache } from 'react-query'

import { Table, Space, Button, Typography, Modal, Form, Input, Row, Col, Select, InputNumber, DatePicker, Descriptions, notification } from 'antd'
import { ArrowDownOutlined, ArrowUpOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons'

import img from 'assets/placeholder-1-1.jpg'

const room_rates = [
    {
        id: 1,
        description: "December weekend rates",
        room_type_id: 1,
        room_type: {
            name: 'Deluxe King',
            code: 'DLX',
            rack_rate: 8000,
            images: [
                // { id: 1, room_type_id: 1, image_path: 'https://services.camayacoast.com.test/storage/Kd7HGyWm3ci7TcLb7vMvsjIUyBoIHHbCp7r5iGEQ.png' }
            ]
        },
        start_datetime: '2020-12-01 00:00:00',
        end_datetime: '2020-12-30 00:00:00',
        room_rate: 9000,
        days_interval: ['fri', 'sat', 'sun'],
        exclude_days: ['2020-12-24', '2020-12-25'],
    },
    {
        id: 2,
        description: "December weekday rates",
        room_type_id: 1,
        room_type: {
            name: 'Deluxe King',
            code: 'DLX',
            rack_rate: 8000,
            images: [
                { id: 1, room_type_id: 1, image_path: 'https://services.camayacoast.com.test/storage/bxBSzYcuLQMSeIQ0yk9QIu7ebl1xDRsM1gC9qWnt.jpeg' }
            ]
        },
        start_datetime: '2020-12-01 00:00:00',
        end_datetime: '2020-12-30 00:00:00',
        room_rate: 7000,
        days_interval: ['mon', 'tue', 'wed'],
        exclude_days: ['2020-12-24', '2020-12-25'],
    }
];

const RoomRateForm = ({type, formName, onFinish, roomTypes}) => {
    const roleListQuery = RoleService.list();
    return (
    <Form
        form={formName}
        layout="vertical"
        onFinish={onFinish}
    >
        <Form.Item name="id" noStyle>
            <Input type="hidden" />
        </Form.Item>
        <Row gutter={[8,8]}>
            <Col xl={24} xs={24}>
                <Form.Item label="Room rate description" name="description" rules={[
                    {
                        required: true
                    }
                ]}>
                    <Input/>
                </Form.Item>
            </Col>

            {type==='new' && (
                <Col xl={24} xs={24}>
                    <Form.Item label="Room types" name="room_types" rules={[
                        {
                            required: type==='new' ? true : false
                        }
                    ]}>
                        <Select mode="multiple">
                            {
                                roomTypes && roomTypes.map( (room_type, key) => <Select.Option key={key} value={room_type.id}>[{room_type.property_name}] {room_type.name} {room_type.code}</Select.Option> )
                            }
                        </Select>
                    </Form.Item>
                </Col>
            )}

            <Col xl={12} xs={24}>
                <Form.Item label="Room rate" name="room_rate" rules={[
                    {
                        required: true
                    }
                ]}>
                    <InputNumber
                        size="large"
                        min={1}
                        style={{width: 180}}
                        steps={0.1}
                        formatter={value => `₱ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                        parser={value => value.replace(/\₱\s?|(,*)/g, '')}
                    />
                </Form.Item>
            </Col>
            <Col xl={24} xs={24}>
                <Form.Item label="Date range" name="date_range" rules={[
                    {
                        required: true
                    }
                ]}>
                    <DatePicker.RangePicker/>
                </Form.Item>
            </Col>
            
            {type==='new' && (
                <>
                    <Col xl={24} xs={24}>
                        <Form.Item label="Allowed days" name="allowed_days">
                            <Select mode="multiple">
                                <Select.Option value="mon">Monday</Select.Option>
                                <Select.Option value="tue">Tuesday</Select.Option>
                                <Select.Option value="wed">Wednesday</Select.Option>
                                <Select.Option value="thu">Thursday</Select.Option>
                                <Select.Option value="fri">Friday</Select.Option>
                                <Select.Option value="sat">Saturday</Select.Option>
                                <Select.Option value="sun">Sunday</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>
                    <Col xl={24} xs={24}>
                        <Form.Item name="exclude_days" label="Excluded days">
                            <Select mode="tags" placeholder="format:YYYY-MM-DD" tokenSeparators={[',',';',' ']}/>
                        </Form.Item>
                    </Col>
                </>
            )}

                <Col xl={24} xs={24}>
                    <Form.Item label="Allowed roles" name='allowed_roles'>
                        <Select mode="multiple">
                            {
                                roleListQuery.data && roleListQuery.data.map( (role, key) => {
                                    return <Select.Option key={key} value={role.name}>{role.name}</Select.Option>
                                })
                            }
                        </Select>
                    </Form.Item>
                </Col>
        </Row>
        <Button htmlType="submit">Save</Button>
    </Form>
)}

function Page(props) {


    const roomRatesListQuery = RoomRateService.list();
    const roomTypeListQuery = RoomTypeService.list();
    const roleListQuery = RoleService.list();
    const [newRoomRateQuery, {isLoading: newRoomRateQueryIsLoading }] = RoomRateService.create();
    const [updateRoomRateQuery, {isLoading: updateRoomRateQueryIsLoading }] = RoomRateService.update();
    const [changeStatusQuery, {isLoading: changeStatusQueryIsLoading }] = RoomRateService.changeStatus();
    const [changeAllowedDays, {isLoading: changeAllowedDaysIsLoading }] = RoomRateService.changeAllowedDays();
    const [changeExcludedDays, {isLoading: changeExcludedDaysIsLoading }] = RoomRateService.changeExcludedDays();

    const [newRoomRateModalVisible, setnewRoomRateModalVisible] = React.useState(false);
    const [viewRoomRateModalVisible, setviewRoomRateModalVisible] = React.useState(false);

    const [newRoomRateForm] = Form.useForm();

    const onNewRateFormFinish = (values) => {
        console.log(values);

        if (newRoomRateQueryIsLoading) {
            return false;
        }

        newRoomRateQuery(values, {
            onSuccess: (res) => {
                console.log(res);

                const content = (
                    <>
                        {
                            res.data.merged.filter(item => item.room_type != null).map( (item, key) => {

                                const diff = (item.room_rate - item.last_room_rate) / item.last_room_rate;
                                
                                return (
                                    <Descriptions column={1} size="small" style={{marginBottom: 48}} key={key} title={`#${key+1} [${item.room_type.property.name}] ${item.room_type.name} ${item.room_type.code}`} bordered>
                                        <Descriptions.Item label="Description">{item.last_description} to {item.description}</Descriptions.Item>
                                        <Descriptions.Item label="Previous date range">{moment(item.last_start_datetime).format('MMM D, YYYY')} ~ {moment(item.last_end_datetime).format('MMM D, YYYY')}</Descriptions.Item>
                                        <Descriptions.Item label="Added date range">{moment(item.start_datetime).format('MMM D, YYYY')} ~ {moment(item.end_datetime).format('MMM D, YYYY')}</Descriptions.Item>
                                        <Descriptions.Item label="Old rate">&#8369; {item.last_room_rate}</Descriptions.Item>
                                        <Descriptions.Item label="New rate">&#8369; {item.room_rate}</Descriptions.Item>
                                        <Descriptions.Item label="Difference">
                                        { diff < 0 ? <span className="text-danger"><ArrowDownOutlined/> {Math.abs(diff * 100).toFixed(2)}%</span> : <span className="text-success"><ArrowUpOutlined/> {Math.abs(diff * 100).toFixed(2)}%</span> }
                                        </Descriptions.Item>
                                    </Descriptions>
                                )
                            })
                        }
                    </>
                )

                Modal.confirm({
                    title: 'New room rates overwritten previously added room rates with overlapping dates.',
                    content: content
                });

                    // queryCache.setQueryData(['room-rates'], res.data.new_room_rates);
                    roomRatesListQuery.refetch();
            
                    notification.success({
                        message: `New room rates added`,
                        description:
                            ``,
                    });

                    newRoomRateForm.resetFields();

                    setnewRoomRateModalVisible(false);
            },
            onError: (e) => {
                console.log(e);
            }
        })

    }

    const onViewRateFormFinish = (values) => {
        console.log(values);

        if (updateRoomRateQueryIsLoading) {
            return false;
        }

        updateRoomRateQuery(values, {
            onSuccess: (res) => {
                console.log(res);                

                roomRatesListQuery.refetch();
        
                notification.success({
                    message: `Room rates updated`,
                    description:
                        ``,
                });

                newRoomRateForm.resetFields();

                setviewRoomRateModalVisible(false);
            },
            onError: (e) => {
                console.log(e);
            }
        })

    }
    
    const showViewRoomRateModal = (record) => {

        console.log(record);        

        setviewRoomRateModalVisible(true);

        newRoomRateForm.resetFields();
        
        if (record.start_datetime && record.end_datetime) {
            record.date_range = [moment(record.start_datetime), moment(record.end_datetime)];
        }
        
        newRoomRateForm.setFieldsValue({
            ...record,            
        });

        console.log(record);
    }

    const handleChangeRoomRateStatus = ({id}, status) => {
        console.log(id, status);
        changeStatusQuery({ id, status }, {
            onSuccess: (res) => {
                console.log(res);

                roomRatesListQuery.refetch();
            
                notification.success({
                    message: `Status Updated`,
                    description:
                        ``,
                });

                newRoomRateForm.resetFields();

                setnewRoomRateModalVisible(false);
            },
            onError: (e) => {
                console.log(e)
                message.warning(e.message);
            }
        });
    }

    const handleChangeRoomRateAllowedDays = ({id, days_interval} ) => {
        changeAllowedDays({ id, days_interval }, {
            onSuccess: (res) => {
                console.log(res);
            
                notification.success({
                    message: `Allowed Days Updated`,
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

    const handleChangeRoomRateExcludedDays = ({id, exclude_days}) => {
        changeExcludedDays({ id, exclude_days }, {
            onSuccess: (res) => {
                console.log(res);
            
                notification.success({
                    message: `Excluded Days Updated`,
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
        <div>
            <Typography.Title level={4}>Room rates</Typography.Title>
            <Button icon={<PlusOutlined/>} onClick={()=>setnewRoomRateModalVisible(true)}>Add Room Rates</Button>
        </div>
           <Table
                scroll={{ x: '100vw' }}
                size="small"
                dataSource={roomRatesListQuery.data && roomRatesListQuery.data}
                rowKey="id"
                expandable={{
                    expandedRowRender: record => <>
                            <Typography.Text strong>Allowed days</Typography.Text>
                            <Select style={{width: '100%'}} 
                                defaultValue={record.days_interval} 
                                mode="multiple" 
                                onChange={(values) => record.days_interval = values} 
                                onBlur={() => handleChangeRoomRateAllowedDays(record)}
                                onDeselect={() => handleChangeRoomRateAllowedDays(record)}>
                                <Select.Option value="mon">Monday</Select.Option>
                                <Select.Option value="tue">Tuesday</Select.Option>
                                <Select.Option value="wed">Wednesday</Select.Option>
                                <Select.Option value="thu">Thursday</Select.Option>
                                <Select.Option value="fri">Friday</Select.Option>
                                <Select.Option value="sat">Saturday</Select.Option>
                                <Select.Option value="sun">Sunday</Select.Option>
                            </Select>
                            <Typography.Text strong>Excluded days</Typography.Text>
                            <Select style={{width: '100%'}} 
                                defaultValue={record.exclude_days} 
                                mode="tags" 
                                onChange={(values) => record.exclude_days = values} 
                                onBlur={() => handleChangeRoomRateExcludedDays(record)}
                                onDeselect={() => handleChangeRoomRateExcludedDays(record)}>
                            {
                                record.exclude_days ? record.exclude_days.map( (item, key) => {
                                    return <Select.Option value={item} key={key}>{item}</Select.Option>
                                }): ''
                            }
                            </Select>
                            <Typography.Text strong>Allowed roles</Typography.Text>
                            <Select style={{width: '100%'}} defaultValue={record.allowed_roles} mode="multiple">
                            {
                                roleListQuery.data && roleListQuery.data.map( (role, key) => {
                                    return <Select.Option key={key} value={role.name}>{role.name}</Select.Option>
                                })
                            }
                            </Select>
                            </>,
                    // rowExpandable: record => record.name !== 'Not Expandable',
                }}
                columns={[
                    // {
                    //     title: 'ID',
                    //     dataIndex: 'id',
                    //     key: 'id',
                    // },
                    {
                        title: 'Room rate description',
                        dataIndex: 'description',
                        key: 'description',
                    },
                    {
                        title: 'Room type',
                        dataIndex: 'room_type',
                        key: 'room_type',
                        render: (text, record) => {
                            return <div style={{display: 'flex', justifyContent: 'flex-start', alignItems:'center'}}>
                                        <img style={{width: 50, borderRadius: 12, marginRight: 12}} src={(record.room_type.images.length) ? record.room_type.images[0].image_path : img} />
                                        <Space>
                                            <span>[{record.room_type.property.name}]</span>
                                            <span>{record.room_type.name}</span>
                                            <span>{record.room_type.code}</span>
                                        </Space>
                                    </div>
                        }
                    },
                    {
                        title: 'Rack rate',
                        dataIndex: 'rack_rate',
                        key: 'rack_rate',
                        render: (text, record) => <>&#8369; {record.room_type.rack_rate}</>
                    },
                    {
                        title: 'Start date',
                        dataIndex: 'start_datetime',
                        key: 'start_datetime',
                        render: (text) => moment(text).format('MMM D, YYYY')
                    },
                    {
                        title: 'End date',
                        dataIndex: 'end_datetime',
                        key: 'end_datetime',
                        render: (text) => moment(text).format('MMM D, YYYY')
                    },
                    {
                        title: 'Room rate',
                        dataIndex: 'room_rate',
                        key: 'room_rate',
                        render: (text, record) => {

                            const diff = (record.room_rate - record.room_type.rack_rate) / record.room_type.rack_rate;

                            return <>&#8369; {record.room_rate} { diff < 0 ? <sub className="text-danger"><ArrowDownOutlined/> {Math.abs(diff * 100).toFixed(2)}%</sub> : <sup className="text-success"><ArrowUpOutlined/> {Math.abs(diff * 100).toFixed(2)}%</sup> }</>
                        }
                    },
                    // {
                    //     title: 'Allowed days',
                    //     dataIndex: 'days_interval',
                    //     key: 'days_interval',
                    //     render: (text) => <>{text.join(', ')}</>
                    // },
                    // {
                    //     title: 'Excluded days',
                    //     dataIndex: 'exclude_days',
                    //     key: 'exclude_days',
                    //     render: (text) => <>{text.join(', ')}</>
                    // },
                    {
                        title: 'Status',
                        dataIndex: 'status',
                        key: 'status',
                        render: (text, record) => (
                            <Select defaultValue={record.status} onChange={(e) => handleChangeRoomRateStatus(record, e)}>
                                {['for_review', 'approved', 'denied'].map((status, key) => 
                                    <Select.Option value={status} key={key}>
                                        <span className={
                                            _.cond([
                                                [_.matches('0'), _.constant('text-warning')],
                                                [_.matches('1'), _.constant('text-success')]])(key.toString())}>
                                            {_.startCase(status)}
                                        </span>
                                    </Select.Option>
                                )}
                            </Select>
                        ),
                    },
                    {
                        title: 'Action',
                        dataIndex: 'action',
                        key: 'action',
                        render: (text, record) => <Button icon={<EditOutlined/>} onClick={()=>showViewRoomRateModal(record)}/>
                    },
                ]}
            />

            <Modal
                title="New Room Rates"
                visible={newRoomRateModalVisible}
                footer={null}
                onCancel={()=>setnewRoomRateModalVisible(false)}
            >
                <RoomRateForm type="new" formName={newRoomRateForm} onFinish={onNewRateFormFinish} roomTypes={roomTypeListQuery.data} />
            </Modal>

            <Modal
                title="Update Room Rates"
                visible={viewRoomRateModalVisible}
                footer={null}
                onCancel={()=>setviewRoomRateModalVisible(false)}
            >
                <RoomRateForm type="view" formName={newRoomRateForm} onFinish={onViewRateFormFinish} roomTypes={roomTypeListQuery.data} />
            </Modal>
        </>
    )
}

export default Page;