import React from 'react'
import { Route, NavLink, Switch } from 'react-router-dom'
import TransportationLayout from 'layouts/Transportation'
import PageNotFound from 'common/PageNotFound'

const TransportationComponent = React.lazy( () => import('components/Transportation'))
const TransportationSeatComponent = React.lazy( () => import('components/Transportation/Seat'))
const TransportationLocationComponent = React.lazy( () => import('components/Transportation/Location'))
const TransportationRouteComponent = React.lazy( () => import('components/Transportation/Route'))

import ShipSolid from 'assets/ship-solid.svg'
import ChairSolid from 'assets/chair-solid.svg'
import RouteSolid from 'assets/route-solid.svg'
import MapMarkerAltSolid from 'assets/map-marker-alt-solid.svg'

import { Typography, Row, Col, Menu, Divider } from 'antd'
import Icon, { ScheduleOutlined } from '@ant-design/icons'


export default function Page(props) {
    return (
        <TransportationLayout {...props}>
            <div className="fadeIn">
                <Typography.Title level={2}>Inventory</Typography.Title>
                
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
                                <Menu.Item className="rounded-12" key="/transportation/inventory" icon={<Icon component={ShipSolid} />}>
                                    <NavLink to="/transportation/inventory">Transportation</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/transportation/inventory/seats" icon={<Icon component={ChairSolid} />}>
                                    <NavLink to="/transportation/inventory/seats">Seats</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/transportation/inventory/locations" icon={<Icon component={MapMarkerAltSolid} />}>
                                    <NavLink to="/transportation/inventory/locations">Locations</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/transportation/inventory/routes" icon={<Icon component={RouteSolid} />}>
                                    <NavLink to="/transportation/inventory/routes">Routes</NavLink>
                                </Menu.Item>
                            </Menu>
                        </Col>
                        <Col xl={19} xs={24}>
                            <Switch>
                                <Route path={`/transportation/inventory`} exact render={ () => <TransportationComponent/>}/>
                                <Route path={`/transportation/inventory/seats`} exact render={ () => <TransportationSeatComponent/>}/>
                                <Route path={`/transportation/inventory/locations`} exact render={ () => <TransportationLocationComponent/>}/>
                                <Route path={`/transportation/inventory/routes`} exact render={ () => <TransportationRouteComponent/>}/>
                                {/* <Route path={`/hotel/inventory/rooms`} exact render={ () => <HotelRoomComponent/>}/> */}
                                <Route render={ () =>  <PageNotFound /> } />
                                {/* <Redirect to="/hoa"/> */}
                            </Switch>
                        </Col>
                    </Row>
                </div>
        </TransportationLayout>
    )
}