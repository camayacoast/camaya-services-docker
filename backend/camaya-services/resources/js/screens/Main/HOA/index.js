import React from 'react'
import { NavLink, Link, Route, Switch } from 'react-router-dom'
import MainLayout from 'layouts/Main'

import HOAPayments from 'screens/Main/HOA/Payments'
import PageNotFound from 'common/PageNotFound'

import { Typography, Card, Row, Col, Menu } from 'antd'
import { MoneyCollectOutlined, HomeOutlined, UserOutlined } from '@ant-design/icons'

function Page(props) {
    
    return (
        <MainLayout {...props}>
            <div className="fadeIn">
                <Typography.Title level={2}>HOA</Typography.Title>

                <Row gutter={[32,0]}>
                    <Col xl={5} xs={24}>
                        {/* <Typography.Text type="secondary">Navigation</Typography.Text> */}
                        <Menu
                            className="mb-4"
                            style={{border: '0', background: 'none'}}
                            theme="light"
                            mode="vertical"
                            selectedKeys={[props.location.pathname]}
                        >
                            <Menu.Item className="rounded-12" key="/hoa" icon={<HomeOutlined />}>
                                <NavLink to="/hoa">Home</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hoa/users" icon={<UserOutlined />}>
                                <NavLink to="/hoa/users">Users</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hoa/payments" icon={<MoneyCollectOutlined />}>
                                <NavLink to="/hoa/payments">Payments</NavLink>
                            </Menu.Item>
                            
                        </Menu>
                    </Col>
                    <Col xl={19} xs={24}>
                        <Switch>
                            <Route path={`/hoa`} exact render={ () => <div className="fadeIn">HOME</div>}/>
                            <Route path={`/hoa/payments`} component={HOAPayments}/>
                            <Route render={ () =>  <PageNotFound /> } />
                            {/* <Redirect to="/hoa"/> */}
                        </Switch>
                    </Col>
                </Row>
            </div>
        </MainLayout>
    )
    
}

export default Page;