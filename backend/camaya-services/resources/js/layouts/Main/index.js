import React from 'react'
import { NavLink, withRouter } from 'react-router-dom'
import {connect} from 'react-redux'
import * as action from 'store/actions'
import { can } from 'utils/casl/ability-context'

import { Layout, Menu, Tooltip, Button } from 'antd'
import Icon, { MenuUnfoldOutlined, MenuFoldOutlined, UserOutlined, LogoutOutlined, AppstoreOutlined, SafetyOutlined } from '@ant-design/icons'
const { Content, Header, Sider } = Layout;

import CamayaSunIcon from 'assets/camaya-sun-icon.svg'


function MainLayout(props) {

    const [collapsed, setCollapsed] = React.useState(true);
    const [collapsedWidth, setCollapsedWidth] = React.useState(80);

    React.useEffect( () => {
        
    }, []);

    // Handle sign out
    const handleSignOut = () => {
        
        props.dispatch(action.authLogout());

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
                        <Icon component={CamayaSunIcon} className="my-2" style={{fontSize: '2rem', display: 'block'}} />
                        Camaya Services
                    </div> 
                    <Menu
                        theme="dark"
                        mode="inline"
                        defaultSelectedKeys={['/']}
                        selectedKeys={[props.location.pathname]}
                    >
                        <Menu.Item key="/" icon={<AppstoreOutlined />}>
                            <NavLink to="/">Apps</NavLink>
                        </Menu.Item>
                        <Menu.Item key="/users" icon={<UserOutlined />}>
                            <NavLink to="/users">Users</NavLink>
                        </Menu.Item>
                        <Menu.Item key="/access" icon={<SafetyOutlined />}>
                            <NavLink to="/access">Access</NavLink>
                        </Menu.Item>
                    </Menu>
                    <Tooltip title="sign out" placement="right">
                        <Button shape="circle" type="primary" icon={<LogoutOutlined />} onClick={handleSignOut} />
                    </Tooltip>
                </div>
            </Sider>
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
                    <div className="p-5">{props.children}</div>
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

export default connect(mapStateToProps)(withRouter(MainLayout))
