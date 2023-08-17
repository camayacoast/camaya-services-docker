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
            title: 'Occupancy Rate %',
            dataIndex: 'name',
            key: 'name',  
            className: 'border-bottom',          
        },
        {
            title: 'Total Occupied Rooms',
            dataIndex: 'description',
            key: 'description1',
            className: 'border-bottom',
        },               
        {
            title: 'Number of Pax',
            dataIndex: 'description',
            key: 'description4',
            className: 'border-bottom',
        },
        {
            title: 'Arrivals',
            dataIndex: 'description',
            key: 'description2',
            className: 'border-bottom',
        },
        {
            title: 'In House',
            dataIndex: 'description',
            key: 'description3',
            className: 'border-bottom',
        }, 
        {
            title: 'Stay Overs',
            dataIndex: 'description',
            key: 'description5',
            className: 'border-bottom',
        },
        {
            title: 'HouseUse',
            dataIndex: 'description',
            key: 'description6',
            className: 'border-bottom',
        },
        {
            title: 'Room Inventory',
            dataIndex: 'description',
            key: 'description7',
            className: 'border-bottom',
        },
        {
            title: 'Actual Room Inventory',
            dataIndex: 'description',
            key: 'description8',
            className: 'border-bottom',
        },
        {
            title: 'Available Rooms',
            dataIndex: 'description',
            key: 'description8',
            className: 'border-bottom',
        },
        {
            title: 'OOO',
            dataIndex: 'description',
            key: 'description8',
            className: 'border-bottom',
        },
    ];
    

    return (
        <>
            <Typography.Title level={4}>Occupancy Forecast</Typography.Title> 

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