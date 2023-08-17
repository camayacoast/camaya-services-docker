import React from 'react'
import {
    useParams
  } from "react-router-dom";

import { Typography, Row, Col, Space, Divider, Select, Table, Input, message, Dropdown, Menu, Button, Modal, InputNumber, Tag, Checkbox, Upload, Card, Tabs, Popconfirm, Form, DatePicker, Descriptions, Badge } from 'antd'
import { EllipsisOutlined, PrinterOutlined, UploadOutlined, DeleteOutlined, CodepenOutlined } from '@ant-design/icons'
const {TabPane} = Tabs;

import { twoDecimalPlace, ordinalNumber } from 'utils/Common';

import SalesAdminPortalService from 'services/SalesAdminPortal'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');

// Components
import AgentDetails from 'components/Common/Reservation/AgentDetails';
import UnitDetails from 'components/Common/Reservation/UnitDetails';
import PaymentDetails from 'components/Common/Reservation/PaymentDetails';
import PaymentTerms from 'components/Common/Reservation/PaymentTerms';
import ActivityLogs from 'components/Common/Reservation/ActivityLogs';
import PenaltyReports from 'components/Common/Reservation/PenaltyReports';
import AmortizationReports from 'components/Common/Reservation/AmortizationReports';
import CashLedgerReports from 'components/Common/Reservation/CashLedgerReports';

import ReactExport from "react-export-excel";
import TextArea from 'antd/lib/input/TextArea';
import { __esModule } from 'react-export-excel/dist/ExcelPlugin/components/ExcelFile';
const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;
let AmortNumber = 0;
let AmortNumberDueDate = 0;
let AmortId = 0;
let AmortNumberAmountDue = 0;
let DefaultPenaltyDiscountPercentage = 0;
let updatedPenaltyDiscount = 0;
let defaultAdditionalPenaltyAmount = 0;
let excludePenaltyPayment = 0;

