import React from 'react'
import { NavLink, withRouter } from 'react-router-dom'
import {connect} from 'react-redux'
import * as action from 'store/actions'
import { can } from 'utils/casl/ability-context'
import Warehouse from 'assets/warehouse-solid.svg'
import ShipSolid from 'assets/ship-solid.svg'

import { Layout, Menu, Tooltip, Button } from 'antd'
import Icon, { MenuUnfoldOutlined, MenuFoldOutlined, TeamOutlined, LogoutOutlined, HomeOutlined, ArrowLeftOutlined, FileTextOutlined } from '@ant-design/icons'
const { Content, Header, Sider } = Layout;


function TransportaionLayout(props) {

    const [collapsed, setCollapsed] = React.useState(true);
    const [collapsedWidth, setCollapsedWidth] = React.useState(80);

    React.useEffect( () => {
    }, []);

    // Handle sign out
    const handleSignOut = () => {
        
        props.dispatch(action.authLogout());

    }
        
    return (
        <Layout style={{height: '100vh'}} hasSider>
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
                style={{
                    overflow: 'auto',
                    height: '100vh',
                    position: collapsedWidth == 0 ? 'inherit' :'fixed',
                    left: 0,
                    top: 0,
                    bottom: 0,
                  }}
                className="py-5"
            >
                <div style={{display: 'flex', justifyContent:'space-between', alignItems:'center', flexDirection:'column', height: '100%'}}>
                    <div className="logo" style={{textAlign: 'center', color: 'white'}}>
                        <Icon component={ShipSolid} />
                        <div>Transpo</div>
                    </div>
                    <Menu
                        theme="dark"
                        mode="inline"
                        defaultSelectedKeys={['/transportation']}
                        selectedKeys={[props.location.pathname]}
                    >
                        <Menu.Item key="/" icon={<ArrowLeftOutlined />}>
                            <NavLink to="/">Back to Main</NavLink>
                        </Menu.Item>
                        <Menu.Item key="/transportation" icon={<HomeOutlined />}>
                            <NavLink to="/transportation">Home</NavLink>
                        </Menu.Item>
                        <Menu.Item key="/transportation/passengers" icon={<TeamOutlined />}>
                            <NavLink to="/transportation/passengers">Passengers</NavLink>
                        </Menu.Item>
                        <Menu.Item key="/transportation/inventory" icon={<Icon component={Warehouse} />}>
                            <NavLink to="/transportation/inventory">Inventory</NavLink>
                        </Menu.Item>
                        <Menu.Item key="/transportation/reports" icon={<FileTextOutlined />}>
                            <NavLink to="/transportation/reports">Reports</NavLink>
                        </Menu.Item>
                    </Menu>
                    <Tooltip title="sign out" placement="right">
                        <Button shape="circle" type="primary" icon={<LogoutOutlined />} onClick={handleSignOut} />
                    </Tooltip>
                </div>
            </Sider>
            <Layout style={{marginLeft: collapsedWidth == 0 ? 0 : 80}}>
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

export default connect(mapStateToProps)(withRouter(TransportaionLayout))
