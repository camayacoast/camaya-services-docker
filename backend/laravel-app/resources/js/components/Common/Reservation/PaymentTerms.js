import React, { useState, useEffect } from 'react';
import { Row, Col, Divider, Descriptions, Tabs, Button, Tag, Switch, Form, Modal, Input, DatePicker, InputNumber, message, Popconfirm, Select } from 'antd';
const {TabPane} = Tabs;
import { useForm } from 'rc-field-form';

import moment from 'moment-timezone';
moment.tz.setDefault('Asia/Manila');

import { numberWithCommas, twoDecimalPlace, ordinalNumber } from 'utils/Common';
import SalesAdminPortalService from 'services/SalesAdminPortal'

function PaymentTerms(props) {

    const [RFandDPForm] = Form.useForm();

    const [showReservationDetails, setShowReservationDetails] = useState(false);
    const [RFandDPModal, setRFandDPModal] = useState(false);
    const [RFDPupdateData, setRFDPupdateData] = useState([]);

    const [updateRFDPQuery, {isLoading: updateRFDPQueryIsLoading, reset: updateRFDPQueryReset}] = SalesAdminPortalService.updateRFDPDetails();

    const reservation = props.reservation;
    const outstandingBalance = props.outstandingBalance;

    // Reservation
    let reservation_paid_checker = _.countBy(reservation.payment_details, {payment_type: 'reservation_fee_payment', is_verified: 1});
    let reservation_paid_count = ( typeof reservation_paid_checker.true !== 'undefined') ? reservation_paid_checker.true : 0;
    let reservation_actual_paid = _.sumBy(reservation.payment_details, function(payment){
        if(payment.payment_type === 'reservation_fee_payment' && payment.is_verified == 1) {
            return parseFloat(payment.payment_amount);
        }
    });

    reservation_actual_paid = (typeof reservation_actual_paid === 'undefined') ? 0 : reservation_actual_paid;

    // Downpayment

    let downpayment_label = 'Downpayment';
    const contract_amount = reservation.with_twelve_percent_vat ? reservation.net_selling_price_with_vat : reservation.net_selling_price;
    let downpayment_amount = ( reservation.split_downpayment == 1 ) ? reservation.split_downpayment_amount : reservation.downpayment_amount;
    let downpayment_start_date = ( reservation.split_downpayment == 1 ) ? reservation.split_downpayment_start_date : reservation.downpayment_due_date;

    if( reservation.payment_terms_type == 'cash' ) {
        downpayment_amount = (reservation.split_cash == 1) ? reservation.split_payment_amount : reservation.total_amount_payable;
        downpayment_start_date = (reservation.split_cash == 1) ? reservation.split_cash_start_date : '';
        downpayment_label = 'Split';
    }

    let downpayment_due_date_new = moment(reservation.reservation_fee_date).clone().add(30, 'days');

    downpayment_start_date = ( downpayment_start_date == null ) ? downpayment_due_date_new : downpayment_start_date;
    
    let dp_counter = 1;
    let dp_records = [];
    let dp_data = [];
    let dp_payment_count = 0;
    let cash_payment_count = 0;
    let remaining_dp_balance = 0;
    let prev_dp_balance = 0;
    let prev_payment = 0;
    let addDate = 0;
    let prev_dp_rmng_balace = (Math.round(parseFloat(downpayment_amount) * 100) / 100);
    let cash_payment_types = ['full_cash', 'partial_cash', 'split_cash'];

    /*
    'full_cash' : 'Full Cash',
    'partial_cash' : 'Partial Cash',
    'split_cash' : 'Split Cash',
    */
    
    let downpayment_actual_paid = _.sumBy(reservation.payment_details, function(payment){
        if(payment.payment_type === 'downpayment' && payment.is_verified == 1) {
            dp_payment_count++;
            return parseFloat(payment.payment_amount);
        }
    });

    let cash_actual_paid = _.sumBy(reservation.payment_details, function(payment){
        if( cash_payment_types.includes(payment.payment_type) && payment.is_verified == 1) {
            cash_payment_count++;
            return parseFloat(payment.payment_amount);
        }
    });

    downpayment_actual_paid = (reservation.payment_terms_type == 'in_house') ? downpayment_actual_paid : cash_actual_paid;

    if( reservation.payment_terms_type == 'cash' ) {
        if( reservation.split_cash ) {
            downpayment_actual_paid = (reservation.number_of_cash_splits === cash_payment_count) ?  Math.round(downpayment_actual_paid) : downpayment_actual_paid;
        }
    } else {
        if( reservation.split_downpayment ) {
            downpayment_actual_paid = (reservation.number_of_downpayment_splits === dp_payment_count) ? Math.round(downpayment_actual_paid) :  downpayment_actual_paid;
        } 
    }

    downpayment_actual_paid = (typeof downpayment_actual_paid === 'undefined') ? 0 : downpayment_actual_paid;

    const checker_params = {payment_type: 'downpayment', is_verified: 1};

    // Add additional downpayment split if payment amount is not satisfied
    let dp_paid_checker = _.countBy(reservation.payment_details, checker_params);
    
    // For cash term mimic the lodash countBy for multiple property values
    // return {true: num, false: num} where true is the number of satisfied condition
    let t = 0;
    let total = {};
    _.map(cash_payment_types, function(type){
        let f = 0;
        let count = _.countBy(reservation.payment_details, {payment_type: type, is_verified: 1});
        if( typeof  count.true !== 'undefined') {
            t = t + count.true;
            total.true = t;
        }
        if( typeof count.true !== 'undefined') {
            f = f + count.false;
            total.false = f; 
        }
    });

    let cash_paid_checker = total;

    dp_paid_checker = (reservation.payment_terms_type == 'in_house') ? dp_paid_checker : cash_paid_checker;

    let dp_paid_count = ( typeof dp_paid_checker.true !== 'undefined') ? dp_paid_checker.true : 0;

    let dp_paid = (Math.ceil(downpayment_actual_paid) + reservation_actual_paid);

    let dp_amount = (reservation.payment_terms_type == 'in_house') ? parseFloat(reservation.downpayment_amount) : Math.round(parseFloat(reservation.split_payment_amount) * 100) / 100;
    dp_amount = (!isNaN(dp_amount)) ? dp_amount : contract_amount;

    let number_of_splits = (reservation.payment_terms_type == 'in_house') ? reservation.number_of_downpayment_splits : reservation.number_of_cash_splits;

    let splits = (dp_paid < dp_amount && dp_paid_count >= number_of_splits) ? 
        (((number_of_splits) + (dp_paid_count - number_of_splits))) : number_of_splits;

    splits = ( dp_paid == dp_amount ) ? ((number_of_splits) + (dp_paid_count - number_of_splits)) : splits;

    splits = number_of_splits;

    let dp_split = ( number_of_splits > 0 ) ? splits : 1;

    if( reservation.payment_terms_type == 'in_house' ) {
        _.map(reservation.payment_details, function(record, i){
            if( record.payment_type === 'downpayment' && record.is_verified === 1 ) {
                dp_records.push(record);
            }
        });
    } else {
        _.map(reservation.payment_details, function(record, i){
            if( cash_payment_types.includes(record.payment_type) && record.is_verified === 1 ) {
                dp_records.push(record);
            }
        });
    }

    dp_records = _.orderBy(dp_records, ['id'], ['asc']);

    for( let i = 0; i < dp_split; i++ ) {
        if( typeof dp_records[i] !== 'undefined' ) {

            dp_records[i].remaining_dp_balance = (prev_dp_balance === 0) ? parseFloat(downpayment_amount).toFixed(2) : prev_dp_balance; 
            dp_data.push(dp_records[i]);

            // Change the prev dp balance to serve on the next iteration
            remaining_dp_balance = (remaining_dp_balance !== 0) ? parseFloat(remaining_dp_balance) - parseFloat(dp_records[i].payment_amount) :
                                    parseFloat(downpayment_amount).toFixed(2) - parseFloat(dp_records[i].payment_amount);
            prev_dp_balance = ( remaining_dp_balance <= 0 ) ? downpayment_amount : remaining_dp_balance;
            prev_payment = parseFloat(dp_records[i].payment_amount);

        } else {
            let last_balance = parseFloat(remaining_dp_balance) - parseFloat(prev_payment);
            dp_data.push({'remaining_dp_balance': (last_balance <= 0) ? 0 : last_balance}); 
        }
    }

    let downpayment_bucket = [];
    let downpayment_splits = ( reservation.split_downpayment == 1 ) ? reservation.number_of_downpayment_splits : splits;
    let is_amount_show = false;

    _.range(downpayment_splits).map((key) => {
        downpayment_bucket.push({
            required_amount: parseFloat(Math.round(downpayment_amount * 100) / 100),
            has_space: true,
            data: []
        });
    })
    
    _.map(downpayment_bucket, function(bucket){
       _.map(dp_records, function(record){
            record.final_balance = parseFloat(Math.round(bucket.required_amount * 100) / 100);
        });
    })

    downpayment_bucket = [];
    for( let i = 0; i < dp_split; i++ ) {
        let d = ( typeof dp_records[i] !== 'undefined') ? dp_records[i] : [];
        downpayment_bucket.push({
            required_amount: parseFloat(Math.round(downpayment_amount * 100) / 100),
            data: [d]
        })
    }

    // Title fee
    let title_fee_paid_checker = _.countBy(reservation.payment_details, {payment_type: 'title_fee', is_verified: 1});
    let title_fee_paid_count = ( typeof title_fee_paid_checker.true !== 'undefined') ? title_fee_paid_checker.true : 0;
    let transfer_title_fee_paid = _.sumBy(reservation.payment_details, function(payment){
        if(payment.payment_type === 'title_fee' && payment.is_verified == 1) {
            return parseFloat(payment.payment_amount);
        }
    });

    transfer_title_fee_paid = (typeof transfer_title_fee_paid === 'undefined') ? 0 : transfer_title_fee_paid;

    // Retention fee
    let retention_fee_paid_checker = _.countBy(reservation.payment_details, {payment_type: 'retention_fee', is_verified: 1});
    let retention_fee_paid_count = ( typeof retention_fee_paid_checker.true !== 'undefined') ? retention_fee_paid_checker.true : 0;
    let retention_fee_paid = _.sumBy(reservation.payment_details, function(payment){
        if(payment.payment_type === 'retention_fee' && payment.is_verified == 1) {
            return parseFloat(payment.payment_amount);
        }
    });

    retention_fee_paid = (typeof retention_fee_paid === 'undefined') ? 0 : retention_fee_paid;

    // Penalties
    let penaltyData = [];
    _.map(reservation.payment_details, function(payment) {
        if( payment.payment_type == 'penalty_fee' && payment.is_verified == 1 ) {
            return penaltyData.push(payment);
        }
    })

    let penalty_total_payments = _.map(reservation.amortization_collections, function(schedule){
        let sched_penalty = _.sumBy(schedule.penalties, function(penalty){
            // return (penalty.paid_at == null) ? parseFloat(penalty.penalty_amount) : parseFloat(penalty.amount_paid);
            return (penalty.paid_at == null) ? parseFloat(penalty.penalty_amount) : 0;
        });
        return sched_penalty;
    }).reduce(function(a, b){
        return a + b;
    }, 0);
    

    let cash_penalty_total_payments = _.map(reservation.cash_ledger_collections, function(ledger){
        let ledger_penalty = _.sumBy(ledger.penalties, function(penalty){
            // return (penalty.paid_at == null) ? parseFloat(penalty.penalty_amount) : parseFloat(penalty.amount_paid);
            return (penalty.paid_at == null) ? parseFloat(penalty.penalty_amount) : 0;
        });
        return ledger_penalty;
    }).reduce(function(a, b){
        return a + b;
    }, 0);

    cash_penalty_total_payments = (typeof cash_penalty_total_payments !== 'undefined') ? cash_penalty_total_payments : 0;

    penalty_total_payments = ( reservation.payment_terms_type == 'in_house' ) ? penalty_total_payments : cash_penalty_total_payments;

    let penalty_amount = _.sumBy(reservation.amortization_collections, function(schedule){
        return (typeof schedule.penalty !== 'undefined') ? parseFloat(schedule.penalty) : 0;
    });

    penalty_amount = ( reservation.payment_terms_type == 'in_house' ) ? penalty_total_payments : cash_penalty_total_payments;
    
    // Other fields

    const ledgerSummaryBalance = (contract_amount - downpayment_actual_paid) - reservation_actual_paid;

    const cashLedgerSummaryBalance = ( ( contract_amount - reservation_actual_paid ) - downpayment_actual_paid ) -  retention_fee_paid;

    const retention_fee_percentage = Math.round((reservation.retention_fee / contract_amount) * 100);

    const less_principal_payment = _.sumBy(reservation.amortization_collections, function(schedule){
        return parseFloat(schedule.principal);
    });

    const TotalActualPaid = reservation_actual_paid + downpayment_actual_paid + less_principal_payment;
    const PercentageOfPayment = (TotalActualPaid / contract_amount) * 100;

    const CashTotalActualPaid = reservation_actual_paid + downpayment_actual_paid + retention_fee_paid;
    const CashPercentageOfPayment = (CashTotalActualPaid / contract_amount) * 100;

    // As of Aug 11, 2022 no computation available
    const raf_credits = 0;

    const outstanding_balance = ( parseFloat(ledgerSummaryBalance) - parseFloat(less_principal_payment) ) - parseFloat(raf_credits);

    const cash_outstanding_balance = ( parseFloat(cashLedgerSummaryBalance) + ( parseFloat(reservation.retention_fee) - parseFloat(retention_fee_paid) ) ) - parseFloat(raf_credits);

    const transfer_title_fee = (contract_amount * 0.05)

    let less_payments = transfer_title_fee_paid;

    const remaining_balance = parseFloat(outstanding_balance) + parseFloat(penalty_amount) + (parseFloat(transfer_title_fee - less_payments));

    let cash_less_payments = transfer_title_fee_paid;

    const cash_remaining_balance = parseFloat(cash_outstanding_balance) + parseFloat(penalty_total_payments) + (parseFloat(transfer_title_fee) - cash_less_payments);

    let downPaymentPercentage = (reservation.downpayment_amount / reservation.net_selling_price) * 100 ;
    if( reservation.with_twelve_percent_vat ) {
        downPaymentPercentage =  (reservation.downpayment_amount / reservation.net_selling_price_with_vat) * 100;
    }

    const handlePreviewPaymentClick = (data) => {
        props.params.transactionIdSetter(data.transaction_id);
        props.params.modal.transactionSetter([data]);
        props.params.modal.visibilitySetter(true);
        props.params.modal.transaction = [data];
        props.params.transactionId = data.transaction_id;
    }

    const showRFandDPUpdateModal = () => {
        // reservation.downpayment_percentage = downPaymentPercentage;

        let default_promo_values = [];
        _.map(reservation.promos, function(e){
            default_promo_values.push(e.promo_type);
        });
        RFandDPForm.setFieldsValue({
            'promos' : default_promo_values,
        });
        setRFDPupdateData(reservation);
        setRFandDPModal(true);
    }

    const handleRFandDPUpdate = (values) => {

        if( updateRFDPQueryIsLoading ) {
            return false;
        }

        let promos_values = (typeof values.promos == 'undefined') ? promos : values.promos;

        updateRFDPQuery({
            reservation_number: values.reservation_number,
            payment_terms_type: values.payment_terms_type,
            reservation_date: RFDPupdateData.reservation_fee_date,
            reservation_fee_amount: RFDPupdateData.reservation_fee_amount,
            // downpayment_percentage: RFDPupdateData.downpayment_percentage,
            downpayment_amount: RFDPupdateData.downpayment_amount,
            downpayment_due_date: RFDPupdateData.downpayment_due_date,
            start_date: (values.payment_terms_type === 'in_house') ? RFDPupdateData.split_downpayment_start_date : RFDPupdateData.split_cash_start_date,
            end_date: (values.payment_terms_type === 'in_house') ? RFDPupdateData.split_downpayment_end_date : RFDPupdateData.split_cash_end_date,
            nsp_computed: getComputedNSP(),
            number_of_splits: (values.payment_terms_type === 'in_house') ? RFDPupdateData.number_of_downpayment_splits : RFDPupdateData.number_of_cash_splits,
            promos: promos_values,
        }, {
            onSuccess: (res) => {
                let msg = (reservation.payment_terms_type === 'in_house') ? 'DP' : 'Split';
                message.success('RF and ' + msg + ' details successfully updated.');
                props.params.addActivityLog({description: `Updated RF and DP details`, action: 'update_rf_dp_details'});
                props.params.reservationUpdater();
                setRFandDPModal(false);
            },
            onError: (e) => {
                updateRFDPQueryReset();
                message.warning(`Updating RF and DP failed: ${e.errors ? _.map(e.errors, (i) => i) : e.message}`)
            }
        })
    }

    const currencyFormatter = {
        formatter: value => `₱ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ','),
        parser: value => value.replace(/\₱\s?|(,*)/g, '')
    };

    const getComputedNSP = () => {
        let net_selling_price = reservation.net_selling_price - reservation.discount_amount;
        let twelve_percent_vat = net_selling_price * 0.12;
        let nsp_with_vat = net_selling_price + twelve_percent_vat;
        let nsp_computed = (reservation.with_twelve_percent_vat ? nsp_with_vat : net_selling_price);
        return nsp_computed;
    }

    const getDownpaymentDueDate = (record) => {

        let start_date = record.payment_terms_type == 'in_house' ? moment(record.split_downpayment_start_date) : moment(record.split_cash_start_date);

        if( start_date == null || isNaN(start_date) ) {

            let new_downpayment_due_date = moment(record.reservation_fee_date).clone().add(30, 'days');

            start_date = moment(new_downpayment_due_date);
        }
         
        return start_date;

    }

    const getDownpaymentEndDueDate = (record) => {

       let end_date = record.payment_terms_type == 'in_house' ? moment(record.split_downpayment_end_date) : moment(record.split_cash_end_date);

       if( end_date == null ||  isNaN(end_date) ) {

            let number_of_splits = 1;
            let form_start_date = null;

            if( reservation.payment_terms_type === 'in_house' ) {
                form_start_date = record.split_downpayment_start_date;
                number_of_splits = (record.split_downpayment) ? record.number_of_downpayment_splits : 1;
            } else {
                form_start_date = record.split_cash_start_date;
                number_of_splits = (record.split_cash) ? record.number_of_cash_splits : 1;
            }

            if( form_start_date == null || isNaN(form_start_date) ) {
                let new_downpayment_due_date = moment(record.reservation_fee_date).clone().add(30, 'days');
                form_start_date = moment(new_downpayment_due_date);
            }

            number_of_splits = number_of_splits <= 0 ? 1 : number_of_splits;
            end_date = moment(form_start_date).clone().add(number_of_splits - 1, 'months');
       }
         
        return end_date;

    }

    return (
        <>
            <Divider orientation="left">Payment terms ({reservation.payment_terms_type == 'cash' ? 'CASH' : 'IN-HOUSE ASSISTED FINANCING'})</Divider>
            <Tabs defaultActiveKey="1">
                <TabPane tab="Reservation Details" key="1">
                    <Descriptions bordered colon={false} layout="horizontal" size="small">
                        <Descriptions.Item span={2} label={<strong>Reservation fee amount</strong>}>{numberWithCommas(reservation.reservation_fee_amount)}</Descriptions.Item>
                        <Descriptions.Item span={2} label={<strong>Reservation fee date</strong>}>{reservation.reservation_fee_date}</Descriptions.Item>
                        <Descriptions.Item span={2} label={<strong>Payment terms type</strong>}>{reservation.payment_terms_type == 'cash' ? 'CASH' : 'IN-HOUSE ASSISTED FINANCING'}</Descriptions.Item>
                        <Descriptions.Item span={2} label={<strong>Discount ({reservation.discount_amount ? ((reservation.discount_amount / reservation.total_selling_price) * 100).toFixed(2) : ''}%)</strong>}>{numberWithCommas(reservation.discount_amount)}</Descriptions.Item>
                        <Descriptions.Item span={2} label={<strong>With 12% vat</strong>}>{reservation.with_twelve_percent_vat ? 'Yes' : 'No'}</Descriptions.Item>
                        <Descriptions.Item span={2} label={<strong>Total selling price</strong>}>{numberWithCommas(reservation.total_selling_price)}</Descriptions.Item>
                        <Descriptions.Item span={2} label={<strong>Net selling price</strong>}>{numberWithCommas(reservation.net_selling_price)}</Descriptions.Item>
                        { reservation.with_twelve_percent_vat ? 
                            <>
                                <Descriptions.Item span={2} label={<strong>12% vat</strong>}>{numberWithCommas(reservation.twelve_percent_vat)}</Descriptions.Item>
                                <Descriptions.Item span={2} label={<strong>Net selling price with vat</strong>}>{numberWithCommas(reservation.net_selling_price_with_vat)}</Descriptions.Item>
                            </>
                            : <></> }
                        {
                            reservation.payment_terms_type == 'cash' ?
                                <>
                                    <Descriptions.Item span={2} label={<strong>With {retention_fee_percentage}% retention fee</strong>}>{reservation.with_five_percent_retention_fee ? 'Yes' : 'No'}</Descriptions.Item>
                                    <Descriptions.Item span={2} label={<strong>Retention fee</strong>}>{numberWithCommas(reservation.retention_fee.toFixed(2))}</Descriptions.Item>
                                    { reservation.split_cash &&
                                        <>
                                            <Descriptions.Item span={2} label={<strong>Split cash</strong>}>{reservation.split_cash ? 'Yes' : 'No'}</Descriptions.Item>
                                            <Descriptions.Item span={2} label={<strong>Number of cash split</strong>}>{reservation.number_of_cash_splits}</Descriptions.Item>
                                            <Descriptions.Item span={2} label={<strong>Split cash amount</strong>}>{numberWithCommas(reservation.split_payment_amount.toFixed(2))}</Descriptions.Item>
                                            <Descriptions.Item span={2} label={<strong>Split cash start date</strong>}>{reservation.split_cash_start_date}</Descriptions.Item>
                                            <Descriptions.Item span={2} label={<strong>Split cash end date</strong>}>{reservation.split_cash_end_date}</Descriptions.Item>
                                        </>
                                    }
                                    <Descriptions.Item span={2} label={<strong>Total amount payable</strong>}>{numberWithCommas(reservation.total_amount_payable.toFixed(2))}</Descriptions.Item>
                                </>
                            :   <>
                                    <Descriptions.Item span={2} label={<strong>Downpayment amount ({`${downPaymentPercentage.toFixed(2)}%`})</strong>}>{numberWithCommas(reservation.downpayment_amount)} (Less RF: {numberWithCommas(reservation.downpayment_amount_less_RF?.toFixed(2))})</Descriptions.Item>
                                    <Descriptions.Item span={2} label={<strong>Downpayment due date</strong>}>{reservation.downpayment_due_date}</Descriptions.Item>
                                    <Descriptions.Item span={2} label={<strong>Number of years</strong>}>{reservation.number_of_years} year{reservation.number_of_years > 1 ? 's' : ''}</Descriptions.Item>
                                    <Descriptions.Item span={2} label={<strong>Factor rate ({reservation.interest_rate}%)</strong>}>{reservation.factor_rate}</Descriptions.Item>
                                    <Descriptions.Item span={2} label={<strong>Monthly amortization</strong>}>{numberWithCommas(reservation.monthly_amortization)}</Descriptions.Item>
                                    <Descriptions.Item span={2} label={<strong>Monthly amortization due date</strong>}>{reservation.monthly_amortization_due_date}</Descriptions.Item>
                                    { reservation.split_downpayment &&
                                        <>
                                            <Descriptions.Item span={2} label={<strong>Split downpayment</strong>}>{reservation.split_downpayment ? 'Yes' : 'No'}</Descriptions.Item>
                                            <Descriptions.Item span={2} label={<strong>Split downpayment amount</strong>}>{numberWithCommas(downpayment_amount.toFixed(2))}</Descriptions.Item>
                                            <Descriptions.Item span={2} label={<strong>Number of downpayment splits</strong>}>{reservation.number_of_downpayment_splits}</Descriptions.Item>
                                            <Descriptions.Item span={2} label={<strong>Split downpayment start date</strong>}>{reservation.split_downpayment_start_date}</Descriptions.Item>
                                            <Descriptions.Item span={2} label={<strong>Split downpayment end date</strong>}>{reservation.split_downpayment_end_date}</Descriptions.Item>
                                        </>
                                    }
                                    <Descriptions.Item span={2} label={<strong>Total balance in-house</strong>}>{numberWithCommas(reservation.total_balance_in_house)}</Descriptions.Item>
                                </>
                        }
                    </Descriptions>
                </TabPane>
                <TabPane tab="Amortization Ledger" key="2">
                    <Row gutter={[48,48]}>
                        <Col xl={13}>
                            { props.params.view === 'view_reservation' &&
                                <Button 
                                    size='small' 
                                    style={{fontSize: 10, border: 'solid 1px gainsboro'}}
                                    className="mb-2"
                                    onClick={() => showRFandDPUpdateModal()}
                                >
                                    Update RF and DP details
                                </Button>
                            }
                            <table cellPadding={8} cellSpacing={3} border="1" style={{fontSize: 10, border: 'solid 1px gainsboro', width: '100%', borderRadius: 12}}>
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Date Paid</th>
                                        <th>Amt. Paid</th>
                                        {/* <th>Rmng. Balance</th> */}
                                        <th>Check #</th>
                                        <th>PR #</th>
                                        <th>OR #</th>
                                        <th>ACCT #</th>
                                        <th>Transaction #</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    {
                                        (typeof reservation.payment_details != 'undefined' && reservation.payment_details.length > 0) && _.map(reservation.payment_details, function(record, key){
                                            if( record.payment_type === 'reservation_fee_payment' && record.is_verified === 1 ) {
                                                return <tr key={key}>
                                                    <td>Reservation</td>
                                                    <td>{moment(reservation.reservation_fee_date).format('MM/DD/YYYY')}</td>
                                                    <td>{numberWithCommas(reservation.reservation_fee_amount)}</td>
                                                    <td>{(record.is_verified) ? moment(record.paid_at).format('MM/DD/YYYY') : ``}</td>
                                                    <td>{(record.is_verified) ? numberWithCommas(record.payment_amount) : ``}</td>
                                                    {/* <td></td> */}
                                                    <td>{(record.is_verified && record.check_number !== null) ? record.check_number : ``}</td>
                                                    <td>{(record.is_verified && record.cr_number !== null) ? record.cr_number : ``}</td>
                                                    <td>{(record.is_verified && record.or_number !== null) ? record.or_number : ``}</td>
                                                    <td>{(record.is_verified && record.bank_account_number !== null) ? record.bank_account_number : ``}</td>
                                                    {/* {
                                                        props.params.view === 'view_account' ?
                                                        <td>{(record.is_verified) ? <Tag onClick={() => handlePreviewPaymentClick(record)} className='transaction-tag'>{record.transaction_id}</Tag>  : ``}</td> :
                                                        <td>{(record.is_verified) ? record.transaction_id : ``}</td>
                                                    } */}

                                                    <td>{(record.is_verified) ? <Tag onClick={() => handlePreviewPaymentClick(record)} className='transaction-tag'>{record.transaction_id}</Tag>  : ``}</td>
                                                    
                                                </tr>
                                            }
                                        })
                                    }

                                    {
                                        (typeof reservation.payment_details != 'undefined' && reservation_paid_count <= 0) && 
                                            <tr>
                                                <td>Reservation</td>
                                                <td>{moment(reservation.reservation_fee_date).format('MM/DD/YYYY')}</td>
                                                <td>{numberWithCommas(reservation.reservation_fee_amount)}</td>
                                                <td></td>
                                                <td></td>
                                                {/* <td></td> */}
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                    }

                                    {
                                        // (reservation.payment_terms_type == 'in_house') && 
                                        _.map(downpayment_bucket, function(bucket, key) {
                                            let html = '';
                                            let data = bucket.data;
                                            if(data.length > 0) {
                                                html = _.map(data, function(record, i){
                                                    if( prev_dp_rmng_balace <= 0) {
                                                        prev_dp_rmng_balace = (Math.round(parseFloat(downpayment_amount) * 100) / 100);
                                                    }
                                                    let dom = <tr key={i}>
                                                        <td>{ ordinalNumber(dp_counter++)} {downpayment_label}</td>
                                                        <td>{
                                                            ( downpayment_start_date != '' ) ?
                                                            moment(downpayment_start_date).clone().add(addDate++, 'months').format('MM/DD/YYYY') : 
                                                            moment(reservation.reservation_fee_date).clone().add(addDate + 1, 'months').format('MM/DD/YYYY')
                                                        }</td>
                                                        <td>{numberWithCommas(parseFloat(prev_dp_rmng_balace).toFixed(2))}</td>
                                                        <td>{(record.is_verified) ? moment(record.paid_at).format('MM/DD/YYYY') : ``}</td>
                                                        <td>{(record.is_verified) ? numberWithCommas(record.payment_amount) : ``}</td>
                                                        {/* <td>{(record.is_verified) ? numberWithCommas((Math.round(record.final_balance * 100) / 100).toFixed(2)) : ``}</td> */}
                                                        <td>{(record.is_verified && record.check_number !== null) ? record.check_number : ``}</td>
                                                        <td>{(record.is_verified && record.cr_number !== null) ? record.cr_number : ``}</td>
                                                        <td>{(record.is_verified && record.or_number !== null) ? record.or_number : ``}</td>
                                                        <td>{(record.is_verified && record.bank_account_number !== null) ? record.bank_account_number : ``}</td>
                                                        <td>{(record.is_verified) ? <Tag onClick={() => handlePreviewPaymentClick(record)} className='transaction-tag'>{record.transaction_id}</Tag>  : ``}</td>
                                                    </tr>
                                                    prev_dp_rmng_balace = (record.is_verified) ? (Math.round(record.final_balance * 100) / 100).toFixed(2) : ``;
                                                    return dom;
                                                })
                                            } else {

                                                if( prev_dp_rmng_balace <= 0) {
                                                    prev_dp_rmng_balace = (Math.round(parseFloat(downpayment_amount) * 100) / 100);
                                                }
                                                
                                                html = <tr key={key}>
                                                    <td>{ordinalNumber(dp_counter++)} {downpayment_label}</td>
                                                    <td>{
                                                        ( downpayment_start_date != '' ) ?
                                                        moment(downpayment_start_date).clone().add(addDate++, 'months').format('MM/DD/YYYY') :
                                                        moment(reservation.reservation_fee_date).clone().add(addDate + 1, 'months').format('MM/DD/YYYY')
                                                    }</td>
                                                    <td>{ 
                                                        (!is_amount_show) ? numberWithCommas(parseFloat(prev_dp_rmng_balace).toFixed(2)) : numberWithCommas(downpayment_amount.toFixed(2))
                                                    }</td>
                                                    <td></td>
                                                    <td></td>
                                                    {/* <td></td> */}
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>

                                                is_amount_show = true;
                                            }
                                            return html;
                                        })
                                    }

                                    { // Default in-house downpayment table
                                        (dp_data.length <= 0) && 
                                        _.range(reservation.number_of_downpayment_splits).map((key) => {
                                            return <tr key={key}>
                                                <td>{ordinalNumber(key+1)} {downpayment_label}</td>
                                                <td>{
                                                    ( downpayment_start_date != '' ) ?
                                                    moment(downpayment_start_date).clone().add(addDate++, 'months').format('MM/DD/YYYY') :
                                                    moment(reservation.reservation_fee_date).clone().add(addDate + 1, 'months').format('MM/DD/YYYY')
                                                    }</td>
                                                <td>{numberWithCommas(downpayment_amount.toFixed(2))}</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                {/* <td></td> */}
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        })
                                    }

                                    {
                                        (penaltyData.length > 0) &&
                                        _.map(penaltyData, function(record, key){
                                            return <tr key={key}>
                                                <td>Penalty {key + 1}</td>
                                                <td></td>
                                                <td>{(record.is_verified) ? numberWithCommas(record.payment_amount) + ` (${record.discount}%)`: ``}</td>
                                                <td>{(record.is_verified) ? moment(record.paid_at).format('MM/DD/YYYY') : ``}</td><td>
                                                    {
                                                    (record.is_verified) ? 
                                                        numberWithCommas(twoDecimalPlace(
                                                            (parseFloat(record.payment_amount) - ( (parseFloat(record.discount) / 100) * parseFloat(record.payment_amount) ))
                                                        )): 
                                                        ``
                                                    }
                                                </td>
                                                <td></td>
                                                <td>{(record.is_verified && record.check_number !== null) ? record.check_number : ``}</td>
                                                <td>{(record.is_verified && record.cr_number !== null) ? record.cr_number : ``}</td>
                                                <td>{(record.is_verified && record.or_number !== null) ? record.or_number : ``}</td>
                                                <td>{(record.is_verified && record.bank_account_number !== null) ? record.bank_account_number : ``}</td>
                                                <td>{(record.is_verified) ? <Tag onClick={() => handlePreviewPaymentClick(record)} className='transaction-tag'>{record.transaction_id}</Tag>  : ``}</td>
                                            </tr>
                                        })
                                    }

                                    {
                                        reservation.payment_terms_type === 'cash' && reservation.with_five_percent_retention_fee &&
                                        _.map(reservation.payment_details, function(record, key){
                                            if( record.payment_type === 'retention_fee' && record.is_verified == 1 ) {
                                                return <tr key={key}>
                                                    <td>Retention Fee</td>
                                                    <td></td>
                                                    <td>{numberWithCommas(twoDecimalPlace(reservation.retention_fee))}</td>
                                                    <td>{(record.is_verified) ? moment(record.paid_at).format('MM/DD/YYYY') : ``}</td>
                                                    <td>{(record.is_verified) ? numberWithCommas(record.payment_amount) : ``}</td>
                                                    {/* <td></td> */}
                                                    <td>{(record.is_verified && record.check_number !== null) ? record.check_number : ``}</td>
                                                    <td>{(record.is_verified && record.cr_number !== null) ? record.cr_number : ``}</td>
                                                    <td>{(record.is_verified && record.or_number !== null) ? record.or_number : ``}</td>
                                                    <td>{(record.is_verified && record.bank_account_number !== null) ? record.bank_account_number : ``}</td>
                                                    <td>{(record.is_verified) ? <Tag onClick={() => handlePreviewPaymentClick(record)} className='transaction-tag'>{record.transaction_id}</Tag>  : ``}</td>
                                                </tr>
                                            }
                                        })
                                    }

                                    {
                                        reservation.payment_terms_type === 'cash' && reservation.with_five_percent_retention_fee &&  typeof reservation.payment_details != 'undefined' && retention_fee_paid_count <= 0 &&
                                            <tr>
                                                <td>Retention Fee</td>
                                                <td></td>
                                                <td>{numberWithCommas(twoDecimalPlace(reservation.retention_fee))}</td>
                                                <td></td>
                                                <td></td>
                                                {/* <td></td> */}
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                    }

                                    {
                                        _.map(reservation.payment_details, function(record, key){
                                            if( record.payment_type === 'title_fee' && record.is_verified == 1 ) {
                                                return <tr key={key}>
                                                    <td>Title Transfer</td>
                                                    <td></td>
                                                    <td>{numberWithCommas((contract_amount * 0.05).toFixed(2))}</td>
                                                    <td>{(record.is_verified) ? moment(record.paid_at).format('MM/DD/YYYY') : ``}</td>
                                                    <td>{(record.is_verified) ? numberWithCommas(record.payment_amount) : ``}</td>
                                                    {/* <td></td> */}
                                                    <td>{(record.is_verified && record.check_number !== null) ? record.check_number : ``}</td>
                                                    <td>{(record.is_verified && record.cr_number !== null) ? record.cr_number : ``}</td>
                                                    <td>{(record.is_verified && record.or_number !== null) ? record.or_number : ``}</td>
                                                    <td>{(record.is_verified && record.bank_account_number !== null) ? record.bank_account_number : ``}</td>
                                                    <td>{(record.is_verified) ? <Tag onClick={() => handlePreviewPaymentClick(record)} className='transaction-tag'>{record.transaction_id}</Tag>  : ``}</td>
                                                </tr>
                                            }
                                        })
                                    }

                                    {
                                        (typeof reservation.payment_details != 'undefined' && title_fee_paid_count <= 0) && 
                                            <tr>
                                                <td>Title Transfer</td>
                                                <td></td>
                                                <td>{numberWithCommas((contract_amount * 0.05).toFixed(2))}</td>
                                                <td></td>
                                                <td></td>
                                                {/* <td></td> */}
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                    }

                                </tbody>
                            </table>
                        </Col>
                        <Col xl={showReservationDetails === true ? 11 : 6}>
                            <div className="mb-2">
                                <Switch 
                                    size='small' 
                                    onChange={(value) => {
                                        setShowReservationDetails(value);
                                    }}
                                /> <strong style={{fontSize: 10}}>Reservation Details</strong>
                            </div>
                            <table cellPadding={8} cellSpacing={3} border="1" style={{fontSize: 10, border: 'solid 1px gainsboro', width: '100%', borderRadius: 12}}>
                                <thead>
                                    <tr>
                                        { showReservationDetails === true &&
                                            <th colSpan={2}>Reservation Details</th>
                                        }
                                        <th colSpan={2}>Ledger Summary</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        { showReservationDetails === true &&
                                            <>
                                                <td>Area</td>
                                                <td>{reservation?.area}</td>
                                            </>
                                        }
                                        { 
                                            reservation.payment_terms_type == 'in_house' ?
                                            <>
                                                <td><strong>Contract Amount</strong></td>
                                                <td>
                                                    <strong>
                                                        {reservation.with_twelve_percent_vat ? numberWithCommas(reservation.net_selling_price_with_vat.toFixed(2)) : numberWithCommas(reservation.net_selling_price.toFixed(2))}
                                                    </strong>
                                                </td>
                                            </> : 
                                            <>
                                                <td><strong>Contract Amount</strong></td>
                                                <td>
                                                    <strong>{ numberWithCommas(twoDecimalPlace(contract_amount))}</strong>
                                                </td>
                                            </>
                                        }
                                    </tr>
                                    <tr>
                                        { showReservationDetails === true &&
                                            <>
                                                <td></td>
                                                <td></td>
                                            </>
                                        }
                                        <td>Reservation Fee (Actual Paid)</td>
                                        <td>{numberWithCommas(reservation_actual_paid.toFixed(2))}</td>
                                    </tr>
                                    <tr>
                                        { showReservationDetails === true &&
                                            <>
                                                <td>Total Selling Price (TSP)</td>
                                                <td>{numberWithCommas(parseFloat(reservation.total_selling_price).toFixed(2))}</td>
                                            </>
                                        }
                                        { 
                                            reservation.payment_terms_type == 'in_house' ?
                                            <>
                                                <td>Downpayment (Actual Paid)</td>
                                                <td>{numberWithCommas(downpayment_actual_paid.toFixed(2))}</td>
                                            </> : 
                                            <>
                                                <td>Downpayment (Actual Paid)</td>
                                                <td>{numberWithCommas(downpayment_actual_paid.toFixed(2))}</td>
                                            </>
                                        }
                                    </tr>
                                    <tr>
                                        { showReservationDetails === true &&
                                            <>
                                                <td>Less: Discount ({reservation.discount_amount ? ((reservation.discount_amount / reservation.total_selling_price) * 100).toFixed(2) : ''}%)</td>
                                                <td>{numberWithCommas(reservation.discount_amount)}</td>
                                            </>
                                        }
                                        { 
                                            reservation.payment_terms_type == 'in_house' ?
                                            <>
                                                <td>Balance</td>
                                                <td>{ numberWithCommas(ledgerSummaryBalance.toFixed(2)) }</td>
                                            </> : 
                                            <>
                                                <td>Balance</td>
                                                <td>{numberWithCommas(twoDecimalPlace(cashLedgerSummaryBalance))}</td>
                                            </>
                                        }
                                    </tr>
                                    <tr>
                                        { showReservationDetails === true &&
                                            <>
                                                <td>Contract Amount</td>
                                                <td>{numberWithCommas(parseFloat(reservation.net_selling_price).toFixed(2))}</td>
                                            </>
                                        }
                                        { 
                                            reservation.payment_terms_type == 'in_house' ?
                                            <>
                                                <td>Less: Principal Payment</td>
                                                <td>{ numberWithCommas(less_principal_payment.toFixed(2)) }</td>
                                            </> : 
                                            <>
                                                <td>Retention Fee ({retention_fee_percentage}%)</td>
                                                <td>{ reservation.with_five_percent_retention_fee ? numberWithCommas(twoDecimalPlace(reservation.retention_fee)) : 0 }</td>
                                            </>
                                        } 
                                    </tr>
                                    <tr>
                                        { showReservationDetails === true &&
                                            <>
                                                <td>VAT (12%)</td>
                                                <td>{ reservation.with_twelve_percent_vat ? numberWithCommas(reservation.twelve_percent_vat) : '-'}</td>
                                            </>
                                        }
                                        { 
                                            reservation.payment_terms_type == 'in_house' ?
                                            <>
                                                <td>Less: RAF Credits</td>
                                                <td>{raf_credits.toFixed(2)}</td>
                                            </> :
                                            <>
                                                <td>Retention Fee (Actual Paid)</td>
                                                <td>{numberWithCommas(twoDecimalPlace(retention_fee_paid))}</td>
                                            </>
                                        }
                                    </tr>
                                    <tr>
                                        { showReservationDetails === true &&
                                            <>
                                                <td><strong>Net Selling Price (NSP)</strong></td>
                                                <td>
                                                    <strong>
                                                        {reservation.with_twelve_percent_vat ? numberWithCommas(parseFloat(reservation.net_selling_price_with_vat).toFixed(2)) : numberWithCommas(parseFloat(reservation.net_selling_price).toFixed(2))}
                                                    </strong>
                                                </td>
                                            </>
                                        }
                                        { 
                                            reservation.payment_terms_type == 'in_house' ?
                                            <>
                                                <td><strong>Outstanding Balance</strong></td>
                                                <td><strong>{numberWithCommas(outstanding_balance.toFixed(2))}</strong></td>
                                            </> :
                                            <>
                                                <td>Less: RAF Credits</td>
                                                <td>{raf_credits.toFixed(2)}</td>
                                            </>
                                        }
                                    </tr>
                                    <tr>
                                        { 
                                            reservation.payment_terms_type == 'in_house' ?
                                            <>
                                                { showReservationDetails === true &&
                                                    <>
                                                        <td>Downpayment Amount ({reservation.downpayment_amount ? ((reservation.downpayment_amount / (reservation.with_twelve_percent_vat ? reservation.net_selling_price_with_vat : reservation.net_selling_price)) * 100).toFixed(2) : ''}%)</td>
                                                        <td>{numberWithCommas(reservation.downpayment_amount)}</td>
                                                    </>
                                                }
                                                <td>Add: Penalties</td>
                                                <td>{numberWithCommas(penalty_amount.toFixed(2))}</td>
                                            </> : 
                                            <>
                                                { showReservationDetails === true &&
                                                    <>
                                                        <td>Split Cash Amount ({reservation.number_of_cash_splits})</td>
                                                        <td>{ (reservation.split_cash == 1) ? numberWithCommas(Math.round(parseFloat(reservation.split_payment_amount) * 100) / 100) : 'N/A' }</td>
                                                    </>
                                                }
                                                <td>Outstanding Balance</td>
                                                <td>{numberWithCommas(twoDecimalPlace(cash_outstanding_balance))}</td>
                                            </>
                                        }
                                    </tr>
                                    <tr>
                                        {/* NSP * dp% - reservation fee / dp split */}
                                        { 
                                            reservation.payment_terms_type == 'in_house' ?
                                            <>
                                                { showReservationDetails === true &&
                                                    <>
                                                        <td>Split Downpayment Amount ({reservation.number_of_downpayment_splits})</td>
                                                        <td>
                                                            { numberWithCommas(parseFloat(downpayment_amount).toFixed(2)) }
                                                        </td>
                                                    </>
                                                }
                                                <td>Add: Title Transfer Fee</td>
                                                <td>{numberWithCommas(transfer_title_fee.toFixed(2))}</td>
                                            </> :
                                            <>
                                                { showReservationDetails === true &&
                                                    <>
                                                        <td></td>
                                                        <td></td>
                                                    </>
                                                }
                                                <td>Add: Penalties</td>
                                                <td>{numberWithCommas(penalty_amount.toFixed(2))}</td>
                                            </>
                                        } 
                                    </tr>
                                    <tr>
                                        
                                        { 
                                            reservation.payment_terms_type == 'in_house' &&
                                            <>
                                                { showReservationDetails === true &&
                                                    <>
                                                        <td></td>
                                                        <td></td>
                                                    </>
                                                }
                                                <td>Less: Payments</td>
                                                <td>{ numberWithCommas(less_payments.toFixed(2)) }</td>
                                            </>
                                        }
                                    </tr>
                                    <tr>
                                        {
                                            reservation.payment_terms_type == 'in_house' ?
                                                <>
                                                    { showReservationDetails === true &&
                                                        <>
                                                            <td><strong>Installment Balance</strong></td>
                                                            <td><strong>{numberWithCommas(parseFloat(reservation.total_balance_in_house).toFixed(2))}</strong></td>
                                                        </>
                                                    }
                                                    <td><strong>Remaining Balance</strong></td>
                                                    <td><strong>{numberWithCommas(remaining_balance.toFixed(2))}</strong></td>
                                                </>
                                                : 
                                                <>
                                                    { showReservationDetails === true &&
                                                        <>
                                                            <td>Retention Fee ({retention_fee_percentage}%)</td>
                                                            <td>{ reservation.with_five_percent_retention_fee ? numberWithCommas(twoDecimalPlace(reservation.retention_fee)) : 'N/A' }</td>
                                                        </>
                                                    }
                                                    <td>Add: Title Transfer Fee</td>
                                                    <td>{numberWithCommas(transfer_title_fee.toFixed(2))}</td>
                                                </>
                                        }
                                    </tr>
                                    {
                                        reservation.payment_terms_type == 'cash' && 
                                        <tr>
                                            { showReservationDetails === true &&
                                                <>
                                                    <td></td>
                                                    <td></td>
                                                </>
                                            }
                                            <td>Less: Payments</td>
                                            <td>{ numberWithCommas(cash_less_payments.toFixed(2)) }</td>
                                        </tr>
                                    }
                                    <tr>
                                    {
                                        reservation.payment_terms_type == 'cash' ?
                                            <> 
                                                { showReservationDetails === true &&
                                                    <>
                                                        <td>Total Amount Payable</td>
                                                        <td>{numberWithCommas(twoDecimalPlace(reservation.total_amount_payable))}</td>
                                                    </>
                                                }
                                                <td>Remaining Balance</td>
                                                <td>{numberWithCommas(twoDecimalPlace(cash_remaining_balance))}</td>
                                            </> : 
                                            <>
                                                { showReservationDetails === true &&
                                                    <>
                                                        <td><strong>Total Actual Paid</strong></td>
                                                        <td><strong>{ numberWithCommas(twoDecimalPlace(TotalActualPaid)) }</strong></td>
                                                    </>
                                                }
                                                <td><strong>Percentage Of Payment</strong></td>
                                                <td><strong>{ Math.round(PercentageOfPayment) }%</strong></td>
                                            </>
                                    }
                                    </tr>
                                    {
                                        reservation.payment_terms_type == 'cash' ?
                                        <>
                                            <tr>
                                                { showReservationDetails === true &&
                                                    <>
                                                        <td><strong>Total Actual Paid</strong></td>
                                                        <td><strong>{numberWithCommas(twoDecimalPlace(CashTotalActualPaid))}</strong></td>
                                                    </>
                                                }
                                                <td><strong>Percentage Of Payment</strong></td>
                                                <td><strong>{Math.round(CashPercentageOfPayment)}%</strong></td>
                                            </tr>
                                        </>
                                        : <></>
                                    }
                                </tbody>
                            </table>
                        </Col>
                    </Row>
                </TabPane>
            </Tabs>

            <Modal
                visible={RFandDPModal}
                footer={null}
                title="Update RF and DP details"
                width={reservation.payment_terms_type == 'in_house' ? 800 : 600}
                onCancel={()=>{
                    RFandDPForm.resetFields();
                    setRFDPupdateData(reservation);
                    setRFandDPModal(false);
                }}
                afterClose={(e) => {
                    RFandDPForm.resetFields();
                    setRFDPupdateData(reservation);
                    setRFandDPModal(false);
                }}
            >
                <Form
                    form={RFandDPForm}
                    onFinish={handleRFandDPUpdate}
                    layout={'vertical'}
                >
                    <Form.Item name="reservation_number" style={{display: 'none'}} initialValue={RFDPupdateData.reservation_number}>
                        <Input hidden={true} value={RFDPupdateData.reservation_number} />
                    </Form.Item>

                    <Form.Item name="payment_terms_type" style={{display: 'none'}} initialValue={RFDPupdateData.payment_terms_type}>
                        <Input hidden={true} value={RFDPupdateData.payment_terms_type} />
                    </Form.Item>

                    <Divider orientation="left">Reservation</Divider>
                    
                    <Row gutter={[16,16]}>
                        <Col xl={reservation.payment_terms_type == 'in_house' ? 6 : 8}>
                            <Form.Item name="reservation_fee_date" label="Reservation fee date" 
                                rules={[{required: true}]}
                                initialValue={moment(RFDPupdateData.reservation_fee_date)}
                            >
                                <DatePicker 
                                    onChange={(e) => {

                                        let new_downpayment_due_date = e.clone().add(30, 'days');
                                        let number_of_splits = 1;
                                        let updateData = {};

                                        if( reservation.payment_terms_type === 'in_house' ) {
                                            number_of_splits = (RFDPupdateData.split_downpayment) ? RFDPupdateData.number_of_downpayment_splits : 1;
                                        } else {
                                            number_of_splits = (RFDPupdateData.split_cash) ? RFDPupdateData.number_of_cash_splits : 1;
                                        }

                                        const end_date = new_downpayment_due_date.clone().add(number_of_splits - 1, 'months');

                                        if( reservation.payment_terms_type === 'in_house' ) {
                                            updateData = { ...RFDPupdateData, 
                                                downpayment_due_date: new_downpayment_due_date,
                                                reservation_fee_date: e ? moment(e) : undefined,
                                                split_downpayment_start_date: new_downpayment_due_date,
                                                split_downpayment_end_date : number_of_splits > 1  ? end_date : new_downpayment_due_date
                                            }
                                        } else {
                                            updateData = { ...RFDPupdateData, 
                                                downpayment_due_date: new_downpayment_due_date,
                                                reservation_fee_date: e ? moment(e) : undefined,
                                                split_cash_start_date: new_downpayment_due_date,
                                                split_cash_end_date : number_of_splits > 1  ? end_date : new_downpayment_due_date
                                            }
                                        }

                                        setRFDPupdateData(updateData);

                                        RFandDPForm.setFieldsValue({
                                            'downpayment_due_date' : new_downpayment_due_date,
                                            'start_date' : new_downpayment_due_date,
                                            'end_date': number_of_splits > 1  ? end_date : moment(e)
                                        });

                                    }}
                                />
                            </Form.Item>
                        </Col>
                        <Col xl={reservation.payment_terms_type == 'in_house' ? 8 : 10}>
                            <Form.Item name="reservation_fee_amount" label="Reservation fee amount" 
                                rules={[{required: true}]}
                                initialValue={RFDPupdateData.reservation_fee_amount}
                            >
                                <InputNumber 
                                    style={{width: '100%'}} 
                                    placeholder="&#8369; 0.0" {...currencyFormatter} 
                                    onChange={(e) => setRFDPupdateData({ ...RFDPupdateData, reservation_fee_amount: e })}
                                />
                            </Form.Item>
                        </Col>
                    </Row>


                    <Divider orientation="left">{reservation.payment_terms_type == 'in_house' ? 'Downpayment' : 'Split'}</Divider>

                    <Row gutter={[16,16]}>
                        
                        <Col xl={reservation.payment_terms_type == 'in_house' ? 7 : 8}>
                            <Form.Item name="downpayment_due_date" label={ reservation.payment_terms_type == 'in_house' ? 'Downpayment due date' : 'Split due date' } 
                                rules={[{required: true}]}
                                initialValue={getDownpaymentDueDate(RFDPupdateData)}
                            >
                                <DatePicker 
                                    disabledDate={(current) => {
                                        return current && current <= moment(RFDPupdateData.reservation_fee_date)
                                    }}
                                    onChange={(e) => {

                                        let number_of_splits = 1;
                                        let updateData = {};

                                        if( reservation.payment_terms_type === 'in_house' ) {
                                            number_of_splits = (RFDPupdateData.split_downpayment) ? RFDPupdateData.number_of_downpayment_splits : 1;
                                        } else {
                                            number_of_splits = (RFDPupdateData.split_cash) ? RFDPupdateData.number_of_cash_splits : 1;
                                        }

                                        const end_date = moment(e).clone().add(number_of_splits - 1, 'months');

                                        if( reservation.payment_terms_type === 'in_house' ) {
                                            updateData = { ...RFDPupdateData, 
                                                downpayment_due_date: e ? moment(e) : undefined,
                                                split_downpayment_start_date: moment(e),
                                                split_downpayment_end_date : number_of_splits > 1  ? end_date : moment(e)
                                            }
                                        } else {
                                            updateData = { ...RFDPupdateData, 
                                                downpayment_due_date: e ? moment(e) : undefined,
                                                split_cash_start_date: moment(e),
                                                split_cash_end_date : number_of_splits > 1  ? end_date : moment(e)
                                            }
                                        }

                                        setRFDPupdateData(updateData);

                                        RFandDPForm.setFieldsValue({
                                            'start_date' : moment(e),
                                            'end_date': number_of_splits > 1  ? end_date : moment(e)
                                        });
                                    }}
                                />
                            </Form.Item>
                        </Col>
                        
                        { reservation.payment_terms_type == 'in_house' && 
                            <>
                                {/* <Col xl={8}>
                                    <Form.Item name="downpayment_percentage" label="% of Downpayment"
                                        rules={[{required: true}]}
                                        initialValue={downPaymentPercentage}
                                    >
                                        <InputNumber style={{width: '100%'}}
                                            formatter={value => `${value}%`}
                                            parser={value => value.replace('%', '')}
                                            // precision={1}
                                            min={0}
                                            max={100}
                                            stringMode
                                            onChange={(e) => {

                                                const nsp_computed = getComputedNSP();
                                    
                                                const percentage = parseFloat(e) / 100;
                                                const total = nsp_computed * percentage;

                                                setRFDPupdateData({ ...RFDPupdateData, 
                                                    downpayment_percentage: e,
                                                    downpayment_amount: total.toFixed(2)
                                                });

                                                RFandDPForm.setFieldsValue({
                                                    'downpayment_amount' : total.toFixed(2)
                                                });

                                            }}
                                        />
                                    </Form.Item>
                                </Col> */}
                                <Col xl={8}>
                                    <Form.Item name="downpayment_amount" label="Downpayment"
                                        rules={[{required: true}]}
                                        initialValue={RFDPupdateData.downpayment_amount}
                                    >
                                        <InputNumber style={{width: '100%'}} placeholder="&#8369; 0.0" {...currencyFormatter}
                                            min={0}
                                            max={RFDPupdateData.total_selling_price}
                                            onChange={(e) => {

                                                const nsp_computed = getComputedNSP();

                                                const total = (e / nsp_computed) * 100;

                                                setRFDPupdateData({ ...RFDPupdateData, 
                                                    downpayment_amount: e,
                                                    downpayment_percentage: total
                                                });

                                                RFandDPForm.setFieldsValue({
                                                    'downpayment_percentage' : total
                                                });

                                            }}
                                        />
                                    </Form.Item>
                                </Col>
                            </>
                        }

                        <Col xl={8}>
                            <Form.Item name="number_of_splits" label="Number of Splits"
                                rules={[{required: true}]}
                                initialValue={reservation.payment_terms_type == 'in_house' ? RFDPupdateData.number_of_downpayment_splits : RFDPupdateData.number_of_cash_splits}
                            >
                                <InputNumber style={{width: '100%'}} placeholder="0"
                                    min={1}
                                    onChange={(e) => {

                                        let number_of_splits = e;
                                        let form_start_date = null;

                                        if( reservation.payment_terms_type === 'in_house' ) {
                                            form_start_date = RFDPupdateData.split_downpayment_start_date;
                                        } else {
                                            form_start_date = RFDPupdateData.split_cash_start_date;
                                        }

                                        number_of_splits = number_of_splits <= 0 ? 1 : number_of_splits;
                                        const end_date = moment(form_start_date).clone().add(number_of_splits - 1, 'months');

                                        let splitData = reservation.payment_terms_type == 'in_house' ? 
                                            { ...RFDPupdateData, 
                                                number_of_downpayment_splits: e,
                                                split_downpayment_end_date : end_date
                                            } : 
                                            { ...RFDPupdateData, 
                                                number_of_cash_splits: e,
                                                split_cash_end_date : end_date
                                            };

                                        setRFDPupdateData(splitData);

                                        RFandDPForm.setFieldsValue({
                                            'end_date': end_date
                                        });
                                    }}
                                />
                            </Form.Item>
                        </Col>

                    </Row>

                    <Row gutter={[16,16]}>
                        <Col xl={reservation.payment_terms_type == 'in_house' ? 7 : 8}>
                            <Form.Item name="start_date" label="Start date" 
                                initialValue={getDownpaymentDueDate(RFDPupdateData)}
                            >
                                <DatePicker disabled />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item name="end_date" label="End date" 
                                initialValue={getDownpaymentEndDueDate(RFDPupdateData)}
                            >
                                <DatePicker disabled />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={[16,16]}>
                        <Col xl={12}>
                            <Form.Item name="promos" label="Promos">
                                <Select mode="multiple">
                                    <Select.Option value="GTP">GTP</Select.Option>
                                    <Select.Option value="GVP">GVP</Select.Option>
                                    <Select.Option value="QGP">QGP</Select.Option>
                                    <Select.Option value="SCP">SCP</Select.Option>
                                    <Select.Option value="SKYP">SKYP</Select.Option>
                                    <Select.Option value="GRP">GRP</Select.Option>
                                    <Select.Option value="TRP">TRP</Select.Option>
                                    <Select.Option value="TTGP">TTGP</Select.Option>
                                    <Select.Option value="3days">3days</Select.Option>
                                    <Select.Option value="Vat Inc">Vat Inc</Select.Option>
                                    <Select.Option value="NDP">NDP</Select.Option>
                                    <Select.Option value="CLP">CLP</Select.Option>
                                    <Select.Option value="NPP">NPP</Select.Option>
                                    <Select.Option value="FTT">FTT</Select.Option>
                                    <Select.Option value="STP">STP</Select.Option>
                                    <Select.Option value="QGTP">QGTP</Select.Option>
                                    <Select.Option value="SUMP">SUMP</Select.Option>
                                </Select>
                            </Form.Item>
                        </Col>
                    </Row>

                    <Popconfirm
                        title="Are you sure you want to apply changes?"
                        onConfirm={() => RFandDPForm.submit()}
                        onCancel={() => console.log("cancell RF and DP update")}
                        okText="Yes"
                        cancelText="No"
                    >
                        <Button type="primary">Update</Button>
                    </Popconfirm>
                </Form>
            </Modal>
        </>
    )
}

export default PaymentTerms;