const numberWithCommas = (x) => {
    if (!x) return false;
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

export default function Page(props) {

    let { reservation_number } = useParams();

    const [reservation, setReservation] = React.useState({});
    const [penaltyModalVisible, setPenaltyModalVisible] = React.useState(false);
    const [addPenaltyData, setAddPenaltyData] = React.useState({
        penalty_amount: 0
    });
    const [paymentModalVisible, setPaymentModalVisible] = React.useState(false);
    const [addPaymentData, setAddPaymentData] = React.useState({
        payment_amount: 0
    });

    const [paymentPreviewModalVisible, setPaymentPreviewModalVisible] = React.useState(false);
    const [paymentDetails, setPaymentDetails] =  React.useState([]);

    const [paymentDetailsPreviewModalVisible, setPaymentDetailsPreviewModalVisible] = React.useState(false);
    const [paymentDetailsTrans, setPaymentDetailsTrans] =  React.useState({});

    const [paymentDetailsModalVisible, setPaymentDetailsModalVisible] = React.useState(false);
    const [addPaymentDetailsData, setAddPaymentDetailsData] = React.useState({
        payment_amount: 0
    });

    const [paymentDetailsTransId, setPaymentDetailsTransId ] = React.useState(false);
    
    const [showPenalties, setShowPenalties] = React.useState(false);
    const [feesModalVisible, setFeesModalVisible] = React.useState(false);

    const [fileList, setFileList] = React.useState([]);
    const [fileToUpload, setFileToUpload] = React.useState({});
    const [uploadedFiles, setUploadedFiles] = React.useState([]);

    const [currentBalance, setCurrentBalance] = React.useState(''); 
    const [currentFloatBalance, setCurrentFloatBalance] = React.useState(''); 
    const [lastBalance, setLastBalance] = React.useState('initial');

    const [ dateDueColumn, setDateDueColumn ] = React.useState(true);
    const [ amountDueColumn, setAmountDueColumn ] = React.useState(true);

    const [viewReservationQuery, { IsLoading: viewReservationQueryIsLoading, reset: viewReservationQueryReset}] = SalesAdminPortalService.viewReservation();
    const [updateReservationClientNumberQuery, { IsLoading: updateReservationClientNumberQueryIsLoading, reset: updateReservationClientNumberQueryReset}] = SalesAdminPortalService.updateReservationClientNumber();
    const [updateReservationStatusQuery, { IsLoading: updateReservationStatusQueryIsLoading, reset: updateReservationStatusQueryReset}] = SalesAdminPortalService.updateReservationStatus();
    const [addPenaltyQuery, { IsLoading: addPenaltyQueryIsLoading, reset: addPenaltyQueryReset}] = SalesAdminPortalService.addPenalty();
    const [addPaymentQuery, { IsLoading: addPaymentQueryIsLoading, reset: addPaymentQueryReset}] = SalesAdminPortalService.addPayment();
    const [addPaymentDetailsQuery, { IsLoading: addPaymentDetailsQueryIsLoading, reset: addPaymentDetailsQueryReset}] = SalesAdminPortalService.addPayment();
    const [addFeesQuery, { IsLoading: addFeesQueryIsLoading, reset: addFeesQueryReset}] = SalesAdminPortalService.addFees();
    const [addPenaltyPaymentQuery, { IsLoading: addPenaltyPaymentQueryIsLoading, reset: addPenaltyPaymentQueryReset}] = SalesAdminPortalService.penaltyPayment();
    const [penaltyDefaultDiscountQuery, { IsLoading: penaltyDefaultDiscountQueryIsLoading, reset: penaltyDefaultDiscountQueryReset}] = SalesAdminPortalService.updateDefaultPenaltyDiscount();

    const [viewPenaltiesQuery, { IsLoading: viewPenaltiesQueryIsLoading, reset: viewPenaltiesQueryReset}] = SalesAdminPortalService.viewPenalties();
    const [waivePenaltyQuery, { IsLoading: waivePenaltyQueryIsLoading, reset: waivePenaltyQueryQueryReset}] = SalesAdminPortalService.waivePenalty();
    const [recomputeAccountQuery, { IsLoading: recomputeAccountQueryIsLoading, reset: recomputeAccountQueryQueryReset}] = SalesAdminPortalService.recomputeAccount();
    const [updateAmortizationQuery, { IsLoading: updateAmortizationQueryIsLoading, reset: updateAmortizationQueryReset}] = SalesAdminPortalService.updateAmortization();

    const [uploadFileQuery, { isLoading: uploadFileQueryIsLoading, reset: uploadFileQueryReset}] = SalesAdminPortalService.uploadReservationAttachmentFile();
    const [removeFileQuery, { isLoading: removeFileQueryIsLoading, reset: removeFileQueryReset}] = SalesAdminPortalService.removeReservationAttachmentFile();
    const [downloadBISQuery, { isLoading: downloadBISQueryIsLoading, reset: downloadBISQueryReset}] = SalesAdminPortalService.downloadBIS();
    const [activityLogsQuery, { isLoading: activityLogsQueryIsLoading, reset: activityLogsQueryReset}] = SalesAdminPortalService.addActivityLogs();
    const [paymentAttachementModal, setPaymentAttachementModal] = React.useState(false);
    const [penaltyPaymentModal, setPenaltyPaymentModal] = React.useState(false);
    const [penaltyPaymentData, setPenaltyPaymentData] = React.useState([]);
    const [penaltyDiscount, setPenaltyDiscount] = React.useState(0);
    const [penaltyAmount, setPenaltyAmount] = React.useState(0);
    const [penaltyFormInitialValue, setPenaltyFormInitialValue] = React.useState(0);
    const [penaltyTableCurrentPage, setPenaltyTableCurrentPage] = React.useState(1);
    const [activityLogRefetch, setActivityLogRefetch] = React.useState(false);
    const [amortizationUpdateData, setAmortizationUpdateData] = React.useState([]);
    const [amortizationUpdatePaymentData, setAmortizationUpdatePaymentData] = React.useState([]);
    const [amortizationUpdateModal, setAmortizationUpdateModal] = React.useState(false);

    const [formAddPayment] = Form.useForm();
    const [formAddFees] = Form.useForm();
    const [formAddPenaltyPayment] = Form.useForm();
    const [defaultPenaltyDiscount] = Form.useForm();
    const [formUpdateAmortization] = Form.useForm();

    const showPaymentDetilsUI = true;

    React.useEffect( () => {

        viewReservationQuery({
            reservation_number: reservation_number
        }, {
            onSuccess: (res) => {
                // console.log(res);
                
                if( res.data.client_number === null ||  res.data.client_number === '' ) {
                    props.history.goBack();
                }

                DefaultPenaltyDiscountPercentage = res.data.default_penalty_discount_percentage;
                setReservation(res.data);
                setUploadedFiles(res.data.attachments);
                setActivityLogRefetch(true);
            },
            onError: (e) => {
                console.log(e)
                if (e.error == 'RESERVATION_NOT_FOUND') {
                    props.history.goBack();
                }
            },
        });

    },[]);

    React.useEffect( () => {
        if (penaltyModalVisible == false) {
            setAddPenaltyData({});
        }
    }, [penaltyModalVisible]);

    React.useEffect( () => {
        if (paymentModalVisible == false) {
            setAddPaymentData({});
        }
    }, [paymentModalVisible]);

    React.useEffect( () => {
        _.map(reservation.amortization_schedule, (i) => {
            if( i.amount_paid !== null && i.is_collection ) {
                setLastBalance(i.balance);
            }
        })
    });

    const updateReservationDetails = () => {
        viewReservationQuery({
            reservation_number: reservation_number
        }, {
            onSuccess: (res) => {
                setReservation(res.data);
                setUploadedFiles(res.data.attachments);
            },
            onError: (e) => {
                console.log(e)
                if (e.error == 'RESERVATION_NOT_FOUND') {
                    props.history.goBack();
                }
            },
        });
    }

    const updateReservationClientNumber = (text) => {
        // console.log(text);

        updateReservationClientNumberQuery({
            id: reservation.id,
            client_number: text
        }, {
            onSuccess: (res) => {
                console.log(res);
                setReservation(res.data);
                message.success('Updated client number')
            },
            onError: (e) => message.info(e.message),
        })
    }

    const updateReservationStatus = (value) => {
        // console.log(value);

        updateReservationStatusQuery({
            id: reservation.id,
            status: value
        }, {
            onSuccess: (res) => {
                // console.log(res);
                setReservation(res.data);
                message.success('Updated status')
            },
            onError: (e) => message.info(e.message),
        })
    }

    const handleAddPenaltyClick = (data) => {

        // if( lastBalance === '0.00' ) {
        //     message.warning(`Amortization is already paid.`);
        //     return false;
        // }

        setPenaltyModalVisible(true);
        setAddPenaltyData({...data, 
            penalty_amount: (data.amount * 0.03).toFixed(2),
            payment_terms_type: reservation.payment_terms_type,
        });
    }

    const handleWaivePenaltyClick = (data, status) => {

        let amortization_month = data.number;
        let penalty_status = ( typeof data.penalty_records[0] !== 'undefined' ) ? data.penalty_records[0].status : null;
        let penalty_payment_count = data.payments.length;
        
        if( status !== null ) {
            status = (data.payments.length > 0) ? 'waived_wp' : 'waived';
        }

        Modal.confirm({
            title: (status == 'waived' || status == 'waived_wp') ? 'Void Penalty' : 'Unvoid Penalty',
            content: <>
                {
                    <Form style={{marginBottom: '10px', marginTop: '10px'}}>
                        <Form.Item label={(status == null) ? 'Additional Payment' : ''} name="addtional_payment">
                            {
                                (status == null) ?
                                <InputNumber defaultValue={defaultAdditionalPenaltyAmount}
                                    min={0}
                                    style={{width: 200}}
                                    size="large"
                                    onChange={(e) => {
                                        defaultAdditionalPenaltyAmount = (e == '' || e == null) ? 0 : e;
                                    }}
                                /> :
                                <Checkbox 
                                    // style={{marginLeft: '-90px'}} 
                                    onChange={(e) => {
                                        let checked = (e.target.checked) ? 1 : 0;
                                        excludePenaltyPayment = checked;
                                    }}
                                >Exclude penalty to payment</Checkbox>
                            }
                            
                        </Form.Item>
                    </Form>
                }
                <div>Are you sure you want to update penalty status?</div>
            </>,
            okText: 'Confirm',
            cancelText: 'Cancel',
            destroyOnClose: true,
            onOk: (e) => {

                if (waivePenaltyQueryIsLoading) {
                    return false;
                }

                Modal.destroyAll();
                message.info('Voiding penalty please wait..');
        
                waivePenaltyQuery({
                    reservation_number: reservation_number,
                    number: amortization_month,
                    status: status,
                    penalty_status: penalty_status,
                    penalty_payment_count: penalty_payment_count,
                    additional_payment: defaultAdditionalPenaltyAmount,
                    exclude_penalty: excludePenaltyPayment,
                }, {
                    onSuccess: (res) => {
                        defaultAdditionalPenaltyAmount = 0;
                        addActivityLog({
                            description: (status == 'waived' || status == 'waived_wp') ? 
                                'Void Penalty ' + `in amortization #${amortization_month}` : 
                                'Unvoid Penalty ' + `in amortization #${amortization_month}`, 
                            action: (status == 'waived' || status == 'waived_wp') ? 'void_penalty' : 'unvoid_penalty'
                        });
                        updateReservationDetails();
                        message.success('Update penalty status success');
                    },
                    onError: (e) => {
                        message.warning("Failed: " + e.message)
                    }
                })
            },
            onCancel: () => {
                excludePenaltyPayment = 0;
                defaultAdditionalPenaltyAmount = 0;
            }
        });
    }

    const handleRecomputeClick = () => {

        Modal.confirm({
            title: 'Recompute',
            content: 'Are you sure you want to calibrate computations in this account?',
            okText: 'Yes',
            cancelText: 'Cancel',
            destroyOnClose: true,
            onOk: () => {

                message.info('Please wait, recomputation of payments is ongoing');

                if (recomputeAccountQueryIsLoading) {
                    return false;
                }
        
                recomputeAccountQuery({
                    reservation_number: reservation_number
                }, {
                    onSuccess: (res) => {
                        addActivityLog({description: `Recomputation of payments`, action: 'recompute'});
                        updateReservationDetails();
                        message.success('Account recomputation success');
                        Modal.destroyAll();
                    },
                    onError: (e) => message.warning("Account recomputation failed")
                })
            }
        });

    }

    const handleUpdateScheduleClick = (data) => {
        let payments = [];
        if( data.payments.length > 0 ) {
            _.map(data.payments, function(payment, i){
                if( payment.payment_type == 'monthly_amortization_payment' ) {
                    payments = payment;
                }
            });
        }
        setAmortizationUpdatePaymentData(payments);
        setAmortizationUpdateData(data);
        setAmortizationUpdateModal(true);
    }

    const updateAmortizationDetils = (values) => {

        if (updateAmortizationQueryIsLoading) {
            return false;
        }

        updateAmortizationQuery({
            data: values
        }, {
            onSuccess: (res) => {
                setAmortizationUpdateModal(false);
                setReservation(res.data);
                setUploadedFiles(res.data.attachments);
                addActivityLog({description: `Updated amortization # ${values.amortization_number} schedule details`, action: 'update_schedule'});
            },
            onError: (e) => {
                updateAmortizationQueryReset();
                message.warning("Update amortization failed")
            }
        })
    }

    const handlePreviewPaymentClick = (data) => {
        let payments = [];
        if( data.payments.length > 0 ) {
            payments = data.payments
        }
        setPaymentDetails(payments);
        setPaymentPreviewModalVisible(true);
    }

    const handleAddPaymentClick = (data) => {
        setPaymentModalVisible(true);
        
        // setAddPaymentData({...data, 
        //     payment_amount: (parseFloat(
        //         (reservation ? 
        //             _.sumBy(reservation.amortization_schedule, (i) => (moment(i.due_date).isBefore(moment()) && (!i.paid_status || i.paid_status == 'partial')) ? parseFloat(i.amount - i.amount_paid) : 0) : 0 )
        //         )).toFixed(2),        
        //     payment_type: 'monthly_amortization_payment',
        // });
    }

    const handleAddPenaltySaveClick = (values) => {

        if (addPenaltyQueryIsLoading) {
            return false;
        }

        addPenaltyQuery({
            reservation_number: values.reservation_number,
            id: values.id,
            penalty_amount: values.penalty_amount,
            number: values.number,
            payment_terms_type: values.payment_terms_type
        }, {
            onSuccess: (res) => {
                message.success("Penalty added!");
                setPenaltyModalVisible(false);
                setAddPenaltyData({
                    penalty_amount: 0,
                    payment_terms_type: reservation.payment_terms_type,
                    split_number: ordinalNumber(values.number) 
                });

                if( values.payment_terms_type === 'cash' ) {
                    addActivityLog({description: `Added penalty details in Split ${values.number} with amount of ${numberWithCommas(values.penalty_amount)}`, action: 'add_penalty'});
                } else {
                    addActivityLog({description: `Added penalty details in Amortization ${values.number} with amount of ${numberWithCommas(values.penalty_amount)}`, action: 'add_penalty'});
                }

                updateReservationDetails();
            },
            onError: (e) => message.warning("Adding penalty failed")
        })
    }

    const addPaymentFinish = (values) => {

        // console.log(values);

        // if( lastBalance === '0.00' ) {
        //     addPaymentQueryReset();
        //     formAddPayment.resetFields();
        //     message.warning(`Amortization is already paid.`);
        //     return false;
        // }

        // return false;

        if (addPaymentQueryIsLoading) {
            return false;
        }

        addPaymentQuery({
            reservation_number: reservation_number,
            payment_amount: values.payment_amount,
            payment_type: values.payment_type,
            pr_number: values.pr_number,
            or_number: values.or_number,
            payment_gateway: values.payment_gateway,
            paid_at: values.paid_at ? moment(values.paid_at).format('YYYY-MM-DD HH:mm:ss') : null,
            remarks: values.remarks,
            discount: values.cash_penalty_discount,
            check_number: values.check_number,
            check_account_number: values.check_account_number,
            bank: values.bank,
            bank_account_number: values.bank_account_number,
            payment_gateway_reference_number: values.payment_gateway_reference_number,
            payment_terms_type: reservation.payment_terms_type,
            advance_payment: (typeof addPaymentData.advance_payment !== 'undefined') ? addPaymentData.advance_payment : 0,
            payment_form: true,
        }, {
            onSuccess: (res) => {
                message.success("Payment added!");
                setPaymentModalVisible(false);
                setAddPaymentData({
                    payment_amount: 0
                });

                setActivityLogRefetch(true);

                setReservation(res.data);
                formAddPayment.resetFields();
            },
            onError: (e) => {
                addPaymentQueryReset();
                message.warning(`Adding payment failed: ${e.errors ? _.map(e.errors, (i) => i) : e.message}`)
            }
        })
    }

    // const handleAddPaymentSaveClick = (values) => {
    //     console.log(values);
    //     //1
    //     if (addPaymentQueryIsLoading) {
    //         return false;
    //     }

    //     addPaymentQuery({
    //         reservation_number: reservation_number,
    //         payment_amount: values.payment_amount,
    //         payment_type: values.payment_type,
    //         pr_number: values.pr_number,
    //         or_number: values.or_number,
    //         payment_gateway: values.payment_gateway,
    //         paid_at: moment(values.paid_at).format('YYYY-MM-DD'),
    //         remarks: values.remarks,
    //     }, {
    //         onSuccess: (res) => {
    //             message.success("Payment added!");
    //             setPaymentModalVisible(false);
    //             setAddPaymentData({
    //                 payment_amount: 0
    //             });

    //             setReservation(res.data);
    //         },
    //         onError: (e) => message.warning("Adding payment failed")
    //     })
    // }

    const addActivityLog = (params) => {
        activityLogsQuery({
            reservation_number: reservation_number,
            action: params.action,
            description: params.description
        }, {
            onSuccess: (res) => {
                setActivityLogRefetch(true);
            }
        })
    }

    const penaltyPaymentModalOpen = (record) => {

        let default_discount = ( record.paid_at == null ) ? DefaultPenaltyDiscountPercentage : record.discount;
        let discount = (record.discount !== 0) ? record.discount : default_discount;
        let payments = record.payments;
        let transaction_id = false;

        if( discount == 0 && parseFloat(record.penalty_amount) > parseFloat(record.amount_paid) && record.amount_paid !== null ) {
            let penalty_amount = parseFloat(record.penalty_amount),
            amount_paid = parseFloat(record.amount_paid);
            discount = (((penalty_amount - amount_paid) / penalty_amount) * 100).toFixed(5);
        }

        if( payments.length > 0 ) {
            _.map(payments, function(payment){
                if( payment.payment_type === 'penalty' ) {
                    transaction_id = payment.transaction_id;
                }
            });
        }

        updatedPenaltyDiscount = discount;
        setPenaltyDiscount(discount);
        setPenaltyAmount(record.penalty_amount);
        setPenaltyFormInitialValue({
            id: record.id,
            amortization_id: record.amortization_schedule_id,
            discount: record.discount,
            remarks: record.remarks,
            penalty_amount: record.penalty_amount,
            number: record.number,
            paid_at: record.paid_at,
            transaction_id: transaction_id,
        });
        setPenaltyPaymentData(record);
        setPenaltyPaymentModal(true);
    }

    const penaltyPaymentFinish = (values) => {

        // let discount = (values.discount != null) ? values.discount : penaltyFormInitialValue.discount;
        // let penalty_amount = (values.penalty_amount != null) ? values.penalty_amount : penaltyFormInitialValue.penalty_amount;

        addPenaltyPaymentQuery({
            id: penaltyFormInitialValue.id,
            discount: penaltyDiscount,
            remarks: (values.remarks != null) ? values.remarks : penaltyFormInitialValue.remarks,
            penalty_amount: penaltyAmount,
            payment_terms_type: reservation.payment_terms_type,
            amortization_id: penaltyFormInitialValue.amortization_id,
            transaction_id: penaltyFormInitialValue.transaction_id,
        }, {
            onSuccess: (res) => {
                let pre_message = 'Updated';
                let action = 'update_penalty';
                if( penaltyFormInitialValue.paid_at == null ) {

                    if(reservation.payment_terms_type === 'cash') {
                        message.success("Penalty payment success!");
                    } else {
                        message.success("Penalty details updated!");
                    }

                    pre_message = (reservation.payment_terms_type === 'cash') ? 'Payment of' : 'Update of';
                    action = (reservation.payment_terms_type === 'cash') ? 'add_penalty' : 'update_penalty';
                } else {
                    message.success("Penalty details updated!");
                }
                Modal.destroyAll();
                setPenaltyPaymentModal(false);
                handleViewPenalties();

                updateReservationDetails();

                if( reservation.payment_terms_type === 'cash' ) {
                    addActivityLog({description: `${pre_message} penalty details in Split ${penaltyFormInitialValue.number} with amount of ${numberWithCommas(penaltyFormInitialValue.penalty_amount)} in ${penaltyDiscount}% discount`, action: action});
                } else {
                    addActivityLog({description: `${pre_message} penalty details in Amortization ${penaltyFormInitialValue.number} with amount of ${numberWithCommas(penaltyFormInitialValue.penalty_amount)} in ${penaltyDiscount}% discount`, action: action});
                }
            },
            onError: (e) => {
                props.params.api.addPenaltyPaymentQueryReset();
                message.warning(`Penalty payment failed: ${e.errors ? _.map(e.errors, (i) => i) : e.message}`)
            }
        })
    }

    const handleDefaultPenaltyDiscount = (values) => {

        if (penaltyDefaultDiscountQueryIsLoading) {
            return false;
        }

        let discount = (typeof values.discount != 'undifined') ? values.discount : 0;

        penaltyDefaultDiscountQuery({
            reservation_number: reservation_number,
            discount: discount
        }, {
            onSuccess: (res) => {
                message.success("Penalty Default Discount Updated");
                Modal.destroyAll();
                DefaultPenaltyDiscountPercentage = discount;
                setPenaltyPaymentModal(false);
                handleViewPenalties();
                updateReservationDetails();
            },
            onError: (e) => {
                message.warning("Update penalty default discount failed");
                penaltyDefaultDiscountQueryReset();
            }
        })
    }

    const handleViewPenalties = () => {
        // console.log('test');

        if (viewPenaltiesQueryIsLoading) {
            return false;
        }

        viewPenaltiesQuery({
            reservation_number: reservation_number,
            payment_terms_type: reservation.payment_terms_type
        }, {
            onSuccess: (res) => {

                let data = res.data;
                let bwp = 0;
                let pp = 0;
                let month = 0;
                let amortization_number = 0;
                let parent_balance = 0;
                let parent_id = 0;
                let penalty_balance = [];
                let penalty_paid = [];
                let dataSourceFilter = ( reservation.payment_terms_type === 'in_house' ) ? {amortization_schedule: { is_collection: 1 }} : {};

                _.map(data, function(penalty){
                    let terms = ( reservation.payment_terms_type === 'in_house' ) ? penalty.amortization_schedule : penalty.cash_term_ledger;
                    let b = 0;
                    if( terms !== null ) {
                        if( month != penalty.number ) {

                            let status = ( typeof penalty.status !== 'undefined' ) ? penalty.status : null;

                            let default_discount = ( penalty.paid_at == null ) ? DefaultPenaltyDiscountPercentage : penalty.discount;
                            let discount = (penalty.discount !== 0) ? penalty.discount : default_discount;
                            let amount_with_penalty = (parseFloat(penalty.penalty_amount) - (parseFloat(penalty.penalty_amount) * (discount / 100))).toFixed(2);

                            if( status == null && penalty.paid_at == null ) {
                                b = parseFloat(terms.amount) + parseFloat(amount_with_penalty);
                                bwp = b + bwp;
                                parent_balance = bwp;
                            } else {
                                pp = parseFloat(terms.amount) + parseFloat(amount_with_penalty);
                            }
                            
                        } else {
                            bwp = parent_balance + parseFloat(penalty.penalty_amount);
                        }
                        penalty_balance.push(bwp.toFixed(2));
                        penalty_paid.push(pp);
                    }
                    month = penalty.number;
                });

                Modal.info({
                    icon: null,
                    title: 'Computation for Interest & Penalties',
                    width: 1200,
                    destroyOnClose: true,
                    transitionName: '',
                    content: <>
                        <Table
                            style={{marginTop: 48}}
                            bordered
                            size="small"
                            dataSource={_.filter(data, dataSourceFilter)}
                            rowKey="id"
                            pagination={{
                                defaultCurrent: penaltyTableCurrentPage,
                                // defaultPageSize: 1
                            }}
                            onChange={(table) => {
                                setPenaltyTableCurrentPage(table.current);
                            }}
                            columns={[
                                {
                                    title: 'Months',
                                    render: (text, record) => {
                                        if( reservation.payment_terms_type === 'in_house' ) {
                                            let due_date = record.amortization_schedule.due_date;
                                            return moment(due_date).format('M/D/YYYY')
                                        } else {
                                            let due_date = record.cash_term_ledger.due_date;
                                            return moment(due_date).format('M/D/YYYY')
                                        }
                                    }
                                },
                                {
                                    title: (reservation.payment_terms_type === 'in_house') ? 'Mos Past Due' : 'Split Past Due',
                                    render: (text, record) => {
                                        if( reservation.payment_terms_type === 'in_house' ) {
                                            let number = record.amortization_schedule.number;
                                            return number;
                                        } else {
                                            let number = record.cash_term_ledger.number;
                                            return number;
                                        }
                                    }
                                },
                                {
                                    title: (reservation.payment_terms_type === 'in_house') ? 'Monthly Amortization' : 'Split Amount',
                                    render: (text, record) => {
                                        if( reservation.payment_terms_type === 'in_house' ) {
                                            let amount = record.amortization_schedule.amount;
                                            if( amortization_number != record.number ) {
                                                parent_id = record.id;
                                                amortization_number = record.number;
                                            }
                                            if( parent_id === record.id ) {
                                                return numberWithCommas(twoDecimalPlace(amount));
                                            } else {
                                                return '-';
                                            }
                                        } else {
                                            let amount = record.cash_term_ledger.amount;
                                            if( amortization_number != record.number ) {
                                                parent_id = record.id;
                                                amortization_number = record.number;
                                            }
                                            if( parent_id === record.id ) {
                                                return numberWithCommas(twoDecimalPlace(amount));
                                            } else {
                                                return '-';
                                            }
                                        }
                                        
                                    }
                                },
                                {
                                    title: 'Penalty',
                                    render: (text, record) => {
                                        let default_discount = ( record.paid_at == null ) ? DefaultPenaltyDiscountPercentage : record.discount;
                                        let discount = (record.discount !== 0) ? record.discount : default_discount;
                                        let amount_with_penalty = (parseFloat(record.penalty_amount) - (parseFloat(record.penalty_amount) * (discount / 100))).toFixed(2);
                                        let amount  = numberWithCommas(twoDecimalPlace(amount_with_penalty));
                                        let additional_amount = (record.system_generated === 1) ? '(3%)' : '';

                                        if( typeof record.status !== 'undefined' ) {
                                            return (record.status != 'waived' && record.status != 'waived_wp') ? amount : '-';
                                        } else {
                                            return amount;
                                        }
                                        
                                    }
                                },
                                {
                                    title: 'Balance',
                                    render: (text, record, i) => {
                                        let total_balance = numberWithCommas(twoDecimalPlace(penalty_balance[i]));
                                        
                                        if( typeof record.status !== 'undefined' ) {
                                            if( (record.status != 'waived' && record.status != 'waived_wp') ) {
                                                return (record.paid_at != null) ? numberWithCommas(twoDecimalPlace(penalty_paid[i])) : total_balance;
                                            } else {
                                                return '-';
                                            }
                                        } else {
                                            return total_balance;
                                        }
                                    }
                                },
                                {
                                    title: 'Discount',
                                    render: (text, record) => {

                                        let default_discount = ( record.paid_at == null ) ? DefaultPenaltyDiscountPercentage : record.discount;
                                        let discount = (record.discount !== 0) ? record.discount : default_discount;

                                        // console.log(record.discount, record.paid_at, default_discount, discount, penaltyDefaultDiscountPercentage);

                                        if( discount == 0 && parseFloat(record.penalty_amount) > parseFloat(record.amount_paid) && record.amount_paid !== null ) {
                                            let penalty_amount = parseFloat(record.penalty_amount),
                                            amount_paid = parseFloat(record.amount_paid);
                                            discount = (((penalty_amount - amount_paid) / penalty_amount) * 100).toFixed(5);
                                        }

                                        if( typeof record.status !== 'undefined' ) {
                                            return (record.status != 'waived' && record.status != 'waived_wp') ? `${discount}%` : '-';
                                        } else {
                                            return `${discount}%`;
                                        }

                                        
                                    }
                                },
                                {
                                    title: 'Actual Payment',
                                    render: (text, record, i) => {
                                        return (record.amount_paid === null) ? 0 : numberWithCommas(twoDecimalPlace(record.amount_paid));
                                    }
                                },
                                {
                                    title: 'Status',
                                    render: (text, record, i) => {

                                        let status = ( typeof record.status !== 'undefined' ) ? record.status : null;

                                        if( status == null ) {
                                            return (record.paid_at == null ) ? <Tag color="orange">Not Paid</Tag> : <Tag color="green">Paid</Tag>;
                                        } else {
                                            switch (status) {
                                                case 'paid':
                                                    return <Tag color="green">Paid</Tag>;
                                                    break;
                                                case 'not_paid':
                                                    return <Tag color="orange">Not Paid</Tag>;
                                                    break;
                                                case 'waived':
                                                case 'waived_wp':
                                                    return <Tag color="red">Void</Tag>;
                                                    break;
                                                default:
                                                    break;
                                            }
                                        }
                                    }
                                },
                                {
                                    title: 'Action',
                                    render: (text, record, i) => {
                                        return <Dropdown overlay={
                                            <Menu>
                                                <Menu.Item onClick={()=> penaltyPaymentModalOpen(record)}>Penalty Details</Menu.Item>
                                            </Menu>
                                        }>
                                            <Button icon={<EllipsisOutlined />} />
                                        </Dropdown>
                                    }
                                },
                            ]}
                            summary = { (data) => {

                                let summary_amortization_month = 0;

                                const total_monthly_amount = _.map(data, function(penalty){
                                    let term = ( reservation.payment_terms_type === 'in_house' ) ? penalty.amortization_schedule : penalty.cash_term_ledger;
                                    if( term !== null ) {

                                        let status = ( typeof penalty.status !== 'undefined' ) ? penalty.status : null;

                                        let amount = 0;
                                        if( status == null && penalty.paid_at == null ) {

                                            if( summary_amortization_month != penalty.number ) {
                                                amount = _.sumBy([term], function(schedule){
                                                    return parseFloat(schedule.amount);
                                                });
                                            }

                                            summary_amortization_month = penalty.number;
                                        }
                                        
                                        return amount;
                                    }
                                }).reduce(function(a, b){
                                    return a + b;
                                }, 0);

                                const penalty = _.map(data, function(penalty){

                                    let status = ( typeof penalty.status !== 'undefined' ) ? penalty.status : null;

                                    if( status == null && penalty.paid_at == null ) {

                                        let default_discount = ( penalty.paid_at == null ) ? DefaultPenaltyDiscountPercentage : penalty.discount;
                                        let discount = (penalty.discount !== 0) ? penalty.discount : default_discount;
                                        let amount_with_penalty = (parseFloat(penalty.penalty_amount) - (parseFloat(penalty.penalty_amount) * (discount / 100))).toFixed(2);
    
                                        return parseFloat(amount_with_penalty);

                                    } else {
                                        return 0;
                                    }
                                    

                                }).reduce(function(a, b){
                                    return a + b;
                                }, 0);

                                const total_actual_paid = _.sumBy(data, function(penalty){
                                    if( penalty.amount_paid != null ) {
                                        return parseFloat(penalty.amount_paid);
                                    }
                                });

                                const balance = total_monthly_amount + penalty;

                                return <><tr><td colSpan={9}></td></tr>
                                    <tr className='table-summary'>
                                        <td><strong>TOTAL:</strong></td>
                                        <td></td>
                                        <td><strong>{numberWithCommas(twoDecimalPlace(total_monthly_amount))}</strong></td>
                                        <td><strong>{numberWithCommas(twoDecimalPlace(penalty))}</strong></td>
                                        <td><strong>{numberWithCommas(twoDecimalPlace(balance))}</strong></td>
                                        <td></td>
                                        <td><strong>{numberWithCommas(twoDecimalPlace(total_actual_paid))}</strong></td>
                                        <td></td>
                                        <td></td>
                                    </tr></>
                            }}
                        />
                        <Form form={defaultPenaltyDiscount} layout="inline" onFinish={handleDefaultPenaltyDiscount} style={{marginTop: '5px'}}>
                            <Form.Item name="discount" label="Default Penalty Discount" initialValue={DefaultPenaltyDiscountPercentage} style={{width: '23%'}}>
                                <InputNumber
                                    formatter={value => `${value}%`}
                                    parser={value => value.replace('%', '')}
                                    min={0}
                                    max={100}
                                    style={{width: '100%', marginTop: '-3px'}}
                                    size="large"
                                    onChange={(e)=> {
                                        let discount = ( e === '' || parseFloat(e) < 0 ) ? 0 : e;
                                    }}
                                />
                            </Form.Item>
                            <Form.Item>
                                <Button type="primary" onClick={()=> defaultPenaltyDiscount.submit()}>Update</Button>
                            </Form.Item>
                        </Form>
                    </>
                })
            },
            onError: (e) => message.info(e.message),
        })
    }

    const uploadProps = {
        onRemove: file => {
          setFileList( prev => {
            const index = prev.indexOf(file);
            const newFileList = prev.slice();
            newFileList.splice(index, 1);

            return newFileList;
          });
        },
        beforeUpload: file => {

            let formData = new FormData();
            formData.append('file', file);
            formData.append('reservation_number', reservation_number);

            uploadFileQuery(formData, {
                onSuccess: (res) => {
                    console.log(res);
                    setUploadedFiles( prev => {
                        return [...prev, res.data];
                    });

                    addActivityLog({description: 'Uploaded Reservation Attachments', action: 'upload_file'});

                    setFileList([]);
                },
                onError: (e) => {
                    console.log(e);
                }
            })
            return false;
        },
        fileList,
    };

    const handleRemoveFileAttachment = (id, file_path) => {
        if (removeFileQueryIsLoading) {
            return false;
        }

        removeFileQuery({
            id: id,
            file_path: file_path
        }, {
            onSuccess: (res) => {
                message.success("Attachment removed!");

                setUploadedFiles( prev => {
                    let newData = [...prev].filter( i => i.file_path != file_path);

                    return newData;
                });
            },
            onError: (e) => {
                message.warning("Attachment not removed.");
            }
        })
    }

    const handleDownloadBIS = () => {
        if (downloadBISQueryIsLoading) {
            return false;
        }

        downloadBISQuery(
            {
                reservation_number: reservation_number,
            }, {
                onSuccess: (res) => {
                    //Create a Blob from the PDF Stream
                    const file = new Blob(
                        [res.data], 
                        {type: 'application/pdf'});
                    //Build a URL from the file
                    const fileURL = URL.createObjectURL(file);
                    //Download fileURL
                    var a = document.createElement("a");
                    a.href = fileURL;
                    a.download = `RIS - ${reservation.reservation_number}`;
                    a.click();
                    window.URL.revokeObjectURL(fileURL);

                    addActivityLog({description: 'Download RIS as PDF', action: 'download_file'});

                    message.success("Download complete!");
                },
                onError: (e) => {
                    message.warning("Failed.");
                }
            }
        )
    }

    const handleAddFeesFinish = (values) => {
        console.log(values);

        Modal.confirm({
            content: <><div>Are you sure you want to add Fees?</div>
            
                    <Descriptions className='mt-4'>
                        <Descriptions.Item span={3} label="Reservation #">{reservation_number}</Descriptions.Item>
                        <Descriptions.Item span={3} label="Type">{values.type}</Descriptions.Item>
                        <Descriptions.Item span={3} label="Amount">{values.amount}</Descriptions.Item>
                        <Descriptions.Item label="Remarks">{values.remarks}</Descriptions.Item>
                    </Descriptions>
                </>,
            onOk: () => {
                // alert("OK");

                if (addFeesQueryIsLoading) {
                    return false;
                }

                addFeesQuery(values,
                {
                    onSuccess: (res) => {
                        console.log(res.data);
                        message.success("Fees added!");

                        formAddFees.resetFields();
                        setFeesModalVisible(false);
                    },
                    onError: (e) => {
                        console.log(e);
                        addFeesQueryReset();
                    }
                })
            }
        });
    }

    const hideSchedColumn = (checked, setColumnState) => {
        if( checked ) {
            setColumnState(false);
        } else {
            setColumnState(true);
        }
    }

    return (
        <div className="mt-4">
            {/* {reservation_number ?? ''} */}

            <Modal
                visible={feesModalVisible}
                onCancel={()=>setFeesModalVisible(false)}
                footer={null}
                title="Add Fees"
            >
                <Form
                    form={formAddFees}
                    onFinish={handleAddFeesFinish}
                    layout={'vertical'}
                    initialValues={{
                        reservation_number: reservation_number,
                    }}
                >
                    <Form.Item label="Reservation #" name="reservation_number" rules={[{ required: true }]}>
                        <Input disabled />
                    </Form.Item>

                    <Form.Item label="Type" name="type" rules={[{ required: true }]}>
                        <Select>
                            <Select.Option value="penalty_fee">Penalty fee</Select.Option>
                            <Select.Option value="retention_fee">Retention fee</Select.Option>
                            <Select.Option value="docs_fee">Docs fee</Select.Option>
                            <Select.Option value="title_fee">Title fee</Select.Option>
                            <Select.Option value="redocs_fee">Redocs fee</Select.Option>
                        </Select>
                    </Form.Item>

                    <Form.Item label="Amount" name="amount" rules={[{ required: true }]}>
                        <InputNumber style={{maxWidth: '100%', width: 200}} />
                    </Form.Item>

                    <Form.Item label="Remarks" name="remarks" rules={[{ required: false }]}>
                        <TextArea style={{borderRadius: 12}} />
                    </Form.Item>

                    <Button block type="primary" className='mt-4' htmlType='submit'>Save</Button>
                </Form>
            </Modal>

            <Modal
                visible={penaltyModalVisible}
                onCancel={()=>setPenaltyModalVisible(false)}
                footer={null}
                title="Add Penalty"
            >
                <Descriptions bordered size="small">
                    <Descriptions.Item span={4} label="Reservation #">{addPenaltyData.reservation_number}</Descriptions.Item>
                    <Descriptions.Item span={4} label="Date due">{moment(addPenaltyData.due_date).format('MMM D, YYYY')}</Descriptions.Item>
                    <Descriptions.Item span={4} label={(reservation.payment_terms_type == 'in_house') ? `Amortization number` : `Split number` }>{addPenaltyData.number}</Descriptions.Item>
                    <Descriptions.Item span={4} label="Amount">{addPenaltyData.amount}</Descriptions.Item>
                </Descriptions>

                <div style={{padding: '10px 0'}}>Penalty (PHP): <InputNumber min={0} style={{width: 200}} size="large" defaultValue={(addPenaltyData.penalty_amount ? addPenaltyData.penalty_amount : 0)} onChange={(e) => setAddPenaltyData({ ...addPenaltyData, penalty_amount: e })} /></div>

                <Button block type="primary" onClick={()=>handleAddPenaltySaveClick(addPenaltyData)}>Save</Button>
            </Modal>

            <Modal
                visible={amortizationUpdateModal}
                onCancel={()=>{
                    formUpdateAmortization.resetFields();
                    setAmortizationUpdateModal(false)
                    setAmortizationUpdateData([]);
                    setAmortizationUpdatePaymentData([]);
                }}
                afterClose={(e) => {
                    formUpdateAmortization.resetFields();
                    setAmortizationUpdateModal(false)
                    setAmortizationUpdateData([]);
                    setAmortizationUpdatePaymentData([]);
                }}
                footer={null}
                title="Update Schedule Details"
                destroyOnClose={true}
            >
                
                <Descriptions bordered size="small" className='mb-4'>
                    <Descriptions.Item span={4} label="Amortization">{ordinalNumber(amortizationUpdateData.number)}</Descriptions.Item>
                    <Descriptions.Item span={4} label="Due Date">{moment(amortizationUpdateData.due_date).format('MMM D, YYYY')}</Descriptions.Item>
                </Descriptions>

                <Form
                    layout={"horizontal"}
                    labelCol={{ span: 8 }}
                    wrapperCol={{ span: 14 }}
                    form={formUpdateAmortization}
                    onFinish={updateAmortizationDetils}
                >
                    
                    <Form.Item name="reservation_number" style={{display: 'none'}} initialValue={amortizationUpdateData.reservation_number}>
                        <Input hidden={true} value={amortizationUpdateData.reservation_number} />
                    </Form.Item>

                    <Form.Item name="amortization_id" style={{display: 'none'}} initialValue={amortizationUpdateData.id}>
                        <Input hidden={true} value={amortizationUpdateData.id} />
                    </Form.Item>

                    <Form.Item name="amortization_number" style={{display: 'none'}} initialValue={amortizationUpdateData.number}>
                        <Input hidden={true} value={amortizationUpdateData.number} />
                    </Form.Item>

                    <Form.Item name="transaction_id" style={{display: 'none'}} initialValue={amortizationUpdatePaymentData.transaction_id}>
                        <Input hidden={true} value={amortizationUpdatePaymentData.transaction_id} />
                    </Form.Item>

                    <Form.Item label="Date paid" name="paid_at" rules={[{required: true}]} initialValue={moment(amortizationUpdatePaymentData.paid_at)}>
                        <DatePicker style={{width: 200}} size="large"
                            onChange={(e) => setAmortizationUpdatePaymentData({ ...amortizationUpdatePaymentData, paid_at: e ? moment(e) : undefined })}
                        />
                    </Form.Item>

                    <Form.Item label="Amount Paid (PHP)" name="amount_paid" rules={[{required:true}]} initialValue={amortizationUpdatePaymentData.payment_amount}>
                        <InputNumber min={0}
                            style={{width: 200}}
                            size="large"
                            onChange={(e) => setAmortizationUpdatePaymentData({ ...amortizationUpdatePaymentData, payment_amount: e })}
                        />
                    </Form.Item>

                    <Form.Item label="PR#" name="cr_number" initialValue={amortizationUpdatePaymentData.cr_number}>
                        <Input
                            style={{width: 200}}
                            size="large"
                            onChange={(e) => setAmortizationUpdatePaymentData({ ...amortizationUpdatePaymentData, cr_number: e.target.value })}
                        />
                    </Form.Item>

                    <Form.Item label="OR#" name="or_number" initialValue={amortizationUpdatePaymentData.or_number}>
                        <Input
                            style={{width: 200}}
                            size="large"
                            onChange={(e) => setAmortizationUpdatePaymentData({ ...amortizationUpdatePaymentData, or_number: e.target.value })}
                        />
                    </Form.Item>

                    <Form.Item label="Payment gateway" rules={[{required: true}]} name="payment_gateway" initialValue={amortizationUpdatePaymentData.payment_gateway}>
                        <Select style={{marginLeft: 4, width: '100%'}}
                            onChange={(e) => setAmortizationUpdatePaymentData({ ...amortizationUpdatePaymentData, payment_gateway: e})}
                            >
                            <Select.OptGroup label="Online Payment">
                                <Select.Option value="DragonPay">DragonPay</Select.Option>
                                <Select.Option value="PayMaya">PayMaya</Select.Option>
                                <Select.Option value="PesoPay">PesoPay</Select.Option>
                            </Select.OptGroup>
                            <Select.Option value="Cash">Cash</Select.Option>
                            <Select.Option value="PDC">PDC</Select.Option>
                            <Select.Option value="Direct Payment">Direct Payment</Select.Option>
                        </Select>
                    </Form.Item>

                    {
                        ['DragonPay', 'PayMaya', 'PesoPay'].includes(amortizationUpdatePaymentData.payment_gateway) &&
                        <Form.Item label="Payment Gateway Reference #" name="payment_gateway_reference_number" rules={[{required: true}]} initialValue={amortizationUpdatePaymentData.payment_gateway_reference_number}>
                            <Input
                                style={{width: 200}}
                                size="large"
                                value={amortizationUpdatePaymentData.payment_gateway_reference_number}
                                onChange={(e) => setAmortizationUpdatePaymentData({ ...amortizationUpdatePaymentData, payment_gateway_reference_number: e.target.value })}
                            />
                        </Form.Item>
                    }

                    {
                        ['PDC', 'Direct Payment', 'Direct Deposit', 'Bank Transfer'].includes(amortizationUpdatePaymentData.payment_gateway) &&
                            <Form.Item label="Bank" name="bank" rules={[{required: true}]} initialValue={amortizationUpdatePaymentData.bank}>
                                <Input
                                    style={{width: 200}}
                                    size="large"
                                    value={amortizationUpdatePaymentData.bank}
                                    onChange={(e) => setAmortizationUpdatePaymentData({ ...amortizationUpdatePaymentData, bank: e.target.value })}
                                />
                            </Form.Item>
                    }

                    {
                        ['PDC'].includes(amortizationUpdatePaymentData.payment_gateway) &&
                        <Form.Item label="Check #" name="check_number" rules={[{required: true}]} initialValue={amortizationUpdatePaymentData.check_number}>
                            <Input
                                style={{width: 200}}
                                size="large"
                                value={amortizationUpdatePaymentData.check_number}
                                onChange={(e) => setAmortizationUpdatePaymentData({ ...amortizationUpdatePaymentData, check_number: e.target.value })}
                            />
                        </Form.Item>
                    }

                    {
                        ['PDC', 'Direct Payment', 'Direct Deposit', 'Bank Transfer'].includes(amortizationUpdatePaymentData.payment_gateway) &&
                        <Form.Item label="Bank Account #" name="bank_account_number" rules={[{required: true}]} initialValue={amortizationUpdatePaymentData.bank_account_number}>
                            <Input
                                style={{width: 200}}
                                size="large"
                                value={amortizationUpdatePaymentData.bank_account_number}
                                onChange={(e) => setAmortizationUpdatePaymentData({ ...amortizationUpdatePaymentData, bank_account_number: e.target.value })}
                            />
                        </Form.Item>
                    }

                    <Form.Item label="Remarks" name="remarks" initialValue={amortizationUpdatePaymentData.remarks}>
                        <TextArea
                            style={{width: '100%', borderRadius: 8}}
                            size="large"
                            onChange={(e) => setAmortizationUpdatePaymentData({ ...amortizationUpdatePaymentData, remarks: e.target.value })}
                        />
                    </Form.Item>

                    <Popconfirm
                        title="Are you sure you want to apply changes?"
                        onConfirm={() => formUpdateAmortization.submit()}
                        onCancel={() => console.log("cancell amortization update")}
                        okText="Yes"
                        cancelText="No"
                    >
                        <Button block type="primary" className='mt-2'>Save</Button>
                    </Popconfirm>
                </Form>

            </Modal>

            <Modal
                visible={paymentPreviewModalVisible}
                onCancel={()=>setPaymentPreviewModalVisible(false)}
                footer={null}
                title="Payment Details"
            >
                {
                    paymentDetails.length > 0 ?
                    _.map(paymentDetails, function(record, i) {
                        // if( i === 0 ) {
                            
                            return <Descriptions bordered size="small" style={{marginBottom: '10px'}}>
                                <Descriptions.Item span={4} label="Date">{moment(record.paid_at).format('MMM D, YYYY')}</Descriptions.Item>
                                <Descriptions.Item span={4} label="Transaction #">{record.transaction_id}</Descriptions.Item>
                                <Descriptions.Item span={4} label="Amount">{numberWithCommas(record.payment_amount)}</Descriptions.Item>
                                {
                                    (record.cr_number) &&
                                    <Descriptions.Item span={4} label="PR #">{record.cr_number}</Descriptions.Item>
                                }
                                {
                                    (record.or_number) &&
                                    <Descriptions.Item span={4} label="OR #">{record.or_number}</Descriptions.Item>
                                }
                                <Descriptions.Item span={4} label="Payment Gateway">{record.payment_gateway}</Descriptions.Item>
                                {
                                    (record.payment_gateway_reference_number) &&
                                    <Descriptions.Item span={4} label="Gateway Reference #">{record.payment_gateway_reference_number}</Descriptions.Item>
                                }
                                {
                                    (record.bank) &&
                                    <Descriptions.Item span={4} label="Bank">{record.bank}</Descriptions.Item>
                                }
                                {
                                    (record.bank_account_number) &&
                                    <Descriptions.Item span={4} label="Bank Account #">{record.bank_account_number}</Descriptions.Item>
                                }
                                {
                                    (record.check_number) &&
                                    <Descriptions.Item span={4} label="Check #">{record.check_number}</Descriptions.Item>
                                }
                                {
                                    (record.payment_type) &&
                                    <Descriptions.Item span={4} label="Payment Type">
                                        <div style={{width: '235px', maxWidth: '235px'}}>{record.payment_type}</div>
                                    </Descriptions.Item>
                                }
                            </Descriptions>
                        // }
                    }) : <div style={{textAlign: 'center'}}>No Payment found</div>
                }

                
            </Modal>

                <Modal
                    visible={penaltyPaymentModal}
                    onCancel={() => {
                        formAddPenaltyPayment.resetFields();
                        setPenaltyPaymentModal(false);
                        setPenaltyPaymentData([]);
                    }}
                    afterClose={(e) => {
                        formAddPenaltyPayment.resetFields();
                        setPenaltyPaymentModal(false)
                        setPenaltyPaymentData([]);
                    }}                    
                    footer={null}
                    title="Penalty Payment Details"
                    destroyOnClose={true}
                >
                    
                    <Form
                        layout={"horizontal"}
                        form={formAddPenaltyPayment}
                        onFinish={penaltyPaymentFinish}
                        style={{marginBottom: '20px'}}
                        // initialValues={penaltyFormInitialValue}
                    >

                        <Descriptions bordered size="small">
                            {
                                (penaltyPaymentData.system_generated == 1) && 
                                <Descriptions.Item span={4} label="System Generated"><Tag color="green">Yes</Tag></Descriptions.Item>
                            }
                            <Descriptions.Item span={4} label="Reservation #">{penaltyPaymentData.reservation_number}</Descriptions.Item>

                            { reservation.payment_terms_type === 'in_house' ?
                                <>
                                    <Descriptions.Item span={4} label="Date due">{(typeof penaltyPaymentData.amortization_schedule !== 'undefined') ? moment(penaltyPaymentData.amortization_schedule.due_date).format('MMM D, YYYY') : ''}</Descriptions.Item>
                                    <Descriptions.Item span={4} label="Amortization number">{(typeof penaltyPaymentData.amortization_schedule !== 'undefined') ? penaltyPaymentData.amortization_schedule.number : ''}</Descriptions.Item>
                                    <Descriptions.Item span={4} label="Monthly amortization">{(typeof penaltyPaymentData.amortization_schedule !== 'undefined') ? numberWithCommas(penaltyPaymentData.amortization_schedule.amount) : ''}</Descriptions.Item>
                                </> : 
                                <>
                                    <Descriptions.Item span={4} label="Date due">{(typeof penaltyPaymentData.cash_term_ledger !== 'undefined') ? moment(penaltyPaymentData.cash_term_ledger.due_date).format('MMM D, YYYY') : ''}</Descriptions.Item>
                                    <Descriptions.Item span={4} label="Split number">{(typeof penaltyPaymentData.cash_term_ledger !== 'undefined') ? penaltyPaymentData.cash_term_ledger.number : ''}</Descriptions.Item>
                                    <Descriptions.Item span={4} label="Split amount">{(typeof penaltyPaymentData.cash_term_ledger !== 'undefined') ? numberWithCommas(penaltyPaymentData.cash_term_ledger.amount) : ''}</Descriptions.Item>
                                </>
                            }

                            {/* <Descriptions.Item span={4} label="Penalty Amount">{penaltyPaymentData.penalty_amount}</Descriptions.Item> */}
                            <Descriptions.Item span={4} label="Penalty Amount">
                                <Form.Item label="" name="penalty_amount" initialValue={penaltyAmount} rules={[{required:true}]}>
                                    <InputNumber
                                        style={{width: '100%'}}
                                        size="large"
                                        // defaultValue={penaltyPaymentData.discount}
                                        onChange={(e)=> {
                                            let penalty_amount = ( e === '' || parseFloat(e) < 0 ) ? 0 : e;
                                            setPenaltyAmount(parseFloat(penalty_amount));
                                        }}
                                    />
                                </Form.Item>
                            </Descriptions.Item>
                            <Descriptions.Item span={4} label="Actual Payment">
                                { 
                                    (!isNaN((parseFloat(penaltyAmount) - (parseFloat(penaltyAmount) * (penaltyDiscount / 100))).toFixed(2))) ?
                                        (parseFloat(penaltyAmount) - (parseFloat(penaltyAmount) * (penaltyDiscount / 100))).toFixed(2) :
                                        0
                                }
                            </Descriptions.Item>
                            <Descriptions.Item span={4} label="Discount">
                                <Form.Item label="" name="discount" noStyle initialValue={updatedPenaltyDiscount}>
                                    <InputNumber
                                        formatter={value => `${value}%`}
                                        parser={value => value.replace('%', '')}
                                        min={0}
                                        max={100}
                                        style={{width: '100%'}}
                                        size="large"
                                        // defaultValue={penaltyPaymentData.discount}
                                        onChange={(e)=> {
                                            let discount = ( e === '' || parseFloat(e) < 0 ) ? 0 : e;
                                            setPenaltyDiscount(parseFloat(discount));
                                        }}
                                    />
                                </Form.Item>
                            </Descriptions.Item>
                            <Descriptions.Item span={4} label="Remarks">
                                <Form.Item label="" name="remarks" noStyle initialValue={penaltyPaymentData.remarks}>
                                    <TextArea
                                        style={{width: '100%', borderRadius: 8}}
                                        size="large"
                                        // defaultValue={penaltyPaymentData.remarks}
                                    />
                                </Form.Item>
                            </Descriptions.Item>
                        </Descriptions>

                    </Form>

                    {
                        penaltyPaymentData.status !== 'waived' && penaltyPaymentData.status != 'waived_wp' &&
                        <Popconfirm
                            title={(penaltyPaymentData.paid_at == null) ? `Are you sure you want to apply Payment?` : `Are you sure you want to update Payment?`}
                            onConfirm={() => formAddPenaltyPayment.submit()}
                            okText="Yes"
                            cancelText="No"
                        >
                            <Button block type="primary" className='mt-2'>{(penaltyPaymentData.paid_at == null) ? `Make Payment` : `Update Payment`}</Button>
                        </Popconfirm>
                    }

                    {
                        (penaltyPaymentData.status == 'waived' || penaltyPaymentData.status == 'waived_wp') &&
                        <Popconfirm
                            title='Are you sure you want to update penalty details?'
                            onConfirm={() => formAddPenaltyPayment.submit()}
                            okText="Yes"
                            cancelText="No"
                        >
                            <Button block type="primary" className='mt-2'>Update Details</Button>
                        </Popconfirm>
                    }

                </Modal>

            <Modal
                visible={paymentModalVisible}
                onCancel={(e)=> {
                    setPaymentModalVisible(false);
                }}
                afterClose={()=> {
                    formAddPayment.resetFields();
                    setPaymentModalVisible(false);
                }} 
                footer={null}
                title="Add Payment"
            >
                <Descriptions bordered size="small">
                    <Descriptions.Item span={4} label="Reservation #">{reservation_number}</Descriptions.Item>
                    {
                        ['penalty_fee'].includes(addPaymentData.payment_type) && 
                        <Descriptions.Item span={4} label="Discounted Amount">
                            {
                                (!isNaN(addPaymentData.cash_penalty_discount)) ? 
                                    parseFloat(addPaymentData.payment_amount) - ((parseFloat(addPaymentData.cash_penalty_discount) / 100) * parseFloat(addPaymentData.payment_amount))
                                    : 0
                            }
                        </Descriptions.Item>
                    }
                </Descriptions>

                <Form
                    layout={"horizontal"}
                    labelCol={{ span: 8 }}
                    wrapperCol={{ span: 14 }}
                    form={formAddPayment}
                    onFinish={addPaymentFinish}
                    initialValues={{
                        reservation_number: reservation_number,
                        payment_amount: 0,
                        payment_type: '',
                        advance_payment: 0
                    }}
                >
                
                <br />

                {/* <div>
                    <strong>Past dues</strong>
                {
                    _.map(reservation.amortization_schedule, (i, o) => {
                        if (moment(i.due_date).isBefore(moment()) && (!i.paid_status || i.paid_status == 'partial') && o == 0) {
                            let amountPaid = ( i.amount_paid !== null ) ? i.amount_paid : 0;
                            return <Tag color="red" key={i}>
                                <div><strong>{i.amount - amountPaid}</strong></div>
                                <div>{moment(i.due_date).format('MMM D, YYYY')}</div>
                                </Tag>
                        }
                    })
                }
                </div> */}

                <Form.Item label="Payment (PHP)" name="payment_amount" rules={[{required:true}]}>
                    <InputNumber min={0}
                        style={{width: 200}}
                        size="large"
                        // value={addPaymentData.payment_amount}
                        onChange={(e) => setAddPaymentData({ ...addPaymentData, payment_amount: e })}
                    />
                </Form.Item>

                {
                    ['penalty_fee'].includes(addPaymentData.payment_type) &&
                    <Form.Item label="Discount" name="cash_penalty_discount" initialValue={0}>
                        <InputNumber
                            formatter={value => `${value}%`}
                            parser={value => value.replace('%', '')}
                            min={0}
                            max={100}
                            style={{width: 100}}
                            size="large"
                            onChange={(e)=> {
                                let discount = ( e === '' || parseFloat(e) < 0 ) ? 0 : e;
                                setAddPaymentData({ ...addPaymentData, cash_penalty_discount: discount })
                            }}
                        />
                    </Form.Item> 
                }

                <Form.Item label="PR#" name="pr_number">
                    <Input
                        style={{width: 200}}
                        size="large"
                        // value={addPaymentData.pr_number}
                        // onChange={(e) => setAddPaymentData({ ...addPaymentData, pr_number: e.target.value })}
                    />
                </Form.Item>

                <Form.Item label="OR#" name="or_number">
                    <Input
                        style={{width: 200}}
                        size="large"
                        value={addPaymentData.or_number}
                        onChange={(e) => setAddPaymentData({ ...addPaymentData, or_number: e.target.value })}
                    />
                </Form.Item>

                <Form.Item label="Date paid" name="paid_at" rules={[{required: true}]}
                    // extra={<div><small>leave blank for pending status</small></div>}
                    >
                    <DatePicker
                        style={{width: 200}}
                        size="large"
                        showTime
                        disabledDate={(current) => {
                            return current && current > moment().endOf('day');
                        }}
                        // value={addPaymentData.paid_at}
                        // allowClear={true}
                        // onChange={(e) => setAddPaymentData({ ...addPaymentData, paid_at: e ? moment(e) : undefined })}
                    />
                </Form.Item>

                <Form.Item label="Remarks" name="remarks">
                    <TextArea
                        style={{width: 200, borderRadius: 8}}
                        size="large"
                        // value={addPaymentData.remarks}
                        // onChange={(e) => setAddPaymentData({ ...addPaymentData, remarks: e.target.value })}
                    />
                </Form.Item>

                <Form.Item label="Payment type" name="payment_type" rules={[{required: true}]}>
                    <Select style={{marginLeft: 4, width: '100%'}}
                        // defaultValue={addPaymentData.payment_type}
                        onChange={(e) => setAddPaymentData({ ...addPaymentData, payment_type: e })}
                        >
                        {
                            reservation.payment_terms_type !== 'cash' &&
                            <>
                                <Select.Option value="reservation_fee_payment">Reservation</Select.Option>
                                <Select.Option value="downpayment">Downpayment</Select.Option>
                                <Select.Option value="title_fee">Title Fee</Select.Option>
                                <Select.Option value="redocs_fee">Redocumentation Fee</Select.Option>
                                <Select.Option value="docs_fee">Documentation Fee</Select.Option>
                                <Select.Option value="monthly_amortization_payment">Monthly Amortization Payment</Select.Option>
                            </>
                        }
                        {
                            reservation.payment_terms_type === 'cash' &&
                            <>
                                <Select.Option value="reservation_fee_payment">Reservation</Select.Option>
                                <Select.Option value="full_cash">Full Cash</Select.Option>
                                <Select.Option value="partial_cash">Partial Cash</Select.Option>
                                {
                                    reservation.split_cash &&
                                    <Select.Option value="split_cash">Split Cash</Select.Option>
                                }
                                {
                                    reservation.with_five_percent_retention_fee &&
                                    <Select.Option value="retention_fee">Retention Fee</Select.Option>
                                }
                                <Select.Option value="title_fee">Title Fee</Select.Option>
                                <Select.Option value="redocs_fee">Redocumentation Fee</Select.Option>
                                <Select.Option value="docs_fee">Documentation Fee</Select.Option>
                            </>
                        }
                    </Select>
                </Form.Item>

                {
                    ['monthly_amortization_payment'].includes(addPaymentData.payment_type) && reservation.old_reservation === 0 &&
                    <Form.Item label="" name="advance_payment" style={{fontSize: 30, marginLeft: 148, marginTop: '-30px'}}>
                        <Checkbox 
                            style={{marginLeft: '-90px'}} 
                            onChange={(e) => {
                                let checked = (e.target.checked) ? 1 : 0;
                                setAddPaymentData({ ...addPaymentData, advance_payment: checked })}
                            }
                        >Advance Reservation Payment</Checkbox>
                    </Form.Item>
                }

                <Form.Item label="Payment gateway" rules={[{required: true}]} name="payment_gateway">
                    <Select style={{marginLeft: 4, width: '100%'}}
                        // defaultValue={addPaymentData.payment_gateway}
                        onChange={(e) => setAddPaymentData({ ...addPaymentData, payment_gateway: e })}
                        >
                        <Select.OptGroup label="Online Payment">
                            <Select.Option value="DragonPay">DragonPay</Select.Option>
                            <Select.Option value="PayMaya">PayMaya</Select.Option>
                            <Select.Option value="PesoPay">PesoPay</Select.Option>
                        </Select.OptGroup>
                        <Select.Option value="Cash">Cash</Select.Option>
                        <Select.Option value="PDC">PDC</Select.Option>
                        <Select.Option value="Direct Payment">Direct Payment</Select.Option>
                    </Select>
                </Form.Item>

                {
                    ['DragonPay', 'PayMaya', 'PesoPay'].includes(addPaymentData.payment_gateway) &&
                    <Form.Item label="Payment Gateway Reference #" name="payment_gateway_reference_number" rules={[{required: true}]}>
                        <Input
                            style={{width: 200}}
                            size="large"
                            // value={addPaymentData.check_account_number}
                            // onChange={(e) => setAddPaymentData({ ...addPaymentData, check_account_number: e.target.value })}
                        />
                    </Form.Item>
                }

                {
                    ['PDC', 'Direct Payment', 'Direct Deposit', 'Bank Transfer'].includes(addPaymentData.payment_gateway) &&
                        <Form.Item label="Bank" name="bank" rules={[{required: true}]}>
                            <Input
                                style={{width: 200}}
                                size="large"
                                // value={addPaymentData.bank}
                                // onChange={(e) => setAddPaymentData({ ...addPaymentData, bank: e.target.value })}
                            />
                        </Form.Item>
                }

                {
                    ['PDC'].includes(addPaymentData.payment_gateway) &&
                    <Form.Item label="Check #" name="check_number" rules={[{required: true}]}>
                        <Input
                            style={{width: 200}}
                            size="large"
                            // value={addPaymentData.check_number}
                            // onChange={(e) => setAddPaymentData({ ...addPaymentData, check_number: e.target.value })}
                        />
                    </Form.Item>
                }

                {
                    ['PDC', 'Direct Payment', 'Direct Deposit', 'Bank Transfer'].includes(addPaymentData.payment_gateway) &&
                    <Form.Item label="Bank Account #" name="bank_account_number" rules={[{required: true}]}>
                        <Input
                            style={{width: 200}}
                            size="large"
                            // value={addPaymentData.check_account_number}
                            // onChange={(e) => setAddPaymentData({ ...addPaymentData, check_account_number: e.target.value })}
                        />
                    </Form.Item>
                }

                </Form>

                <Popconfirm
                    title="Are you sure you want to apply Payment?"
                    // onConfirm={() => handleAddPaymentSaveClick(addPaymentData)}
                    onConfirm={() => formAddPayment.submit()}
                    onCancel={() => console.log("cancelled payment")}
                    okText="Yes"
                    cancelText="No"
                >
                    <Button block type="primary" className='mt-2'>Save</Button>
                </Popconfirm>

            </Modal>

            <Row gutter={[48,48]}>
                <Col xl={3}><strong>Client</strong></Col>
                <Col xl={5}>
                    <strong>{reservation.client?.first_name} {reservation.client?.last_name}</strong><br/>
                    {reservation.client?.email}<br/>
                    Status: {(reservation && reservation.client && reservation.client.information) ? 'BIS OK' : 'BIS incomplete'}<br/>
                </Col>
                <Col xl={12}>
                    <Row gutter={[16,16]}>
                        <Col xl={24}>
                            <Space style={{float:'right'}}>
                                {/* <ExcelFile filename={`Reservation_Agreement - ${reservation_number}`} element={<Button onClick={() => addActivityLog({description: 'Download Reservation Agreement', action: 'download_file'})} className="ml-2" size="small" icon={<PrinterOutlined/>}> Print Reservation Agreement</Button>}>
                                    <ExcelSheet data={[reservation]} name="reservation_agreement">
                                        <ExcelColumn label="Name" value={ (r) => `${r.client.last_name}, ${r.client.first_name}`}/>
                                    </ExcelSheet>
                                </ExcelFile> */}
                                {/* <Button size="small" onClick={() => handleDownloadBIS()}>Export Buyer's Information Sheet as PDF</Button> */}
                                {/* <Button size="small" onClick={() => handleDownloadBIS()}>Export RIS as PDF</Button> */}
                                {/* <Button size="small" onClick={() => setFeesModalVisible(true)}>Add Fees</Button> */}
                                <ActivityLogs size="small" reservation_number={reservation_number} refetch={activityLogRefetch} refetchSetter={setActivityLogRefetch} />
                                <Dropdown overlay={
                                    <Menu>
                                        <Menu.Item onClick={() => addActivityLog({description: 'Download Penalty Reports', action: 'download_file'})}>
                                            <PenaltyReports reservation={reservation} button={false} />
                                        </Menu.Item>
                                        { (reservation.payment_terms_type === 'in_house') &&
                                            <Menu.Item onClick={() => addActivityLog({description: 'Download Amortization Reports', action: 'download_file'})}>
                                                <AmortizationReports reservation={reservation} button={false} />
                                            </Menu.Item>
                                        }
                                        {
                                            (reservation.payment_terms_type === 'cash') && 
                                            <Menu.Item onClick={() => addActivityLog({description: 'Download Cash Ledger Reports', action: 'download_file'})}>
                                                <CashLedgerReports reservation={reservation} button={false} />
                                            </Menu.Item>
                                        }
                                        <Menu.Item onClick={() => addActivityLog({description: 'Download Reservation Agreement', action: 'download_file'})}>
                                            <ExcelFile filename={`Reservation_Agreement - ${reservation_number}`} element={<span>Print Reservation Agreement</span>}>
                                                <ExcelSheet data={[reservation]} name="reservation_agreement">
                                                    <ExcelColumn label="Name" value={ (r) => `${r.client.last_name}, ${r.client.first_name}`}/> 
                                                </ExcelSheet>
                                            </ExcelFile>
                                        </Menu.Item>
                                        <Menu.Item onClick={() => handleDownloadBIS()}>
                                            Export RIS as PDF
                                        </Menu.Item>
                                    </Menu>
                                }>
                                    <Button size="small" icon={<PrinterOutlined/>}> Print Reports</Button>
                                </Dropdown>
                                {
                                    reservation.recalculated === 0 && 
                                    <Button size="small" type="default" className='mr-2' onClick={()=>handleRecomputeClick()}>Recompute</Button>
                                }
                                <Button size="small" type="primary" className='mr-2' onClick={()=>handleAddPaymentClick()}>Make Payment</Button>

                            </Space>
                        </Col>
                        <Col xl={24}>
                            Status: <span style={{textTransform:'capitalize'}}>{reservation.status}</span>
                        </Col>
                        <Col xl={12}>
                            Client number: <Typography.Text>{reservation.client_number}</Typography.Text>{reservation.client_number ? '' : <span className="ml-2 text-secondary">Type client number</span>}
                        </Col>
                    </Row>
                </Col>
            </Row>

            {/*Agent | Refer-A-Friend | Existing client*/}
            <AgentDetails reservation={reservation} />

            {/* Unit details */}
            <UnitDetails reservation={reservation} />

            {/* Payment Terms */}
            { reservation.old_reservation === 0  && 
                <PaymentTerms reservation={reservation} outstandingBalance={currentFloatBalance} 
                    params={{
                        view: 'view_account',
                        transactionId: paymentDetailsTransId,
                        transactionIdSetter: setPaymentDetailsTransId,
                        reservationUpdater: updateReservationDetails,
                        addActivityLog: addActivityLog,
                        modal: {
                            visibility: paymentDetailsPreviewModalVisible,
                            visibilitySetter: setPaymentDetailsPreviewModalVisible,
                            transaction: paymentDetailsTrans,
                            transactionSetter: setPaymentDetailsTrans,
                        }
                    }}
                />
            }

            <Row style={{marginTop: 8}} gutter={[48,48]}>

                { reservation.old_reservation === 0  &&
                    <Col xl={24}>
                        <Descriptions bordered colon={false} layout="vertical" size="small">
                            <Descriptions.Item span={2} label={<strong>Remarks</strong>}>{reservation.remarks}</Descriptions.Item>
                            <Descriptions.Item span={2} label={<strong>Promos</strong>}>{reservation.promos && reservation.promos.map( (i, key) => <Tag key={key} style={{marginRight: 8}}>{i.promo_type}</Tag>)}</Descriptions.Item>
                        </Descriptions>
                    </Col>
                }
                <Col xl={24}>
                    <Typography.Title level={5}>Attachments</Typography.Title>
                    {/* <Form.Item name="attachments"> */}
                        <Upload {...uploadProps}
                            name="files"
                            itemRender={(originNode, file, currFileList) => (
                                <Card style={{marginTop: 8}}>{originNode}</Card>
                            )}
                        >
                            <Button icon={<UploadOutlined />}>Select File</Button>
                        </Upload>
                    {/* </Form.Item> */}

                    {
                        uploadedFiles && uploadedFiles.map( (item, key) => {
                            return <Card key={key} style={{marginTop: 8,}}>
                                    <div style={{float:'left'}}>
                                        <a href={item.file_path} target="_blank">{item.file_name}</a>
                                    </div>
                                    <Button style={{float:'right'}} icon={<DeleteOutlined/>} onClick={() => handleRemoveFileAttachment(item.id, item.file_path)} />
                                </Card>
                        })
                    }
                </Col>
            </Row>

            {/* Payment Details */}
            {
                showPaymentDetilsUI &&
                <PaymentDetails 
                    params={{
                        view: 'view_account',
                        type: 'ledger',
                        reservation: reservation,
                        is_old_reservation: reservation.old_reservation,
                        payment_terms_type: reservation.payment_terms_type,
                        reservationSetter: setReservation,
                        transactionId: paymentDetailsTransId,
                        transactionIdSetter: setPaymentDetailsTransId,
                        modal: {
                            visibility: paymentDetailsPreviewModalVisible,
                            visibilitySetter: setPaymentDetailsPreviewModalVisible,
                            transaction: paymentDetailsTrans,
                            transactionSetter: setPaymentDetailsTrans,
                            paymentFormVisibility: paymentDetailsModalVisible,
                            paymentFormVisibilitySetter: setPaymentDetailsModalVisible,
                            addPaymentDetailsData: addPaymentDetailsData,
                            addPaymentDetailsDataSetter: setAddPaymentDetailsData,
                            paymentAttachementModal: paymentAttachementModal, 
                            setPaymentAttachementModal: setPaymentAttachementModal
                        },
                        api: {
                            addPaymentDetailsQuery: addPaymentDetailsQuery,
                            addPaymentDetailsQueryIsLoading: addPaymentDetailsQueryIsLoading,
                            addPaymentDetailsQueryReset: addPaymentDetailsQueryReset
                        },
                        addActivityLog: addActivityLog,
                        setActivityLogRefetch: setActivityLogRefetch,
                    }} 
                /> 
            }

            {
                reservation.payment_terms_type == 'cash' && reservation.old_reservation === 0 &&
                <Row gutter={[48,48]}>
                    <Col xl={24}>
                        <Divider orientation="left">Cash Splits</Divider>
                        <Button size="small" className='mr-2' onClick={()=>handleViewPenalties()}>View penalties</Button>

                        <Table
                            rowKey="id"
                            pagination={{
                                pageSizeOptions: [10, 20, 50, 100, 120, 240]
                            }}
                            scroll={{ x: 1500 }}
                            columns={[
                                {
                                    title: '',
                                    dataIndex: 'number',
                                    key: 'number',
                                    render: (text, record) => {
                                        return ordinalNumber(record.number) + ` Split`
                                    }
                                },
                                {
                                    title: dateDueColumn ? 'Date due' : '',
                                    dataIndex: 'due_date',
                                    key: 'due_date',
                                    className: dateDueColumn ? '' : 'd-none',
                                    render: (text) => dateDueColumn ? moment(text).format('M/D/YYYY') : ''
                                },
                                {
                                    title: amountDueColumn ? 'Amount due' : '',
                                    dataIndex: 'amount',
                                    key: 'amount',
                                    className: amountDueColumn ? '' : 'd-none',
                                    render: (text) => amountDueColumn ? numberWithCommas(text) : ''
                                },
                                {
                                    title: 'Date paid',
                                    dataIndex: 'date_paid',
                                    key: 'date_paid',
                                    render: (text) => text != null ? moment(text).format('M/D/YYYY') : ''
                                },
                                {
                                    title: 'Amount paid',
                                    render: (text, record) => (record.amount_paid != null) ? numberWithCommas(parseFloat(record.amount_paid).toFixed(2)) : ''
                                },
                                {
                                    title: 'PR #',
                                    dataIndex: 'pr_number',
                                    key: 'pr_number',
                                },
                                {
                                    title: 'OR #',
                                    dataIndex: 'or_number',
                                    key: 'or_number',
                                },
                                {
                                    title: 'Remarks',
                                    render: (text, record) => record.remarks
                                },
                                {
                                    title: 'Action',
                                    render: (text, record) => {
                                        return <Dropdown overlay={
                                            <Menu>
                                                <Menu.Item onClick={() => handleAddPenaltyClick(record)}>Add penalty</Menu.Item>
                                                <Menu.Item onClick={() => handlePreviewPaymentClick(record)}>View Payment</Menu.Item>
                                                {/* <Menu.Item onClick={() => handleAddPaymentClick(record)}>Add payment</Menu.Item> */}
                                            </Menu>
                                        }>
                                            <Button icon={<EllipsisOutlined />} />
                                        </Dropdown>
                                    }
                                },
                            ]}
                            dataSource={reservation?.cash_ledger_collections}
                        />
                    </Col>
                </Row>
            }

            {
                reservation.payment_terms_type == 'in_house' && reservation.old_reservation === 0 &&
                <Row gutter={[48,48]}>
                    <Col xl={24}>
                        <Divider orientation="left">Amortization Schedule</Divider>
                        {/* <Button size="small" type="primary" className='mr-2' onClick={()=>handleAddPaymentClick()}>Make Payment</Button> */}
                        <Button size="small" className='mr-2' onClick={()=>handleViewPenalties()}>View penalties</Button>
                        {/* <Button size="small" onClick={()=>{
                            Modal.info({
                                title: 'Amortization Fees',
                                icon: null,
                                width: 800,
                                content: <>
                                    {
                                        reservation.amortization_fees ?
                                        <Table
                                            size='small'
                                            dataSource={reservation.amortization_fees}
                                            rowKey="id"
                                            columns={[
                                                {
                                                    title: 'Type',
                                                    dataIndex: 'type',
                                                },
                                                {
                                                    title: 'Amount',
                                                    dataIndex: 'amount',
                                                },
                                                {
                                                    title: 'Remarks',
                                                    dataIndex: 'remarks',
                                                },
                                                {
                                                    title: 'Date created',
                                                    render: (text, record) => moment(record.created_at).format('YYYY-MM-DD')
                                                },
                                                {
                                                    title: 'Added by',
                                                    render: (text, record) => record.added_by.first_name
                                                },
                                            ]}
                                        />
                                        :''
                                    }
                                </>
                            })
                        }}>View amortization fees</Button> */}
                        <Checkbox style={{marginLeft: 16}} onChange={(e) => setShowPenalties(e.target.checked)}>Show penalties</Checkbox>

                        <Checkbox style={{marginLeft: 16}} onChange={(e) => hideSchedColumn(e.target.checked, setDateDueColumn)}>Hide date due</Checkbox>
                        <Checkbox style={{marginLeft: 16}} onChange={(e) => hideSchedColumn(e.target.checked, setAmountDueColumn)}>Hide amount due</Checkbox>

                        <Table
                            rowKey="id"
                            pagination={{
                                pageSizeOptions: [10, 20, 50, 100, 120, 240],
                                defaultPageSize: 10,
                                defaultCurrent: 1,
                                showSizeChanger: (total) => {return total > 10}
                                // showTotal: (total) => { return `Total ${total} items`}
                                // defaultPageSize: 100
                            }}
                            scroll={{ x: 1500 }}
                            columns={[
                                {
                                    title: 'Amortization',
                                    dataIndex: 'number',
                                    key: 'number',
                                    render: (text, record) => {
                                        let number = record.number;
                                        if( AmortNumber !== number ) {
                                            AmortNumber = number;
                                            AmortId = record.id;
                                            return number;
                                        } else {
                                            return ( AmortId !== record.id ) ? '' : number;
                                        }
                                    }
                                },
                                {
                                    title: dateDueColumn ? 'Date due' : '',
                                    dataIndex: 'due_date',
                                    key: 'due_date',
                                    className: dateDueColumn ? '' : 'd-none',
                                    render: (text, record) => {
                                        let number = record.number;
                                        if( AmortNumberDueDate !== number ) {
                                            AmortNumberDueDate = number;
                                            AmortId = record.id;
                                            return dateDueColumn && !record.excess_payment ? moment(text).format('M/D/YYYY') : ''
                                        } else {
                                            return (dateDueColumn && !record.excess_payment) ? (( AmortId !== record.id && record.date_paid !== null) ? '' : moment(text).format('M/D/YYYY')) : '';
                                        }
                                    }
                                },
                                {
                                    title: amountDueColumn ? 'Amount due' : '',
                                    dataIndex: 'amount',
                                    key: 'amount',
                                    className: amountDueColumn ? '' : 'd-none',
                                    render: (text, record) => {
                                        let number = record.number;
                                        if( AmortNumberAmountDue !== number ) {
                                            AmortNumberAmountDue = number;
                                            AmortId = record.id;
                                            return amountDueColumn && !record.excess_payment ? numberWithCommas(text) : ''
                                        } else {
                                            return ( AmortId !== record.id && record.date_paid !== null ) ? '' : numberWithCommas(text);
                                        }
                                    }
                                },
                                {
                                    title: 'Date paid',
                                    dataIndex: 'date_paid',
                                    key: 'date_paid',
                                    render: (text, record) => {
                                        return text != null && !record.excess_payment ? moment(text).format('M/D/YYYY') : ''
                                    }
                                },
                                {
                                    title: 'Amount paid',
                                    render: (text, record) => (record.amount_paid != null && record.type != 'penalty') ? numberWithCommas(parseFloat(record.amount_paid).toFixed(2)) : ''
                                },
                                {
                                    title: 'Penalty paid',
                                    render: (text, record) => {
                                        let payments = record.payments;
                                        return _.map(payments, function(payment){
                                            if( payment.payment_type === 'penalty' ) {
                                                return numberWithCommas(parseFloat(payment.payment_amount).toFixed(2));
                                            }
                                        });
                                    }
                                },
                                {
                                    title: 'PR #',
                                    dataIndex: 'pr_number',
                                    key: 'pr_number',
                                },
                                {
                                    title: 'Account #',
                                    dataIndex: 'account_number',
                                    key: 'account_number',
                                },
                                {
                                    title: 'Principal',
                                    render: (text, record) => {
                                        let value = numberWithCommas(parseFloat(record.principal).toFixed(2));
                                        if( !record.is_old ) {
                                            value = ( record.date_paid != null ) ? numberWithCommas(parseFloat(record.principal).toFixed(2)) : '';
                                        }
                                        return value;
                                    }
                                },
                                {
                                    title: 'Interest',
                                    render: (text, record) => {
                                        let value = numberWithCommas(parseFloat(record.interest).toFixed(2));
                                        if( !record.is_old ) {
                                            value = ( record.date_paid != null && !record.excess_payment ) ? numberWithCommas(parseFloat(record.interest).toFixed(2)) : '';
                                        }
                                        return value;
                                    }
                                },
                                {
                                    title: 'Balance',
                                    render: (text, record) => {
                                        let value = numberWithCommas(parseFloat(record.balance).toFixed(2));
                                        let floatValue = parseFloat(record.balance).toFixed(2);
                                        setCurrentBalance(value);
                                        setCurrentFloatBalance(floatValue);
                                        return value;
                                    }
                                },
                                {
                                    title: 'Remarks',
                                    render: (text, record) => record.remarks
                                },
                                {
                                    title: showPenalties ? <span className="text-danger">Penalties</span> : '',
                                    render: (text, record) => {

                                        if( record.penalty_status == 'waived' || record.penalty_status == 'waived_wp' ) {
                                           return '';
                                        } else {
                                            return (record.computed_penalty_amount > 0 && showPenalties) ? numberWithCommas(record.computed_penalty_amount.toFixed(2)) : '';
                                        }

                                        
                                    }
                                },
                                {
                                    title: showPenalties ? <span className="text-danger">Amount Due<br/>with Penalties</span> : '',
                                    // render: (text, record) => showPenalties ? '' : ''
                                    render: (text, record, i) => {
                                        if( record.penalty_status == 'void' && record.penalty_status == 'waived_wp' ) {
                                            return '';
                                        } else {
                                            return (record.balance_with_penalty !== record.balance && parseFloat(record.balance_with_penalty) > 0 && showPenalties) ? numberWithCommas(record.balance_with_penalty.toFixed(2)) : '';
                                        }
                                    }
                                },
                                {
                                    title: 'Action',
                                    render: (text, record) => {
                                        let component = '';
                                        if( record.excess_payment != 1  ) {
                                            component = <Dropdown overlay={
                                                <Menu>
                                                    { record.date_paid !== null && record.excess_payment !== 1 &&
                                                        <Menu.Item onClick={() => handleUpdateScheduleClick(record)}>Update</Menu.Item>
                                                    }
                                                    {/* <Menu.Item onClick={() => handleAddPenaltyClick(record)}>Add penalty</Menu.Item> */}
                                                    { record.penalty_records.length > 0 && (record.penalty_status != 'waived' && record.penalty_status != 'waived_wp') &&
                                                        <Menu.Item onClick={() => handleWaivePenaltyClick(record, 'waived')}>Void penalty</Menu.Item>
                                                    }
                                                    { record.penalty_records.length > 0 && (record.penalty_status == 'waived' && record.penalty_status != 'waived_wp') &&
                                                        <Menu.Item onClick={() => handleWaivePenaltyClick(record, null)}>Unvoid penalty</Menu.Item>
                                                    }
                                                    <Menu.Item onClick={() => handlePreviewPaymentClick(record)}>View Payment</Menu.Item>
                                                    {/* <Menu.Item onClick={() => handleAddPaymentClick(record)}>Add payment</Menu.Item> */}
                                                </Menu>
                                            }>
                                                <Button icon={<EllipsisOutlined />} />
                                            </Dropdown>
                                        }

                                        return component;
                                    }
                                },
                            ]}
                            dataSource={reservation?.amortization_collections}
                        />
                    </Col>
                </Row>
            }

            {/* Related Payment Details */}
            {
                showPaymentDetilsUI &&
                <PaymentDetails 
                    params={{
                        view: 'view_account',
                        type: 'others',
                        reservation: reservation,
                        is_old_reservation: reservation.old_reservation,
                        payment_terms_type: reservation.payment_terms_type,
                        reservationSetter: setReservation,
                        transactionId: paymentDetailsTransId,
                        transactionIdSetter: setPaymentDetailsTransId,
                        modal: {
                            visibility: paymentDetailsPreviewModalVisible,
                            visibilitySetter: setPaymentDetailsPreviewModalVisible,
                            transaction: paymentDetailsTrans,
                            transactionSetter: setPaymentDetailsTrans,
                            paymentFormVisibility: paymentDetailsModalVisible,
                            paymentFormVisibilitySetter: setPaymentDetailsModalVisible,
                            addPaymentDetailsData: addPaymentDetailsData,
                            addPaymentDetailsDataSetter: setAddPaymentDetailsData,
                            paymentAttachementModal: paymentAttachementModal, 
                            setPaymentAttachementModal: setPaymentAttachementModal
                        },
                        api: {
                            addPaymentDetailsQuery: addPaymentDetailsQuery,
                            addPaymentDetailsQueryIsLoading: addPaymentDetailsQueryIsLoading,
                            addPaymentDetailsQueryReset: addPaymentDetailsQueryReset
                        },
                        addActivityLog: addActivityLog,
                        setActivityLogRefetch: setActivityLogRefetch,
                    }} 
                /> 
            }
        </div>
    )
}