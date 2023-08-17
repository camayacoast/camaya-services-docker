import React from 'react'

import { Table, Modal, Button, Form, Input, Select, message, Space, Row, Col, Card, Typography } from 'antd'
import { PlusCircleOutlined, UserOutlined } from '@ant-design/icons'

import SalesAdminPortalServices from 'services/SalesAdminPortal';

const NewSalesTeamContent = (props) => {

    // Queries
    // Team owner
    
    const [newSalesTeamQuery, { isLoading: newSalesTeamQueryIsLoading, reset: newSalesTeamQueryReset }] = SalesAdminPortalServices.newSalesTeam(); 

    // Forms
    const [newSalesTeamForm] = Form.useForm();

    const handleFormFinish = (values) => {
        // console.log(values);


        if (newSalesTeamQueryIsLoading) {
            return false;
        }

        newSalesTeamQuery(values, {
            onSuccess: (res) => {
                console.log(res);

                message.success('New Sales Team Created!');

                newSalesTeamForm.resetFields();
                newSalesTeamQueryReset();

                props.setNewSalesTeamModalVisible(false);

                props.salesTeamListQuery.refetch();
            },
            onError: (e) => {
                message.info(e.message);
                newSalesTeamQueryReset();
            }
        })
    }

    return <div>
        <Form
            form={newSalesTeamForm}
            layout="vertical"
            onFinish={handleFormFinish}
        >

            {/* <Form.Item name="parent_team" label="Parent team (optional)">
                <Select placeholder="Parent team">
                    <Select.Option value=""></Select.Option>
                    {
                        props.teams && props.teams.map(
                            (item, key) => {
                                return <Select.Option key={key} value={item.id}>{item.id} {item.name}</Select.Option>
                        })
                    }
                </Select>
            </Form.Item> */}
            
            <Form.Item name="team_name" label="Team name" rules={[{ required: true }]}>
                <Input placeholder="Team name" />
            </Form.Item>

            <Form.Item name="owner" label="Team owner" rules={[{ required: true }]}>
                <Select 
                    placeholder="Owner"
                    showSearch
                    optionFilterProp="children"
                    filterOption={true}>
                    {
                        props.agents && props.agents
                            .filter( i => i.roles[0].name == 'Sales Director')
                            .map(
                            (item, key) => {
                                return <Select.Option key={key} value={item.id}>{item.first_name} {item.last_name} - {item.email}</Select.Option>
                        })
                        
                    }
                </Select>
            </Form.Item>

            <Form.Item name="members" label="Sales Team Members">
                <Select 
                    placeholder="Members" 
                    mode="multiple"
                    showSearch
                    optionFilterProp="children"
                    filterOption={true}>
                    {
                        props.agents && props.agents
                            .filter( item => (item.team_member_of?.team == null))
                            .map(
                            (item, key) => {
                                return <Select.Option key={key} value={item.id}>{item.first_name} {item.last_name} - {item.email}</Select.Option>
                        })
                        
                    }
                </Select>
            </Form.Item>


            <Button htmlType="submit">Save</Button>
        </Form>
    </div>
}

