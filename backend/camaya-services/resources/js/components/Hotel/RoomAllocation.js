import React from 'react'
import RoomTypeService from 'services/Hotel/RoomType'
import RoleService from 'services/RoleService'
import RoomAllocationService from 'services/Hotel/RoomAllocationService'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');

import { Typography, Modal, Form, Row, Col, Input, InputNumber, Select, DatePicker, Button, message, Alert, notification, Dropdown, Table, Space, Menu, Descriptions } from 'antd'
import { EditOutlined, MinusCircleOutlined, PlusOutlined, PlusCircleOutlined, EllipsisOutlined, CalendarOutlined, ReloadOutlined } from '@ant-design/icons'
import { queryCache } from 'react-query'

const roomCountInitialValues = {
    monday: 0,
    tuesday: 0,
    wednesday: 0,
    thursday: 0,
    friday: 0,
    saturday: 0,
    sunday: 0,
}

const RoomAllocationForm = ({formName, onFinish, entities}) => {

    const [selected, setSelected] = React.useState([]);

    const [roomCount, setroomCount] = React.useState(roomCountInitialValues);
    const [roomDayCount, setroomDayCount] = React.useState(roomCountInitialValues);

    const roomTypeListQuery = RoomTypeService.list();
    const roleListQuery = RoleService.list();

    React.useEffect(()=> {
        console.log(roomCount.monday, roomDayCount.monday);
    }, [roomCount, roomDayCount]);
    
    const handleChangeEntity = (value, el) => {

        setSelected(_.map(formName.getFieldValue('allocations'), 'entity'));

    }

    const handleRoomTypeChange = (value) => {
        const room_type = _.find(roomTypeListQuery.data, i => i.id === value);

        setroomCount({
            monday: room_type.enabled_rooms_count,
            tuesday: room_type.enabled_rooms_count,
            wednesday: room_type.enabled_rooms_count,
            thursday: room_type.enabled_rooms_count,
            friday: room_type.enabled_rooms_count,
            saturday: room_type.enabled_rooms_count,
            sunday: room_type.enabled_rooms_count,
        });
    }

    const handleRoomCountChange = (value, key, day) => {
        const sumByDay = _.sumBy(formName.getFieldValue('allocations'), day);

        if (sumByDay > roomCount[day]) {
            // console.log('exceeded');
            message.info('Maxed out allocations for '+ day +'.');
            const values = formName.getFieldValue('allocations');

            values[key][day] = 0;
            formName.setFieldsValue({
                ...values
            });

            return false;
        }

    }

    return (
        <Form
            layout="vertical"
            form={formName}
            onFinish={onFinish}
        >
            <Row gutter={[8,8]}>
                <Col xl={24}>
                    <Alert type="info" message="This room allocation generation feature only creates new records and will skip existing dates with an entity allocation."/>
                </Col>
                <Col xl={9}>
                    <Form.Item label="Room type" name="room_type_id" rules={[{ required: true }]}>
                        <Select onChange={handleRoomTypeChange}>
                            {
                                roomTypeListQuery.data && roomTypeListQuery.data.map( (room_type, key) => (
                                    <Select.Option key={key} value={room_type.id}>{room_type.property_code} {room_type.name} - {room_type.enabled_rooms_count}</Select.Option>       
                                ))
                            }
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={6}>
                    <Form.Item label="Date range" name="range" rules={[{ required: true }]}>
                        <DatePicker.RangePicker />
                    </Form.Item>
                </Col>
                <Col xl={24}>
                    <Form.List rules={[{required:true}]} name="allocations">
                        {(fields, {add, remove}) => (
                            <>
                            {fields.map(field => (
                                <Row gutter={[12,12]} key={field.key}>
                                    <Col xl={1}>
                                        <Button
                                            type="link"
                                            size="small"
                                            onClick={() => {
                                                remove(field.name);
                                                setSelected(_.map(formName.getFieldValue('allocations'), 'entity'));
                                            }}
                                            icon={<MinusCircleOutlined />}
                                        />
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label={`#${field.name+1} Entity`} name={[field.name, 'entity']} rules={[{ required: true }]}>
                                            <Select onSelect={handleChangeEntity} onDeselect={(v,e) => console.log('deselect', v, e)}>
                                                <Select.Option value={formName.getFieldValue('entity')}>None selected</Select.Option>
                                                {
                                                    entities
                                                    .filter( (item) => !_.includes(selected, item) )
                                                    .map( (item, key) =>
                                                        <Select.Option value={item} key={key}>{item}</Select.Option>
                                                    )
                                                }
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                    <Col xl={12}>
                                        <Form.Item label="Allowed roles" name={[field.name, 'allowed_roles']}>
                                            <Select mode="multiple">
                                                {
                                                    roleListQuery.data && roleListQuery.data.map( (role, key) => {
                                                        return <Select.Option key={key} value={role.name}>{role.name}</Select.Option>
                                                    })
                                                }
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                    
                                    <Col xl={20}>
                                        <Row>
                                            <Col xl={1}>
                                                
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item initialValue={0} label="Monday" name={[field.name, 'monday']}>
                                                    <InputNumber min={0} max={roomCount.monday} onChange={(e) => handleRoomCountChange(e, field.name, 'monday')} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item initialValue={0} label="Tuesday" name={[field.name, 'tuesday']}>
                                                    <InputNumber min={0} max={roomCount.tuesday} onChange={(e) => handleRoomCountChange(e, field.name, 'tuesday')} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item initialValue={0} label="Wednesday" name={[field.name, 'wednesday']}>
                                                    <InputNumber min={0} max={roomCount.wednesday} onChange={(e) => handleRoomCountChange(e, field.name, 'wednesday')} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item initialValue={0} label="Thursday" name={[field.name, 'thursday']}>
                                                    <InputNumber min={0} max={roomCount.thursday} onChange={(e) => handleRoomCountChange(e, field.name, 'thursday')} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item initialValue={0} label="Friday" name={[field.name, 'friday']}>
                                                    <InputNumber min={0} max={roomCount.friday} onChange={(e) => handleRoomCountChange(e, field.name, 'friday')} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item initialValue={0} label="Saturday" name={[field.name, 'saturday']}>
                                                    <InputNumber min={0} max={roomCount.saturday} onChange={(e) => handleRoomCountChange(e, field.name, 'saturday')} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item initialValue={0} label="Sunday" name={[field.name, 'sunday']}>
                                                    <InputNumber min={0} max={roomCount.sunday} onChange={(e) => handleRoomCountChange(e, field.name, 'sunday')} />
                                                </Form.Item>
                                            </Col>
                                        </Row>
                                    </Col>
                                </Row>
                            ))}
                            {
                                fields.length < entities.length &&
                                <Button
                                    type="dashed"
                                    onClick={() => {
                                        add();
                                    }}
                                >
                                <PlusOutlined /> Add allocation
                                </Button>
                            }
                            </>
                        )}
                    </Form.List>
                </Col>
            </Row>
            <Button className="mt-3 ant-btn ant-btn-primary" htmlType="submit">Save</Button>
        </Form>
    )
}

