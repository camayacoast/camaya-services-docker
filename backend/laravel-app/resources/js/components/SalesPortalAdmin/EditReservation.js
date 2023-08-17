import React from 'react'
import { useParams, useNavigate } from "react-router-dom";
import { Spin, Card, Row, Col, Space, Form, Input, Divider, InputNumber, DatePicker, Button, Select, Tabs, Alert, Checkbox, message, Table, Modal } from 'antd'
const { TabPane } = Tabs;

import SalesAdminPortalService from 'services/SalesAdminPortal'

import factor_rate_table from 'common/factor_rate_table.json'
import moment, { min } from 'moment';

let factor_percentage = (typeof process.env.FACTOR_PERCENTAGE !== 'undefined') ? parseFloat(process.env.FACTOR_PERCENTAGE) : 7;
let DefaultPenaltyDiscountPercentage = 0;
let formValues = {};

const numberWithCommas = (x) => {
    if (!x) return false;
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

const RetentionFee = (props) => {
    return <span style={{width: '100%', textDecoration: props.withRetentionFee ? 'inherit' : 'line-through' }}>
                &#8369; {numberWithCommas(props.value ?? 0)}
           </span>
}

export default function Page(props) {
    
    const [editReservationForm] = Form.useForm();
    let { reservation_number } = useParams();

    const [reservation, setReservation] = React.useState({});
    const [selectedSubdivision, setSelectedSubdivision] = React.useState(null);
    const [selectedBlock, setSelectedBlock] = React.useState(null);
    const [selectedLot, setSelectedLot] = React.useState(null);
    const [tab, setTab] = React.useState('');
    const [amortizationSchedule, setAmortizationSchedule] = React.useState([]);
    const [monthlyAmortizationDueDate, setMonthlyAmortizationDueDate] = React.useState(null);
    const [originalPaymentTermsType, setOriginalPaymentTermsType] = React.useState('');


    // Common
    const [pricePerSqm, setPricePerSqm] = React.useState(0);
    const [totalSellingPrice, setTotalSellingPrice] = React.useState(0);
    const [netSellingPrice, setNetSellingPrice] = React.useState(0);
    const [NSPWithVAT, setNSPWithVAT] = React.useState(0);
    const [NSPComputed, setNSPComputed] = React.useState(0);
    const [twelvePercentVAT, setTwelvePercentVAT] = React.useState(0);
    const [withTwelvePercentVAT, setWithTwelvePercentVAT] = React.useState(false);
    const [reservationFeeAmount, setReservationFeeAmount] = React.useState(0);
    const [reservationFeeDate, setReservationFeeDate] = React.useState(null);

    // Cash
    const [totalAmountPayable, setTotalAmountPayable] = React.useState(0);
    const [discountPercentage, setDiscountPercentage] = React.useState(0);
    const [discountAmount, setDiscountAmount] = React.useState(0);
    const [retentionFee, setRetentionFee] = React.useState(0);
    const [withRetentionFee, setWithRetentionFee] = React.useState(false);
    const [cashSplitPaymentAmount, setCashSplitPaymentAmount] = React.useState(0);
    const [splitCash, setSplitCash] = React.useState(false);
    const [cashSplitNumber, setCashSplitNumber] = React.useState(null);

    // In-house
    const [factorRate, setFactorRate] = React.useState({});
    const [downpaymentPercentage, setDownpaymentPercentage] = React.useState(0);
    const [downpaymentAmount, setDownpaymentAmount] = React.useState(0);
    const [DPLessRF, setDPLessRF] = React.useState(0);
    const [totalBalanceInHouse, setTotalBalanceInHouse] = React.useState(0);
    const [monthlyAmortization, setMonthlyAmortization] = React.useState(0);
    const [splitDownpaymentAmount, setSplitDownpaymentAmount] = React.useState(0);
    const [splitDP, setSplitDP] = React.useState(false);
    const [downpaymentSplitNumber, setDownpaymentSplitNumber] = React.useState(null);
    const [downpaymentDueDate, setDownpaymentDueDate] = React.useState(null);

    const [subdivisionLabel, setSubdivisionLabel] = React.useState('Subdivision');
    const [lotLabel, setlotLabel] = React.useState('Lot');
    const [propertyType, setPropertyType] = React.useState('');

    const promoList = SalesAdminPortalService.realestatePromosViaField({column: 'status', value: 'Active'});
    const [viewReservationQuery, { IsLoading: viewReservationQueryIsLoading, reset: viewReservationQueryReset}] = SalesAdminPortalService.viewReservation();
    const [updateReservationQuery, {IsLoading: updateReservationQueryIsLoading, reset: updateReservationQueryReset}] = SalesAdminPortalService.updateReservation();
    const [deleteReservationQuery, {IsLoading: deleteReservationQueryIsLoading, reset: deleteReservationQueryReset}] = SalesAdminPortalService.deleteReservation();
    const salesClientsListQuery = SalesAdminPortalService.salesClientsList();
    const lotInventoryList = SalesAdminPortalService.lotCondoInventoryList();
    const reservationListQuery = SalesAdminPortalService.reservationList();
    const [interestRate, setInterestRate] = React.useState(factor_percentage / 100);
    const [recordSaveType, setRecordSaveType] = React.useState('default');
    const [finishLoading, setFinishLoading] = React.useState(true);

    React.useEffect( () => {

        editReservationForm.setFieldsValue({
            client: '',
            co_buyers: [],
            referrer: null,
            referrer_property: null,
            source: null,
            property_type: null,
            subdivision: null,
            reservation_fee_date: null,
            reservation_fee_amount: null,
            discount_percentage: null,
            downpayment_due_date: null,
            number_of_years: null,
            monthly_amortization_due_date: null,
            split_downpayment_end_date: null,
            split_downpayment_start_date: null,
            split_downpayment_end_date: null,
        });

        if(viewReservationQueryIsLoading) {
            return false;
        }

        viewReservationQuery({
            reservation_number: reservation_number
        }, {
            onSuccess: (res) => {

                DefaultPenaltyDiscountPercentage = res.data.default_penalty_discount_percentage;

                setReservation(res.data);

                let data = res.data;

                if( data.status !== 'draft' ) {
                    props.history.goBack();
                }

                let co_buyers = [];
                co_buyers.push(_.map(data.co_buyers, (i) => { return i.details.id }));

                let promos = [];
                promos.push(_.map(data.promos, (i) => { return `${i.promo_type}`}));

                if( data.payment_terms_type == 'cash' ) {
                    formValues = {
                        client: data.client.id,
                        co_buyers: data.co_buyers.length > 0 ? co_buyers[0] : [],
                        referrer: (data.referrer !== null) ? data.referrer.id : null,
                        referrer_property: (data.referrer_property !== null) ? data.reservation_number : null,
                        source: data.source,
                        property_type: data.property_type,
                        subdivision: data.subdivision,
                        reservation_fee_date: moment(data.reservation_fee_date),
                        reservation_fee_amount: data.reservation_fee_amount,
                        discount_percentage: data.discount_percentage,
                        remarks: data.remarks,
                        promos: data.promos.length > 0 ? promos[0] : [],
                        cash_split_number: (data.number_of_cash_splits == 0) ? null : data.number_of_cash_splits,
                        split_cash_start_date: data.split_cash_start_date != null ? moment(data.split_cash_start_date) : null,
                        split_cash_end_date: (data.split_cash_end_date != null) ? moment(data.split_cash_end_date) : null,
                    };
                } else {

                    const nsp_computed = (data.with_twelve_percent_vat ? data.net_selling_price_with_vat : data.net_selling_price);
                    const downpayment_percentage = (data.downpayment_amount / nsp_computed) * 100;
                    setDownpaymentPercentage(downpayment_percentage);

                    formValues = {
                        client: data.client.id,
                        co_buyers: data.co_buyers.length > 0 ? co_buyers[0] : [],
                        referrer: (data.referrer !== null) ? data.referrer.id : null,
                        referrer_property: (data.referrer_property !== null) ? data.reservation_number : null,
                        source: data.source,
                        property_type: data.property_type,
                        subdivision: data.subdivision,
                        reservation_fee_date: moment(data.reservation_fee_date),
                        reservation_fee_amount: data.reservation_fee_amount,
                        discount_percentage: data.discount_percentage,
                        downpayment_percentage: downpayment_percentage,
                        downpayment_amount: data.downpayment_amount,
                        downpayment_due_date: moment(data.downpayment_due_date),
                        number_of_years: data.number_of_years,
                        monthly_amortization_due_date: (data.monthly_amortization_due_date != null) ? moment(data.monthly_amortization_due_date) : null,
                        downpayment_split_number: data.number_of_downpayment_splits == 0 ? null : data.number_of_downpayment_splits,
                        split_downpayment_start_date: data.split_downpayment_start_date != null ? moment(data.split_downpayment_start_date) : null,
                        split_downpayment_end_date: data.split_downpayment_end_date != null ? moment(data.split_downpayment_end_date) : null,
                        remarks: data.remarks,
                        promos: data.promos.length > 0 ? promos[0] : [],
                    };
                }

                editReservationForm.setFieldsValue(formValues);
                
                // Payment terms tab
                setTab(data.payment_terms_type);
                setOriginalPaymentTermsType(data.payment_terms_type);

                // Common 
                setReservationFeeAmount(data.reservation_fee_amount);

                setTotalSellingPrice(data.total_selling_price);
                setDiscountPercentage(data.discount_percentage);
                setDiscountAmount(data.discount_amount);

                setNetSellingPrice(data.net_selling_price);
                setWithTwelvePercentVAT(data.with_twelve_percent_vat);
                setTwelvePercentVAT(data.twelve_percent_vat);
                setNSPWithVAT(data.net_selling_price_with_vat);

                if( data.payment_terms_type == 'cash' ) {

                    setWithRetentionFee(data.with_five_percent_retention_fee);
                    setSplitCash(data.split_cash);
                    setCashSplitNumber(data.number_of_cash_splits);
                    setCashSplitPaymentAmount(data.split_payment_amount);

                } else {

                    setFactorRate({ year: data.number_of_years, rate: _.find(factor_rate_table[factor_percentage], i => i.year == data.number_of_years).rate});
                    factor_percentage = data.interest_rate;

                    setDownpaymentAmount(data.downpayment_amount);
                    setDPLessRF(data.downpayment_amount_less_RF);
                    setDownpaymentDueDate(moment(data.downpayment_due_date));
                    setTotalBalanceInHouse(data.total_balance_in_house);
                    setMonthlyAmortization(data.monthly_amortization);
                    setMonthlyAmortizationDueDate((data.monthly_amortization_due_date != null) ? moment(data.monthly_amortization_due_date) : null);
                    setSplitDP(data.split_downpayment);
                    setDownpaymentSplitNumber(data.number_of_downpayment_splits);
                    setSplitDownpaymentAmount(data.split_downpayment_amount);
                }

                // Unit Details
                setPropertyType(data.property_type.toLowerCase());
                setSelectedSubdivision(data.subdivision);
                let prev = editReservationForm.getFieldsValue();
                setTimeout(function(){
                    editReservationForm.setFieldsValue({...prev,
                        block: data.block,
                    });
                    setSelectedBlock(data.block);
                    setTimeout(function() {
                        prev = editReservationForm.getFieldsValue();
                        editReservationForm.setFieldsValue({...prev,
                            lot: data.lot,
                        });
                        setSelectedLot(data.lot);
                        setFinishLoading(false);
                    }, 2000)
                }, 2000);
            },
            onError: (e) => {
                console.log(e)
                if (e.error == 'RESERVATION_NOT_FOUND') {
                    props.history.goBack();
                }
            },
        });

    },[]);

    const generateAmortizationSchedule = (total_balance_in_house, monthly_amortization, year) => {
        // $interest = $r->total_balance_in_house * 0.07 / 12;
        // $principal = $r->monthly_amortization - $interest;
        // $balance = $r->total_balance_in_house - $principal;

        let interest = total_balance_in_house * interestRate / 12;
        let principal = monthly_amortization - interest;
        let balance = total_balance_in_house - principal;

        // interest = interest.toFixed(2);
        // principal = principal.toFixed(2);
        // balance = balance.toFixed(2);

        let amortization_schedule = [];

        // $initial_date = Carbon::parse($request->monthly_amortization_due_date)->setTimezone('Asia/Manila');
        // $amortization_date = Carbon::parse($request->monthly_amortization_due_date)->setTimezone('Asia/Manila');

        const initial_date = moment(monthlyAmortizationDueDate);
        let amortization_date = moment(monthlyAmortizationDueDate);

        for (var i = 1; i <= (year * 12); i++) {

            if (initial_date.date() > amortization_date.date() && amortization_date.month() != 1) {
                if (initial_date.date() == 31 && [1, 3, 5, 8, 10].includes(amortization_date.month())) {
                    amortization_date.set('date', 30);
                } else {
                    amortization_date.set('date', initial_date.date());
                }
            }

            amortization_schedule.push({
                number: i,
                date_due: moment(amortization_date).format('M/D/YYYY'),
                amount: numberWithCommas(monthly_amortization.toFixed(2)),
                principal: numberWithCommas(principal.toFixed(2)),
                interest: numberWithCommas(interest.toFixed(2)),
                balance: numberWithCommas(balance.toFixed(2))
            });

            interest = balance.toFixed(2) * interestRate / 12;
            principal = monthly_amortization - interest.toFixed(2);
            balance = balance.toFixed(2) - principal.toFixed(2);

            // let add_month;

            // If Jan, calculate february months
            if (amortization_date.month() == 0) {
                if (initial_date.date() >= 29) {
                    if (moment([amortization_date.year()]).isLeapYear()) {
                        amortization_date = moment().set({ 'year':amortization_date.year(), 'month':amortization_date.month() + 1, 'date': 29});
                    } else {
                        amortization_date = moment().set({ 'year':amortization_date.year(), 'month':amortization_date.month() + 1, 'date': 28});
                    }
                } else {
                    amortization_date.add(1, 'months');
                }
            } else {
                amortization_date.add(1, 'months');
            }   
        }

        setAmortizationSchedule(amortization_schedule);
    }

    /**
     * Update net selling price
     */

    const updateComputation = () => {

        // VAT
        if (totalSellingPrice) {

            // Common
            const net_selling_price = totalSellingPrice - discountAmount;
            const twelve_percent_vat = net_selling_price * .12;
            const nsp_with_vat = net_selling_price + twelve_percent_vat;
            const nsp_computed = (withTwelvePercentVAT ? nsp_with_vat : net_selling_price);

            // Cash
            const retention_fee = nsp_computed * .05;

            const total_amount_payable = nsp_computed - ((withRetentionFee && tab == 'cash' ? retention_fee : 0) + parseFloat(reservationFeeAmount));

            // In-house
            const total_balance_in_house = nsp_computed - (downpaymentAmount > 0 ? downpaymentAmount : reservationFeeAmount);
            const monthly_amortization = total_balance_in_house * parseFloat(factorRate.rate ?? 0);

            /**
             * Update states
             */

            const paymentTermsValues = editReservationForm.getFieldsValue();
            let downpayment_amount = nsp_computed * (paymentTermsValues.downpayment_percentage/100);

            downpayment_amount = isNaN(downpayment_amount) ? 0 : downpayment_amount;

            editReservationForm.setFieldsValue({ ...paymentTermsValues,
                downpayment_amount: downpayment_amount.toFixed(2),
                downpayment_percentage: paymentTermsValues.downpayment_percentage,
            });

            // Common
            setNetSellingPrice(net_selling_price.toFixed(2));
            setNSPWithVAT(nsp_with_vat.toFixed(2));
            setTwelvePercentVAT(twelve_percent_vat.toFixed(2));
            setNSPComputed(nsp_computed.toFixed(2));

            // Cash
            setRetentionFee(retention_fee.toFixed(2));
            setTotalAmountPayable(total_amount_payable.toFixed(2));
            
            // In-house
            setDPLessRF(((nsp_computed * (downpaymentPercentage/100)) - (downpaymentPercentage ? reservationFeeAmount:0)).toFixed(2));
            setTotalBalanceInHouse((total_balance_in_house).toFixed(2));

            const years = editReservationForm.getFieldValue('number_of_years');
            if (years && !factorRate.year) {
                setFactorRate({ year: years, rate: _.find(factor_rate_table[factor_percentage], i => i.year == years).rate});
            }
            setMonthlyAmortization(monthly_amortization.toFixed(2) ?? 0);

            if (tab == 'in_house') {
                generateAmortizationSchedule(total_balance_in_house, monthly_amortization, factorRate.year);
            }
        } else {
            // handleResetStates();
            setTotalSellingPrice(0);
        }

    }

    /**
     * Update discounts
     */

    const updateDiscountPercentage = () => {

        if (totalSellingPrice) {
            const paymentTermsValues = editReservationForm.getFieldsValue();

            const total = totalSellingPrice * (discountPercentage/100);
            // Update states
            setDiscountAmount(total.toFixed(2));

            // Update form values
            editReservationForm.setFieldsValue({ ...paymentTermsValues,
                discount_amount: total.toFixed(2),
            });
        }

    }

    const updateDiscountAmount = () => {

        const paymentTermsValues = editReservationForm.getFieldsValue();
    
        if (totalSellingPrice) {

            const total = Math.round((discountAmount/totalSellingPrice) * 100);
            // Update states
            setDiscountPercentage(total);

            // Update form values
            editReservationForm.setFieldsValue({ ...paymentTermsValues,
                discount_percentage: total,
            });
        }

    }

    /**
     * Update DPs
     */

     const updateDownpaymentPercentage = () => {
        
        if (totalSellingPrice) {
            
            const paymentTermsValues = editReservationForm.getFieldsValue();
            
            const net_selling_price = totalSellingPrice - discountAmount;
            const twelve_percent_vat = net_selling_price * .12;
            const nsp_with_vat = net_selling_price + twelve_percent_vat;
            const nsp_computed = (withTwelvePercentVAT ? nsp_with_vat : net_selling_price);

            const total = nsp_computed * (downpaymentPercentage/100);
            
            // Update states
            setDownpaymentAmount(total.toFixed(2));
            setDPLessRF((total - reservationFeeAmount).toFixed(2));

            // Update form values
            editReservationForm.setFieldsValue({ ...paymentTermsValues,
                downpayment_amount: total.toFixed(2),
            });
        }

    }

    const updateDownpaymentAmount = () => {

        if (totalSellingPrice) {
            const paymentTermsValues = editReservationForm.getFieldsValue();
            const nsp_computed = (withTwelvePercentVAT ? NSPWithVAT : netSellingPrice);

            const total = Math.round((downpaymentAmount/nsp_computed) * 100);
            // Update states
            setDownpaymentPercentage(total);

            // Update form values
            editReservationForm.setFieldsValue({ ...paymentTermsValues,
                downpayment_percentage: total,
            });
        }

    }

    // React.useEffect( () => {
    //     if (selectedSubdivision && selectedBlock && selectedLot) {
    //         const i = _.find(lotInventoryList.data, i => (i.subdivision == selectedSubdivision && i.block == selectedBlock && i.lot == selectedLot) );
    //         const prev = editReservationForm.getFieldsValue();

    //         let total_selling_price = 0;

    //         if (i) {

    //             let tsp_rounded = Math.round(parseFloat(i.area) * parseFloat(pricePerSqm));
    //             let tsp_floor = Math.floor(parseFloat(i.area) * parseFloat(pricePerSqm));

    //             let tsp_final = (tsp_rounded % 10 > 0) ? (tsp_rounded - (tsp_rounded % 10)) : tsp_rounded;

    //             total_selling_price = (i.area && pricePerSqm) ? tsp_final : '';

    //             editReservationForm.setFieldsValue({ ...prev,
    //                 price_per_sqm: pricePerSqm ?? '',
    //                 total_selling_price: total_selling_price ?? 0
    //             });

    //             setTotalSellingPrice(total_selling_price);
    //         }
    //     } else {
    //         setTotalSellingPrice(0);
    //     }

    // }, [pricePerSqm]);

    React.useEffect( () => {

        if (selectedSubdivision && selectedBlock && selectedLot) {
            
            const i = _.find(lotInventoryList.data, i => (i.subdivision == selectedSubdivision && i.block == selectedBlock && i.lot == selectedLot) );
            const prev = editReservationForm.getFieldsValue();

            let total_selling_price = 0;

            if (i) {

                let tsp_rounded = Math.round(parseFloat(i.area) * parseFloat(i.price_per_sqm));
                let tsp_floor = Math.floor(parseFloat(i.area) * parseFloat(i.price_per_sqm));

                let tsp_final = (tsp_rounded % 10 > 0) ? tsp_floor : tsp_rounded;

                total_selling_price = (i.area && i.price_per_sqm) ? tsp_final : '';

                reservation.type = '';

                editReservationForm.setFieldsValue({ ...prev,
                    lot_type: i.type ?? '',
                    area: i.area ?? '',
                    price_per_sqm: i.price_per_sqm ?? '',
                    total_selling_price: total_selling_price ?? 0
                });

                setPricePerSqm(i.price_per_sqm);
                setTotalSellingPrice(total_selling_price);
            }
            
        } else {
            setTotalSellingPrice(0);
        }

    }, [selectedSubdivision, selectedBlock, selectedLot]);

    React.useEffect( () => {
 
        setSelectedBlock('');
        setSelectedLot('');
        
        setTotalSellingPrice(0);

        const prev = editReservationForm.getFieldsValue();

        editReservationForm.setFieldsValue({ ...prev,
            block: '',
            lot: '',
            lot_type: '',
            area: '',
            discount_amount: '',
            downpayment_amount: '',
        });

    }, [selectedSubdivision]);

    React.useEffect( () => {

        const prev = editReservationForm.getFieldsValue();

        editReservationForm.setFieldsValue({ ...prev,
            lot: '',
            lot_type: '',
            area: '',
            discount_amount: '',
            downpayment_amount: '',
        });

        setSelectedLot('');
        setTotalSellingPrice(0);

    }, [selectedBlock]);

    React.useEffect( () => {

        if (!selectedLot) {
            const prev = editReservationForm.getFieldsValue();

            editReservationForm.setFieldsValue({ ...prev,
                lot_type: '',
                area: '',
                discount_amount: '',
                downpayment_amount: '',
            });

            setTotalSellingPrice(0);
        }

    }, [selectedLot]);

    // DISCOUNTS

    React.useEffect( () => {

        updateDiscountPercentage();
        updateComputation();

    }, [discountPercentage]);

    React.useEffect( () => {

        updateDiscountAmount();
        updateComputation();

    }, [discountAmount]);

    // DPs

    React.useEffect( () => {

        updateDownpaymentPercentage();
        updateComputation();

    }, [downpaymentPercentage]);

    React.useEffect( () => {

        updateDownpaymentAmount();
        updateComputation();

    }, [downpaymentAmount]);

    // Updates on total selling price

    React.useEffect( () => {

        updateDownpaymentPercentage();

    }, [withTwelvePercentVAT]);

    React.useEffect( () => {

        if (downpaymentSplitNumber && downpaymentSplitNumber >= 0) {
            setSplitDownpaymentAmount((DPLessRF / downpaymentSplitNumber).toFixed(2));
        } else {
            setSplitDownpaymentAmount(0);
        }

    }, [downpaymentSplitNumber, totalBalanceInHouse]);

    React.useEffect( () => {

        if (cashSplitNumber && cashSplitNumber >= 0) {
            const net_selling_price = totalSellingPrice - discountAmount;
            const twelve_percent_vat = net_selling_price * .12;
            const nsp_with_vat = net_selling_price + twelve_percent_vat;
            const nsp_computed = (withTwelvePercentVAT ? nsp_with_vat : net_selling_price);

            // Cash
            const retention_fee = nsp_computed * .05;
            const total_amount_payable = nsp_computed - ((withRetentionFee && tab == 'cash' ? retention_fee : 0) + parseFloat(reservationFeeAmount));
            
            setCashSplitPaymentAmount((total_amount_payable / cashSplitNumber).toFixed(2));
        } else {
            setCashSplitPaymentAmount(0);
        }

    }, [cashSplitNumber, totalAmountPayable]);

    React.useEffect( () => {
        updateComputation();
    },[monthlyAmortizationDueDate]);

    React.useEffect( () => {
        
        // console.log(reservationFeeDate);

        if (reservationFeeDate) {

            const downpayment_due_date = reservationFeeDate.clone().add(30, 'days');
            const prev = editReservationForm.getFieldsValue();

            const split_downpayment_end_date = downpayment_due_date.clone().add(downpaymentSplitNumber - 1, 'months');

            const split_cash_start_date = reservationFeeDate.clone().add(1, 'months');
            const split_cash_end_date = split_cash_start_date.clone().add(cashSplitNumber - 1, 'months');

            const monthly_amortization_due_date = splitDP ? split_downpayment_end_date.clone().add(1, 'months') : downpayment_due_date.clone().add(1, 'months');

            if (downpayment_due_date.date() > monthly_amortization_due_date.date() && monthly_amortization_due_date.month() != 1) {
                if (downpayment_due_date.date() == 31 && [1, 3, 5, 8, 10].includes(monthly_amortization_due_date.month())) {
                    monthly_amortization_due_date.set('date', 30);
                } else {
                    monthly_amortization_due_date.set('date', downpayment_due_date.date());
                }
            }

            if (splitDP) {

                if (downpayment_due_date.date() > split_downpayment_end_date.date() && split_downpayment_end_date.month() != 1) {
                    if (downpayment_due_date.date() == 31 && [1, 3, 5, 8, 10].includes(split_downpayment_end_date.month())) {
                        split_downpayment_end_date.set('date', 30);
                    } else {
                        split_downpayment_end_date.set('date', downpayment_due_date.date());
                    }
                }

            }

            if (splitCash) {
                if (reservationFeeDate.date() > split_cash_end_date.date() && split_cash_end_date.month() != 1) {
                    if (reservationFeeDate.date() == 31 && [1, 3, 5, 8, 10].includes(split_cash_end_date.month())) {
                        split_cash_end_date.set('date', 30);
                    } else {
                        split_cash_end_date.set('date', reservationFeeDate.date());
                    }
                }
            }

            editReservationForm.setFieldsValue({ ...prev, 

                split_cash_start_date: splitCash ? split_cash_start_date : null,
                split_cash_end_date: splitCash ? split_cash_end_date : null,

                downpayment_due_date: downpayment_due_date,
                split_downpayment_start_date: splitDP ? downpayment_due_date : null,
                split_downpayment_end_date: splitDP ? split_downpayment_end_date : null,
                monthly_amortization_due_date: monthly_amortization_due_date,
            });

            setMonthlyAmortizationDueDate(monthly_amortization_due_date);
        }


    }, [reservationFeeDate, splitDP, downpaymentSplitNumber, splitCash, cashSplitNumber]);

    React.useEffect( () => {

        updateDiscountPercentage();
        updateDownpaymentPercentage();
        updateComputation();

    }, [
        // Common
        totalSellingPrice,
        withTwelvePercentVAT,
        reservationFeeAmount,
        tab,

        // Cash
        withRetentionFee,

        // In-house
        factorRate.year
    ]);

    /**
     * Common: totalSellingPrice, reservationFeeAmount, withTwelvePercentVAT, tab
     * Excluded common: discountAmount, discountPercentage
     * Cash: withRetentionFee, splitCash, cashSplitNumber
     * 
     * In-house: factorRate.year, monthlyAmortization, splitDP, downpaymentSplitNumber
     * Excluded in-house: downpaymentAmount, downpaymentPercentage
     */ 

// END OF USE EFFECTS

    const currencyFormatter = {
        formatter: value => `₱ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ','),
        parser: value => value.replace(/\₱\s?|(,*)/g, '')
    };


    const getLotStatus = () => {
        const i = _.find(lotInventoryList.data, i => (i.subdivision == selectedSubdivision && i.block == selectedBlock && i.lot == selectedLot) );
        
        if (!i) return '';

        return _.capitalize(i.status);
    }

    const getPricePerSqm = () => {
        const i = _.find(lotInventoryList.data, i => (i.subdivision == selectedSubdivision && i.block == selectedBlock && i.lot == selectedLot) );
        
        if (!i) return '';

        return (i.price_per_sqm);
    }

    const handleEditReservationFinish = (values) => {

        let newValue = {
            ...values,
            payment_terms_type: tab,
            reservation_number: reservation_number,
            record_save_type: recordSaveType,
            factor_percentage: factor_percentage,
            with_twelve_percent_vat: withTwelvePercentVAT,
            split_downpayment: splitDP,
            split_cash: splitCash,
        }

        if( tab == 'in_house' ) {
            newValue = {
                ...newValue,
                factor_rate: factorRate.rate,
            }
        } else {
            newValue = {
                ...newValue,
                with_retention_fee: withRetentionFee,
            }
        }

        console.log(newValue);

        if( updateReservationQueryIsLoading ) {
            return false;
        }

        updateReservationQuery(newValue, {
            onSuccess: (response) => {
                // console.log(response);
                if( recordSaveType == 'draft' ) {
                    Modal.success({
                        title: "Successfully updated draft reservation!",
                        content: ''
                    });

                } else {
                    Modal.success({
                        title: "Successfully created new Reservation!",
                        content: <div><Button onClick={() => {
                            window.location.href = process.env.APP_URL + '/sales-admin-portal/view-reservation/' + reservation_number;
                            Modal.destroyAll();
                        }}>Go to Reservation</Button></div>,
                        onOk: () => {
                            window.location.href = process.env.APP_URL + '/sales-admin-portal/reservation-documents';
                            Modal.destroyAll();
                        }
                    });
                }
                
            },
            onError: (e) => {
                if (e.message == 'Unauthorized.') {
                    message.error("You don't have access to do this action.");
                }
                message.info(e.message);
                updateReservationQueryReset();
            }
        })
        
    }

    const resetPaymentTerms = () => {

        let params = {};

        if( tab == 'cash' ) {

            params = {
                reservation_fee_date: null,
                split_cash_start_date: null,
                split_cash_end_date: null,
                cash_split_number: null,
            };
            
            setRetentionFee(0);
            setWithRetentionFee(false);
            setSplitCash(false);
            setCashSplitNumber(null);
        } else {
            params = {
                reservation_fee_date: null,
                downpayment_percentage: null,
                downpayment_due_date: null,
                number_of_years: null,
                monthly_amortization_due_date: null,
                downpayment_split_number: null,
                split_downpayment_start_date: null,
                split_downpayment_end_date: null,
            };

            setDownpaymentPercentage(0);
            setDownpaymentAmount(0);
            setDownpaymentDueDate(null);
            setDPLessRF(0);
            setSplitDP(false);
            setMonthlyAmortization(0);
            setDownpaymentSplitNumber(null);

        }

        setReservationFeeDate(null);
        editReservationForm.setFieldsValue(params);
    }

    const handleResetStates = () => {

        // Common
        setTotalSellingPrice(0);
        setNetSellingPrice(0);
        setNSPWithVAT(0);
        setNSPComputed(0);
        setTwelvePercentVAT(0);
        setWithTwelvePercentVAT(false);
        // setReservationFeeAmount(0);

        // Cash
        setTotalAmountPayable(0);
        // setDiscountPercentage(0);
        setDiscountAmount(0);
        setRetentionFee(0);
        setWithRetentionFee(false);
        setCashSplitPaymentAmount(0);
        // setSplitCash(false);

        // In-house
        setFactorRate({});
        // setDownpaymentPercentage(0);
        setDownpaymentAmount(0);
        setDPLessRF(0);
        setTotalBalanceInHouse(0);
        setMonthlyAmortization(0);
        setSplitDownpaymentAmount(0);
        // setSplitDP(false);
        setAmortizationSchedule([]);
    }

    const handlePropertyType = (value) => {

        setSelectedSubdivision('');
        setSelectedBlock(null);
        setSelectedLot(null);

        handleResetStates();

        editReservationForm.setFieldsValue({
            subdivision: '',
            block: '',
            unit: '',
            price_per_sqm: null,
        });

        let blankSub = document.getElementById('blank-subdivision');
        if( blankSub !== null ) {
            document.getElementById('blank-subdivision').click();
        }
        
        if( value === 'Lot' ) {
            setSubdivisionLabel('Subdivision');
            setlotLabel('Lot');
            setPropertyType('lot');
        } else {
            setSubdivisionLabel('Project');
            setlotLabel('Unit');
            setPropertyType('condo');
        }
    }

    const handleSubdivision = (value) => {
        handleResetStates();
        setSelectedSubdivision(value);
    }

    const handleBlock = (value) => {
        handleResetStates();
        setSelectedBlock(value);
    }

    const handleLot = (value) => {
        setSelectedLot(value);
    }

    const deleteDraftRecord = () => {
        Modal.confirm({
            title: "Are you sure you want to delete this draft?",
            okText: 'Ok',
            cancelText: 'Cancel',
            onOk: () => {

                if( deleteReservationQueryIsLoading ) {
                    return false;
                }

                deleteReservationQuery({
                    reservation_number: reservation_number
                }, {
                    onSuccess: (response) => {

                        let data = response.data;

                        if( typeof data.reservation_number !== 'undefined' ) {
                            message.success('Draft reservation is successfully deleted.');
                            window.location.href = process.env.APP_URL + '/sales-admin-portal/reservation-documents';
                        }

                    },
                    onError: (e) => {
                        message.info(e.message);
                        deleteReservationQueryReset();
                    }
                });
            }
        });
    }

    return(
        <div className="mt-4">

            <Form form={editReservationForm} onFinish={handleEditReservationFinish} layout="vertical" style={{clear: 'both'}} initialValues={formValues}>

                <Space style={{float: 'right'}}>
                    <Button disabled={finishLoading} htmlType="submit" onClickCapture={() => setRecordSaveType('draft')}>Save as draft</Button>
                    <Button disabled={finishLoading}type="primary" htmlType="submit" onClickCapture={() => setRecordSaveType('default')}>Save</Button>
                    <Button disabled={finishLoading} type='primary' danger onClick={() => deleteDraftRecord()}>Delete</Button>
                </Space>

                <Row gutter={[8,8]}>
                    <Col xl={10}>
                        <Form.Item name="client" label="Client">
                            <Select
                                showSearch
                                style={{ width: '100%' }}
                                placeholder={`Select client`}
                                optionFilterProp="children"
                                size="large"
                                filterOption={(input, option) => {
                                    if( typeof option.children !== 'undefined' ) {
                                        return option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                    }
                                }}
                            >
                                { salesClientsListQuery.data &&
                                    salesClientsListQuery.data.map( (item, key) => (
                                        <Select.Option key={key} value={item.id}>{`${item.first_name} ${item.last_name} ${item.email}`}</Select.Option>
                                    ))
                                }
                            </Select>
                        </Form.Item>

                        <Form.Item name="co_buyers" label="Co-buyers">
                            <Select
                                showSearch
                                style={{ width: '100%' }}
                                placeholder={`Select co-buyers`}
                                optionFilterProp="children"
                                mode="multiple"
                                size="large"
                                filterOption={(input, option) => {
                                    if( typeof option.children !== 'undefined' ) {
                                        return option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                    }
                                }}
                            >
                                { salesClientsListQuery.data &&
                                    salesClientsListQuery.data.map( (item, key) => (
                                        <Select.Option key={key} value={item.id}>{`${item.first_name} ${item.last_name} ${item.email}`}</Select.Option>
                                    ))
                                }
                            </Select>
                        </Form.Item>
                      </Col>

                      <Col xl={12}>              
                        <Card>
                            <Form.Item name="referrer" label="Existing Client (Refer-A-Friend)">
                                <Select
                                    showSearch
                                    style={{ width: '100%' }}
                                    placeholder={`Select client`}
                                    optionFilterProp="children"
                                    // onSearch={onSearch}
                                    size="large"
                                    filterOption={(input, option) => {
                                        if( typeof option.children !== 'undefined' ) {
                                            return option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                        } 
                                    }}
                                >
                                    <Select.Option value=""></Select.Option>
                                    { salesClientsListQuery.data &&
                                        salesClientsListQuery.data.map( (item, key) => (
                                            <Select.Option key={key} value={item.id}>{`${item.first_name} ${item.last_name} ${item.email}`}</Select.Option>
                                        ))
                                    }
                                </Select>
                            </Form.Item>

                            <Form.Item name="referrer_property" label="Property Purchased">
                                <Select
                                    showSearch
                                    style={{ width: '100%' }}
                                    placeholder={`Select property`}
                                    optionFilterProp="children"
                                    size="large"
                                    filterOption={(input, option) => {
                                        if( typeof option.children !== 'undefined' ) {
                                            return option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                        } 
                                    }}
                                >
                                    <Select.Option value=""></Select.Option>
                                    { reservationListQuery.data &&
                                        reservationListQuery.data.map( (item, key) => {
                                            if(item.status != 'draft') {
                                                return <>
                                                    <Select.Option key={key} value={item.reservation_number}>
                                                        {`${item.subdivision} - ${item.lot} - ${item.block} - ${item.reservation_number} `} 
                                                    </Select.Option>
                                                </>
                                            }
                                            
                                        })
                                    }
                                </Select>
                            </Form.Item>
                        </Card>
                    </Col>
                </Row>

                <Divider orientation="left">Unit details</Divider>

                <Row gutter={[16,16]}>
                    <Col xl={6}>
                        <Form.Item name="source" label="Source" rules={[{
                            required: true
                        }]}>
                            <Input />
                        </Form.Item>
                    </Col>
                    <Col xl={6}>
                        <Form.Item name="property_type" label="Property type"rules={[{
                            required: true
                        }]}>
                            <Select onChange={e => handlePropertyType(e)}>
                                <Select.Option value="Lot">Lot</Select.Option>
                                <Select.Option value="Condo">Condo</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>
                    <Col xl={6}>
                        {
                            lotInventoryList.isLoading ?
                            <><Spin size="small" style={{marginRight: 12}}/>Loading current lot inventory...</>
                            :
                                <Form.Item name="subdivision" label={subdivisionLabel} rules={[{
                                    required: true
                                }]}>
                                    <Select onChange={e => handleSubdivision(e)}>
                                        <Select.Option value="" id="blank-subdivision"></Select.Option>
                                        {
                                            lotInventoryList.data &&
                                                _.uniqBy(lotInventoryList.data.map( i => { return { subdivision: i.subdivision, name: i.subdivision_name, property_type: i.property_type} }), 'subdivision')
                                                .map( (i, key) => {
                                                    if( i.property_type == propertyType ) {
                                                        return <Select.Option key={key} value={i.subdivision}>{i.subdivision} {i.name ? `- ${i.name}` : ''}</Select.Option>
                                                    }
                                                })
                                        }
                                    </Select>
                                </Form.Item>
                        }
                    </Col>
                    <Col xl={6} xs={0}></Col>
                    <Col xl={3}>
                        <Form.Item name="block" label="Block" rules={[{
                            required: true
                        }]}>
                            {/* <Input /> */}
                            <Select onChange={e => handleBlock(e)}>
                                <Select.Option id="blank-block" value=""></Select.Option>
                                {
                                    _.uniqBy(_.filter(lotInventoryList.data, i => i.subdivision == selectedSubdivision), 'block')
                                    .map( (i, key) => {
                                        return <Select.Option key={key} value={i.block}>Block {i.block}</Select.Option>
                                    })
                                }
                            </Select>
                        </Form.Item>
                    </Col>
                    <Col xl={3}>
                        <Form.Item name="lot" label={lotLabel} help={`Status: ${getLotStatus()}`} rules={[{
                            required: true
                        }]}>
                            {/* <Input /> */}
                            <Select onChange={e => handleLot(e)}>
                                <Select.Option id="blank-lot" value=""></Select.Option>
                                {
                                    _.filter(lotInventoryList.data, i => (i.subdivision == selectedSubdivision &&
                                     i.block == selectedBlock && _.includes(['available', 'reserved'], i.status)))
                                    .map( (i, key) => {
                                        return <Select.Option key={key} value={i.lot}>{lotLabel} {i.lot}</Select.Option>
                                    })
                                }
                            </Select>
                        </Form.Item>
                    </Col>
                    <Col xl={3}>
                        <Form.Item name="lot_type" label="Type">
                            <Input readOnly style={{background: 'none', border: 'none'}} />
                        </Form.Item>
                    </Col>
                    <Col xl={3}>
                        <Form.Item name="area" label="Approximate area">
                            <Input readOnly style={{background: 'none', border: 'none'}}  />
                        </Form.Item>
                    </Col>
                    <Col xl={3}>
                        <Form.Item name="price_per_sqm" label="Price per sqm">
                            <InputNumber onChange={e => setPricePerSqm(e)} style={{width: '100%', background: 'none', border: 'none'}} placeholder="&#8369; 0.0"
                                {...currencyFormatter}
                            />
                        </Form.Item>
                    </Col>
                    <Col xl={6}>
                        <Form.Item name="total_selling_price" label="Total selling price" help={<small>Inventory price per sqm: {numberWithCommas(getPricePerSqm())}</small>}>
                            <span style={{width: '100%'}} className="text-success">
                                &#8369; {numberWithCommas(totalSellingPrice ?? 0)}
                            </span>
                        </Form.Item>
                    </Col>
                </Row>

                <Divider orientation="left">Payment terms</Divider>

                <Row gutter={[16,16]}>
                    <Col xl={6}>
                        <Form.Item name="reservation_fee_date" label="Reservation fee date" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <DatePicker value={reservationFeeDate} onChange={e => setReservationFeeDate(e)} />
                        </Form.Item>
                    </Col>
                    <Col xl={6}>
                        <Form.Item name="reservation_fee_amount" label="Reservation fee amount" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <InputNumber onChange={e => setReservationFeeAmount(e)} style={{width: '100%'}} placeholder="&#8369; 0.0" {...currencyFormatter} />
                        </Form.Item>
                    </Col>
                </Row>

                { tab != '' &&  
                    <Tabs type="card" defaultActiveKey={tab} className="mb-5" 
                        onChange={(activeKey) => {
                            if(activeKey !== tab) {
                                resetPaymentTerms();
                            }
                            setTab(activeKey)
                        }}
                    >
                        <TabPane tab="CASH" key="cash">
                            <Alert className="mb-4" message={<>Your payment terms is&nbsp;<strong>CASH</strong>. Full payment will start 30 days from Reservation Date</>} />

                            <Row gutter={[16,16]}>
                                <Col xl={6}>
                                    <Form.Item name="total_selling_price" label="Total selling price">
                                        <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(totalSellingPrice ?? 0)}
                                        </span>
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <Form.Item id="discount_percentage1" name="discount_percentage" label="Less: Discount(%)">
                                        <InputNumber stringMode onChange={e => setDiscountPercentage(e)} style={{width: '100%'}}
                                            formatter={value => `${value}%`}
                                            parser={value => value.replace('%', '')}
                                            // precision={1}
                                            min={0}
                                            max={100}
                                        />
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <Form.Item id="discount_amount1" name="discount_amount" label="Discount">
                                        <InputNumber onChange={e => { e > totalSellingPrice ? totalSellingPrice : setDiscountAmount(e)}} style={{width: '100%'}} placeholder="&#8369; 0.0"
                                            {...currencyFormatter}
                                            min={0}
                                            max={totalSellingPrice}
                                        />
                                    </Form.Item>
                                </Col>
                            </Row>
                            <Row gutter={[16,16]}>
                                <Col xl={5}>
                                    <div>&nbsp;</div>
                                    <Form.Item label={<>Net selling price&nbsp;<strong>without</strong>&nbsp;VAT</>}>
                                        <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(netSellingPrice ?? 0)}
                                        </span>
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <div>&nbsp;</div>
                                    <Form.Item name="with_vat" style={{marginBottom: 0}}>
                                        <Checkbox checked={withTwelvePercentVAT} onChange={e => setWithTwelvePercentVAT(e.target.checked)} className="pb-2">12% VAT</Checkbox>
                                    </Form.Item>
                                    <span style={{width: '100%'}} className="text-success">
                                        &#8369; {withTwelvePercentVAT ? numberWithCommas(twelvePercentVAT ?? 0) : '-'}
                                    </span>
                                </Col>
                                <Col xl={5}>
                                    { withTwelvePercentVAT &&
                                        <>
                                            <div>&nbsp;</div>
                                            <Form.Item label={<>Net selling price&nbsp;<strong>with</strong>&nbsp;VAT</>}>
                                                <span style={{width: '100%'}} className="text-success">
                                                        &#8369; {numberWithCommas(NSPWithVAT ?? 0)}
                                                </span>
                                            </Form.Item>
                                        </>
                                    }
                                </Col>
                            </Row>
                            <Row gutter={[16,16]}>
                                <Col xl={4}>
                                    <Form.Item name="reservation_fee_amount" label="Less: Reservation fee">
                                        <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(reservationFeeAmount ?? 0)}
                                        </span>
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <Form.Item name="with_retention_fee" style={{marginBottom: 0}}>
                                        <Checkbox checked={withRetentionFee} onChange={e => setWithRetentionFee(e.target.checked)} className="pb-2">5% retention fee</Checkbox>
                                    </Form.Item>
                                    <RetentionFee value={retentionFee} withRetentionFee={withRetentionFee} />
                                </Col>

                                <Col xl={6}>
                                    <Form.Item label={<>Total amount payable</>}>
                                        <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(totalAmountPayable ?? 0)}
                                        </span>
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={[16,16]}>
                                    <Col xl={4}>
                                        <Checkbox checked={splitCash} onChange={e => {

                                            const paymentTermsValues = editReservationForm.getFieldsValue();

                                            if( e.target.checked == false ) {
                                                editReservationForm.setFieldsValue({ ...paymentTermsValues,
                                                    cash_split_number: 0,
                                                });
                                                setCashSplitNumber(0);
                                            } else {
                                                editReservationForm.setFieldsValue({ ...paymentTermsValues,
                                                    reservation_fee_date: paymentTermsValues.reservation_fee_date,
                                                    cash_split_number: 1,
                                                });
                                                setReservationFeeDate(paymentTermsValues.reservation_fee_date);
                                                setCashSplitNumber(1);
                                            }

                                            setSplitCash(!splitCash)
                                        }} className="pb-2">Split Cash</Checkbox>
                                        <Form.Item name="cash_split_number" label="Number of splits" rules={[{
                                            required: splitCash ? true : false,
                                            pattern: new RegExp(/^[0-9]+$/)
                                        }]}>
                                            <InputNumber value={cashSplitNumber} disabled={!splitCash} onChange={ e => setCashSplitNumber(e) } style={{width: '100%'}} placeholder="0" />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={4}>
                                        <div className="pb-2">&nbsp;</div>
                                        <Form.Item name="cash_split_payment_amount" label="Split payment amount">
                                            <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(cashSplitPaymentAmount ?? 0)}
                                            </span>
                                        </Form.Item>
                                    </Col>
                                    <Col xl={4}>
                                        <div className="pb-2">&nbsp;</div>
                                        <Form.Item name="split_cash_start_date" label="Start date">
                                            <DatePicker disabled />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={4}>
                                        <div className="pb-2">&nbsp;</div>
                                        <Form.Item name="split_cash_end_date" label="End date">
                                            <DatePicker disabled />
                                        </Form.Item>
                                    </Col>
                                </Row>
                        </TabPane>
                        <TabPane tab="IN-HOUSE ASSISTED FINANCING" key="in_house">
                            <Alert className="mb-4" message={<>Your payment terms is&nbsp;<strong>IN-HOUSE ASSISTED FINANCING</strong>. DP will start within 30 days from Reservation Date. Amortization to start 30 days after Full DP.</>} />

                            <Row gutter={[16,16]}>
                                <Col xl={6}>
                                    <Form.Item name="total_selling_price" label="Total selling price">
                                        {/* <InputNumber readOnly style={{width: '100%'}} placeholder="&#8369; 0.0"
                                            formatter={value => `₱ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                                            parser={value => value.replace(/\₱\s?|(,*)/g, '')}
                                        /> */}
                                        <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(totalSellingPrice)}
                                        </span>
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <Form.Item name="discount_percentage" label="Less: Discount(%)">
                                        <InputNumber onChange={e => setDiscountPercentage(e)} style={{width: '100%'}}
                                            formatter={value => `${value}%`}
                                            parser={value => value.replace('%', '')}
                                            // precision={1}
                                            min={0}
                                            max={100}
                                            stringMode />
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <Form.Item name="discount_amount" label="Discount">
                                        <InputNumber onChange={e => { e > totalSellingPrice ? totalSellingPrice : setDiscountAmount(e)}} style={{width: '100%'}} placeholder="&#8369; 0.0"
                                            {...currencyFormatter}
                                            min={0}
                                            max={totalSellingPrice}
                                        />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={[16,16]}>
                                <Col xl={5}>
                                    <div>&nbsp;</div>
                                    <Form.Item name="net_selling_price" label={<>Net selling price&nbsp;<strong>without</strong>&nbsp;VAT</>}>
                                        {/* <InputNumber readOnly style={{width: '100%'}} placeholder="&#8369; 0.0" {...currencyFormatter} /> */}
                                        <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(netSellingPrice ?? 0)}
                                        </span>
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <div>&nbsp;</div>
                                    <Form.Item name="with_vat" style={{marginBottom: 0}}>
                                        <Checkbox checked={withTwelvePercentVAT} onChange={e => setWithTwelvePercentVAT(e.target.checked)} className="pb-2">12% VAT</Checkbox>
                                    </Form.Item>
                                    <span style={{width: '100%'}} className="text-success">
                                        &#8369; {withTwelvePercentVAT ? numberWithCommas(twelvePercentVAT ?? 0) : '-'}
                                    </span>
                                </Col>
                                <Col xl={5}>
                                    { withTwelvePercentVAT &&
                                        <>
                                        <div>&nbsp;</div>
                                        <Form.Item name="nsp_with_vat" label={<>Net selling price&nbsp;<strong>with</strong>&nbsp;VAT</>}>
                                            <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(NSPWithVAT ?? 0)}
                                            </span>
                                        </Form.Item>
                                        </>
                                    }
                                </Col>
                            </Row>

                            <Row gutter={[16,16]}>
                                <Col xl={4}>
                                    <Form.Item name="downpayment_percentage" label="% of Downpayment">
                                        <InputNumber onChange={e => setDownpaymentPercentage(e)} style={{width: '100%'}}
                                            formatter={value => `${value}%`}
                                            parser={value => value.replace('%', '')}
                                            // precision={1}
                                            min={0}
                                            max={100}
                                            stringMode
                                        />
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <Form.Item name="downpayment_amount" label="Downpayment">
                                        <InputNumber onChange={e => { e > totalSellingPrice ? totalSellingPrice : setDownpaymentAmount(e)}} style={{width: '100%'}} placeholder="&#8369; 0.0" {...currencyFormatter}
                                            min={0}
                                            max={totalSellingPrice}
                                        />
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <Form.Item name="reservation_fee_amount" label="Less: Reservation fee">
                                        <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(reservationFeeAmount ?? 0)}
                                        </span>
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <Form.Item name="downpayment_amount_less_rf" label="DP less RF">
                                        <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(DPLessRF ?? 0)}
                                        </span>
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <Form.Item name="downpayment_due_date" label="Downpayment due date" rules={[
                                        {
                                            required: downpaymentAmount > 0 ? true : false
                                        }
                                    ]}>
                                        <DatePicker />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={[16,16]}>
                                <Col xl={6}>
                                    <Form.Item name="total_balance_in_house" label="Total balance for in-house financing">
                                        <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(totalBalanceInHouse ?? 0)}
                                        </span>
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <Form.Item name="number_of_years" label="Number of years" rules={[
                                        {
                                            required: tab == 'in_house' ? true : false
                                        }
                                    ]}>
                                        <Select onChange={e => setFactorRate({ year: e, rate: _.find(factor_rate_table[factor_percentage], i => i.year == e).rate})}>
                                            {
                                                factor_rate_table[factor_percentage].map( (i, key) => {
                                                    return <Select.Option key={key} value={i.year}>{i.year} yr{i.year > 1 ? 's':''}</Select.Option>
                                                })
                                            }
                                        </Select>
                                    </Form.Item>
                                </Col>
                                <Col xl={2}>
                                    <Form.Item name="percentage" label="Percentage">
                                        <span className="text-success">{factor_percentage}%</span>
                                    </Form.Item>
                                </Col>
                                <Col xl={2}>
                                    <Form.Item name="factor_rate" label="Factor rate">
                                        <span className="text-success">{factorRate.rate ?? ''}</span>
                                    </Form.Item>
                                </Col>
                            </Row>
                            <Row gutter={[16,16]}>
                                <Col xl={4}>
                                    <Form.Item label="Monthly amortization">
                                        <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(monthlyAmortization ?? 0)}
                                        </span>
                                    </Form.Item>
                                </Col>
                                <Col xl={6}>
                                    <Form.Item name="monthly_amortization_due_date" label="Monthly amortization due date" rules={[
                                        {
                                            required: tab != 'cash' ? true : false
                                        }
                                    ]}>
                                        <DatePicker value={monthlyAmortizationDueDate} onChange={e => setMonthlyAmortizationDueDate(e)} />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={[16,16]}>
                                <Col xl={4}>
                                    <Checkbox checked={splitDP} onChange={e => {
                                            
                                        const paymentTermsValues = editReservationForm.getFieldsValue();

                                        if( e.target.checked == false ) {
                                            editReservationForm.setFieldsValue({ ...paymentTermsValues,
                                                downpayment_split_number: null,
                                            });
                                            setDownpaymentSplitNumber(null);
                                        } else {
                                            editReservationForm.setFieldsValue({ ...paymentTermsValues,
                                                reservation_fee_date: paymentTermsValues.reservation_fee_date,
                                                downpayment_split_number: 1,
                                            });
                                            setReservationFeeDate(paymentTermsValues.reservation_fee_date);
                                            setDownpaymentSplitNumber(1);
                                        }

                                        setSplitDP(!splitDP)
                                    }} className="pb-2">Split Downpayment</Checkbox>
                                    <Form.Item name="downpayment_split_number" label="Number of splits" rules={[{
                                        required: splitDP ? true : false,
                                        pattern: new RegExp(/^[0-9]+$/)
                                    }]}>
                                        <InputNumber value={downpaymentSplitNumber} min={1} disabled={!splitDP} onChange={ e => setDownpaymentSplitNumber(e)} style={{width: '100%'}} />
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <div className="pb-2">&nbsp;</div>
                                    <Form.Item label="Split payment amount">
                                        <span style={{width: '100%'}} className="text-success">
                                                &#8369; {numberWithCommas(splitDownpaymentAmount ?? 0)}
                                        </span>
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <div className="pb-2">&nbsp;</div>
                                    <Form.Item name="split_downpayment_start_date" label="Start date">
                                        <DatePicker inputReadOnly={true} disabled />
                                    </Form.Item>
                                </Col>
                                <Col xl={4}>
                                    <div className="pb-2">&nbsp;</div>
                                    <Form.Item name="split_downpayment_end_date" label="End date">
                                        <DatePicker inputReadOnly={true} disabled />
                                    </Form.Item>
                                </Col>
                            </Row>

                        </TabPane>
                    </Tabs>
                }

                <Row gutter={[16,16]}>
                    <Col xl={16}>
                        <Form.Item name="remarks" label="Remarks">
                                    <Input.TextArea style={{borderRadius: 8, width: '100%'}} size={4} />
                        </Form.Item>
                    </Col>
                </Row>

                <Row gutter={[16,16]}>
                    <Col xl={5}>
                        <Form.Item name="promos" label="Promos">
                            <Select mode="multiple">
                                {
                                    promoList.data &&
                                        promoList.data.map( (i, key) => {
                                            return <Select.Option key={key} value={i.promo_type}>{i.promo_type}</Select.Option>
                                        })
                                }
                            </Select>
                            
                        </Form.Item>
                    </Col>
                </Row>

                <Space style={{float: 'right'}}>
                    <Button disabled={finishLoading} htmlType='submit' onClickCapture={() => setRecordSaveType('draft')}>Save as draft</Button>
                    <Button disabled={finishLoading}type="primary" htmlType="submit" onClickCapture={() => setRecordSaveType('default')}>Save</Button>
                    <Button disabled={finishLoading} type='primary' danger onClick={() => deleteDraftRecord()}>Delete</Button>
                </Space>

            </Form>
            { tab == 'in_house' ?
                <>
                    <Divider orientation="left">Amortization schedule sample</Divider>
                    <Table
                        style={{marginTop: 16}}
                        rowKey="number"
                        dataSource={amortizationSchedule}
                        pagination={{
                            pageSizeOptions: [10, 20, 50, 100, 120, 240]
                        }}
                        columns={[
                            {
                                title: 'Amortization',
                                dataIndex: 'number',
                                key: 'number',
                            },
                            {
                                title: 'Date due',
                                dataIndex: 'date_due',
                                key: 'date_due',
                            },
                            {
                                title: 'Amount',
                                dataIndex: 'amount',
                                key: 'amount',
                            },
                            {
                                title: 'Principal',
                                dataIndex: 'principal',
                                key: 'principal',
                            },
                            {
                                title: 'Interest',
                                dataIndex: 'interest',
                                key: 'interest',
                            },
                            {
                                title: 'Balance',
                                dataIndex: 'balance',
                                key: 'balance',
                            },
                        ]}
                    />
                </> : ''
            }
        </div>
    )
}