import React from 'react'
import SalesPortalAdminLayout from 'layouts/SalesPortalAdmin'

// import ReservationDocumentComponent from 'components/SalesPortalAdmin/ReservationDocument'
// Implement Pagination
import ReservationDocumentComponent from 'components/SalesPortalAdmin/ReservationDocumentList'

import { Typography } from 'antd'

export default function Page(props) {
    return (
        <SalesPortalAdminLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}>Reservation Document List</Typography.Title>
                    <ReservationDocumentComponent/>
            </div>
        </SalesPortalAdminLayout>
    )
}