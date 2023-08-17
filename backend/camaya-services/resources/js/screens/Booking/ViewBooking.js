import React from 'react'
import BookingLayout from 'layouts/Booking'
import ViewBooking from 'components/Booking/View'
import { useParams } from "react-router-dom";

export default (props) => {

    let { reference_number } = useParams();

    return (
        <BookingLayout {...props}>
            <div className="fadeIn">
                <ViewBooking referenceNumber={reference_number} />
            </div>
        </BookingLayout>
    )

}

