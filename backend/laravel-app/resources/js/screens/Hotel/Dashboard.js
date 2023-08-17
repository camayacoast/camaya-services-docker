import React from 'react'
import HotelLayout from 'layouts/Hotel'

import { Typography } from 'antd'

import HotelOccupancyDashboard from 'components/Hotel/HotelOccupancyDashboard'


export default function Page(props) {

    return <HotelLayout {...props}>
        <div className="fadeIn">
            <Typography.Title level={2}>Hotel Dashboard</Typography.Title>

            <HotelOccupancyDashboard />
        </div>
    </HotelLayout>
}