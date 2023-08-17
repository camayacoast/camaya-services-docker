import React from 'react'
import PropertyService from 'services/Hotel/Property'
import RoomService from 'services/Hotel/RoomType'
import { queryCache } from 'react-query'

import { Typography, Select, Table, Space, Button, Row, Col, Modal, Form, Input, notification } from 'antd'
import { PlusOutlined } from '@ant-design/icons';

function Page(props) {

    const [selectedProperty, setselectedProperty] = React.useState(null);
    const propertyListQuery = PropertyService.list();
    const [changeStatus, {isLoading: changeRoomStatusIsLoading, error: changeRoomStatusError}] = RoomService.changeStatus();

    const columns = [
        {
            title: 'Room Type',
            render: (text, record) => (
                <>{record.name} ({record.code})</>
            )
        },
        {
            title: 'Description',
            dataIndex: 'description',
            key: 'description',
        },
        {
            title: 'Capacity',
            dataIndex: 'capacity',
            key: 'capacity',
        },
        {
            title: 'Max Capacity',
            dataIndex: 'max_capacity',
            key: 'max_capacity',
        },
        {
            title: 'Rack Rate',
            dataIndex: 'rack_rate',
            key: 'rack_rate',
        },
        {
            title: 'Enabled Rooms',
            render: (text, record) => (
                <>{record.enabled_rooms_count}</>
            )
        },
        {
            title: 'Status',
            render: (text, record) => (
                <Select defaultValue={record.status} onChange={(e) => handleChangeRoomStatus(record, e)}>
                    <Select.Option value="enabled"><span className="text-success">Enabled</span></Select.Option>
                    <Select.Option value="disabled"><span className="text-warning">Disabled</span></Select.Option>
                </Select>
            ),
        },
    ];

    const handleSelectProperty = (e) => {

        const property = _.find(propertyListQuery.data, { id: e });
        setselectedProperty(property);

    }

    const handleChangeRoomStatus = ({id}, status) => {
        changeStatus({ id, status }, {
            onSuccess: (res) => {
                console.log(res);

                queryCache.setQueryData(['properties', { id: res.data.property_id }], {rooms: res.data});

                setselectedProperty(prev => {

                    const roomtypes = prev.room_types;

                    roomtypes.forEach(function (room, index) {
                        if (room.id === id) {
                            roomtypes[index] = res.data;
                        }
                    });

                    return {...prev, room_types: [...roomtypes], updated: res.updated_at};
                });
            
                notification.success({
                    message: `Room Type ${parseInt(enabled) ? 'Enabled' : 'Disabled'}`,
                    description:
                        ``,
                });
            },
            onError: (e) => {
                console.log(e)
                message.warning(e.message);
            }
        });
    }

    return (
        <>
            <Typography.Title level={4}>Room Types</Typography.Title>

            
            <Row justify="space-between" className="my-4">
                <Col xl={4} xs={24}>
                    <Select style={{minWidth: 200}} placeholder="Select property" onChange={handleSelectProperty}>
                        { 
                            propertyListQuery.data && propertyListQuery.data.map( (item, key) => (
                                <Select.Option value={item.id} key={key}>{item.name}</Select.Option>
                            ))
                        }
                    </Select>
                </Col>
            </Row>

            <Table 
                // loading={propertyListQuery.status === 'loading'}
                columns={columns}
                // dataSource={tableFilters && tableFilters.status ? myBookingsFiltered : myBookingsQuery.data ? myBookingsQuery.data : []}
                dataSource={selectedProperty && selectedProperty.room_types}
                rowKey="id"
                rowClassName="table-row"
                size="small"
                // onChange={(pagination, filters, sorter) => handleTableChange(pagination, filters, sorter, 'all')}
            />
        </>
    )
}

export default Page;