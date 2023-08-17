import React, {Suspense} from 'react'
import { NavLink, Switch, Route } from 'react-router-dom'
import TransportationLayout from 'layouts/Transportation'
import PageNotFound from 'common/PageNotFound'
import Loading from 'common/Loading'
import ScheduleComponent from 'components/Transportation/Schedule'

// const MyBookingsComponent = React.lazy(() => import('components/Booking/MyBookings'))

import { Typography, Tabs, Space, Button } from 'antd'


export default function Page(props) {

    return <TransportationLayout {...props}>
        <div className="fadeIn">
            <Typography.Title level={2}>Transportation</Typography.Title>

            Schedules, Seat Allocation Requests, Trips

            <ScheduleComponent/>
        </div>
    </TransportationLayout>
}