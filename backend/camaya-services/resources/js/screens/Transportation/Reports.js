import React, {Suspense} from 'react'
import { NavLink, Switch, Route } from 'react-router-dom'
import TransportationLayout from 'layouts/Transportation'
import PageNotFound from 'common/PageNotFound'
import Loading from 'common/Loading'

import { Typography, Row, Col, Menu } from 'antd'
import { FileOutlined } from '@ant-design/icons'

import FerryPassengersManifestoComponent from 'components/Transportation/Reports/FerryPassengersManifesto';
import FerrySeatsPerSDComponent from 'components/Transportation/Reports/FerrySeatsPerSD';

export default function Page(props) {

    return ( 
        <TransportationLayout {...props}>
            <div className="fadeIn">
                <Typography.Title level={2}>Transportation Reports</Typography.Title>

                <Row gutter={[32,0]}>
                    <Col xl={5} xs={24}>
                        <Menu
                            className="mb-4"
                            style={{border: '0', background: 'none'}}
                            theme="light"
                            mode="vertical"
                            selectedKeys={[props.location.pathname]}
                        >
                            <Menu.Item className="rounded-12" key="/transportation/reports" icon={<FileOutlined />}>
                                <NavLink to="/transportation/reports">Ferry Passengers Manifesto</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/transportation/reports/ferry-seats-per-sd" icon={<FileOutlined />}>
                                <NavLink to="/transportation/reports/ferry-seats-per-sd">Ferry Seats Per Sales Director</NavLink>
                            </Menu.Item>                            
                        </Menu>
                    </Col>
                    <Col xl={19} xs={24}>
                        <Switch>
                            <Route path={`/transportation/reports`} exact render={ () => <FerryPassengersManifestoComponent/>}/>
                            <Route path={`/transportation/reports/ferry-seats-per-sd`} exact render={ () => <FerrySeatsPerSDComponent/>}/>                            

                            <Route render={ () =>  <PageNotFound /> } />
                            {/* <Redirect to="/hoa"/> */}
                        </Switch>
                    </Col>
                </Row>
            </div>
        </TransportationLayout>
    )
}