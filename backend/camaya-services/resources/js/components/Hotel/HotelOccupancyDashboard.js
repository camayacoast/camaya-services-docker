import React, { useState, useEffect } from 'react'
import HotelService from 'services/Hotel/Report'
import HotelRoomTypeService from 'services/Hotel/RoomType'

import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');

import { Typography, Select, Checkbox } from 'antd'
import { LoadingOutlined } from '@ant-design/icons';

const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
const years = [moment().add(-1,'year').year(), moment().year(), moment().add(1,'year').year()];

const enumerateDaysBetweenDates = function(startDate, endDate) {
    var dates = [];

    var currDate = moment(startDate).startOf('day');
    var lastDate = moment(endDate).startOf('day');

    while (currDate.add(1, 'days').diff(lastDate) < 0) {
        // console.log(currDate.toDate());
        dates.push(currDate.clone().format('YYYY-MM-DD'));
    }

    return [moment(startDate).format('YYYY-MM-DD'), ...dates, moment(endDate).format('YYYY-MM-DD')];
};

export default () => {

    const [selectedMonth, setSelectedMonth] = useState(moment().format('MMMM'));
    const [selectedYear, setSelectedYear] = useState(moment().format('YYYY'));
    const [period, setPeriod] = useState([]);
    const [propertyFilter, setPropertyFilter] = useState(['AF']);

    const occupancyDashboard = HotelService.occupancyDashboard(selectedMonth, selectedYear);
    const roomTypes = HotelRoomTypeService.typeOnly();

    // console.log(roomTypes.data);

    useEffect(() => {
        
            setPeriod(enumerateDaysBetweenDates(moment(selectedMonth+" "+selectedYear).startOf('month').format('YYYY-MM-DD'), moment(selectedMonth+" "+selectedYear).endOf('month').format('YYYY-MM-DD' )))
    
            /**
             * On change of month and year,
             * Update calendar view of Hotel Occupancy Dashboard
             */
            // Do it here
            occupancyDashboard.refetch();

    },[selectedMonth, selectedYear])

    // useEffect(() => {
    //     console.log(occupancyDashboard.data);
    // },[occupancyDashboard.data])

    // useEffect(() => {
    //     console.log(propertyFilter);
    // },[propertyFilter])

    const handlePropertyFilterChange = (checked, property) => {
        setPropertyFilter( prev => {
            return checked ? [...prev, property] : [...prev].filter( (i) => i !== property);
        })
    }

    return <>
        <Typography.Title level={4}>Hotel Occupancy</Typography.Title>

        <Select style={{width:300}} value={selectedMonth} onChange={m => setSelectedMonth(m)}>
            {
                months.map( month => <Select.Option key={month} value={month}>{month}</Select.Option>)
            }
        </Select>

        <Select className="ml-2" style={{width:100}} value={selectedYear} onChange={y => setSelectedYear(y)}>
            {
                years.map( year => <Select.Option key={year} value={year}>{year}</Select.Option>)
            }
        </Select>

        <div className='my-2'>
            Property filter: {
                roomTypes.data && _.uniq(roomTypes.data.map( (i) => i.property_code)).map( (i, key) => {
                    return <Checkbox checked={propertyFilter.includes(i)} key={key} onChange={(e) => handlePropertyFilterChange(e.target.checked, i)}>{i}</Checkbox>
                })
            }
        </div>
    
        <div style={{marginTop:36, borderRadius: 8, border:'solid 1px gainsboro', overflow:'hidden'}}>
            <table style={{width: '100%'}}>
                <thead>
                    <tr>
                        <th style={{borderRight:'solid 1px gainsboro', borderBottom:'solid 1px gainsboro'}}>Date</th>
                        {
                            roomTypes.data && roomTypes.data
                                .filter( item => propertyFilter.length ? propertyFilter.includes(item.property_code) : true)
                                .map( (item) => {
                                    return <th key={item.code} style={{borderRight:'solid 1px gainsboro', borderBottom:'solid 1px gainsboro'}}>{item.code}</th>
                                })
                        }
                    </tr>
                </thead>
                <tbody>
                {
                    period.map( (day, key) => {

                        return <tr key={key}>
                                <td valign="top" className="px-3 py-2" style={{borderRight:'solid 1px gainsboro', borderBottom:'solid 1px gainsboro', width: 110}}>
                                    <div style={{position: 'relative'}}>
                                        { moment().format('D MMM ddd') == moment(day).format('D MMM ddd') ? <div style={{position: 'absolute', top: -16, left: 0, color: 'green'}}><small>today</small></div> : <></>}
                                        <small><b style={{ color: moment().format('D MMM ddd') == moment(day).format('D MMM ddd') ? 'green' : moment(day).format('MMMM') != selectedMonth ? 'gainsboro' : '' }}>{moment(day).format('D MMM ddd')}</b></small>
                                    </div>
                                </td>
                                {
                                    roomTypes.data && roomTypes.data
                                        .filter( item => propertyFilter.length ? propertyFilter.includes(item.property_code) : true)
                                        .map( (item) => {

                                        const occupancy_count = occupancyDashboard.data && occupancyDashboard.data.hasOwnProperty(item.code) && occupancyDashboard.data[item.code].hasOwnProperty(moment(day).format('YYYY-MM-DD')) ? occupancyDashboard.data[item.code][moment(day).format('YYYY-MM-DD')] : 0;

                                        return <td key={item.code} style={{borderRight:'solid 1px gainsboro', borderBottom:'solid 1px gainsboro', padding: 4}}>
                                            {(occupancyDashboard.isFetching) ? <LoadingOutlined spin size="small" /> :
                                                <>
                                                    <div style={{border: 'solid 1px gainsboro', padding:2, height:10, width:'100%', position: 'relative', borderRadius: 8, overflow: 'hidden'}}>
                                                        <div style={{background: 'limegreen', position: 'absolute', top: 0, left:0, height:'100%', width: (occupancy_count / item.enabled_rooms_count * 100).toFixed(2)+'%', borderRadius: 5}}></div>
                                                    </div>
                                                    <div style={{display: 'flex', justifyContent: 'space-between', fontSize: '0.55rem'}}>
                                                        <div>{occupancy_count} / {item.enabled_rooms_count}</div>
                                                        <div>{(occupancy_count / item.enabled_rooms_count * 100).toFixed(0)}%</div>
                                                    </div>
                                                </>
                                            }
                                            </td>
                                    })
                                }
                            </tr>

                    })
                }
                </tbody>
            </table>
        </div>
    </>

}