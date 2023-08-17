import React from 'react'

import BookingLayout from 'layouts/Booking'

const SettingsComponent = React.lazy( () => import('components/Booking/Settings'))

import PageNotFound from 'common/PageNotFound'



export default function Page(props) {
    return (
        <BookingLayout {...props}>
            <div className="fadeIn">
                    <SettingsComponent/>
            </div>
        </BookingLayout>
    )
}