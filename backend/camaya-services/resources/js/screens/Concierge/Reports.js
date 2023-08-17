import React, {Suspense} from 'react'
import { NavLink, Switch, Route } from 'react-router-dom'
import ConciergeLayout from 'layouts/Concierge'
import PageNotFound from 'common/PageNotFound'
import Loading from 'common/Loading'

import { Typography, Row, Col, Menu, Divider, Card } from 'antd'
import { FileOutlined } from '@ant-design/icons'

import FerryManifestoComponent from 'components/Concierge/Reports/FerryPassengerManifest';

export default function Page(props) {

    return ( 
        <ConciergeLayout {...props}>
            <div className="fadeIn">
                <Typography.Title level={2}>Concierge Reports</Typography.Title>

                <Row gutter={[32,0]}>
                    {/* <Col xl={5} xs={24}>
                        <Card size="small">
                            <Menu
                                className="mb-4"
                                style={{border: '0', background: 'none'}}
                                theme="light"
                                mode="vertical"
                                selectedKeys={[props.location.pathname]}
                            >
                                <Menu.Item className="rounded-12" key="/concierge/reports/passenger-manifest-concierge-report">
                                    <NavLink to="/concierge/reports/passenger-manifest-concierge-report"><small>Ferry Passenger Manifest</small></NavLink>
                                </Menu.Item>

                            </Menu>
                        </Card>
                    </Col> */}
                    <Col xl={24} xs={24}>
                        <FerryManifestoComponent/>
                    </Col>
                </Row>
            </div>
        </ConciergeLayout>
    )
}