import React from 'react';
import { message, Button, Menu} from 'antd'
import { PrinterOutlined } from '@ant-design/icons'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');
import SalesAdminPortalService from 'services/SalesAdminPortal'

const PenaltyReports = function(props) {

    let reservation = props.reservation;
    let is_button = props.button;

    const [exportPenaltyReportsQuery, { isLoading: exporPenaltyReportsQueryIsLoading, reset: exporPenaltyReportsQueryReset}] = SalesAdminPortalService.exportPenaltyReports();

    const handleExportPenaltyReport = () => {

        if (exporPenaltyReportsQueryIsLoading) {
            return false;
        }

        exportPenaltyReportsQuery({
            reservation_number: reservation.reservation_number,
            payment_terms_type: reservation.payment_terms_type
        }, {
            onSuccess: (res) => {

                var file = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;' });
                //Build a URL from the file
                const fileURL = URL.createObjectURL(file);
                //Download fileURL
                var a = document.createElement("a");
                a.href = fileURL;
                a.download = `${reservation.client_number}_Reservation_Penalty_Reports_${moment().format('YYYY-MM-DD HH:mm:ss')}`;
                a.click();
                window.URL.revokeObjectURL(fileURL);
            },
            onError: (e) => {
                message.info("Error");
            }
        })
    }


    return (
        (is_button) ?
            <Button onClick={()=>handleExportPenaltyReport()} className='mr-2' style={{marginTop: 20}} icon={<PrinterOutlined/>}>
                Export Penalty Report
            </Button> :
            <div onClick={()=>handleExportPenaltyReport()}>Export Penalty Report</div>
    )
}

export default PenaltyReports;