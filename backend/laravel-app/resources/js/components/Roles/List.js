import React from 'react'
import { NavLink, Switch as RouteSwitch, Route, withRouter } from 'react-router-dom'
import PageNotFound from 'common/PageNotFound'
import PermissionServices from 'services/PermissionService'
import RoleServices from 'services/RoleService'
import { queryCache } from 'react-query'

import { List, Collapse, Row, Col, Switch, Menu, message, Typography, Button, Modal, Form, Input, notification, Alert } from 'antd'
import { UserOutlined, PlusOutlined } from '@ant-design/icons'

const { Panel } = Collapse;

const PermissionList = (props) => {

    const { status, data, isFetching } = PermissionServices.listByRole(props.match.params.id);

    const [changeRolePermissionQuery, {isLoading, error: changeRolePermissionError}] = PermissionServices.changeRolePermissions();

    const [modules, setModules] = React.useState([]);


    React.useEffect( () => {

        setModules(buildRoleModulePermission());

        // Clean up
        return ( () => {

        });
    },[data]);

    const buildRoleModulePermission = () => {
        return _.uniqBy(_.map(props.permissions, (item) => {

            const module_name = _.split(item, '.')[0];

            const module_data = {
                module: module_name,
                permissions: _.compact(_.map(props.permissions, p => {
                    const permission_name = _.split(p, '.')[0];
                    return permission_name == module_name ? 
                        (
                            _.includes(data, p)
                            ? { action: p, allowed: true }
                            : { action: p, allowed: false }
                        )
                    : null;
                }))
            }

            return module_data;
        }), 'module');
    }
    

    const handlePermissionChange = (e, module, index, role) => {

        const module_index = _.findIndex(modules, { module: module });
        modules[module_index].permissions[index].allowed = e;

        changeRolePermissionQuery({ 
            allowed: e,
            permission: modules[module_index].permissions[index].action,
            role: role,
         }, {
            onSuccess: (res) => {
    
            // console.log(res);
            //   queryCache.setQueryData(['',], data);
      
              message.success(`${res.data.permission} has been ${e == true ? 'allowed' : 'disallowed'} for ${_.replace(role, '+', ' ').toLowerCase()}.`);
    
            },
            onError: ({response}) => {
                // console.log(response);
                message.error("You're not allowed to change this.");
                modules[module_index].permissions[index].allowed = !e;
            }
        });

    }


    return (
        <>
        { 
            _.includes(_.map(props.roles, i => i.name.toLowerCase()), _.replace(props.match.params.id, '+', ' ').toLowerCase()) ?
                <Collapse ghost className="card-shadow">
                        {
                            modules && modules.map( (i, key) => {
                                return (
                                    <Panel
                                        header={<>{i.module} <small style={{float: 'right'}}>{_.filter(i.permissions, 'allowed').length} active &middot; {i.permissions.length} permissions</small></>}
                                        key={key}
                                    >
                                        <List
                                            className="pl-4"
                                            bordered={false}
                                            dataSource={i.permissions}
                                            renderItem={ (item, index) => (
                                                <List.Item>
                                                    {_.split(item.action, '.')[1]} {_.split(item.action, '.')[2]}
                                                    <Switch checked={item.allowed} onChange={e => handlePermissionChange(e, i.module, index, props.match.params.id)} style={{float: 'right'}} />
                                                </List.Item>
                                            )}
                                        />
                                    </Panel>
                                )
                            })
                        }
                </Collapse>
            :
            <PageNotFound/>
        }
        </>
    )
};


