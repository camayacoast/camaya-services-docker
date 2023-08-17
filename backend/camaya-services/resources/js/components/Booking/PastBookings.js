import React from 'react'
import BookingService from 'services/Booking'
import moment from 'moment'
import ShipSolid from 'assets/ship-solid.svg'

import { Table, Space, Button, Typography, Row, Col, Tag } from 'antd'
import Icon, { UserOutlined } from '@ant-design/icons'


export default function Page(props) {

    const [tableFilters, setTableFilters] = React.useState();
    const [bookingsFiltered, setBookingsFiltered] = React.useState();
    const bookingsQuery = BookingService.list('past', props.isTripping || false);

    const tagColor = {
        draft: 'purple',
        pending: 'orange',
        confirmed: 'green',
        cancelled: 'red',
    };

    const columns = [
        {
          title: 'Reference #',
          dataIndex: 'reference_number',
          key: 'reference_number',
        },
        {
            title: '-',
            render: (text, record) => {
                return record.mode_of_transportation == 'camaya_transportation' ? <Icon component={ShipSolid}/> : <span/>
            }
        },
        {
            title: 'Status',
            dataIndex: 'status',
            filters: [
                // { text: 'draft', value: 'draft' },
                { text: 'pending', value: 'pending' },
                { text: 'confirmed', value: 'confirmed' },
                { text: 'cancelled', value: 'cancelled' },
            ],
            defaultFilteredValue: ['pending', 'confirmed'],
            onFilter: (value, record) => record.status.includes(value),
            render: (text, record) => (
                <Tag color={tagColor[text]}>{text}</Tag>
            ),
        },
        {
            title: 'Customer',
            dataIndex: 'customer',
            render: (text, record) => <>
                <UserOutlined/> <span style={{textTransform:'uppercase'}}>{record.customer.first_name} {record.customer.last_name}</span>
                { record.customer.user_type ? <Tag className="ml-2">{record.customer.user_type}</Tag> :  ''}
            </>
        },
        {
            title: 'Booked by',
            render: (text, record) => <>
                { record.booked_by ?
                    <>
                        <UserOutlined/> <span style={{textTransform:'uppercase'}}>{record.booked_by.first_name} {record.booked_by.last_name}</span>
                        { record.booked_by.user_type ? <Tag className="ml-2">{record.booked_by.user_type}</Tag> :  ''}
                    </> :
                    <>
                        <UserOutlined/> <span style={{textTransform:'uppercase'}}>{record.customer.first_name} {record.customer.last_name}</span>
                        { record.customer.user_type ? <Tag className="ml-2">{record.customer.user_type}</Tag> :  ''}
                    </>
                }
            </>
        },
        {
            title: 'Pax',
            dataIndex: 'pax',
            render: (text, record) => `adult: ${record.adult_pax} kid: ${record.kid_pax} infant: ${record.infant_pax}`,
        },
        {
            title: 'Date of visit',
            dataIndex: 'date_of_visit',
            render: (text, record) => `${moment(record.start_datetime).format('MMM D, YYYY')} ${moment(record.end_datetime).isAfter(moment(record.start_datetime)) ? " ~ "+moment(record.end_datetime).format('MMM D, YYYY') : ''}`,
            sorter: (a, b) => moment(a.start_datetime).unix() - moment(b.start_datetime).unix(),
        },
        {
            title: 'Booked at',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (text, record) => moment(record.created_at).fromNow(),
            sorter: (a, b) => moment(a.created_at).unix() - moment(b.created_at).unix(),
        },
        // {
        //     title: 'Action',
        //     key: 'action',
        //     render: (text, record) => (
        //       <Space size="middle">
        //         <Button type="link" onClick={() => props.bookingPaneEdit(record.reference_number, 'view', record.status)}>View</Button>
        //       </Space>
        //     ),
        // },
    ];

    const handleTableChange = (pagination, filters, sorter, table) => {

        setTableFilters(filters);

        // setBookingsFiltered(
        //     _.filter(bookingsQuery.data, item => _.includes(filters.status, item.status))
        // ); 
        
    };

    return (
        <>
            <Row gutter={[12, 12]}>
                <Col xl={24}>
                    <Typography.Title level={4} className="my-4">Past Bookings</Typography.Title>
                    <Table 
                        loading={bookingsQuery.status === 'loading'}
                        columns={columns}
                        // dataSource={tableFilters && tableFilters.status ? bookingsFiltered : bookingsQuery.data ? bookingsQuery.data : []}
                        dataSource={bookingsQuery.data ? bookingsQuery.data : []}
                        rowKey="reference_number"
                        rowClassName="table-row"
                        size="small"
                        onChange={(pagination, filters, sorter) => handleTableChange(pagination, filters, sorter, 'all')}
                        onRow={(record, rowIndex) => {
                            return {
                              onClick: event => props.bookingPaneEdit(record.reference_number, 'view', record.status), // click row
                            //   onDoubleClick: event => {}, // double click row
                            //   onContextMenu: event => {}, // right button click row
                            };
                        }}
                    />
                </Col>
            </Row>
        </>
        
    )
}