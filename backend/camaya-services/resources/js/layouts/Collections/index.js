import React from 'react'
import { NavLink, withRouter } from 'react-router-dom'
import {connect} from 'react-redux'
import * as action from 'store/actions'
import { can } from 'utils/casl/ability-context'
import Warehouse from 'assets/warehouse-solid.svg'
import UserService from 'services/UserService'

import { Layout, Menu, Tooltip, Button, Dropdown, Drawer, Descriptions, Typography, Input, Space, Form, message, notification } from 'antd'
import Icon, { MenuUnfoldOutlined, MenuFoldOutlined, TeamOutlined, LogoutOutlined, HomeOutlined, ArrowLeftOutlined, FileTextOutlined, BookOutlined, LikeOutlined, DashboardOutlined, UserOutlined, SettingOutlined, FieldTimeOutlined } from '@ant-design/icons'
const { Content, Header, Sider } = Layout;

import CamayaSunIcon from 'assets/camaya-sun-icon.svg'


function Collections(props) {

    const [collapsed, setCollapsed] = React.useState(false);
    const [collapsedWidth, setCollapsedWidth] = React.useState(240);
    const [myAccountDrawerVisible, setmyAccountDrawerVisible] = React.useState(false);  

    const [selectedMenu, setselectedMenu] = React.useState(null);

    const [changePasswordForm] = Form.useForm();

    const [changePasswordQuery, {isLoading: changePasswordQueryIsLoading, error: changePasswordQueryError}] = UserService.changePassword();

    React.useEffect( () => {

        // console.log(props.location.pathname)

        /**
         * Block page in production (Remove once live)
         */
        // if (process.env.APP_ENV == 'production') {
        //     props.history.push("/");
        // }

        switch (props.location.pathname) {

            case '/sales-portal-admin/sales-clients':
                setselectedMenu('/sales-admin-portal/sales-clients');
            
            case '/sales-portal-admin/reservation-documents':
                setselectedMenu('/sales-admin-portal/reservation-documents');

            default:
                setselectedMenu('/sales-admin-portal');
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
                theme="light"
                trigger={null}
                collapsible
                collapsed={collapsed}
                breakpoint="lg"
                collapsedWidth={collapsedWidth}
                onBreakpoint={broken => {
                    if (broken) {
                        setCollapsedWidth(0);
                        setCollapsed(true);
                    } else { setCollapsedWidth(280); }
                }}
                className="py-4"
                style={{borderRight: 'solid 1px rgba(0,0,0,0.05)'}}
            >
                <div style={{display: 'flex', justifyContent:'space-between', flexDirection:'column', height: '100%'}}>
                    <div>
                        <div className="logo mb-4" style={{textAlign: 'center'}}>
                            <Icon component={CamayaSunIcon} className="my-2" style={{color: 'goldenrod', fontSize: '4rem', display: 'block'}} />
                            Collections
                        </div> 
                        <Menu
                            mode="inline"
                            defaultSelectedKeys={['/']}
                            selectedKeys={[props.location.pathname]}
                        >
                            <Menu.Item key="/" icon={<ArrowLeftOutlined />}>
                                <NavLink to="/">Back to Main</NavLink>
                            </Menu.Item>
                            
                            <Menu.Item key="/collections" icon={<DashboardOutlined />}>
                                <NavLink to="/collections">Dashboard</NavLink>
                            </Menu.Item>

                            <Menu.Item key="/collections/transacted-accounts" icon={<FileTextOutlined />}>
                                <NavLink to="/collections/transacted-accounts">Transacted Accounts</NavLink>
                            </Menu.Item>


                            

                        </Menu>
                    </div>
                    <div style={{display:'flex', justifyContent:'center', alignItems:'center'}}>
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
                            <Button icon={<UserOutlined />}>{props.user.first_name}</Button>
                    </Dropdown>
                    </div>
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

export default connect(mapStateToProps)(withRouter(Collections))
