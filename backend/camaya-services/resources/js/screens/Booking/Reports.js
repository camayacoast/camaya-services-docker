import React, {Suspense} from 'react'
import { NavLink, Switch, Route } from 'react-router-dom'
import BookingLayout from 'layouts/Booking'
import PageNotFound from 'common/PageNotFound'
import Loading from 'common/Loading'

import { Typography, Row, Col, Menu, Divider, Card } from 'antd'
import { FileOutlined } from '@ant-design/icons'

import ReportHomeComponent from 'components/Booking/Reports';
import BookingsWithInclusionsComponent from 'components/Booking/Reports/BookingsWithInclusions';
import ArrivalForecastComponent from 'components/Booking/Reports/ArrivalForecast';
import GuestArrivalStatusComponent from 'components/Booking/Reports/GuestArrivalStatus';
import DTTComponent from 'components/Booking/Reports/DTT';
import DTTRevenueComponent from 'components/Booking/Reports/DTTRevenue';
import CommercialSalesComponent from 'components/Booking/Reports/CommercialSales';

import FerryManifestoComponent from 'components/Booking/Reports/FerryPassengerManifesto';
import ArrivalForecastPerSegmentComponent from 'components/Booking/Reports/ArrivalForecastPerSegment'

import RevenueReportComponent from 'components/Booking/Reports/RevenueReport';
import RevenuePerMOPComponent from 'components/Booking/Reports/RevenuePerMOP';

import SDMBGolfCartComponent from 'components/Booking/Reports/SDMBGolfCart'
import SDMBGolfPlayComponent from 'components/Booking/Reports/SDMBGolfPlay'

import DailyBookingsPerSDComponent from 'components/Booking/Reports/DailyBookingsPerSD';
import SDMBBookingConsumptionComponent from 'components/Booking/Reports/SDMBBookingConsumption';
import SDMBSalesRoomComponent from 'components/Booking/Reports/SDMBSalesRoom';

