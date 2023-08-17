import React, {Suspense} from 'react'
// import { NavLink, Switch, Route } from 'react-router-dom'
import HotelLayout from 'layouts/Hotel'
// import PageNotFound from 'common/PageNotFound'
// import Loading from 'common/Loading'
// import RoomReservationCalendarComponent from 'components/Hotel/RoomReservationCalendar'
import RoomReservationCalendarComponent2 from 'components/Hotel/RoomReservationCalendar2'

// const MyBookingsComponent = React.lazy(() => import('components/Booking/MyBookings'))

import { Typography } from 'antd'


export default function Page(props) {

    return <HotelLayout {...props}>
        <div className="fadeIn">
            <Typography.Title level={2}>Hotel Calendar Dashboard</Typography.Title>

            <RoomReservationCalendarComponent2/>
        </div>
    </HotelLayout>
}