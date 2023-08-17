import React from 'react'
import { Typography, Table } from 'antd'

import HotelReportService from 'services/Hotel/Report'

function Page(props) {    
    const reportListQuery = HotelReportService.dttArrivalForecast();     

    const columns = [        
        {
            title: 'Date',
            dataIndex: 'number',
            key: 'number',
            className: 'border-bottom',
        },
        {
            title: 'Name of Event',
            dataIndex: 'name',
            key: 'name',   
            className: 'border-bottom',         
        },
        {
            title: 'Number of Pax',
            dataIndex: 'description',
            key: 'description1',
            className: 'border-bottom',
        },
        {
            title: 'Booking Type (Daytour/ Overnight)',
            dataIndex: 'description',
            key: 'description2',
            className: 'border-bottom',
        },
        {
            title: 'Billing Instruction',
            dataIndex: 'description',
            key: 'description3',
            className: 'border-bottom',
        },        
        {
            title: 'Preferences',
            dataIndex: 'description',
            key: 'description4',
            className: 'border-bottom',
        },
        {
            title: 'Remarks',
            dataIndex: 'description',
            key: 'description5',
            className: 'border-bottom',
        },        
    ];
    

    return (
        <>
            <Typography.Title level={4}>Event Calendar</Typography.Title>                        

            <Table 
                loading={reportListQuery.status === 'loading'}
                columns={columns}
                dataSource={[]}
                rowKey="id"                    
                scroll={{ x: 'max-content' }}  
                bordered                
            />   
        </>
    )
}

export default Page;