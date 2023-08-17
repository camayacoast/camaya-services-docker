import React from 'react'
import { Typography, DatePicker, Button } from 'antd'

import BookingReportService from 'services/Booking/ReportService'
import moment from 'moment';
import { LoadingOutlined } from '@ant-design/icons';

export default function Page(props) {

    const [arrivalForecastPerSegment, setArrivalForecastPerSegment] = React.useState([]);

    const [startDate, setStartDate] = React.useState(moment());
    const [endDate, setEndDate] = React.useState(moment());

    const [getArrivalForecastPerSegmentQuery, { isLoading: getArrivalForecastPerSegmentQueryIsLoading, reset: getArrivalForecastPerSegmentQueryReset }] = BookingReportService.arrivalForecastPerSegment();
    const [downloadArrivalForecastPerSegmentQuery, { isLoading: downloadArrivalForecastPerSegmentQueryIsLoading, reset: downloadArrivalForecastPerSegmentQueryReset }] = BookingReportService.downloadArrivalForecastPerSegment();

    React.useEffect( () => {
        
    }, []);

    const handleDateRangeChange = (dates) => {
        setStartDate(null);
        setEndDate(null);

        if (dates && dates.length == 2) {
            setStartDate(dates[0]);
            setEndDate(dates[1]);
        }
    }

    const handleGo = () => {

        setArrivalForecastPerSegment([]);

        if (startDate && endDate) {
            getArrivalForecastPerSegmentQuery({
                start_date: moment(startDate).format('YYYY-MM-DD'),
                end_date: moment(endDate).format('YYYY-MM-DD'),
            }, {
                onSuccess: (res) => {
                    console.log(res);
                    setArrivalForecastPerSegment(res.data);
                }
            })
        }
    }

    const handleDownload = () => {

        if (startDate && endDate) {
            downloadArrivalForecastPerSegmentQuery({
                start_date: moment(startDate).format('YYYY-MM-DD'),
                end_date: moment(endDate).format('YYYY-MM-DD'),
            }, {
                onSuccess: (res) => {
                    console.log(res);
                    // setArrivalForecastPerSegment(res.data);
                }
            })
        }
    }

    return (
        <>
            <Typography.Title level={4}>Arrival Forecast Per Segment</Typography.Title>

            Jump to date: <DatePicker.RangePicker onChange={handleDateRangeChange} value={[startDate, endDate]} className="ml-2" style={{marginBottom: 12}} />
            <Button onClick={()=>handleGo()} className="ml-2" type="primary" loading={getArrivalForecastPerSegmentQueryIsLoading}>Go</Button>
            <Button onClick={()=>handleDownload()} className="ml-2" type="primary">Download</Button>
            {
                getArrivalForecastPerSegmentQueryIsLoading && <span className="ml-2">Loading report...</span>
            }

            <Typography.Paragraph>
                <strong>Arrival Forecast per Segment:</strong><br/>
                Both pending and confirmed bookings will reflect on this report.
            </Typography.Paragraph>

            <table style={{border: 'solid 1px black'}}>
                <tbody>
                {
                    arrivalForecastPerSegment &&

                    Object.entries(arrivalForecastPerSegment).map( (item, key) => {
                        return <tr key={key}>
                            <td>
                                <table border={1} style={{width: '500px'}}>
                                    <tbody>
                                    <tr>
                                        <td style={{backgroundColor: '#a9d08d'}} colSpan={5} align="center">{moment(item[0]).format('MMMM D - dddd')}</td>
                                    </tr>
                                    <tr>
                                        <td style={{backgroundColor: '#a9d08d'}} rowSpan={2} align="center">GUEST TYPE</td>
                                        <td style={{backgroundColor: '#a9d08d'}} colSpan={2} align="center">DTT</td>
                                        <td style={{backgroundColor: '#a9d08d'}} colSpan={2} align="center">OVERNIGHT</td>
                                    </tr>
                                    <tr>
                                        <td style={{backgroundColor: '#a9d08d'}} align="center">FERRY</td>
                                        <td style={{backgroundColor: '#a9d08d'}} align="center">BY LAND</td>
                                        <td style={{backgroundColor: '#a9d08d'}} align="center">FERRY</td>
                                        <td style={{backgroundColor: '#a9d08d'}} align="center">BY LAND</td>
                                    </tr>
                                    
                                    <tr>
                                        <td>REAL ESTATE</td>
                                        <td align="center">{item[1].DT?.ferry?.real_estate || 0}</td>
                                        <td align="center">{item[1].DT?.by_land?.real_estate || 0}</td>
                                        <td align="center">{item[1].ON?.ferry?.real_estate || 0}</td>
                                        <td align="center">{item[1].ON?.by_land?.real_estate || 0}</td>
                                    </tr>

                                    <tr>
                                        <td>HOMEOWNER</td>
                                        <td align="center">{item[1].DT?.ferry?.homeowner || 0}</td>
                                        <td align="center">{item[1].DT?.by_land?.homeowner || 0}</td>
                                        <td align="center">{item[1].ON?.ferry?.homeowner || 0}</td>
                                        <td align="center">{item[1].ON?.by_land?.homeowner || 0}</td>
                                    </tr>

                                    <tr>
                                        <td>COMMERCIAL</td>
                                        <td align="center">{item[1].DT?.ferry?.commercial || 0}</td>
                                        <td align="center">{item[1].DT?.by_land?.commercial || 0}</td>
                                        <td align="center">{item[1].ON?.ferry?.commercial || 0}</td>
                                        <td align="center">{item[1].ON?.by_land?.commercial || 0}</td>
                                    </tr>

                                    <tr>
                                        <td>EMPLOYEES</td>
                                        <td align="center">{item[1].DT?.ferry?.employees || 0}</td>
                                        <td align="center">{item[1].DT?.by_land?.employees || 0}</td>
                                        <td align="center">{item[1].ON?.ferry?.employees || 0}</td>
                                        <td align="center">{item[1].ON?.by_land?.employees || 0}</td>
                                    </tr>

                                    <tr>
                                        <td>OTHERS</td>
                                        <td align="center">{item[1].DT?.ferry?.others || 0}</td>
                                        <td align="center">{item[1].DT?.by_land?.others || 0}</td>
                                        <td align="center">{item[1].ON?.ferry?.others || 0}</td>
                                        <td align="center">{item[1].ON?.by_land?.others || 0}</td>
                                    </tr>

                                    <tr>
                                        <td>TOTAL</td>
                                        <td align="center">{item[1].DT?.ferry?.total || 0}</td>
                                        <td align="center">{item[1].DT?.by_land?.total || 0}</td>
                                        <td align="center">{item[1].ON?.ferry?.total || 0}</td>
                                        <td align="center">{item[1].ON?.by_land?.total || 0}</td>
                                    </tr>

                                    <tr>
                                        <td>TOTAL PAX</td>
                                        <td colSpan={5} align="center">
                                            {
                                                (item[1].DT?.ferry?.total || 0) +
                                                (item[1].DT?.by_land?.total || 0) +
                                                (item[1].ON?.ferry?.total || 0) +
                                                (item[1].ON?.by_land?.total || 0)
                                            } 
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>;
                                
                    })

                }
                </tbody>
            </table>
        </>
    )
}