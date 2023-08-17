import React, { useContext, Suspense } from "react";
import DataContext from "components/Hotel/RoomReservationCalendar/DataContext"
// import {useLocation, useHistory} from "react-router-dom"
import { SelectableGroup } from 'react-selectable';
import Loading from 'common/Loading'

import { Row, Col, Select, message } from 'antd'

import { DataProvider } from 'components/Hotel/RoomReservationCalendar/DataContext';
const CalendarViewDatesList = React.lazy(() => import('components/Hotel/RoomReservationCalendar/CalendarViewDates'));

import RoomService from 'services/Hotel/Room'

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

function ItemList({
    items,
    roomReservationsListQuery,
    calendarViewDates,
    calendarDateRangeStart,
  }) {

  // Put, 
  const [updateRoomStatusQuery, {isLoading: updateRoomStatusQueryIsLoading}] = RoomService.updateRoomStatus();

  const [data, setData,
              //selectItem
        ] = useContext(DataContext);

  React.useEffect( () => {
    // setData(items);
    setData(items);
  },[]);

  const handleRoomStatusChange = ({room_id}, room_status) => {
    console.log(room_id, room_status);

    updateRoomStatusQuery({
        room_id: room_id,
        room_status: room_status,
    }, {
      onSuccess: (res) => {
        console.log(res);
        message.success('Update room status successful!');
      },
      onError: (e) => {
        console.log(e);
      }
    })

  }

  const CalendarViewDatesMemo = React.memo( (props) => <CalendarViewDatesList {...props} 
            items={calendarViewDates}
            calendarDateRangeStart={calendarDateRangeStart}
        />)
  
  return (
    <>
      {data && data.map((item, key) => {

        return (
          <React.Fragment key={`item-${key}`}>
            <Row key={key} gutter={[8,8]}>
                <Col xl={2} style={{color: item.enabled == 0 ? 'crimson' : 'inherit'}}><small><strong>{item.number}</strong> {item.property_code} {item.name}</small></Col>
                <Col xl={2}>
                    <span style={{ color: roomStatusColor[item.room_status], textTransform:'capitalize'}}>{item.room_status}</span>
                </Col>
                <Col xl={20} className="room-reservation-pane">
                      <DataProvider>
                        <Suspense fallback={<Loading/>}>
                          <CalendarViewDatesMemo
                            roomReservationsListQuery={roomReservationsListQuery}
                            item={item} locked={!item.enabled} /></Suspense>
                      </DataProvider>
                </Col>
            </Row>
          </React.Fragment>
        );
      })}
    </>
  );
}

const ItemListMemo = React.memo(ItemList);

export default ItemListMemo;