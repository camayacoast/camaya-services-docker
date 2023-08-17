import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import GolfLayout from 'layouts/Golf'

const GolfTeeTimeComponent = React.lazy( () => import('components/Golf/TeeTime'))

import { Typography } from 'antd'
import { FieldTimeOutlined } from '@ant-design/icons' 

export default function Page(props) {
    return (
        <GolfLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}><FieldTimeOutlined className="mr-2"/>Golf Tee Time</Typography.Title>
                    <GolfTeeTimeComponent/>
            </div>
        </GolfLayout>
    )
}