function Page(props) {

    const { data: roles, refetch: refetchRoles } = RoleServices.list();

    const [newRoleQuery, {isLoading: newRoleIsLoading, error: newRoleError}] = RoleServices.create();
    const [newPermissionQuery, {isLoading: newPermissionIsLoading, error: newPermissionError}] = PermissionServices.create();

    const { status, data: permissions, isFetching } = PermissionServices.list();

    const [newRoleModal, setNewRoleModal] = React.useState(false);
    const [newPermissionModal, setNewPermissionModal] = React.useState(false);

    const [newRoleForm] = Form.useForm();
    const [newPermissionForm] = Form.useForm();

    const onFinish = (values) => {
        // console.log(values);

        newRoleQuery(values, {
            onSuccess: (res) => {
    
            //   roles.push(values.role);
            refetchRoles();
      
              notification.success({
                  message: 'Created new role!',
                  description:
                      `You have successfully created role ${values.role}`,
              });
      
              // Reset Forms
              newRoleForm.resetFields();
      
              // Close Modal
              setNewRoleModal(false);
    
            },
        });
    }

    const onFinishNewPermissionForm = (values) => {
        console.log(values);

        newPermissionQuery({ permission: `${values.module}.${values.action}.${values.feature}` }, {
            onSuccess: (res) => {
    
            //   roles.push(values.role);
            console.log(res.data.permission);
            permissions.push(res.data.permission.join('.'));
      
              notification.success({
                  message: 'Created new permission!',
                  description:
                      `You have successfully created permission ${values.module}.${values.action}.${values.feature}`,
              });
      
              // Reset Forms
              newPermissionForm.resetFields();
      
              // Close Modal
              setNewPermissionModal(false);
    
            },
        });
    }

    return (
        <>
        <Modal
            title="New Role"
            visible={newRoleModal}
            onOk={()=>newRoleForm.submit()}
        //   confirmLoading={confirmLoading}
            okText={'Save'}
            onCancel={()=>setNewRoleModal(false)}
        >
            <Form
                onFinish={onFinish}
                form={newRoleForm}
            >
                <Form.Item name="role" className="mb-0"
                    rules={[
                        {
                            required: true,
                            message: 'Role is required.',
                        },
                    ]}
                >
                    <Input placeholder="e.g.: admin, bpo agent, sales agent" />
                </Form.Item>
            </Form>
            {
              newRoleError && 
              <Alert
                message={newRoleError && newRoleError.errors.role}
                type="warning"
                showIcon
                className="mt-2"
              />
              
            }
        </Modal>

        <Modal
            title="New Permission"
            visible={newPermissionModal}
            onOk={()=>newPermissionForm.submit()}
        //   confirmLoading={confirmLoading}
            okText={'Save'}
            onCancel={()=>setNewPermissionModal(false)}
        >
            <Form
                onFinish={onFinishNewPermissionForm}
                form={newPermissionForm}
            >
                <Row gutter={[8,8]}>
                    <Col xs={24} xl={8}>
                        <Form.Item name="module" className="mb-0"
                            rules={[
                                {
                                    required: true,
                                    message: 'module is required.',
                                },
                                {
                                    // pattern: new RegExp('^[a-zA-Z]+$','g'),
                                    message: 'Accepts letters only.'
                                }
                            ]}
                        >
                            <Input placeholder="Module" />
                        </Form.Item>
                    </Col>

                    <Col xs={24} xl={8}>
                        <Form.Item name="action" className="mb-0"
                            rules={[
                                {
                                    required: true,
                                    message: 'action is required.',
                                },
                                {
                                    // pattern: new RegExp('^[a-zA-Z]+$','g'),
                                    message: 'Accepts letters only.'
                                }
                            ]}
                        >
                            <Input placeholder="Action" />
                        </Form.Item>
                    </Col>

                    <Col xs={24} xl={8}>
                        <Form.Item name="feature" className="mb-0"
                            rules={[
                                {
                                    required: true,
                                    message: 'feature is required.',
                                },
                                {
                                    // pattern: new RegExp('^[a-zA-Z]+$','g'),
                                    message: 'Accepts letters only.'
                                }
                            ]}
                        >
                            <Input placeholder="Feature" />
                        </Form.Item>
                    </Col>
                </Row>
            </Form>
            {
              newPermissionError && 
              <Alert
                message={newPermissionError && newPermissionError.errors.permission}
                type="warning"
                showIcon
                className="mt-2"
              />
            }
        </Modal>
        <Row gutter={[32,0]}>
            <Col xl={5} xs={24}>
                <Typography.Title level={4} type="secondary">Roles<Button style={{float: 'right'}} type="link" icon={<PlusOutlined/>} onClick={()=>setNewRoleModal(true)} /></Typography.Title>
                <Menu
                    className="mb-4"
                    style={{border: '0'}}
                    theme="light"
                    mode="vertical"
                    selectedKeys={[props.location.pathname]}
                >
                    {
                        roles && roles.map( (item, key) => (
                            <Menu.Item className="rounded-12" key={`/access/${_.replace(item.name, ' ', '+').toLowerCase()}`}>
                                <NavLink to={`/access/${_.replace(item.name, ' ', '+').toLowerCase()}`}>{item.name}</NavLink>
                            </Menu.Item>
                        ))
                    }
                    
                </Menu>
            </Col>
            <Col xl={8} xs={24}>
                <Typography.Title level={4} type="secondary">Permissions<Button style={{float: 'right'}} type="link" icon={<PlusOutlined/>} onClick={()=>setNewPermissionModal(true)} /></Typography.Title>
                <RouteSwitch>
                    <Route path={`/access`} exact render={ () => <div className="fadeIn">Please select a role</div>}/>
                    <Route path={`/access/:id`} component={(props) => <PermissionList roles={roles} match={props.match} permissions={permissions} />}/>
                    <Route render={ () =>  <PageNotFound /> } />
                </RouteSwitch>
            </Col>
        </Row>
        </>
    )
}

export default withRouter(Page);