import React from 'react'
import { Typography, DatePicker, Button } from 'antd'
import moment from 'moment'
moment.tz.setDefault('Asia/Manila');
import { enumerateDaysBetweenDates, numberWithCommas } from 'common/utils'

import BookingReportService from 'services/Booking/ReportService'
import { LoadingOutlined } from '@ant-design/icons';
import { saveAs } from 'file-saver';
import XLSX from 'xlsx-with-styles';

export default function Page(props) {


    const [data, setData] = React.useState([]);

    const [startDate, setStartDate] = React.useState(moment());
    const [endDate, setEndDate] = React.useState(moment().clone().add(14,'d'));

    const [dateRange, setDateRange] = React.useState([]);

    const [getSDMBGolfCartConsumptionQuery, { isLoading: getSDMBGolfCartConsumptionQueryIsLoading, reset: getSDMBGolfCartConsumptionQueryReset }] = BookingReportService.getSDMBGolfCartConsumption();

    const handleDateRangeChange = (dates) => {
        setStartDate(null);
        setEndDate(null);

        if (dates && dates.length == 2) {
            setStartDate(dates[0]);
            setEndDate(dates[1]);

            // setDateRange([dates[0].format('YYYY-MM-DD'), ...enumerateDaysBetweenDates(dates[0], dates[1]), dates[1].format('YYYY-MM-DD')]);
        }
    }

    const handleGo = () => {

        setData([]);
        if (startDate.format('YYYY-MM-DD') == endDate.format('YYYY-MM-DD')) {
            setDateRange([startDate.format('YYYY-MM-DD')]);
        } else {
            setDateRange([startDate.format('YYYY-MM-DD'), ...enumerateDaysBetweenDates(startDate, endDate), endDate.format('YYYY-MM-DD')]);
        }

        getSDMBGolfCartConsumptionQuery({
            start_date: startDate.format('YYYY-MM-DD'),
            end_date: endDate.format('YYYY-MM-DD'),
        },{
            onSuccess: (res) => {
                // console.log(res);
                setData(res.data);
            }
        })
    }

    const handleDownload = () => {
        var wb = XLSX.utils.table_to_book(document.getElementById('table'), {sheet:"Summary", raw: false, dateNF:'dd-MMM-yy'});

        var ws = wb.Sheets["Summary"]; //  get the current sheet

        ws['!cols'] = [{wch:20}];

        for (let key in ws) {
            if (key[0] === '!') continue;

            let cell = XLSX.utils.decode_cell(key);

            ws[key].s = {
                border: {
                    top: {style: "thin", color: {auto: 1}},
                    right: {style: "thin", color: {auto: 1}},
                    bottom: {style: "thin", color: {auto: 1}},
                    left: {style: "thin", color: {auto: 1}}
                },
                alignment: {
                    vertical: 'center', horizontal: 'center'
                }
            };

            if (cell.c == 0 && cell.r  > 2) { // first column
                ws[key].s = {
                    border: {
                        top: {style: "thin", color: {auto: 1}},
                        right: {style: "thin", color: {auto: 1}},
                        bottom: {style: "thin", color: {auto: 1}},
                        left: {style: "thin", color: {auto: 1}}
                    },
                    alignment: {
                        vertical: 'center', horizontal: 'right'
                    }
                }
            }
            // console.log(key);
        }

        ws["A1"].s = {
            fill: {
                fgColor: {rgb: "70ad47"}
            },
            font: {
                sz: 16,
                color: {rgb: "#FF000000"},
                bold: true,
                italic: false,
                underline: false
            },
            alignment: {
                vertical: 'center', horizontal: 'center'
            }
        };

        var wbout = XLSX.write(wb, {bookType:'xlsx', bookSST:true, type: 'binary'});
        function s2ab(s) {
                        var buf = new ArrayBuffer(s.length);
                        var view = new Uint8Array(buf);
                        for (var i=0; i<s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
                        return buf;
        }

        saveAs(new Blob([s2ab(wbout)],{type:"application/octet-stream"}), 'SDMB-Golf-Cart.xlsx');
    }

    return (
        <>
            <Typography.Title level={4}>Golf Cart Consumption</Typography.Title>

            Jump to date: <DatePicker.RangePicker onChange={handleDateRangeChange} value={[startDate, endDate]} className="ml-2" style={{marginBottom: 12}} />
            <Button onClick={()=>handleGo()} className="ml-2" type="primary" loading={getSDMBGolfCartConsumptionQueryIsLoading}>Go</Button>
            <Button onClick={()=>handleDownload()} className="ml-2" type="primary">Download</Button>

            <Typography.Paragraph>
                <strong>SDMB - Golf Cart Report</strong><br/>
                Only those confirmed bookings with SDMB Booking tag that availed products with GOLFCART code will reflect on this report
            </Typography.Paragraph>

        { data.length ?
            <>{
                (getSDMBGolfCartConsumptionQueryIsLoading) ?
                    <><LoadingOutlined/> Loading report...</>
                :
                    <div style={{position: 'relative', overflowX: 'auto'}}>
                    <table id="table">
                        <thead>
                            <tr>
                                <th colSpan={dateRange.length + 2} style={{backgroundColor: 'rgb(112,173,71)'}} align="center" >Golf Cart Consumption</th>
                            </tr>
                            <tr>
                                <th rowSpan="2">Sales Director</th>
                                {
                                    dateRange && dateRange.map( (date, key2) => {
                                        return <td key={key2} style={{padding: 5, border: 'solid 1px gainsboro'}} align="center">
                                            <span style={{whiteSpace:'nowrap'}}>{moment(date).format('D-MMM-YY')}</span>
                                        </td>
                                    })
                                }
                                <th rowSpan="2" style={{padding: 5, border: 'solid 1px gainsboro'}} align="center">Total</th>
                            </tr>
                            <tr>
                                {
                                    dateRange && dateRange.map( (date, key2) => {
                                        return <td key={key2} style={{padding: 5, border: 'solid 1px gainsboro'}} align="center">
                                            <span style={{whiteSpace:'nowrap'}}>{moment(date).format('ddd')}</span>
                                        </td>
                                    })
                                }
                            </tr>
                        </thead>
                        <tbody>
                            {
                                data && data.map( (item, key) => {
                                    return <tr key={key}>
                                        <td style={{padding: 5, border: 'solid 1px gainsboro'}}>
                                            {item.sales_director.first_name} {item.sales_director.last_name}
                                            {/* <br/><small>{item.owned_team?.name}</small> */}
                                        </td>
                                        {
                                            item.consumptions && Object.values(item.consumptions).map( (consumption, key2) => {
                                                return <td key={key2} style={{padding: 5, border: 'solid 1px gainsboro'}} align="center">
                                                    {numberWithCommas(consumption.golf_cart_rates || 0)}
                                                </td>
                                            })
                                        }
                                        <td style={{padding: 5, border: 'solid 1px gainsboro'}} align="center">
                                            { numberWithCommas(_.sumBy(Object.values(item.consumptions), 'golf_cart_rates')) }
                                        </td>
                                    </tr>
                                })
                            }
                        </tbody>
                    </table>
                    </div>
            }</> : ''
        }
        </>
    )
}