import React, {Suspense} from 'react'
import { NavLink, Switch, Route } from 'react-router-dom'
import HotelLayout from 'layouts/Hotel'
import PageNotFound from 'common/PageNotFound'
import Loading from 'common/Loading'

import { Typography, Row, Col, Menu } from 'antd'
import { FileOutlined } from '@ant-design/icons'

import DailyArrivalComponent from 'components/Hotel/Reports/DailyArrival'
import DailyDepartureComponent from 'components/Hotel/Reports/DailyDeparture'
import OccupancyForecastComponent from 'components/Hotel/Reports/OccupancyForecast'
import InHouseGuestListComponent from 'components/Hotel/Reports/InHouseGuestList'
import StayOverGuestListComponent from 'components/Hotel/Reports/StayOverGuestList'
import HouseKeepingRoomStatusComponent from 'components/Hotel/Reports/HouseKeepingRoomStatus'
import DTTArrivalForecastComponent from 'components/Hotel/Reports/DTTArrivalForecast'
import DTTDailyArrivalComponent from 'components/Hotel/Reports/DTTDailyArrival'
import GuestHistoryComponent from 'components/Hotel/Reports/GuestHistory'
import FerryManifestComponent from 'components/Hotel/Reports/FerryManifest'
import EventCalendarComponent from 'components/Hotel/Reports/EventCalendar'
import HotelOccupancyComponent from 'components/Hotel/Reports/HotelOccupancy'

export default function Page(props) {

    return ( 
        <HotelLayout {...props}>
            <div className="fadeIn">
                <Typography.Title level={2}>Hotel Reports</Typography.Title>

                <Row gutter={[32,0]}>
                    <Col xl={5} xs={24}>
                        <Menu
                            className="mb-4"
                            style={{border: '0', background: 'none'}}
                            theme="light"
                            mode="vertical"
                            selectedKeys={[props.location.pathname]}
                        >
                            <Menu.Item className="rounded-12" key="/hotel/reports/daily-arrival">
                                <NavLink to="/hotel/reports/daily-arrival">Daily Arrival</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hotel/reports/daily-departure">
                                <NavLink to="/hotel/reports/daily-departure">Daily Departure</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hotel/reports/in-house-guest-list">
                                <NavLink to="/hotel/reports/in-house-guest-list">In-House Guest List</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hotel/reports/stay-over-guest-list">
                                <NavLink to="/hotel/reports/stay-over-guest-list">Stay-Over Guest List</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hotel/reports/occupancy-forecast">
                                <NavLink to="/hotel/reports/occupancy-forecast">Occupancy Forecast</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hotel/reports/housekeeping-room-status">
                                <NavLink to="/hotel/reports/housekeeping-room-status">Housekeeping Room Status</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hotel/reports/dtt-arrival-forecast">
                                <NavLink to="/hotel/reports/dtt-arrival-forecast">DTT Arrival Forecast</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hotel/reports/dtt-daily-arrival">
                                <NavLink to="/hotel/reports/dtt-daily-arrival">DTT Daily Arrival (By Land &amp; Ferry)</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hotel/reports/guest-history">
                                <NavLink to="/hotel/reports/guest-history">Guest History</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hotel/reports/ferry-manifest">
                                <NavLink to="/hotel/reports/ferry-manifest">Ferry Manifest</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hotel/reports/event-calendar">
                                <NavLink to="/hotel/reports/event-calendar">Event Calendar</NavLink>
                            </Menu.Item>
                            <Menu.Item className="rounded-12" key="/hotel/reports/hotel-occupancy">
                                <NavLink to="/hotel/reports/hotel-occupancy">Hotel Occupancy</NavLink>
                            </Menu.Item>
                        </Menu>
                    </Col>
                    <Col xl={19} xs={24}>
                        <Switch>                            
                            <Route path={`/hotel/reports`} exact render={ () => <DailyArrivalComponent/>}/>
                            <Route path={`/hotel/reports/daily-arrival`} exact render={ () => <DailyArrivalComponent/>}/>
                            <Route path={`/hotel/reports/daily-departure`} exact render={ () => <DailyDepartureComponent/>}/>
                            <Route path={`/hotel/reports/occupancy-forecast`} exact render={ () => <OccupancyForecastComponent/>}/>
                            <Route path={`/hotel/reports/in-house-guest-list`} exact render={ () => <InHouseGuestListComponent/>}/>
                            <Route path={`/hotel/reports/stay-over-guest-list`} exact render={ () => <StayOverGuestListComponent/>}/>
                            <Route path={`/hotel/reports/housekeeping-room-status`} exact render={ () => <HouseKeepingRoomStatusComponent/>}/>
                            <Route path={`/hotel/reports/dtt-arrival-forecast`} exact render={ () => <DTTArrivalForecastComponent/>}/>
                            <Route path={`/hotel/reports/dtt-daily-arrival`} exact render={ () => <DTTDailyArrivalComponent/>}/>
                            <Route path={`/hotel/reports/guest-history`} exact render={ () => <GuestHistoryComponent/>}/>
                            <Route path={`/hotel/reports/ferry-manifest`} exact render={ () => <FerryManifestComponent/>}/>
                            <Route path={`/hotel/reports/event-calendar`} exact render={ () => <EventCalendarComponent/>}/>
                            <Route path={`/hotel/reports/hotel-occupancy`} exact render={ () => <HotelOccupancyComponent/>}/>
                            <Route render={ () =>  <PageNotFound /> } />
                            {/* <Redirect to="/hoa"/> */}
                        </Switch>
                    </Col>
                </Row>
            </div>
        </HotelLayout>
    )
}