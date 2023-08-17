import React from 'react'

import BookingLayout from 'layouts/Booking'

const GuestListComponent = React.lazy( () => import('components/Booking/GuestList'))

import PageNotFound from 'common/PageNotFound'

import { Typography, Row, Col, Menu } from 'antd'
import { HomeOutlined, BookOutlined } from '@ant-design/icons'


export default function Page(props) {
    return (
        <BookingLayout {...props}>
            <div className="fadeIn">
                <Typography.Title level={2}>Guests</Typography.Title>
                    <GuestListComponent/>
                </div>
        </BookingLayout>
    )
}