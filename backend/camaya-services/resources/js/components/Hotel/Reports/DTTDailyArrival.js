import React from 'react'
import { Typography, Table } from 'antd'

import HotelReportService from 'services/Hotel/Report'

function Page(props) {    
    const reportListQuery = HotelReportService.dttDailyArrival();    

    const columns = [        
        {
            title: 'Date',
            dataIndex: 'number',
            key: 'number',
            className: 'border-bottom',
        },             
        {
            title: 'Total Number of Pax (Ferry)',
            dataIndex: 'description',
            key: 'description4',
            className: 'border-bottom',
        },
        {
            title: 'Total Number of Pax (Land)',
            dataIndex: 'description',
            key: 'description5',
            className: 'border-bottom',
        },
        {
            title: 'Guest List',
            dataIndex: 'description',
            key: 'description6',
            className: 'border-bottom',
        },
        {
            title: 'Remarks',
            dataIndex: 'description',
            key: 'description6',
            className: 'border-bottom',
        },
    ];
    

    return (
        <>
            <Typography.Title level={4}>DTT Daily Arrival (By Land and Ferry)</Typography.Title>                        

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