const EditSalesTeamContent = (props) => {

    // console.log(props);

    const [newName, setNewName] = React.useState({});

    const [updateSalesTeam, { isLoading: updateSalesTeamIsLoading, reset: updateSalesTeamReset }] = SalesAdminPortalServices.updateSalesTeam(); 

    const [addSubTeamQuery, { isLoading: addSubTeamQueryIsLoading, reset: addSubTeamQueryReset }] = SalesAdminPortalServices.addSubTeam(); 
    const [updateTeamLeadQuery, { isLoading: updateTeamLeadQueryIsLoading, reset: updateTeamLeadQueryReset }] = SalesAdminPortalServices.updateTeamLead(); 
    const [updateTeamMembersQuery, { isLoading: updateTeamMembersQueryIsLoading, reset: updateTeamMembersQueryReset }] = SalesAdminPortalServices.updateTeamMembers(); 
    const [updateTeamNameQuery, { isLoading: updateTeamNameQueryIsLoading, reset: updateTeamNameQueryReset }] = SalesAdminPortalServices.updateTeamName(); 
    
    
    // Forms
    const [editSalesTeamForm] = Form.useForm();

    React.useEffect( () => {

        editSalesTeamForm.resetFields();

        const fields = {
            team_name: props.team.name,
            owner: props.team.owner ? props.team.owner.user.id : null,
            // members: _.map(props.team.members, m => { return { id: m.id, first_name: m.user.first_name, last_name: m.user.last_name, email: m.user.email } } ),
            members: _.map(props.team.members, m => m.user.id ),
        }

        editSalesTeamForm.setFieldsValue(fields);
    },[]);

    const handleFormFinish = (values) => {
        // console.log(values);


        if (updateSalesTeamIsLoading) {
            return false;
        }

        const newValues = {
            ...values,
            id: props.team.id
        }

        updateSalesTeam(newValues, {
            onSuccess: (res) => {
                console.log(res);

                editSalesTeamForm.resetFields();
                updateSalesTeamReset();

                props.setEditSalesTeamModalVisible(false);

                props.salesTeamListQuery.refetch();
            },
            onError: (e) => {
                message.info(e.message);
                updateSalesTeamReset();
            }
        })
    }

    const handleAddSubTeam = () => {
        console.log(props.team.id);

        if (addSubTeamQueryIsLoading) {
            return false;
        }

        props.team.sub_teams = [];

        addSubTeamQuery({
            team_id: props.team.id
        }, {
            onSuccess: (res) => {
                // console.log(res);
                props.salesTeamListQuery.refetch();
                props.setEditTeam(res.data.data);
            },
            onError: (e) => {
                message.info(e.message);
            }
        })
    }

    const handleUpdateTeamLead = (team_id, team_lead_id) => {
        // console.log(props.team.id);

        if (updateTeamLeadQueryIsLoading) {
            return false;
        }

        updateTeamLeadQuery({
            team_id: team_id,
            team_lead_id: team_lead_id
        }, {
            onSuccess: (res) => {
                // console.log(res);
                props.salesTeamListQuery.refetch();
                props.setEditTeam(res.data.data);
                message.success('Team lead updated');
            },
            onError: (e) => {
                message.info(e.message);
            }
        })
    }

    const handleUpdateTeamMembers = (team_id, team_member_ids) => {
        // console.log(props.team.id);

        if (updateTeamMembersQueryIsLoading) {
            return false;
        }

        updateTeamMembersQuery({
            team_id: team_id,
            team_member_ids: team_member_ids
        }, {
            onSuccess: (res) => {
                // console.log(res);
                props.salesTeamListQuery.refetch();
                props.setEditTeam(res.data.data);
                message.success('Team members updated');
            },
            onError: (e) => {
                message.info(e.message);
            }
        })
    }

    const handleTeamNameUpdate = (team_id, name) => {
        // console.log(props.team.id);

        if (updateTeamNameQueryIsLoading) {
            return false;
        }

        updateTeamNameQuery({
            team_id: team_id,
            name: name
        }, {
            onSuccess: (res) => {
                // console.log(res);
                setNewName({
                    ...newName,
                    [team_id]: name
                });
                props.salesTeamListQuery.refetch();
                message.success('Team name updated');
            },
            onError: (e) => {
                message.info(e.message);
            }
        })
    }

    

    return <div>
        <Row gutter={[8,8]}>
            <Col xs={12}>
                <Form
                    form={editSalesTeamForm}
                    layout="vertical"
                    onFinish={handleFormFinish}
                >

                    {/* <Form.Item name="parent_team" label="Parent team (optional)">
                        <Select placeholder="Parent team">
                            <Select.Option value=""></Select.Option>
                            {
                                props.teams && props.teams.map(
                                    (item, key) => {
                                        return <Select.Option key={key} value={item.id}>{item.id} {item.name}</Select.Option>
                                })
                                
                            }
                        </Select>
                    </Form.Item> */}
                    
                    <Form.Item name="team_name" label="Team name" rules={[{ required: true }]}>
                        <Input placeholder="Team name" />
                    </Form.Item>

                    <Form.Item name="owner" label="Sales Director" rules={[{ required: true }]}>
                        <Select 
                            placeholder="Owner"
                            showSearch
                            optionFilterProp="children"
                            filterOption={true}>
                            {
                                props.agents && props.agents
                                .filter( i => i.roles[0].name == 'Sales Director')
                                .map(
                                    (item, key) => {
                                        return <Select.Option key={key} value={item.id}>{item.first_name} {item.last_name} - {item.email}</Select.Option>
                                })
                                
                            }
                        </Select>
                    </Form.Item>

                    <Form.Item name="members" label="Team members">
                        <Select 
                            placeholder="Members" 
                            showSearch
                            optionFilterProp="children"
                            mode="multiple" 
                            filterOption={true}>
                            {
                                props.agents && props.agents
                                    .filter( item => (item.team_member_of?.team == null || _.includes(props.team.members.map(i => i.user_id), item.id)) && item.id != props.team.owner_id)
                                    .map(
                                    (item, key) => {
                                        return <Select.Option key={key} value={item.id}>{item.first_name} {item.last_name} - {item.email}</Select.Option>
                                })
                            }
                        </Select>
                    </Form.Item>


                    <Button htmlType="submit">Save</Button>
                </Form>
            </Col>
            <Col xs={12}>
                <Typography.Title level={5}>Sub teams ({props.team.sub_teams.length})</Typography.Title>
                <Button size="small" icon={<PlusCircleOutlined/>} onClick={()=>handleAddSubTeam()} block style={{marginBottom: 8}}>Add sub team</Button>
                {
                    props.team.sub_teams.length ?
                    props.team.sub_teams.map( (v, k) => {
                        return <Card size="small" key={k} className="mb-2">
                            <Row gutter={[8,8]}>
                                <Col xs={1}>
                                    {k+1}
                                </Col>
                                <Col xs={11}>
                                    Team name: <Typography.Text editable={{ onChange: (e) => handleTeamNameUpdate(v.id, e) }}>{newName[v.id] ? newName[v.id] : v.name}</Typography.Text>
                                </Col>
                                <Col xs={12}>
                                    Team lead:
                                    <Select 
                                        defaultValue={v.owner_id}
                                        onChange={(e) => handleUpdateTeamLead(v.id, e)}
                                        placeholder="Team lead" 
                                        showSearch
                                        optionFilterProp="children"
                                        // mode="multiple" 
                                        style={{width: '100%'}}
                                        filterOption={true}>
                                            <Select.Option value={null}>-</Select.Option>
                                        {
                                            props.team.members && props.team.members
                                                .filter( item => item.user.sub_team == null)
                                                .map(
                                                (item, key) => { 
                                                    return <Select.Option key={key} value={item.user_id}>{item.user.sub_team?.team?.name} {item.user.first_name} {item.user.last_name} - {item.user.email}</Select.Option>
                                            })
                                        }
                                    </Select>
                                </Col>
                                <Col xs={24}>
                                    Team members:
                                    <Select
                                        defaultValue={v.members.map(i => i.user_id)}
                                        onChange={(e) => handleUpdateTeamMembers(v.id, e)}
                                        placeholder="Members" 
                                        showSearch
                                        optionFilterProp="children"
                                        mode="multiple" 
                                        style={{width: '100%'}}
                                        filterOption={true}>
                                        {
                                            props.team.members && props.team.members
                                            .filter( item => (item.user.sub_team == null || _.includes(v.members.map(i => i.user_id), item.user_id)) && item.user_id != v.owner_id)
                                            .filter( item => !_.includes(props.team.sub_teams.map(i => i.owner_id), item.user_id ))
                                            .map(
                                                (item, key) => {
                                                    return <Select.Option key={key} value={item.user_id}>{item.user.first_name} {item.user.last_name} - {item.user.email}</Select.Option>
                                            })
                                        }
                                    </Select>
                                </Col>
                            </Row>
                        </Card>
                    }) : ''
                }
                {addSubTeamQueryIsLoading && <div style={{width: '100%', textAlign: 'center', padding: 5}}>Loading...</div>}
            </Col>
        </Row>
    </div>

}


