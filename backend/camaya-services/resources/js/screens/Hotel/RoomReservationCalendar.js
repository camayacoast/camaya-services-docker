import React from 'react'

import HotelLayout from 'layouts/Hotel'

import RoomReservationCalendarComponent from 'components/Hotel/RoomReservationCalendar3'

// const MyBookingsComponent = React.lazy(() => import('components/Booking/MyBookings'))

import { Typography } from 'antd'


export default function Page(props) {

    return <HotelLayout {...props}>
        <div className="fadeIn">
            <Typography.Title level={2}>Room Reservation Calendar (UNDER DEVELOPMENT)</Typography.Title>

            <RoomReservationCalendarComponent/>
        </div>
    </HotelLayout>
}