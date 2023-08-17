import React from 'react'
// import moment from 'moment-timezone'
// import Loading from 'common/Loading'
// import ReactExport from "react-export-excel";
// const ExcelFile = ReactExport.ExcelFile;
// const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
// const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;

import { Row, Col, Card, message, Select, Checkbox } from 'antd'

// import { PrinterOutlined } from '@ant-design/icons'

import RoomService from 'services/Hotel/Room'
// import HousekeepingService from 'services/Hotel/HousekeepingService'

const roomStatusColor = {
    clean: '#5b8c00',
    clean_inspected: '#7cb305',
    dirty: '#873800',
    dirty_inspected: '#873800',
    pickup: '#597ef7',
    sanitized: '#36cfc9',
    inspected: '#4096ff',
    'out-of-service': '#434343',
    'out-of-order': '#262626',
    'none':'gray'
}

export default function Page(props) {

    // States
    // const [searchString, setSearchString] = React.useState(null);
    const [roomStatusFilter, setRoomStatusFilter] = React.useState([]);

    // Get
    const roomListQuery = RoomService.list();
    
    // Post,put
    const [updateRoomStatusQuery, {isLoading: updateRoomStatusQueryIsLoading}] = RoomService.updateRoomStatus();

    // React.useEffect(()=> {
    //     // console.log(dashboardDataQuery.data);
    // },[]);

    // React.useEffect(() => {
    //     console.log(roomStatusFilter)
    // }, [roomStatusFilter])

    // const handleSearch = (search) => {
    //     setSearchString(search.toLowerCase());
    // }

    const handleRoomStatusChange = ({room_id}, room_status) => {
        // console.log(room_id, room_status);

        if (updateRoomStatusQueryIsLoading) return false;
    
        updateRoomStatusQuery({
            room_id: room_id,
            room_status: room_status,
        }, {
          onSuccess: (res) => {
            console.log(res);
            message.success('Update room status successful!');
            roomListQuery.refetch();
          },
          onError: (e) => {
            console.log(e);
          }
        })
    
      }

    return (
        <React.Fragment>

        <div className='mt-2'>
            <div>Vacant: <b>{roomListQuery.data ? roomListQuery.data.filter( i => i.fo_status == 'vacant' ).length : 0}</b></div>
            <div>Occupied: <b>{roomListQuery.data ? roomListQuery.data.filter( i => i.fo_status == 'occupied' ).length : 0}</b></div>
        </div>
        <div className='mt-4' style={{display: 'flex', flexWrap: 'wrap'}}>
            {/* <Select onChange={(e)=>setRoomStatusFilter(e)} style={{width: 500}} size="large" mode="multiple" placeholder="Status filter">
                {
                    _.uniq(_.map(roomListQuery.data, i => i.room_status)).sort((a, b) => a?.toLowerCase().localeCompare(b?.toLowerCase())).map( (item, key) => {
                        return (
                            <Select.Option key={key} value={item ? item : 'none'}>{item ? item : 'No-status'}</Select.Option>
                        )
                    })
                }
            </Select> */}
            {
                _.uniq(_.map(roomListQuery.data, i => i.room_status)).sort((a, b) => a?.toLowerCase().localeCompare(b?.toLowerCase())).map( (item, key) => {
                    return (
                        <Checkbox style={{fontSize: 24, border: 'solid 1px gainsboro', borderRadius: 7, padding: 8, display: 'flex', width: 'auto', alignContent: 'center'}} key={key} onChange={ e => e.target.checked ? setRoomStatusFilter([...roomStatusFilter, item ? item : 'none']) : setRoomStatusFilter([...roomStatusFilter.filter(i => i != (item ? item : 'none'))])}>{roomListQuery.data ? roomListQuery.data.filter( i => i.room_status == item ).length : 0} {item ? item.toUpperCase().replace("_", " ") : 'No Status'}</Checkbox>
                    )
                })
            }
        </div>
        <Row gutter={[12,12]} className="mt-4">
            {
                (roomListQuery.data) && roomListQuery.data
                .filter( i => roomStatusFilter.length ? roomStatusFilter.includes(i.room_status ? i.room_status : 'none') : true )
                .map( (item,key) => {
                    return (
                            <Col xl={4} key={key}>
                                <Card
                                    bordered={false}
                                    hoverable={true}
                                    className="card-shadow"
                                    title={<span style={{color:'white'}}>Room {item.number}<br/>{item.property_code} {item.name}</span>}
                                    // title={<div style={{color:'white'}}>Room {item.number} <span>{item.property_code} {item.name}</span></div>}
                                    style={{background:roomStatusColor[item.room_status || 'none'], color: 'white'}}>
                                            <div>
                                                <div style={{color:'white'}}>{item.fo_status}</div>
                                                <Select size="large" value={item.room_status} onChange={(e)=>handleRoomStatusChange(item, e)} style={{width: '100%'}} disabled={(item.can_edit ? false : true) || roomListQuery.isFetching}>
                                                    <Select.Option value="clean">Clean</Select.Option>
                                                    <Select.Option value="clean_inspected">Clean Inspected</Select.Option>
                                                    <Select.Option value="dirty">Dirty</Select.Option>
                                                    <Select.Option value="dirty_inspected">Dirty Inspected</Select.Option>
                                                    <Select.Option value="pickup">Pickup</Select.Option>
                                                    <Select.Option value="sanitized">Sanitized</Select.Option>
                                                    <Select.Option value="inspected">Inspected</Select.Option>
                                                    <Select.Option value="out-of-service">Out-of-service</Select.Option>
                                                    <Select.Option value="out-of-order">Out-of-order</Select.Option>
                                                </Select>
                                            </div>
                                </Card>   
                            </Col>
                    )
                }) 
            }
        </Row>
        </React.Fragment>
    )
}