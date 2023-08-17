import React from 'react'
import { NavLink } from 'react-router-dom'
import {connect} from 'react-redux'
import HotelSolid from 'assets/hotel-solid.svg'
import GolfBallSolid from 'assets/golf-ball-solid.svg'
import ShipSolid from 'assets/ship-solid.svg'
import UtensilSolid from 'assets/utensils-solid.svg'
import ConciergeBellSolid from 'assets/concierge-bell-solid.svg'

import { Card, Typography, Row, Col, Divider } from 'antd'
import Icon, { AppstoreOutlined, MinusOutlined, BookOutlined, HomeOutlined, TeamOutlined, MoneyCollectOutlined, FileTextOutlined } from '@ant-design/icons'


function Page(props) {

  return (
    <>
        <Typography.Title level={2} className="mb-4"><AppstoreOutlined/> Apps</Typography.Title>

        {/* AppListComponent */}
        <Row gutter={[32,32]}>

            <Col xl={4} xs={12}>
                <NavLink to="/booking">
                    <Card hoverable className="card-shadow" bordered={false}>
                        <div className="flex-center-all">
                            <BookOutlined style={{ fontSize: '2rem'}}/>
                            <Typography.Text type="secondary" className="mt-2">Booking</Typography.Text>
                        </div>
                    </Card>
                </NavLink>
                <Row gutter={[8,8]} className="mt-2">
                    <Col xl={24}>
                        <NavLink to="/concierge">
                            <Card hoverable className="card-shadow" bordered={false}>
                                <div>
                                    <Icon component={ConciergeBellSolid} style={{ fontSize: '1rem', marginRight: 8}}/>
                                    <Typography.Text type="secondary" className="mt-2"><small>Concierge</small></Typography.Text>
                                </div>
                            </Card>
                        </NavLink>
                    </Col>
                    <Col xl={24} xs={24}>
                        <NavLink to="/food-outlets">
                            <Card hoverable className="card-shadow" bordered={false}>
                                <div>
                                    <Icon component={UtensilSolid} style={{ fontSize: '1rem', marginRight: 8}}/>
                                    <Typography.Text type="secondary" className="mt-2"><small>Food Outlets</small></Typography.Text>
                                </div>
                            </Card>
                        </NavLink>
                    </Col>
                </Row>
            </Col>

            <Col xl={4} xs={12}>
                <NavLink to="/hotel/dashboard">
                    <Card hoverable className="card-shadow" bordered={false}>
                        <div className="flex-center-all">
                            <Icon component={HotelSolid} style={{ fontSize: '2rem'}}/>
                            <Typography.Text type="secondary" className="mt-2">Hotel</Typography.Text>
                        </div>
                    </Card>
                </NavLink>
                <Row gutter={[8,8]} className="mt-2">
                    <Col xl={24}>
                        <NavLink to="/housekeeping">
                            <Card hoverable className="card-shadow" bordered={false}>
                                <div>
                                    <Icon component={HotelSolid} style={{ fontSize: '1rem', marginRight: 8}}/>
                                    <Typography.Text type="secondary" className="mt-2"><small>Housekeeping</small></Typography.Text>
                                </div>
                            </Card>
                        </NavLink>
                    </Col>
                </Row>
                <Row gutter={[8,8]} className="mt-2">
                    <Col xl={24}>
                        <NavLink to="/hotel/reports">
                            <Card hoverable className="card-shadow" bordered={false}>
                                <div>
                                    <Icon component={FileTextOutlined} style={{ fontSize: '1rem', marginRight: 8}}/>
                                    <Typography.Text type="secondary" className="mt-2"><small>Reports</small></Typography.Text>
                                </div>
                            </Card>
                        </NavLink>
                    </Col>
                </Row>
            </Col>

            <Col xl={4} xs={12}>
                <NavLink to="/transportation">
                    <Card hoverable className="card-shadow" bordered={false}>
                        <div className="flex-center-all">
                            <Icon component={ShipSolid} style={{ fontSize: '2rem'}}/>
                            <Typography.Text type="secondary" className="mt-2">Transportation</Typography.Text>
                        </div>
                    </Card>
                </NavLink>
                {/* <Row gutter={[8,8]} className="mt-2">
                    <Col xl={24}>
                        <NavLink to="/af-parking-monitoring">
                            <Card hoverable className="card-shadow" bordered={false}>
                                <div>
                                    <Icon component={CarOutlined} style={{ fontSize: '1rem', marginRight: 8}}/>
                                    <Typography.Text type="secondary" className="mt-2"><small>AF Parking Monitoring</small></Typography.Text>
                                </div>
                            </Card>
                        </NavLink>
                    </Col>
                </Row> */}
            </Col>

            <Col xl={4} xs={12}>
                <NavLink to="/auto-gate">
                    <Card hoverable className="card-shadow" bordered={false}>
                        <div className="flex-center-all">
                            <Icon component={MinusOutlined} style={{ fontSize: '2rem'}}/>
                            <Typography.Text type="secondary" className="mt-2">Auto-Gate</Typography.Text>
                        </div>
                    </Card>
                </NavLink>
            </Col>
            <Col xl={4} xs={12}>
                <NavLink to="/golf">
                    <Card hoverable className="card-shadow" bordered={false}>
                        <div className="flex-center-all">
                            <Icon component={GolfBallSolid} style={{ fontSize: '2rem'}}/>
                            <Typography.Text type="secondary" className="mt-2">Golf</Typography.Text>
                        </div>
                    </Card>
                </NavLink>
            </Col>
        </Row>
        <Divider/>
        <Row gutter={[32,32]}>

            <Col xl={4} xs={12}>
                {/* <NavLink to={ process.env.APP_ENV == 'production' ? '/': '/sales-admin-portal' }> */}
                <NavLink to="/sales-admin-portal">
                    <Card hoverable className="card-shadow" bordered={false}>
                        <div className="flex-center-all">
                            <TeamOutlined style={{ fontSize: '2rem'}}/>
                            <Typography.Text type="secondary" className="mt-2">Sales Admin Portal
                                {/* {process.env.APP_ENV == 'production' ? <div style={{textAlign:'center'}}><small>Not yet available</small></div>: ''} */}
                            </Typography.Text>
                        </div>
                    </Card>
                </NavLink>
            </Col>

            <Col xl={4} xs={12}>
                {/* <NavLink to={ process.env.APP_ENV == 'local' ? '/': '/collections' }> */}
                <NavLink to="/collections">
                    <Card hoverable className="card-shadow" bordered={false}>
                        <div className="flex-center-all">
                            <MoneyCollectOutlined style={{ fontSize: '2rem'}}/>
                            <Typography.Text type="secondary" className="mt-2">Collections
                                {/* {process.env.APP_ENV == 'production' ? <div style={{textAlign:'center'}}><small>Under Maintenance</small></div>: ''} */}
                            </Typography.Text>
                        </div>
                    </Card>
                </NavLink>
            </Col>

            <Col xl={4} xs={12}>
                <NavLink to="/real-estate-payments">
                    <Card hoverable className="card-shadow" bordered={false}>
                        <div className="flex-center-all">
                            <HomeOutlined style={{ fontSize: '2rem'}}/>
                            <Typography.Text type="secondary" className="mt-2"><small>Real Estate Payments</small></Typography.Text>
                        </div>
                    </Card>
                </NavLink>
            </Col>

            <Col xl={4} xs={12}>
                <NavLink to="/golf-admin-portal">
                    <Card hoverable className="card-shadow" bordered={false}>
                        <div className="flex-center-all">
                            <Icon component={GolfBallSolid} style={{ fontSize: '2rem', color: '#004100'}}/>
                            <Typography.Text type="secondary" className="mt-2"><small>Golf Membership Admin</small></Typography.Text>
                        </div>
                    </Card>
                </NavLink>
            </Col>

            <Col xl={4} xs={12}>
                <NavLink to="/hoa" disabled>
                    <Card hoverable className="card-shadow" bordered={false} style={{opacity: 0.3}}>
                        <div className="flex-center-all">
                            <HomeOutlined style={{ fontSize: '2rem'}}/>
                            <Typography.Text type="secondary" className="mt-2">HOA Portal</Typography.Text>
                        </div>
                    </Card>
                </NavLink>
            </Col>
        </Row>

        {/* <h3>Todo:</h3>
        <ul>
            <li>Booking
                <ul>
                    <li>Manage Product Inventory</li>
                    <li>Manage Package/Promo Inventory</li>
                    <li>Manage Tags</li>
                    <li>Settings Page</li>
                </ul>
            </li>
            <li>Hotel
                <ul>
                    <li>Manage Property Inventory</li>
                    <li>Manage Room Inventory</li>
                    <li>Manage Room Type Inventory</li>
                </ul>
            </li>
            <li>Transportation
                <ul>
                    <li>Manage Vehicle Inventory</li>
                    <li>Manage Route Inventory</li>
                    <li>Manage Seat Inventory</li>
                    <li>Manage Schedules</li>
                    <li>Manage Trips</li>
                    <li>Manage Class Allocations</li>
                </ul>
            </li>
        </ul> */}
    </>
  )
}

const mapStateToProps = (state) => {
  return {
    user: state.Auth.user,
  }
}

export default connect(mapStateToProps)(Page);