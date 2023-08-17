import React from 'react'
import { Route, NavLink, Switch } from 'react-router-dom'
import BookingLayout from 'layouts/Booking'
import PageNotFound from 'common/PageNotFound'

const ProductsComponent = React.lazy( () => import('components/Booking/Products'))
const PackagesComponent = React.lazy( () => import('components/Booking/Packages'))
const CustomersComponent = React.lazy( () => import('components/Booking/CustomerList'))
const PassesComponent = React.lazy( () => import('components/Booking/PassesList'))
const VouchersComponent = React.lazy( () => import('components/Booking/VouchersList'))
const LandAllocationComponent = React.lazy( () => import('components/Booking/LandAllocation'))
const DailyGuestPerDayComponent = React.lazy( () => import('components/Booking/DailyGuestPerDay'))

import { Typography, Row, Col, Menu } from 'antd'
import Icon, { HomeOutlined, CarOutlined, TeamOutlined, LoginOutlined, UserAddOutlined} from '@ant-design/icons'
import TicketIcon from 'assets/ticket-alt-solid.svg'
import BoxesSolid from 'assets/boxes-solid.svg'

// Inventory component

// <ul>
//     <li>Products</li>
//     <li>Packages</li>
//     <li>Table</li>
//     <li>Parking slot</li>
//     {/* <li>Locker</li> */}
    
//     <li>Tags</li>
// </ul>


export default function Page(props) {
    return (
        <BookingLayout {...props}>
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
                                <Menu.Item className="rounded-12" key="/booking/inventory" icon={<HomeOutlined />}>
                                    <NavLink to="/booking/inventory">Home</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/booking/inventory/products" icon={<Icon component={BoxesSolid} />}>
                                    <NavLink to="/booking/inventory/products">Products</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/booking/inventory/packages" icon={<Icon component={BoxesSolid} />}>
                                    <NavLink to="/booking/inventory/packages">Packages</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/booking/inventory/customers" icon={<TeamOutlined/>}>
                                    <NavLink to="/booking/inventory/customers">Customers</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/booking/inventory/passes" icon={<LoginOutlined/>}>
                                    <NavLink to="/booking/inventory/passes">Passes</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/booking/inventory/vouchers" icon={<Icon component={TicketIcon} />}>
                                    <NavLink to="/booking/inventory/vouchers">Vouchers</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/booking/inventory/land-allocation" icon={<CarOutlined/>}>
                                    <NavLink to="/booking/inventory/land-allocation">Land Allocation</NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/booking/inventory/daily-guest-per-day" icon={<UserAddOutlined/>}>
                                    <NavLink to="/booking/inventory/daily-guest-per-day">Daily Guest Limit</NavLink>
                                </Menu.Item>
                            </Menu>
                        </Col>
                        <Col xl={19} xs={24}>
                            <Switch>
                                <Route path={`/booking/inventory`} exact render={ () => <div className="fadeIn">HOME</div>}/>
                                <Route path={`/booking/inventory/products`} exact render={ () => <ProductsComponent/>}/>
                                <Route path={`/booking/inventory/packages`} exact render={ () => <PackagesComponent/>}/>
                                <Route path={`/booking/inventory/customers`} exact render={ () => <CustomersComponent/>}/>
                                <Route path={`/booking/inventory/passes`} exact render={ () => <PassesComponent/>}/>
                                <Route path={`/booking/inventory/vouchers`} exact render={ () => <VouchersComponent/>}/>
                                <Route path={`/booking/inventory/land-allocation`} exact render={ () => <LandAllocationComponent/>}/>
                                <Route path={`/booking/inventory/daily-guest-per-day`} exact render={ () => <DailyGuestPerDayComponent/>}/>
                                <Route render={ () =>  <PageNotFound /> } />
                                {/* <Redirect to="/hoa"/> */}
                            </Switch>
                        </Col>
                    </Row>
                </div>
        </BookingLayout>
    )
}