import React, {Suspense} from 'react'
import { NavLink, Switch, Route } from 'react-router-dom'
import HotelLayout from 'layouts/Hotel'
import PageNotFound from 'common/PageNotFound'
import Loading from 'common/Loading'

const HotelGuestListComponent = React.lazy( () => import('components/Hotel/GuestList'))

import { Typography, Tabs, Space, Button } from 'antd'


export default function Page(props) {

    return <HotelLayout {...props}>
        <div className="fadeIn">
            <Typography.Title level={2}>Hotel Guests</Typography.Title>

            <HotelGuestListComponent/>
        </div>
    </HotelLayout>
}