export default function Page(props) {

    return ( 
        <BookingLayout {...props}>
            <div className="fadeIn">
                <Typography.Title level={2}>Booking Reports</Typography.Title>

                <Row gutter={[32,0]}>
                    <Col xl={5} xs={24}>
                        <Card size="small">
                        <Typography.Title level={5}>General Reports</Typography.Title>
                            <Menu
                                className="mb-4"
                                style={{border: '0', background: 'none'}}
                                theme="light"
                                mode="vertical"
                                selectedKeys={[props.location.pathname]}
                            >
                                <Menu.Item className="rounded-12" key="/booking/reports/bookings-with-inclusions">
                                    <NavLink to="/booking/reports/bookings-with-inclusions"><small>Bookings with Inclusions Report</small></NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/booking/reports/arrival-forecast">
                                    <NavLink to="/booking/reports/arrival-forecast"><small>Arrival Forecast Report</small></NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/booking/reports/guest-arrival-status">
                                    <NavLink to="/booking/reports/guest-arrival-status"><small>Guest Arrival Status Report</small></NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/booking/reports/dtt">
                                    <NavLink to="/booking/reports/dtt"><small>DTT Report</small></NavLink>
                                </Menu.Item>
                                {/* <Menu.Item className="rounded-12" key="/booking/reports/dtt-revenue">
                                    <NavLink to="/booking/reports/dtt-revenue">DTT Revenue Report</NavLink>
                                </Menu.Item> */}
                                <Menu.Item className="rounded-12" key="/booking/reports/commercial-sales">
                                    <NavLink to="/booking/reports/commercial-sales"><small>Commercial Sales Report</small></NavLink>
                                </Menu.Item>

                                <Menu.Item className="rounded-12" key="/booking/reports/passenger-manifest-bpo-report">
                                    <NavLink to="/booking/reports/passenger-manifest-bpo-report"><small>Passenger Manifest BPO Report</small></NavLink>
                                </Menu.Item>

                                <Menu.Item className="rounded-12" key="/booking/reports/arrival-forecast-per-segment">
                                    <NavLink to="/booking/reports/arrival-forecast-per-segment"><small>Arrival Forecast Per Segment</small></NavLink>
                                </Menu.Item>

                                <Menu.Item className="rounded-12" key="/booking/reports/revenue-report">
                                    <NavLink to="/booking/reports/revenue-report"><small><sup style={{color: 'tomato'}}>NEW</sup> Revenue Report</small></NavLink>
                                </Menu.Item>

                                <Menu.Item className="rounded-12" key="/booking/reports/revenue-per-mop">
                                    {/* <NavLink to="/booking/reports/revenue-per-mop"><small><sup style={{color: 'tomato'}}>NEW</sup> Revenue Report per MOP Summary</small></NavLink> */}
                                    <NavLink to="/booking/reports/"><small><sup style={{color: 'purple'}}>DEV</sup> Revenue Report per MOP Summary</small></NavLink>
                                </Menu.Item>

                                
                            </Menu>
                            <Divider/>
                            <Typography.Title level={5}>SDMB Consumption Reports</Typography.Title>
                            <Menu
                                className="mb-4"
                                style={{border: '0', background: 'none'}}
                                theme="light"
                                mode="vertical"
                                selectedKeys={[props.location.pathname]}
                            >
                                <Menu.Item className="rounded-12" key="/booking/reports/sdmb/golf-cart">
                                    <NavLink to="/booking/reports/sdmb/golf-cart"><small>Golf Cart Consumption Report</small></NavLink>
                                </Menu.Item>
                                <Menu.Item className="rounded-12" key="/booking/reports/sdmb/golf-play">
                                    <NavLink to="/booking/reports/sdmb/golf-play"><small>Golf Play Consumption Report</small></NavLink>
                                </Menu.Item>

                                <Menu.Item className="rounded-12" key="/booking/reports/daily-bookings-per-sd">
                                    <NavLink to="/booking/reports/daily-bookings-per-sd"><small>Daily Bookings Per Sales Director Report</small></NavLink>
                                </Menu.Item>
                                
                                <Menu.Item className="rounded-12" key="/booking/reports/sdmb-booking-consumption">
                                    <NavLink to="/booking/reports/sdmb-booking-consumption"><small>Booking Consumption Report</small></NavLink>
                                </Menu.Item>

                                <Menu.Item className="rounded-12" key="/booking/reports/sdmb-sales-room">
                                    <NavLink to="/booking/reports/sdmb-sales-room"><small>Sales Room Accommodation Report</small></NavLink>
                                </Menu.Item>


                            </Menu>
                        </Card>
                    </Col>
                    <Col xl={19} xs={24}>
                        <Switch>
                            <Route path={`/booking/reports`} exact render={ () => <><ReportHomeComponent/></>}/>
                            
                            <Route path={`/booking/reports/bookings-with-inclusions`} exact render={ () => <BookingsWithInclusionsComponent/>}/>
                            <Route path={`/booking/reports/commercial-sales`} exact render={ () => <CommercialSalesComponent/>}/>

                            <Route path={`/booking/reports/arrival-forecast`} exact render={ () => <ArrivalForecastComponent/>}/>
                            <Route path={`/booking/reports/guest-arrival-status`} exact render={ () => <GuestArrivalStatusComponent/>}/>
                            <Route path={`/booking/reports/dtt`} exact render={ () => <DTTComponent/>}/>
                            <Route path={`/booking/reports/dtt-revenue`} exact render={ () => <DTTRevenueComponent/>}/>

                            <Route path={`/booking/reports/packages`} exact render={ () => <PackagesComponent/>}/>
                            <Route path={`/booking/reports/customers`} exact render={ () => <CustomersComponent/>}/>
                            <Route path={`/booking/reports/passes`} exact render={ () => <PassesComponent/>}/>

                            <Route path={`/booking/reports/passenger-manifest-bpo-report`} exact render={ () => <FerryManifestoComponent/>}/>
                            <Route path={`/booking/reports/arrival-forecast-per-segment`} exact render={ () => <ArrivalForecastPerSegmentComponent/>}/>

                            <Route path={`/booking/reports/revenue-report`} exact render={ () => <RevenueReportComponent/>}/>
                            <Route path={`/booking/reports/revenue-per-mop`} exact render={ () => <RevenuePerMOPComponent/>}/>

                            <Route path={`/booking/reports/sdmb/golf-cart`} exact render={ () => <SDMBGolfCartComponent/>}/>
                            <Route path={`/booking/reports/sdmb/golf-play`} exact render={ () => <SDMBGolfPlayComponent/>}/>

                            <Route path={`/booking/reports/daily-bookings-per-sd`} exact render={ () => <DailyBookingsPerSDComponent/>}/>         
                            <Route path={`/booking/reports/sdmb-booking-consumption`} exact render={ () => <SDMBBookingConsumptionComponent/>}/>
                            <Route path={`/booking/reports/sdmb-sales-room`} exact render={ () => <SDMBSalesRoomComponent/>}/>                            

                            <Route render={ () =>  <PageNotFound /> } />
                            {/* <Redirect to="/hoa"/> */}
                        </Switch>
                    </Col>
                </Row>
            </div>
        </BookingLayout>
    )
}