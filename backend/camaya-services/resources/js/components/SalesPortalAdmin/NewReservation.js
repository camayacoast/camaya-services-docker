import React from 'react'
import {Link} from 'react-router-dom'
import { Spin, Card, Row, Col, Space, Form, Input, Divider, InputNumber, DatePicker, Button, Select, Tabs, Alert, Checkbox, message, Table, Modal } from 'antd'
import { PrinterOutlined } from '@ant-design/icons'
const { TabPane } = Tabs;

import SalesAdminPortalService from 'services/SalesAdminPortal'

import subdivisions from 'common/subdivisions.json'
// import lot_inventory from 'common/lot_inventory.json'
import factor_rate from 'common/factor_rate.json'
import factor_rate_table from 'common/factor_rate_table.json'
import moment from 'moment';
import { sinfulPrecision } from 'utils/Common';

const factor_percentage = (typeof process.env.FACTOR_PERCENTAGE !== 'undefined') ? parseFloat(process.env.FACTOR_PERCENTAGE) : 7;

const numberWithCommas = (x) => {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

const RetentionFee = (props) => {
    return <span style={{width: '100%', textDecoration: props.withRetentionFee ? 'inherit' : 'line-through' }}>
                &#8369; {numberWithCommas(props.value ?? 0)}
           </span>
}

export default function Page(props) {

    const [selectedSubdivision, setSelectedSubdivision] = React.useState(null);
    const [selectedBlock, setSelectedBlock] = React.useState(null);
    const [selectedLot, setSelectedLot] = React.useState(null);
    const [tab, setTab] = React.useState('cash');
    const [amortizationSchedule, setAmortizationSchedule] = React.useState([]);
    const [monthlyAmortizationDueDate, setMonthlyAmortizationDueDate] = React.useState(null);


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
    const [cashSplitNumber, setCashSplitNumber] = React.useState(0);

    // In-house
    const [factorRate, setFactorRate] = React.useState({});
    const [downpaymentPercentage, setDownpaymentPercentage] = React.useState(0);
    const [downpaymentAmount, setDownpaymentAmount] = React.useState(0);
    const [DPLessRF, setDPLessRF] = React.useState(0);
    const [totalBalanceInHouse, setTotalBalanceInHouse] = React.useState(0);
    const [monthlyAmortization, setMonthlyAmortization] = React.useState(0);
    const [splitDownpaymentAmount, setSplitDownpaymentAmount] = React.useState(0);
    const [splitDP, setSplitDP] = React.useState(false);
    const [downpaymentSplitNumber, setDownpaymentSplitNumber] = React.useState(0);
    const [downpaymentDueDate, setDownpaymentDueDate] = React.useState(null);

    const [subdivisionLabel, setSubdivisionLabel] = React.useState('Subdivision');
    const [lotLabel, setlotLabel] = React.useState('Lot');
    const [propertyType, setPropertyType] = React.useState('');
    
    
    const [newReservationForm] = Form.useForm();

    const promoList = SalesAdminPortalService.realestatePromosViaField({column: 'status', value: 'Active'});
    const salesClientsListQuery = SalesAdminPortalService.salesClientsList();
    const lotInventoryList = SalesAdminPortalService.lotCondoInventoryList();
    const reservationListQuery = SalesAdminPortalService.reservationList();
    const [newReservationQuery, {isLoading: newReservationQueryIsLoading, reset: newReservationQueryReset}] = SalesAdminPortalService.newReservation();
    const [interestRate, setInterestRate] = React.useState(factor_percentage / 100);
    const [recordSaveType, setRecordSaveType] = React.useState('default');

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

        // const paymentTermsValues = newReservationForm.getFieldsValue();

        // VAT
        if (totalSellingPrice) {

            // Common
            const net_selling_price = totalSellingPrice - discountAmount;
            const twelve_percent_vat = net_selling_price * .12;
            const nsp_with_vat = net_selling_price + twelve_percent_vat;
            const nsp_computed = (withTwelvePercentVAT ? nsp_with_vat : net_selling_price);

            // Cash
            const retention_fee = nsp_computed * .05;
            const total_amount_payable = nsp_computed - ((withRetentionFee && tab == 'cash' ? retention_fee : 0) + reservationFeeAmount);

            // In-house
            const total_balance_in_house = nsp_computed - (downpaymentAmount > 0 ? downpaymentAmount : reservationFeeAmount);
            const monthly_amortization = total_balance_in_house * parseFloat(factorRate.rate ?? 0);

            /**
             * Update states
             */

            const paymentTermsValues = newReservationForm.getFieldsValue();
            let downpayment_amount = nsp_computed * (paymentTermsValues.downpayment_percentage/100);

            downpayment_amount = isNaN(downpayment_amount) ? 0 : downpayment_amount; 

            newReservationForm.setFieldsValue({ ...paymentTermsValues,
                downpayment_amount: sinfulPrecision(downpayment_amount, 2),
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

            const years = newReservationForm.getFieldValue('number_of_years');
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
            const paymentTermsValues = newReservationForm.getFieldsValue();

            const total = sinfulPrecision(totalSellingPrice * (discountPercentage/100), 2);
            // Update states
            setDiscountAmount(total);

            // Update form values
            newReservationForm.setFieldsValue({ ...paymentTermsValues,
                discount_amount: total,
            });
        }

    }

    const updateDiscountAmount = () => {

        const paymentTermsValues = newReservationForm.getFieldsValue();
    
        if (totalSellingPrice) {

            const total = sinfulPrecision((discountAmount/totalSellingPrice) * 100, 2);
            // Update states
            setDiscountPercentage(total);

            // Update form values
            newReservationForm.setFieldsValue({ ...paymentTermsValues,
                discount_percentage: total,
            });
        }

    }

    /**
     * Update DPs
     */

     const updateDownpaymentPercentage = () => {
        
        if (totalSellingPrice) {
            
            const paymentTermsValues = newReservationForm.getFieldsValue();
            
            const net_selling_price = totalSellingPrice - discountAmount;
            const twelve_percent_vat = net_selling_price * .12;
            const nsp_with_vat = net_selling_price + twelve_percent_vat;
            const nsp_computed = (withTwelvePercentVAT ? nsp_with_vat : net_selling_price);

            const total = sinfulPrecision((nsp_computed * (downpaymentPercentage/100)), 2);
            
            // Update states
            setDownpaymentAmount(total);
            setDPLessRF((total - reservationFeeAmount));

            // Update form values
            newReservationForm.setFieldsValue({ ...paymentTermsValues,
                downpayment_amount: total,
            });
        }

    }

    const updateDownpaymentAmount = () => {

        if (totalSellingPrice) {
            const paymentTermsValues = newReservationForm.getFieldsValue();
            const nsp_computed = (withTwelvePercentVAT ? NSPWithVAT : netSellingPrice);

            const quotient = (downpaymentAmount / nsp_computed);
            const product = quotient * 100;
            const cf = 10;

            const total = sinfulPrecision((downpaymentAmount/nsp_computed) * 100 ,2);
            // Update states
            setDownpaymentPercentage(total);

            // Update form values
            newReservationForm.setFieldsValue({ ...paymentTermsValues,
                downpayment_percentage: total,
            });
        }

    }

    React.useEffect( () => {
        if (selectedSubdivision && selectedBlock && selectedLot) {
            const i = _.find(lotInventoryList.data, i => (i.subdivision == selectedSubdivision && i.block == selectedBlock && i.lot == selectedLot) );
            const prev = newReservationForm.getFieldsValue();

            let total_selling_price = 0;

            if (i) {

                let tsp_rounded = parseFloat(i.area) * parseFloat(pricePerSqm);
                let tsp_floor = parseFloat(i.area) * parseFloat(pricePerSqm);

                // let tsp_final = (tsp_rounded % 10 > 0) ? (tsp_rounded - (tsp_rounded % 10)) : tsp_rounded;

                let tsp_final = tsp_rounded.toFixed(2);

                total_selling_price = (i.area && pricePerSqm) ? tsp_final : '';
                total_selling_price =  !isNaN(total_selling_price) ? total_selling_price : 0;

                newReservationForm.setFieldsValue({ ...prev,
                    price_per_sqm: pricePerSqm ?? '',
                    total_selling_price: total_selling_price ?? 0
                });

                setTotalSellingPrice(total_selling_price);
            }
        } else {
            setTotalSellingPrice(0);
            handleResetStates('selected subdivision, selected block, selected lot changes 2');
        }

    }, [pricePerSqm]);

    React.useEffect( () => {

        if (selectedSubdivision && selectedBlock && selectedLot) {
            // console.log(selectedSubdivision);
            
            const i = _.find(lotInventoryList.data, i => (i.subdivision == selectedSubdivision && i.block == selectedBlock && i.lot == selectedLot) );
            const prev = newReservationForm.getFieldsValue();

            let total_selling_price = 0;

            if (i) {

                let tsp_rounded = parseFloat(i.area) * parseFloat(i.price_per_sqm);
                let tsp_floor = parseFloat(i.area) * parseFloat(i.price_per_sqm);

                // let tsp_final = (tsp_rounded % 10 > 0) ? tsp_floor : tsp_rounded;

                let tsp_final = tsp_rounded.toFixed(2);

                total_selling_price = (i.area && i.price_per_sqm) ? tsp_final : '';
                total_selling_price =  !isNaN(total_selling_price) ? total_selling_price : 0;

                newReservationForm.setFieldsValue({ ...prev,
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
            handleResetStates('selected subdivision, selected block, selected lot changes 2');
        }

    }, [selectedSubdivision, selectedBlock, selectedLot]);

    React.useEffect( () => {
 
        setSelectedBlock('');
        setSelectedLot('');
        
        setTotalSellingPrice(0);
        // handleResetStates('selected subdivision');

        const prev = newReservationForm.getFieldsValue();

        newReservationForm.setFieldsValue({ ...prev,
            block: '',
            lot: '',
            lot_type: '',
            area: '',
            // total_selling_price: '',
            discount_amount: '',
            // discount_percentage: '',
            downpayment_amount: '',
            // downpayment_percentage: '',
            // cash_split_number: '',
            split_cash_start_date: '',
            split_cash_end_date: '',
            // downpayment_split_number: '',
            split_downpayment_start_date: '',
            split_downpayment_end_date: '',
        });

    }, [selectedSubdivision]);

    React.useEffect( () => {

        const prev = newReservationForm.getFieldsValue();

        newReservationForm.setFieldsValue({ ...prev,
            lot: '',
            lot_type: '',
            area: '',
            // total_selling_price: '',
            discount_amount: '',
            // discount_percentage: '',
            downpayment_amount: '',
            // downpayment_percentage: '',
            // cash_split_number: '',
            split_cash_start_date: '',
            split_cash_end_date: '',
            // downpayment_split_number: '',
            split_downpayment_start_date: '',
            split_downpayment_end_date: '',
        });

        // newReservationForm.resetFields();

        setSelectedLot('');
        setTotalSellingPrice(0);
        // handleResetStates('selected block');

    }, [selectedBlock]);

    React.useEffect( () => {

        if (!selectedLot) {
            const prev = newReservationForm.getFieldsValue();

            newReservationForm.setFieldsValue({ ...prev,
                lot_type: '',
                area: '',
                // total_selling_price: '',
                discount_amount: '',
                // discount_percentage: '',
                downpayment_amount: '',
                // downpayment_percentage: '',
                // cash_split_number: '',
                split_cash_start_date: '',
                split_cash_end_date: '',
                // downpayment_split_number: '',
                split_downpayment_start_date: '',
                split_downpayment_end_date: '',
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
            const total_amount_payable = nsp_computed - ((withRetentionFee && tab == 'cash' ? retention_fee : 0) + reservationFeeAmount);
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
            const prev = newReservationForm.getFieldsValue();

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

            newReservationForm.setFieldsValue({ ...prev, 

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

    const handleNewReservationFinish = (values) => {
        // console.log(values);

        if (newReservationQueryIsLoading) {
            return false;
        }

        const newValues = {
            ...values,
            payment_terms_type: tab,
            with_twelve_percent_vat: withTwelvePercentVAT,
            factor_rate: factorRate.rate,
            split_downpayment: splitDP,
            split_cash: splitCash,
            record_save_type: recordSaveType,
        }

        newReservationQuery(newValues, {
            onSuccess: (res) => {
                // console.log(res);
                // handleResetFields();

                // alert("Success!");

                if( recordSaveType == 'draft' ) {
                    
                    Modal.success({
                        title: "Successfully created new draft reservation!",
                        onOk: () => {
                            window.location.href = process.env.APP_URL + '/sales-admin-portal/reservation-documents';
                            Modal.destroyAll();
                        }
                    });

                } else {
                    Modal.success({
                        title: "Successfully created new reservation!",
                        content: <div><Button onClick={() => {
                            props.history.push(`view-reservation/${res.data.reservation_number}`);
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
                // alert(e.message)
                if (e.message == 'Unauthorized.') {
                    message.error("You don't have access to do this action.");
                }
                message.info(e.message);
            },
        })
    }

    const handleResetStates = (i) => {

        // Common
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

    const handleResetFields = () => {
        newReservationForm.resetFields();

        setSelectedSubdivision('');

        handleResetStates('reset fields');
    }

    const handlePropertyType = (value) => {

        setSelectedSubdivision('');
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
        newReservationForm.setFieldsValue(params);
    }

    return (
        <div className="mt-4">

            <Form form={newReservationForm} onFinish={handleNewReservationFinish} layout="vertical" style={{clear: 'both'}} initialValues={{
                reservation_fee_amount: 0,
                discount_percentage: 0,
                discount_amount: 0,
            }}>
                <Space style={{float: 'right'}}>
                    {/* <Button disabled icon={<PrinterOutlined/>}>Print Reservation Agreement</Button> */}
                    <Button htmlType="submit" onClickCapture={() => setRecordSaveType('draft')}>Save as draft</Button>
                    <Button type="primary" htmlType="submit" onClickCapture={() => setRecordSaveType('default')}>Save</Button>
                </Space>
                <Row gutter={[8,8]}>
                    <Col xl={10}>
                        <Form.Item name="client" label="Client">
                            {/* <Input placeholder="Search client" /> */}
                            <Select
                                showSearch
                                style={{ width: '100%' }}
                                placeholder={`Select client`}
                                optionFilterProp="children"
                                // onSearch={onSearch}
                                size="large"
                                filterOption={(input, option) =>
                                    option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                }
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
                                // onSearch={onSearch}
                                size="large"
                                filterOption={(input, option) =>
                                    option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                }
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
                            {/* <Input placeholder="Search client" /> */}
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
                            {/* <Input placeholder="Search client" /> */}
                            <Select
                                showSearch
                                style={{ width: '100%' }}
                                placeholder={`Select property`}
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
                                { reservationListQuery.data &&
                                    reservationListQuery.data.map( (item, key) => {
                                        if(item.status != 'draft') {
                                            return <>
                                                <Select.Option key={key} value={item.reservation_number}>
                                                    {`${(item.client_number != null && item.client_number != '' ? item.client_number + ',' : 'Not Set,')} ${item.subdivision} B ${item.block} L ${item.lot} - ${item.reservation_number} `} 
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
                                    <Select onChange={e => setSelectedSubdivision(e)}>
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
                                        {/* <Select.Option value="Menara Point North">Menara Point North</Select.Option>
                                        <Select.Option value="Golf Town Commercial">Golf Town Commercial</Select.Option>
                                        <Select.Option value="Bayu Peaks">Bayu Peaks</Select.Option>
                                        <Select.Option value="Quinawan Golf Residences">Quinawan Golf Residences</Select.Option>
                                        <Select.Option value="Taman Ridge">Taman Ridge</Select.Option>
                                        <Select.Option value="Tandatangan Golf Residences">Tandatangan Golf Residences</Select.Option>
                                        <Select.Option value="The Town Golf Residences">The Town Golf Residences</Select.Option> */}
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
                            <Select onChange={e => setSelectedBlock(e)}>
                                <Select.Option value=""></Select.Option>
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
                            <Select onChange={e => setSelectedLot(e)}>
                                <Select.Option value=""></Select.Option>
                                {
                                    _.filter(lotInventoryList.data, i => (i.subdivision == selectedSubdivision && i.block == selectedBlock && _.includes(['available', 'reserved'], i.status)))
                                    .map( (i, key) => {
                                        return <Select.Option key={key} value={i.lot}>{lotLabel} {i.lot}</Select.Option>
                                    })
                                }
                            </Select>
                        </Form.Item>
                    </Col>
                    <Col xl={3}>
                        <Form.Item name="lot_type" label="Type">
                            <Input readOnly style={{background: 'none', border: 'none'}}  />
                        </Form.Item>
                    </Col>
                    <Col xl={3}>
                        <Form.Item name="area" label="Approximate area">
                            <Input readOnly style={{background: 'none', border: 'none'}}  />
                        </Form.Item>
                        {/* <Form.Item style={{display: 'none'}} name="price_per_sqm" label="Price per sqm">
                            <Input readOnly />
                        </Form.Item> */}
                    </Col>
                    <Col xl={3}>
                        <Form.Item name="price_per_sqm" label="Price per sqm">
                            {/* <Input readOnly /> */}
                            <InputNumber disabled={!selectedSubdivision} onChange={e => setPricePerSqm(e)} style={{width: '100%'}} placeholder="&#8369; 0.0"
                                {...currencyFormatter}
                            />
                        </Form.Item>
                    </Col>
                    <Col xl={6}>
                        <Form.Item name="total_selling_price" label="Total selling price" help={<small>Inventory price per sqm: {numberWithCommas(getPricePerSqm())}</small>}>
                            {/* <InputNumber onChange={e => setTotalSellingPrice(e)} style={{width: '100%'}} placeholder="&#8369; 0.0"
                                {...currencyFormatter}
                            /> */}
                            <span style={{width: '100%'}} className="text-success">
                                &#8369; {numberWithCommas(totalSellingPrice  ?? 0)}
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

                <Tabs type="card" defaultActiveKey="cash" className="mb-5" 
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
                                    {/* <InputNumber readOnly style={{width: '100%'}} placeholder="&#8369; 0.0"
                                        {...currencyFormatter}
                                    /> */}
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
                                    {/* <InputNumber style={{width: '100%'}} placeholder="&#8369; 0.0"
                                        {...currencyFormatter}
                                    /> */}
                                    <span style={{width: '100%'}} className="text-success">
                                            &#8369; {numberWithCommas(netSellingPrice ?? 0)}
                                    </span>
                                </Form.Item>
                            </Col>
                            <Col xl={4}>
                                <div>&nbsp;</div>
                                <Form.Item valuePropName="checked" name="with_vat" style={{marginBottom: 0}}>
                                    <Checkbox onChange={e => setWithTwelvePercentVAT(e.target.checked)} className="pb-2">12% VAT</Checkbox>
                                </Form.Item>
                                {/* <Form.Item name="12_percent_vat"> */}
                                    <span style={{width: '100%'}} className="text-success">
                                            &#8369; {withTwelvePercentVAT ? numberWithCommas(twelvePercentVAT ?? 0) : '-'}
                                    </span>
                                {/* </Form.Item> */}
                            </Col>
                            <Col xl={5}>
                                { withTwelvePercentVAT &&
                                    <>
                                    <div>&nbsp;</div>
                                    <Form.Item label={<>Net selling price&nbsp;<strong>with</strong>&nbsp;VAT</>}>
                                        {/* <InputNumber readOnly style={{width: '100%'}} placeholder="&#8369; 0.0"
                                            {...currencyFormatter}
                                        /> */}
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
                                    {/* <InputNumber readOnly style={{width: '100%'}} placeholder="&#8369; 0.0" {...currencyFormatter} /> */}
                                    <span style={{width: '100%'}} className="text-success">
                                            &#8369; {numberWithCommas(reservationFeeAmount ?? 0)}
                                    </span>
                                </Form.Item>
                            </Col>
                            <Col xl={4}>
                                <Form.Item valuePropName="checked" name="with_retention_fee" style={{marginBottom: 0}}>
                                    <Checkbox onChange={e => setWithRetentionFee(e.target.checked)} className="pb-2">5% retention fee</Checkbox>
                                </Form.Item>
                                {/* <InputNumber value={retentionFee} readOnly style={{width: '100%', textDecoration: 'line-through' }} placeholder="&#8369; 0.0" {...currencyFormatter} /> */}
                                <RetentionFee value={retentionFee} withRetentionFee={withRetentionFee} />
                            </Col>

                            <Col xl={6}>
                                <Form.Item label={<>Total amount payable</>}>
                                    {/* <InputNumber readOnly style={{width: '100%'}} placeholder="&#8369; 0.0" {...currencyFormatter} /> */}
                                    <span style={{width: '100%'}} className="text-success">
                                            &#8369; {numberWithCommas(totalAmountPayable ?? 0)}
                                    </span>
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={[16,16]}>
                                <Col xl={4}>
                                    <Checkbox checked={splitCash} onChange={e => {

                                        const paymentTermsValues = newReservationForm.getFieldsValue();

                                        if( e.target.checked == false ) {
                                            newReservationForm.setFieldsValue({ ...paymentTermsValues,
                                                cash_split_number: null,
                                            });
                                            setCashSplitNumber(null);
                                        } else {
                                            newReservationForm.setFieldsValue({ ...paymentTermsValues,
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
                                        {/* <InputNumber style={{width: '100%'}} placeholder="&#8369; 0.0" /> */}
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
                                            &#8369; {numberWithCommas(totalSellingPrice ?? 0)}
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
                                <Form.Item valuePropName="checked" name="with_vat" style={{marginBottom: 0}}>
                                    <Checkbox onChange={e => setWithTwelvePercentVAT(e.target.checked)} className="pb-2">12% VAT</Checkbox>
                                </Form.Item>
                                {/* <Form.Item name="12_percent_vat"> */}
                                {/* <InputNumber readOnly value={twelvePercentVAT} style={{width: '100%'}} placeholder="&#8369; 0.0"
                                    {...currencyFormatter}
                                /> */}
                                <span style={{width: '100%'}} className="text-success">
                                    &#8369; {withTwelvePercentVAT ? numberWithCommas(twelvePercentVAT ?? 0) : '-'}
                                </span>
                            </Col>
                            <Col xl={5}>
                                { withTwelvePercentVAT &&
                                    <>
                                    <div>&nbsp;</div>
                                    <Form.Item name="nsp_with_vat" label={<>Net selling price&nbsp;<strong>with</strong>&nbsp;VAT</>}>
                                        {/* <InputNumber readOnly style={{width: '100%'}} placeholder="&#8369; 0.0"
                                            {...currencyFormatter}
                                        /> */}
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
                                        stringMode
                                    />
                                </Form.Item>
                            </Col>
                            <Col xl={4}>
                                <Form.Item name="reservation_fee_amount" label="Less: Reservation fee">
                                    {/* <InputNumber readOnly style={{width: '100%'}} placeholder="&#8369; 0.0" {...currencyFormatter} /> */}
                                    <span style={{width: '100%'}} className="text-success">
                                            &#8369; {numberWithCommas(reservationFeeAmount ?? 0)}
                                    </span>
                                </Form.Item>
                            </Col>
                            <Col xl={4}>
                                <Form.Item name="downpayment_amount_less_rf" label="DP less RF">
                                    {/* <InputNumber readOnly style={{width: '100%'}} placeholder="&#8369; 0.0" {...currencyFormatter} /> */}
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
                                    {/* <InputNumber readOnly style={{width: '100%'}} placeholder="&#8369; 0.0" {...currencyFormatter}  /> */}
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
                                    {/* <InputNumber style={{width: '100%'}} placeholder="Years" /> */}
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
                                    {/* <InputNumber readOnly style={{display: 'none'}} placeholder="&#8369; 0.0" /> */}
                                    <span className="text-success">{factor_percentage}%</span>
                                </Form.Item>
                            </Col>
                            <Col xl={2}>
                                <Form.Item name="factor_rate" label="Factor rate">
                                    {/* <InputNumber readOnly style={{display: 'none'}} placeholder="&#8369; 0.0" /> */}
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
                                            
                                    const paymentTermsValues = newReservationForm.getFieldsValue();

                                    if( e.target.checked == false ) {
                                        newReservationForm.setFieldsValue({ ...paymentTermsValues,
                                            downpayment_split_number: null,
                                        });
                                        setDownpaymentSplitNumber(null);
                                    } else {
                                        newReservationForm.setFieldsValue({ ...paymentTermsValues,
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

                <Row gutter={[16,16]}>
                    <Col xl={16}>
                        <Form.Item name="remarks" label="Remarks">
                                    <Input.TextArea style={{borderRadius: 8, width: '100%'}} size={4} />
                        </Form.Item>
                    </Col>
                </Row>
                {/* <Row gutter={[16,16]}>
                    <Col xl={5}>
                        <Form.Item name="promos" label="Promos">
                            <Select mode="multiple">
                                <Select.Option value="3-Day Promo">3-Day Promo</Select.Option>
                                <Select.Option value="VAT Inclusive">VAT Inclusive</Select.Option>
                                <Select.Option value="5% Additional Discount">5% Additional Discount</Select.Option>
                                <Select.Option value="10% Additional Discount">10% Additional Discount</Select.Option>
                                <Select.Option value="Free Transfer of Title">Free Transfer of Title</Select.Option>
                                <Select.Option value="Additional 4%">Additional 4%</Select.Option>
                                <Select.Option value="Cash Split 3">Cash Split 3</Select.Option>

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
                </Row> */}

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

                {/* <Row gutter={[16,16]}>
                    <Col xl={5}>
                        <Form.Item name="reservation_date" label="Reservation Date" rules={[{
                            required: true
                        }]}>
                            <DatePicker />
                        </Form.Item>
                    </Col>
                </Row> */}

                <Space style={{float: 'right'}}>
                    <Button htmlType='submit' onClickCapture={() => setRecordSaveType('draft')}>Save as draft</Button>
                    <Button type="primary" htmlType="submit" onClickCapture={() => setRecordSaveType('default')}>Save</Button>
                </Space>
                
            </Form>
            {
                 tab == 'in_house' ?
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