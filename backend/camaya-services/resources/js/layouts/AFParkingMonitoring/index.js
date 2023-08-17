import React from 'react'
import { NavLink, withRouter } from 'react-router-dom'
import {connect} from 'react-redux'
import * as action from 'store/actions'
import UserService from 'services/UserService'

import { Layout, Menu, Button, Dropdown, Drawer, Descriptions, Typography, Input, Space, Form, message, notification } from 'antd'
import { MenuUnfoldOutlined, MenuFoldOutlined, LogoutOutlined, ArrowLeftOutlined, DashboardOutlined, UserOutlined, CarOutlined } from '@ant-design/icons'
const { Content, Header, Sider } = Layout;


function Concierge(props) {

    const [collapsed, setCollapsed] = React.useState(true);
    const [collapsedWidth, setCollapsedWidth] = React.useState(80);
    const [myAccountDrawerVisible, setmyAccountDrawerVisible] = React.useState(false);  

    const [selectedMenu, setselectedMenu] = React.useState(null);

    const [changePasswordForm] = Form.useForm();

    const [changePasswordQuery, {isLoading: changePasswordQueryIsLoading, error: changePasswordQueryError}] = UserService.changePassword();

    React.useEffect( () => {

        // console.log(props.location.pathname)

        switch (props.location.pathname) {

            default:
                setselectedMenu('/concierge');
        }

    }, []);

    // Handle sign out
    const handleSignOut = () => {
        
        props.dispatch(action.authLogout());

    }

    const changePasswordFormOnFinish = (values) => {
        console.log(values);

        changePasswordQuery(values, {
            onSuccess: (res) => {
                console.log(res);

                notification.success({
                    message: `Change password success`,
                    description:
                        ``,
                });

                changePasswordForm.resetFields();
            },
            onError: (e) => {
                console.log(e);
                message.info(e.message);
            }
        })
    }
        
    return (
        <Layout style={{height: '100vh'}}>
            <Sider
                trigger={null}
                collapsible
                collapsed={collapsed}
                breakpoint="lg"
                collapsedWidth={collapsedWidth}
                onBreakpoint={broken => {
                    if (broken) { setCollapsedWidth(0);
                    } else { setCollapsedWidth(80); }
                }}
                className="py-5"
            >
                <div style={{display: 'flex', justifyContent:'space-between', alignItems:'center', flexDirection:'column', height: '100%'}}>
                    <div className="logo" style={{textAlign: 'center', color: 'white'}}>
                        <CarOutlined />
                        <div>AF Parking Monitoring</div>
                    </div>
                    <Menu
                        theme="dark"
                        mode="inline"
                        defaultSelectedKeys={['/af-parking-monitoring']}
                        selectedKeys={[selectedMenu]}
                    >
                        <Menu.Item key="/" icon={<ArrowLeftOutlined />}>
                            <NavLink to="/">Back to Main</NavLink>
                        </Menu.Item>
                        <Menu.Item key="/af-parking-monitoring" icon={<DashboardOutlined />}>
                            <NavLink to="/af-parking-monitoring">Dashboard</NavLink>
                        </Menu.Item>
                    </Menu>
                    <Dropdown placement="topLeft" overlay={
                        <Menu style={{overflow:'hidden'}}>
                            <Menu.Item icon={<LogoutOutlined />} onClick={handleSignOut}>
                                Sign out
                            </Menu.Item>
                            <Menu.Divider/>
                            <Menu.Item icon={<UserOutlined />} onClick={()=>setmyAccountDrawerVisible(true)}>
                                My account ({props.user.first_name})
                            </Menu.Item>
                        </Menu>
                    }>
                        <Button shape="circle" type="primary" icon={<UserOutlined />} />
                    </Dropdown>
                </div>
            </Sider>
            <Drawer
                placement="left"
                visible={myAccountDrawerVisible}
                onClose={()=>setmyAccountDrawerVisible(false)}
                title="My account"
                width="500"
            >
                <Descriptions column={2} bordered size="small">
                    <Descriptions.Item label="First name">{props.user.first_name}</Descriptions.Item>
                    <Descriptions.Item label="Last name">{props.user.last_name}</Descriptions.Item>
                    <Descriptions.Item label="Email">{props.user.email}</Descriptions.Item>
                </Descriptions>
                <div className="my-4">
                    <Typography.Text strong>Change password</Typography.Text>

                    <Form layout="vertical" form={changePasswordForm}
                            onFinish={changePasswordFormOnFinish}
                        >
                        <Space>
                        <Form.Item label="Old password" name="old_password" rules={[{required: true}, {min: 6}]}>
                            <Input placeholder="Old password" type="password" />
                        </Form.Item>
                        <Form.Item label="New password" name="new_password" rules={[{required: true}, {min: 6}]}>
                            <Input placeholder="New password" type="password" />
                        </Form.Item>
                        <Button htmlType="submit">Update</Button>
                        </Space>
                    </Form>
                </div>
            </Drawer>
            <Layout>
                { collapsedWidth == 0 &&
                    <Header>
                        {
                            React.createElement(collapsed ? MenuUnfoldOutlined : MenuFoldOutlined, {
                                className: 'trigger',
                                onClick: () => setCollapsed(!collapsed),
                            })
                        }
                    </Header> 
                }
                <Content>                    
                    <div className="p-5">
                        <div style={{float: 'right'}}><UserOutlined/> {props.user.first_name}</div>
                        {props.children}
                    </div>
                </Content>
            </Layout>
        </Layout>
    );

}

const mapStateToProps = (state) => {
    return {
        user: state.Auth.user,
        isTokenExpired: state.Auth.isTokenExpired,
        isAuthenticated: state.Auth.isAuthenticated,
    }
}

export default connect(mapStateToProps)(withRouter(Concierge))
