import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import SalesPortalAdminLayout from 'layouts/SalesPortalAdmin'

import EditReservationComponent from 'components/SalesPortalAdmin/EditReservation'

import { Link } from 'react-router-dom'

import { Typography, Button } from 'antd'
import { ArrowLeftOutlined, FileTextOutlined } from '@ant-design/icons' 

export default function Page(props) {
    return (
        <SalesPortalAdminLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}><FileTextOutlined/> Edit Reservation</Typography.Title>
                    
                    <Link to="/sales-admin-portal/reservation-documents"><Button icon={<ArrowLeftOutlined />} type="link">Back to list</Button></Link>

                    <EditReservationComponent {...props}/>
            </div>
        </SalesPortalAdminLayout>
    )
}