function Page(props) {

    const entities = [
        'BPO', 
        'HOA', 
        'RE', 
        'OTA',
        'SD Rudolph Cortez',
        'SD Louie Paule',
        'SD Luz Dizon',
        'SD John Rizaldy Zuno',
        'SD Brian Beltran',
        'SD Jake Tuazon',
        'SD Joey Bayon',
        'SD Grace Laxa',
        'SD Stephen Balbin',
        'SD Maripaul Milanes',
        'SD Danny Ngoho',
        'SD Harry Colo',
        'SD Lhot Quiambao'
    ];

    // States
    const [newRoomAllocationModalVisible, setnewRoomAllocationModalVisible] = React.useState(false);
    const [editRoomAllocationModalVisible, setEditRoomAllocationModalVisible] = React.useState(false);
    const [roomAllocationsEditList, setroomAllocationsEditList] = React.useState([]);
    const [roomAllocationsEdit, setroomAllocationsEdit] = React.useState({});
    const [dateFilter, setdateFilter] = React.useState(moment());
    const [dateForRoomTypeList, setDateForRoomTypeList] = React.useState();

    // Form
    const [generateRoomAllocationForm] = Form.useForm();

    // Get
    const roomAllocationListQuery = RoomAllocationService.list();
    const roleListQuery = RoleService.list();
    const roomTypeListQuery = RoomTypeService.listForRoomAllocation(dateForRoomTypeList);

    // Put, Post
    const [newRoomAllocationQuery, { isLoading: newRoomAllocationQueryIsLoading }] = RoomAllocationService.create();
    const [changeRoomAllocationStatusQuery, { isLoading: changeRoomAllocationStatusQueryIsLoading }] = RoomAllocationService.changeRoomAllocationStatus();
    const [updateRoomAllocationAllowedRolesQuery, { isLoading: updateRoomAllocationAllowedRolesQueryIsLoading }] = RoomAllocationService.updateRoomAllocationAllowedRoles();
    const [updateRoomAllocationQuery, { isLoading: updateRoomAllocationQueryIsLoading }] = RoomAllocationService.updateRoomAllocation();

    React.useEffect( () => {
        if (editRoomAllocationModalVisible == false) {
            setroomAllocationsEditList([]);
            setroomAllocationsEdit({});
        }
    }, [editRoomAllocationModalVisible]);

    React.useEffect(()=> {
        if (dateForRoomTypeList) {
            roomTypeListQuery.refetch();
        }
    }, [dateForRoomTypeList]);
    

    const onGenerateRoomAllocationFormFinish = values => {
        console.log(values);

        // if (newRoomAllocationQueryIsLoading) {
        //     message.info("Saving...");
        //     return false;
        // }

        newRoomAllocationQuery(values, {
            onSuccess: (res) => {
                console.log(res);

                generateRoomAllocationForm.resetFields();
                setnewRoomAllocationModalVisible(false);

                // roomAllocationListQuery.refetch();
            
                notification.success({
                    message: `New room allocations added`,
                    description:
                        ``,
                });

                if (res.data.skipped.length) {
                    Modal.confirm({
                        title: 'Skipped saving record',
                        content: (
                            <>
                                <Alert className="mb-2" message="Skipped room allocations. These items already exist in our records. Please apply corresponding change manually."/>
                                <Table
                                    size="small"
                                    dataSource={res.data.skipped}
                                    rowKey="id"
                                    columns={[
                                        {
                                            title: 'Entity',
                                            dataIndex: 'entity',
                                            key: 'entity',
                                            // render: (text, record) => <>{record.entity}</>
                                        },
                                        {
                                            title: 'Date',
                                            dataIndex: 'date',
                                            key: 'date',
                                            render: (text, record) => <>{moment(record.date).format('YYYY-MM-DD')}</>
                                        },
                                        {
                                            title: 'Room type',
                                            dataIndex: 'room_type',
                                            key: 'room_type',
                                            render: (text, record) => <>{record.room_type.name}</>
                                        },
                                    ]}
                                    />
                            </>
                        )
                    })
                }


            },
            onError: (e) => {
                console.log(e)
                message.warning(e.message);
            }
        });

    }

    const handleChangeRoomAllocationStatus = ({id}, status) => {
        changeRoomAllocationStatusQuery({ id: id, new_status: status}, {
            onSuccess: (res) => {
                console.log(res);
            
                notification.success({
                    message: `Changed room allocation status`,
                    description:
                        ``,
                });

                // queryCache.setQueryData(['room-allocations', { id:res.data.id }], res.data);

                queryCache.setQueryData(['room-allocations'], prev => {

                    const index = _.findIndex([...prev], i => i.id == res.data.id);
                    let newData = [...prev];
                    newData[index] = res.data;

                    return [...newData];
                });

            },
            onError: (e) => {
                console.log(e)
                message.warning(e.message);
            }
        });
    }

    const handleRoomAllocationAllowedRolesChange = (id, roles) => {

        updateRoomAllocationAllowedRolesQuery({
            id: id,
            new_roles: roles,
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success('Room allocation allowed roles update successful!');
                
                queryCache.setQueryData(['room-allocations'], prev => {

                    const index = _.findIndex([...prev], i => i.id == res.data.id);
                    let newData = [...prev];
                    newData[index] = res.data;

                    return [...newData];
                });
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handleEditRoomAllocation = ({id, room_type_id, entity, date}) => {
        // console.log(id, room_type_id, entity);
        // setroomAllocationsEditList([]);
        // setroomAllocationsEdit({});
        setEditRoomAllocationModalVisible(true);
        
        const roomAllocation = _.find(roomAllocationListQuery.data, item => (item.id == id));
        const roomAllocationsRelated = _.filter(roomAllocationListQuery.data, item => (item.room_type.id == room_type_id && moment(item.date).format('YYYY-MM-DD') == moment(date).format('YYYY-MM-DD')));

        console.log(roomAllocation);
        setDateForRoomTypeList(moment(date).format('YYYY-MM-DD'));
        setroomAllocationsEditList(roomAllocationsRelated);
        setroomAllocationsEdit(roomAllocation);
    }

    const handleRoomAllocationChange = (id, allocation, related_ids) => {
        
        updateRoomAllocationQuery({ id: id, new_allocation: allocation, related_ids: related_ids}, {
            onSuccess: (res) => {
                console.log(res);

                roomAllocationListQuery.refetch();
                message.success("Room allocation update successful!");

                setroomAllocationsEditList(res.data.related_allocation);
                setroomAllocationsEdit(res.data.room_allocation);

            },
            onError: (e) => {
                console.log(e)
                message.warning(e.message);
            }
        });
    }

    return (
        <>
        <div className="mb-4">
            <Typography.Title level={4}>Room allocations</Typography.Title>
            <Space>
                <Button icon={<PlusOutlined/>} onClick={()=>setnewRoomAllocationModalVisible(true)}>Add Room Allocation</Button>
                <Button icon={<ReloadOutlined />} onClick={() => roomAllocationListQuery.refetch()} />
            </Space>
        </div>

        <Table
            loading={roomAllocationListQuery.isLoading}
            size="small"
            dataSource={roomAllocationListQuery.data && roomAllocationListQuery.data.filter(i => (dateFilter ? moment(i.date).format('YYYY-MM-DD') == moment(dateFilter).format('YYYY-MM-DD') : true))}
            rowKey="id"
            expandable={{
                expandedRowRender: record => <>
                        <Typography.Text strong>Allowed roles</Typography.Text>
                        <Select onChange={(e) => handleRoomAllocationAllowedRolesChange(record.id, e)} style={{width: '100%'}} defaultValue={record.allowed_roles} mode="multiple">
                        {
                            roleListQuery.data ? roleListQuery.data.map( (item, key) => {
                                return <Select.Option value={item.name} key={key}>{item.name}</Select.Option>
                            }): ''
                        }
                        </Select></>,
                // rowExpandable: record => record.name !== 'Not Expandable',
            }}
            columns={[
                {
                    // title: 'Room type',
                    // dataIndex: 'room_type',
                    // key: 'room_type',
                    // render: (text, record) => <>{record.room_type.property.code} {record.room_type.name} <small className="text-secondary">({record.allowed_roles ? record.allowed_roles.length : 0} allowed roles)</small></>

                    title: 'Room type',
                    dataIndex: 'room_type',
                    key: 'room_type',
                    filters: [
                        { text: 'Aqua Fun Hotel', value: 'AF' },
                        { text: 'Sands Hotel', value: 'SANDS' },
                        { text: 'Beach Villa', value: 'BV' },
                        { text: 'Guest House', value: 'GH' },
                    ],
                    defaultFilteredValue: ['AF', 'SANDS', 'BV', 'GH'],
                    onFilter: (value, record) => record.room_type.property.code.includes(value),
                    render: (text, record) => <>{record.room_type.property.code} {record.room_type.name}<br/><small className="text-secondary">({record.allowed_roles ? record.allowed_roles.length : 0} allowed roles)</small></>
                },
                {
                    title: <>Date <DatePicker size="small" defaultValue={moment()} onChange={e => setdateFilter(e)}/></>,
                    dataIndex: 'date',
                    key: 'date',
                    render: (text, record) => <>{moment(record.date).format('MMM DD, YYYY')}</>
                },
                {
                    title: 'Requested at',
                    dataIndex: 'created_at',
                    key: 'created_at',
                    render: (text, record) => record.created_at ? moment(record.created_at).format('MMM DD, YYYY h:mm:ss A') : ''
                },
                {
                    title: 'Entity',
                    dataIndex: 'entity',
                    key: 'entity',
                    // render: (text, record) => <>{record.entity}</>
                    filters: [
                        { text: 'BPO', value: 'BPO' },
                        { text: 'RE', value: 'RE' },
                        { text: 'HOA', value: 'HOA' },
                        { text: 'OTA', value: 'OTA' },
                        { text: 'SD Rudolph Cortez', value: 'SD Rudolph Cortez' },
                        { text: 'SD Louie Paule', value: 'SD Louie Paule' },
                        { text: 'SD Luz Dizon', value: 'SD Luz Dizon' },
                        { text: 'SD John Rizaldy Zuno', value: 'SD John Rizaldy Zuno' },
                        { text: 'SD Brian Beltran', value: 'SD Brian Beltran' },
                        { text: 'SD Jake Tuazon', value: 'SD Jake Tuazon' },
                        { text: 'SD Jose Bayon', value: 'SD Joey Bayon' },
                        { text: 'SD Grace Laxa', value: 'SD Grace Laxa' },
                        { text: 'SD Stephen Balbin', value: 'SD Stephen Balbin' },
                        { text: 'SD Maripaul Milanes', value: 'SD Maripaul Milanes' },
                        { text: 'SD Danny Ngoho', value: 'SD Danny Ngoho' },
                        { text: 'SD Harry Colo', value: 'SD Harry Colo' },
                        { text: 'SD Lhot Quiambao', value: 'SD Lhot Quiambao' }
                    ],
                    onFilter: (value, record) => record.entity.includes(value),
                    
                },
                {
                    title: 'Allocation',
                    dataIndex: 'allocation',
                    key: 'allocation',
                    // render: (text, record) => <>{record.entity}</>
                },
                {
                    title: 'Used',
                    dataIndex: 'used',
                    key: 'used',
                    // render: (text, record) => <>{record.entity}</>
                },
                {
                    title: 'Status',
                    dataIndex: 'status',
                    key: 'status',
                    filters: [
                        { text: 'For review', value: 'for_review' },
                        { text: 'Approved', value: 'approved' },
                        { text: 'Denied', value: 'denied' },
                    ],
                    defaultFilteredValue: ['for_review', 'approved', 'denied'],
                    onFilter: (value, record) => record.status.includes(value),
                    render: (text, record) =>
                                <Select defaultValue={record.status} onChange={(e) => handleChangeRoomAllocationStatus(record, e)}>
                                    <Select.Option value="for_review"><span className="text-warning">For review</span></Select.Option>
                                    <Select.Option value="approved"><span className="text-success">Approved</span></Select.Option>
                                    <Select.Option value="denied">Denied</Select.Option>
                                </Select>
                },
                {
                    title: 'Action',
                    dataIndex: 'action',
                    key: 'action',
                    render: (text, record) =>
                                        <Space>
                                            <Button icon={<EditOutlined/>} onClick={()=>handleEditRoomAllocation(record)}/>
                                            <Dropdown
                                                    overlay={
                                                        <Menu>
                                                            <Menu.Item onClick={()=>message.info('Coming soon...')}>Request allocation</Menu.Item>
                                                            <Menu.Item onClick={()=>message.info('Coming soon...')}>Give allocation</Menu.Item>
                                                        </Menu>
                                                    }
                                                    trigger={['click']}>
                                                {/* <a className="ant-dropdown-link" onClick={e => e.preventDefault()}>
                                                Click me
                                                </a> */}
                                                <Button icon={<EllipsisOutlined/>}/>
                                            </Dropdown>
                                        </Space>
                },
            ]}
        />


           <Modal
                title="Generate room allocation"
                visible={newRoomAllocationModalVisible}
                onCancel={()=>setnewRoomAllocationModalVisible(false)}
                footer={null}
                width={1000}
            >
                <RoomAllocationForm
                    entities={entities}
                    formName={generateRoomAllocationForm}
                    onFinish={onGenerateRoomAllocationFormFinish}
                />
            </Modal>

            <Modal
                title={`Edit room allocation`}
                visible={editRoomAllocationModalVisible}
                onCancel={()=>setEditRoomAllocationModalVisible(false)}
                footer={null}
                width={1000}
            >
                <Row gutter={[24,24]}>
                <Col xl={12} xs={24}>
                { (roomAllocationsEdit && roomAllocationsEdit.room_type) &&
                <>
                    <Descriptions column={2}>
                        <Descriptions.Item label="Date">{roomAllocationsEdit && <span className="text-primary"><CalendarOutlined/> {moment(roomAllocationsEdit.date).format('MMM D, YYYY')}</span>}</Descriptions.Item>
                        <Descriptions.Item label="Room type">{roomAllocationsEdit && roomAllocationsEdit.room_type.name}</Descriptions.Item>
                        <Descriptions.Item label="Available room that can be allocated" span={2}>
                            <span className="text-success">{ (parseInt(_.find(roomTypeListQuery.data, i => i.id == roomAllocationsEdit.room_type.id).enabled_rooms_count || 0) - parseInt(_.find(roomTypeListQuery.data, i => i.id == roomAllocationsEdit.room_type.id).blocked_rooms) || 0) - (_.sumBy(roomAllocationsEditList, 'allocation')) }</span>
                        </Descriptions.Item>
                        <Descriptions.Item label="Blocked rooms">
                            {parseInt(_.find(roomTypeListQuery.data, i => i.id == roomAllocationsEdit.room_type.id).blocked_rooms || 0)}
                        </Descriptions.Item>
                    </Descriptions>

                    <Descriptions column={2} bordered>
                        <Descriptions.Item label="Entity">{roomAllocationsEdit.entity}</Descriptions.Item>
                        <Descriptions.Item label="Used">{roomAllocationsEdit && roomAllocationsEdit.used}</Descriptions.Item>
                        <Descriptions.Item span={2} label="Allocation">
                            <Select onChange={e => handleRoomAllocationChange(roomAllocationsEdit.id, e, _.map(roomAllocationsEditList, 'id'))} defaultValue={roomAllocationsEdit.allocation}>
                                {
                                    _.map(_.range(roomAllocationsEdit.used, (1+roomAllocationsEdit.allocation+(parseInt(_.find(roomTypeListQuery.data, i => i.id == roomAllocationsEdit.room_type.id).enabled_rooms_count || 0) - parseInt(_.find(roomTypeListQuery.data, i => i.id == roomAllocationsEdit.room_type.id).blocked_rooms || 0)) - (_.sumBy(roomAllocationsEditList, 'allocation'))) ), (count, key) => {
                                        return <Select.Option key={key} value={count}>{count}</Select.Option>
                                    })
                                }
                            </Select>
                        </Descriptions.Item>
                        {/* <Descriptions.Item span={2} label="Status">
                            <Select defaultValue={roomAllocationsEdit.status} onChange={(e) => handleChangeRoomAllocationStatus(roomAllocationsEdit, e)}>
                                <Select.Option value="for_review"><span className="text-warning">For review</span></Select.Option>
                                <Select.Option value="approved"><span className="text-success">Approved</span></Select.Option>
                                <Select.Option value="denied">Denied</Select.Option>
                            </Select>
                        </Descriptions.Item>
                        <Descriptions.Item span={2} label="Allowed roles">
                            <Select onChange={(e) => handleRoomAllocationAllowedRolesChange(roomAllocationsEdit.id, e)} style={{width: '100%'}} defaultValue={roomAllocationsEdit.allowed_roles} mode="multiple">
                            {
                                roleListQuery.data ? roleListQuery.data.map( (item, key) => {
                                    return <Select.Option value={item.name} key={key}>{item.name}</Select.Option>
                                }): ''
                            }
                            </Select>
                        </Descriptions.Item> */}
                    </Descriptions>
                </>
                }
                </Col>
                <Col xl={12} xs={24}>
                <Typography.Title level={5} className="my-2">Related room allocations</Typography.Title>
                <Table
                    size="small"
                    dataSource={roomAllocationsEditList}
                    rowKey="id"
                    columns={[
                        // {
                        //     title: 'ID',
                        //     dataIndex: 'id',
                        //     key: 'id',
                        // },
                        {
                            title: 'Entity',
                            dataIndex: 'entity',
                            key: 'entity',
                        },
                        {
                            title: 'Allocation',
                            dataIndex: 'allocation',
                            key: 'allocation',
                        },
                        {
                            title: 'Used',
                            dataIndex: 'used',
                            key: 'used',
                        },
                    ]}
                />
                </Col>
                </Row>
            </Modal>
        </>
    )
}

export default Page;