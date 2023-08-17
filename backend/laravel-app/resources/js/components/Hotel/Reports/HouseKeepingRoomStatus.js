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
            title: 'Guest',
            dataIndex: 'name',
            key: 'name', 
            className: 'border-bottom',           
        },
        {
            title: 'Room Number',
            dataIndex: 'description',
            key: 'description1',
            className: 'border-bottom',
        },
        {
            title: 'Room Type',
            dataIndex: 'description',
            key: 'description2',
            className: 'border-bottom',
        },
        {
            title: 'Total Number of Pax',
            dataIndex: 'description',
            key: 'description3',
            className: 'border-bottom',
        },        
        {
            title: 'Arrival Date',
            dataIndex: 'description',
            key: 'description4',
            className: 'border-bottom',
        },
        {
            title: 'Departure Date',
            dataIndex: 'description',
            key: 'description5',
            className: 'border-bottom',
        },        
    ];
    

    return (
        <>
            <Typography.Title level={4}>Housekeeping Room Status Report</Typography.Title>                        

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