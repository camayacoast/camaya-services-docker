import React from 'react'

import BookingLayout from 'layouts/Booking'

const DashboardComponent = React.lazy( () => import('components/Booking/Dashboard'))

import PageNotFound from 'common/PageNotFound'



export default function Page(props) {
    return (
        <BookingLayout {...props}>
            <div className="fadeIn">
                    <DashboardComponent/>
            </div>
        </BookingLayout>
    )
}