export default function Page(props) {

    const columns = [
        {
            title: 'ID',
            dataIndex: 'id',
            key:'id',
        },
        {
            title: 'Name',
            dataIndex: 'name',
            key:'name',
        },
        {
            title: 'Sales Director',
            render: (text, record) => {
                return record.owner ? record.owner.user.first_name + ' ' + record.owner.user.last_name : ''
            }
        },
        {
            title: 'Action',
            render: (text, record) => {
                return <Space>
                        <Button size="small" icon={<UserOutlined/>} onClick={() => handleEditTeamClick(record)}>{record.owner ? record.members_count + 1 : record.members_count} edit team</Button>
                    </Space>
            }
        },
    ];

    const [newSalesTeamModalVisible, setNewSalesTeamModalVisible] = React.useState(false);
    const [editSalesTeamModalVisible, setEditSalesTeamModalVisible] = React.useState(false);
    const [editTeam, setEditTeam] = React.useState(null);

    const salesTeamListQuery = SalesAdminPortalServices.salesTeamList();
    const salesAgentListQuery = SalesAdminPortalServices.salesAgentList();

    React.useEffect( () => {
        if (editSalesTeamModalVisible == false) {
            setEditTeam(null);
        }
    },[editSalesTeamModalVisible]);

    const handleEditTeamClick = (team) => {
        setEditSalesTeamModalVisible(true);
        setEditTeam(team);
    }

    return (
        <div>

            <Button onClick={()=>setNewSalesTeamModalVisible(true)}>New Sales Team</Button>

            <Modal
                title="New Sales Team"
                visible={newSalesTeamModalVisible}
                onCancel={()=>setNewSalesTeamModalVisible(false)}
                footer={null}
            >
                <NewSalesTeamContent salesTeamListQuery={salesTeamListQuery} setNewSalesTeamModalVisible={setNewSalesTeamModalVisible} teams={salesTeamListQuery.data} agents={salesAgentListQuery.data}/>
            </Modal>

            { editSalesTeamModalVisible && 
                <Modal
                    title="Edit Sales Team"
                    visible={editSalesTeamModalVisible}
                    onCancel={()=>setEditSalesTeamModalVisible(false)}
                    footer={null}
                    width={1000}
                    forceRender
                >
                    <EditSalesTeamContent setEditTeam={setEditTeam} team={editTeam} salesTeamListQuery={salesTeamListQuery} setEditSalesTeamModalVisible={setEditSalesTeamModalVisible} teams={salesTeamListQuery.data} agents={salesAgentListQuery.data}/>
                </Modal>
            }

            <Table
                dataSource={salesTeamListQuery.data}
                columns={columns}
                rowKey="id"
                size="small"
            />

        </div>
    )
}