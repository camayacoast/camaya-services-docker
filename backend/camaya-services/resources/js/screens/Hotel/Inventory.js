import React from 'react'
import { Route, NavLink, Switch } from 'react-router-dom'
import HotelLayout from 'layouts/Hotel'
import PageNotFound from 'common/PageNotFound'

const HotelPropertyComponent = React.lazy( () => import('components/Hotel/Properties'))
const HotelRoomComponent = React.lazy( () => import('components/Hotel/Rooms'))
const HotelRoomTypesComponent = React.lazy( () => import('components/Hotel/RoomTypes'))
const HotelRoomAllocationComponent =  React.lazy( () => import('components/Hotel/RoomAllocation'))
const HotelRoomRatesComponent = React.lazy( () => import('components/Hotel/RoomRates'))

import HotelSolid from 'assets/hotel-solid.svg'
import BedSolid from 'assets/bed-solid.svg'

import { Typography, Row, Col, Menu } from 'antd'
import Icon, { HomeOutlined, BookOutlined, MoneyCollectOutlined } from '@ant-design/icons'


export default function Page(props) {
    return (
        <HotelLayout {...props}>
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
                                <Menu.Item className="rounded-12" key="/hotel/inventory" icon={<Icon component={HotelSolid} />}>
                                    <NavLink to="/hotel/inventory">Properties</NavLink>
                                </Menu.Item>
                                {/* <Menu.Item className="rounded-12" key="/hotel/inventory/properties" icon={<Icon component={HotelSolid} />}>
                                    <NavLink to="/hotel/inventory/properties">Properties</NavLink>
                                </Menu.Item> */}
                                <Menu.Item className="rounded-12" key="/hotel/inventory/rooms" icon={<Icon component={BedSolid} />}>
                                    <NavLink to="/hotel/inventory/rooms">Rooms</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/hotel/inventory/room-types" icon={<Icon component={BedSolid} />}>
                                    <NavLink to="/hotel/inventory/room-types">Room Types</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/hotel/inventory/room-allocation" icon={<Icon component={BedSolid} />}>
                                    <NavLink to="/hotel/inventory/room-allocation">Room Allocation</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/hotel/inventory/room-rates" icon={<><Icon component={BedSolid} /><MoneyCollectOutlined/></>}>
                                    <NavLink to="/hotel/inventory/room-rates">Room Rates</NavLink>
                                </Menu.Item>
                            </Menu>
                        </Col>
                        <Col xl={19} xs={24}>
                            <Switch>
                                <Route path={`/hotel/inventory`} exact render={ () => <HotelPropertyComponent/>}/>
                                {/* <Route path={`/hotel/inventory/properties`} exact render={ () => <HotelPropertyComponent/>}/> */}
                                <Route path={`/hotel/inventory/rooms`} exact render={ () => <HotelRoomComponent/>}/>
                                <Route path={`/hotel/inventory/room-types`} exact render={ () => <HotelRoomTypesComponent />}/>
                                <Route path={`/hotel/inventory/room-allocation`} exact render={ () => <HotelRoomAllocationComponent/>}/>
                                <Route path={`/hotel/inventory/room-rates`} exact render={ () => <HotelRoomRatesComponent/>}/>
                                <Route render={ () =>  <PageNotFound /> } />
                                {/* <Redirect to="/hoa"/> */}
                            </Switch>
                        </Col>
                    </Row>
                </div>
        </HotelLayout>
    )
}