import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import PageLayout from 'layouts/Collections'

import ViewAccountComponent from 'components/Collections/ViewAccount'

import { Link } from 'react-router-dom'

import { Typography, Button } from 'antd'
import { ArrowLeftOutlined, FileTextOutlined } from '@ant-design/icons' 

export default function Page(props) {
    return (
        <PageLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}><FileTextOutlined/> View Account</Typography.Title>
                    
                    <Link to="/collections/transacted-accounts"><Button icon={<ArrowLeftOutlined />} type="link">Back to list</Button></Link>

                    <ViewAccountComponent {...props}/>
            </div>
        </PageLayout>
    )
}