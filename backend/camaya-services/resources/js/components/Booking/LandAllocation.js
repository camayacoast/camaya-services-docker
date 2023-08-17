import React, { useState } from 'react'
import RoleService from 'services/RoleService'
import UserService from 'services/UserService'
import LandAllocationService from 'services/Booking/LandAllocationService'
// import SalesAdminPortalServices from 'services/SalesAdminPortal';

import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');

import { Typography, Modal, Form, Row, Col,InputNumber, Select, DatePicker, Button, message, Alert, notification, Table, Space, } from 'antd'
import { MinusCircleOutlined, PlusOutlined, ReloadOutlined } from '@ant-design/icons'

import { queryCache } from 'react-query';

const LandAllocationForm = ({formName, onFinish, entities, sales_director, users}) => {

    const [selected, setSelected] = React.useState([]);
    // const [paxCount, setpaxCount] = React.useState(paxCountInitialValues);
    // const [paxDayCount, setpaxDayCount] = React.useState(paxCountInitialValues);

    const [agentList, setAgentList] = useState(queryCache.getQueryData("agent-users"));

    const roleListQuery = RoleService.list();
    // const userListQuery = UserService.list();
    // const salesAgentListQuery = SalesAdminPortalServices.salesAgentList();

    // React.useEffect(()=> {
    //     console.log(paxCount.monday, paxDayCount.monday);
    // }, [paxCount, paxDayCount]);
    
    const handleChangeEntity = (value, el) => {

        setSelected(_.map(formName.getFieldValue('allocations'), 'entity'));

    }

    return (
        <Form
            layout="vertical"
            form={formName}
            onFinish={onFinish}
        >
            <Row gutter={[8,8]}>
                <Col xl={24}>
                    <Alert type="info" message="This land allocation generation feature only creates new records and will skip existing dates with an entity allocation."/>
                </Col>
                <Col xl={9}>
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
                                                setSelected(_.map(formName.getFieldValue('allocations'), 'sales_director'));
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
                                                    .map( (item, key) =>
                                                        <Select.Option value={item} key={key}>{item}</Select.Option>
                                                    )
                                                }
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                    <Col xl={12}>
                                        <Form.Item label={`Sales Director`} name={[field.name, 'owner_id']} rules={[{ required: false }]}>
                                        <Select 
                                                showSearch
                                                optionFilterProp="children"
                                                filterOption={true}>
                                                {
                                                    agentList && agentList
                                                        .filter( i => i.roles[0].name == 'Sales Director')
                                                        .map(
                                                        (item, key) => {
                                                            return <Select.Option key={key} value={item.id}>{item.first_name} {item.last_name} ({item.email})</Select.Option>
                                                    })
                                                    
                                                }
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                    <Col xl={9}>
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

                                    <Col xl={12}>
                                        <Form.Item label="Allowed users" name={[field.name, 'allowed_users']}>
                                            <Select
                                                style={{width:'100%'}}
                                                showSearch
                                                optionFilterProp="children"
                                                // filterOption={(input, option) =>
                                                //     option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                                // }
                                                filterOption={true}
                                                mode="multiple">
                                                {
                                                    agentList && agentList
                                                    // .filter( i => i.roles[0].name == 'POC Agent' || i.roles[0].name == 'Property Consultant' || i.roles[0].name == 'Sales Director' || i.roles[0].name == 'Sales Manager')
                                                    .map((item, key) => {
                                                        return <Select.Option key={key} value={item.id}>{item.first_name} {item.last_name} ({item.email})</Select.Option>
                                                    })
                                                }
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                    
                                    <Col xl={20}>
                                        <Row>
                                            <Col xl={1}></Col>
                                            <Col xl={3}>
                                                <Form.Item label="Monday" name={[field.name, 'monday']}>
                                                    {/* <InputNumber min={0} max={1000} onChange={(e) => handleLandAllocationChange(e, field.name, 'monday')} /> */}
                                                    <InputNumber min={0} max={1000} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item label="Tuesday" name={[field.name, 'tuesday']}>
                                                    <InputNumber min={0} max={1000} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item label="Wednesday" name={[field.name, 'wednesday']}>
                                                    <InputNumber min={0} max={1000} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item label="Thursday" name={[field.name, 'thursday']}>
                                                    <InputNumber min={0} max={1000} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item label="Friday" name={[field.name, 'friday']}>
                                                    <InputNumber min={0} max={1000} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item label="Saturday" name={[field.name, 'saturday']}>
                                                    <InputNumber min={0} max={1000} />
                                                </Form.Item>
                                            </Col>
                                            <Col xl={3}>
                                                <Form.Item label="Sunday" name={[field.name, 'sunday']}>
                                                    <InputNumber min={0} max={1000} />
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
        // 'BPO', 
        // 'HOA', 
        'RE', 
        // 'OTA',
    ];

    const sales_director = [
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
        'SD Lhot Quiambao',
    ];

    // States

    const [newLandAllocationModalVisible, setnewLandAllocationModalVisible] = React.useState(false);

    const [dateFilter, setdateFilter] = React.useState(moment());
    // const [dateForRoomTypeList, setDateForRoomTypeList] = React.useState();

    // Form
    const [generateLandAllocationForm] = Form.useForm();

    // Get
    const landAllocationListQuery = LandAllocationService.list(dateFilter.format('YYYY-MM-DD'));
    const roleListQuery = RoleService.list();
    const userListQuery = UserService.allAgentList();

    // Put, Post
    const [newLandAllocationQuery, { isLoading: newLandAllocationQueryIsLoading }] = LandAllocationService.create();
    const [changeLandAllocationStatusQuery, { isLoading: changeLandAllocationStatusQueryIsLoading }] = LandAllocationService.changeLandAllocationStatus();
    const [updateLandAllocationAllowedRolesQuery, { isLoading: updateLandAllocationAllowedRolesQueryIsLoading }] = LandAllocationService.updateLandAllocationAllowedRoles();
    const [updateLandAllocationAllowedUsersQuery, { isLoading: updateLandAllocationAllowedUsersQueryIsLoading }] = LandAllocationService.updateLandAllocationAllowedUsers();
    const [updateAllocationQuery, { isLoading: updateAllocationQueryIsLoading }] = LandAllocationService.updateLandAllocation();


    React.useEffect(()=> {
        if (dateFilter) landAllocationListQuery.refetch();
    }, [dateFilter]);

    const handleChangeAllocation = (value, id) => {
        console.log(value, id);

        if (updateAllocationQueryIsLoading) return false;

        updateAllocationQuery({
            id: id,
            value: parseInt(value),
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success("Update allocation successful!");
                landAllocationListQuery.refetch();
            },
            onError: (e) => {
                console.log(e);
                message.error(e.error)
            }
        })
    }
    
    const onGenerateLandAllocationFormFinish = values => {
        console.log(values);

        if (newLandAllocationQueryIsLoading) return false;

        newLandAllocationQuery(values, {
            onSuccess: (res) => {
                console.log(res);

                generateLandAllocationForm.resetFields();
                setnewLandAllocationModalVisible(false);
            
                notification.success({
                    message: `New land allocations added`,
                    description:
                        ``,
                });

                if (res.data.skipped.length) {
                    Modal.confirm({
                        title: 'Skipped saving record',
                        content: (
                            <>
                                <Alert className="mb-2" message="Skipped land allocations. These items already exist in our records. Please apply corresponding change manually."/>
                                <Table
                                    size="small"
                                    dataSource={res.data.skipped}
                                    rowKey="id"
                                    columns={[
                                        {
                                            title: 'Entity',
                                            dataIndex: 'entity',
                                            key: 'entity',
                                        },
                                        {
                                            title: 'Date',
                                            dataIndex: 'date',
                                            key: 'date',
                                            render: (text, record) => <>{moment(record.date).format('YYYY-MM-DD')}</>
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

    const handleChangeLandAllocationStatus = ({id}, status) => {

        if (changeLandAllocationStatusQueryIsLoading) return false;

        changeLandAllocationStatusQuery({ id: id, new_status: status}, {
            onSuccess: (res) => {
                console.log(res);
            
                notification.success({
                    message: `Changed land allocation status`,
                    description:
                        ``,
                });

                // queryCache.setQueryData(['land-allocations'], prev => {

                //     const index = _.findIndex([...prev], i => i.id == res.data.id);
                //     let newData = [...prev];
                //     newData[index] = res.data;

                //     return [...newData];
                // });

            },
            onError: (e) => {
                console.log(e)
                message.warning(e.message);
            }
        });
    }

    const handleLandAllocationAllowedRolesChange = (id, roles) => {

        if (updateLandAllocationAllowedRolesQueryIsLoading) return false;

        updateLandAllocationAllowedRolesQuery({
            id: id,
            new_roles: roles,
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success('Land allocation allowed roles update successful!');
                
                // queryCache.setQueryData(['land-allocations'], prev => {

                //     const index = _.findIndex([...prev], i => i.id == res.data.id);
                //     let newData = [...prev];
                //     newData[index] = res.data;

                //     return [...newData];
                // });
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handleLandAllocationAllowedUserChange = (id, users) => {

        if (updateLandAllocationAllowedUsersQueryIsLoading) return false;

        updateLandAllocationAllowedUsersQuery({
            id: id,
            new_users: users,
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success('Land allocation allowed users update successful!');
                
                // queryCache.setQueryData(['land-allocations'], prev => {

                //     const index = _.findIndex([...prev], i => i.id == res.data.id);
                //     let newData = [...prev];
                //     newData[index] = res.data;

                //     return [...newData];
                // });
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    return (
        <>
        <div className="mb-4">
            <Typography.Title level={4}>Land Allocations</Typography.Title>
            <Space>
                <Button icon={<PlusOutlined/>} onClick={()=>setnewLandAllocationModalVisible(true)}>Add allocation</Button>
                <Button icon={<ReloadOutlined />} onClick={() => landAllocationListQuery.refetch()} />
            </Space>
        </div>

        <Table
            loading={landAllocationListQuery.isLoading}
            size="small"
            dataSource={landAllocationListQuery.data && landAllocationListQuery.data.filter(i => (dateFilter ? moment(i.date).format('YYYY-MM-DD') == moment(dateFilter).format('YYYY-MM-DD') : true))}
            rowKey="id"
            expandable={{
                expandedRowRender: record => <>
                        <Typography.Text strong>Allowed roles</Typography.Text>
                        <Select onChange={(e) => handleLandAllocationAllowedRolesChange(record.id, e)} style={{width: '100%'}} defaultValue={record.allowed_roles} mode="multiple">
                        {
                            roleListQuery.data ? roleListQuery.data.map( (item, key) => {
                                return <Select.Option value={item.name} key={key}>{item.name}</Select.Option>
                            }): ''
                        }
                        </Select>

                        <Typography.Text strong>Allowed users</Typography.Text>
                        <Select onChange={(e) => handleLandAllocationAllowedUserChange(record.id, e)} style={{width: '100%'}} mode="multiple" defaultValue={_.map(record.allowed_users, i => i.user_id)}>
                        {
                            userListQuery.data && userListQuery.data
                            .filter( i => i.roles[0].name == 'POC Agent' || i.roles[0].name == 'Property Consultant' || i.roles[0].name == 'Sales Director' || i.roles[0].name == 'Sales Manager')
                            .map((item, key) => {
                                return <Select.Option key={key} value={item.id}>{item.first_name} {item.last_name} ({item.email})</Select.Option>
                            })
                        }
                        </Select></>,
            }}
            columns={[
                {
                    title: <>Date <DatePicker size="small" defaultValue={moment()} onChange={e => setdateFilter(e)}/></>,
                    dataIndex: 'date',
                    key: 'date',
                    render: (text, record) => <>{moment(record.date).format('MMM DD, YYYY')}</>
                },
                {
                    title: 'Entity',
                    dataIndex: 'entity',
                    key: 'entity',
                    filters: [
                        // { text: 'BPO', value: 'BPO' },
                        { text: 'RE', value: 'RE' },
                        // { text: 'HOA', value: 'HOA' },
                        // { text: 'OTA', value: 'OTA' },
                    ],
                    onFilter: (value, record) => record.entity.includes(value),
                },
                {
                    title: 'Sales Director',
                    dataIndex: '',
                    key: '',
                    render: (text, record) => <>{record.owner.first_name} {record.owner.last_name}<br/><small>{record.owner.email}</small></>
                },
                {
                    title: 'Allocation',
                    dataIndex: 'allocation',
                    key: 'allocation',
                    render: (text, record) => <><Typography.Text editable={{ onChange: (value) =>  handleChangeAllocation(value, record.id) }}>{record.allocation}</Typography.Text></>
                },
                {
                    title: 'Used',
                    dataIndex: 'used',
                    key: 'used',
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
                                <Select defaultValue={record.status} onChange={(e) => handleChangeLandAllocationStatus(record, e)}>
                                    <Select.Option value="for_review"><span className="text-warning">For review</span></Select.Option>
                                    <Select.Option value="approved"><span className="text-success">Approved</span></Select.Option>
                                    <Select.Option value="denied">Denied</Select.Option>
                                </Select>
                },
            ]}
        />

           <Modal
                title="Generate land allocation"
                visible={newLandAllocationModalVisible}
                onCancel={()=>setnewLandAllocationModalVisible(false)}
                footer={null}
                width={1000}
            >
                <LandAllocationForm
                    entities={entities}
                    sales_director={sales_director}
                    formName={generateLandAllocationForm}
                    onFinish={onGenerateLandAllocationFormFinish}
                    user={userListQuery.data ?? []}
                />
            </Modal>
        </>
    )
}

export default Page;