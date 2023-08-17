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
            title: 'ETA',
            dataIndex: 'name',
            key: 'name',  
            className: 'border-bottom',          
        },
        {
            title: 'ETD',
            dataIndex: 'description',
            key: 'description1',
            className: 'border-bottom',
        },
        {
            title: 'Vessel',
            dataIndex: 'description',
            key: 'description2',
            className: 'border-bottom',
        },
        {
            title: 'Guest Name',
            dataIndex: 'description',
            key: 'description3',
            className: 'border-bottom',
        },        
        {
            title: 'Age',
            dataIndex: 'description',
            key: 'description4',
            className: 'border-bottom',
        },
        {
            title: 'Nationality',
            dataIndex: 'description',
            key: 'description5',
            className: 'border-bottom',
        },
        {
            title: 'Booking Type (Daytour / Overnight)',
            dataIndex: 'description',
            key: 'description6',
            className: 'border-bottom',
        },        
    ];
    

    return (
        <>
            <Typography.Title level={4}>Ferry Manifest</Typography.Title>                        

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