import React from 'react'
import {
    useParams
  } from "react-router-dom";

import { Typography, Row, Col, Space, Descriptions, Divider, Select, Table, Input, message, Dropdown, Menu, Button, Modal, InputNumber, Tag, Checkbox, Upload, Card, Tabs, Form, DatePicker, Popconfirm } from 'antd'
import { EllipsisOutlined, PrinterOutlined, UploadOutlined, DeleteOutlined, ExportOutlined } from '@ant-design/icons'
const {TabPane} = Tabs;
const {TextArea} = Input;

import SalesAdminPortalService from 'services/SalesAdminPortal'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');

import { twoDecimalPlace } from 'utils/Common';

// Components
import AgentDetails from 'components/Common/Reservation/AgentDetails';
import UnitDetails from 'components/Common/Reservation/UnitDetails';
import PaymentDetails from 'components/Common/Reservation/PaymentDetails';
import PaymentTerms from 'components/Common/Reservation/PaymentTerms';
import PenaltyReports from 'components/Common/Reservation/PenaltyReports';

import ReactExport from "react-export-excel";
const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;
let DefaultPenaltyDiscountPercentage = 0;

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

    const [paymentDetailsPreviewModalVisible, setPaymentDetailsPreviewModalVisible] = React.useState(false);
    const [paymentDetailsTrans, setPaymentDetailsTrans] =  React.useState({});

    const [paymentDetailsModalVisible, setPaymentDetailsModalVisible] = React.useState(false);
    const [addPaymentDetailsData, setAddPaymentDetailsData] = React.useState({
        payment_amount: 0
    });

    const [paymentDetailsTransId, setPaymentDetailsTransId ] = React.useState(false);
    
    const [showPenalties, setShowPenalties] = React.useState(false);

    const [fileList, setFileList] = React.useState([]);
    const [fileToUpload, setFileToUpload] = React.useState({});
    const [uploadedFiles, setUploadedFiles] = React.useState([]);

    const [filteredData, setFilteredData] = React.useState([]);
    const [lastBalance, setLastBalance] = React.useState('initial');
    const [penaltyTableCurrentPage, setPenaltyTableCurrentPage] = React.useState(1);

    const [viewReservationQuery, { IsLoading: viewReservationQueryIsLoading, reset: viewReservationQueryReset}] = SalesAdminPortalService.viewReservation();
    const [updateReservationClientNumberQuery, { IsLoading: updateReservationClientNumberQueryIsLoading, reset: updateReservationClientNumberQueryReset}] = SalesAdminPortalService.updateReservationClientNumber();
    const [updateReservationStatusQuery, { IsLoading: updateReservationStatusQueryIsLoading, reset: updateReservationStatusQueryReset}] = SalesAdminPortalService.updateReservationStatus();
    const [addPenaltyQuery, { IsLoading: addPenaltyQueryIsLoading, reset: addPenaltyQueryReset}] = SalesAdminPortalService.addPenalty();
    const [addPaymentQuery, { IsLoading: addPaymentQueryIsLoading, reset: addPaymentQueryReset}] = SalesAdminPortalService.addPayment();
    const [addPaymentDetailsQuery, { IsLoading: addPaymentDetailsQueryIsLoading, reset: addPaymentDetailsQueryReset}] = SalesAdminPortalService.addPayment();
    const [activityLogsQuery, { isLoading: activityLogsQueryIsLoading, reset: activityLogsQueryReset}] = SalesAdminPortalService.addActivityLogs();

    const [viewPenaltiesQuery, { IsLoading: viewPenaltiesQueryIsLoading, reset: viewPenaltiesQueryReset}] = SalesAdminPortalService.viewPenalties();

    const [uploadFileQuery, { isLoading: uploadFileQueryIsLoading, reset: uploadFileQueryReset}] = SalesAdminPortalService.uploadReservationAttachmentFile();
    const [removeFileQuery, { isLoading: removeFileQueryIsLoading, reset: removeFileQueryReset}] = SalesAdminPortalService.removeReservationAttachmentFile();
    const [downloadBISQuery, { isLoading: downloadBISQueryIsLoading, reset: downloadBISQueryReset}] = SalesAdminPortalService.downloadBIS();
    const [paymentAttachementModal, setPaymentAttachementModal] = React.useState(false);
    const [activityLogRefetch, setActivityLogRefetch] = React.useState(false);


    const [formAddPayment] = Form.useForm();

    const showPaymentDetilsUI = true;

    React.useEffect( () => {

        viewReservationQuery({
            reservation_number: reservation_number
        }, {
            onSuccess: (res) => {
                // console.log(res);
                DefaultPenaltyDiscountPercentage = res.data.default_penalty_discount_percentage;
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

        if( lastBalance === '0.00' ) {
            message.warning(`Amortization is already paid.`);
            return false;
        }

        setPenaltyModalVisible(true);
        setAddPenaltyData({...data, 
            penalty_amount: (data.amount * 0.03).toFixed(2)
        });
    }

    const handleAddPaymentClick = () => {
        setPaymentModalVisible(true);
        // setAddPaymentData({...data, 
        //     payment_amount: (parseFloat(data.amount)).toFixed(2),
        //     // payment_type: 'monthly_amortization_payment',
        // });
    }

    const handleAddPenaltySaveClick = (values) => {
        console.log(values);

        //1
        if (addPenaltyQueryIsLoading) {
            return false;
        }

        addPenaltyQuery({
            reservation_number: values.reservation_number,
            amortization_schedule_id: values.id,
            penalty_amount: values.penalty_amount,
            number: values.number
        }, {
            onSuccess: (res) => {
                message.success("Penalty added!");
                setPenaltyModalVisible(false);
                setAddPenaltyData({
                    penalty_amount: 0
                });
            },
            onError: (e) => message.warning("Adding penalty failed")
        })
    }

    const handleAddPaymentSaveClick = (values) => {
        console.log(values);
    }

    const addPaymentFinish = (values) => {

        // console.log(values);

        // return false;

        if( lastBalance === '0.00' ) {
            addPaymentQueryReset();
            formAddPayment.resetFields();
            message.warning(`Amortization is already paid.`);
            return false;
        }

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

                setReservation(res.data);
                formAddPayment.resetFields();
            },
            onError: (e) => {
                addPaymentQueryReset();
                message.warning(`Adding payment failed: ${e.errors ? _.map(e.errors, (i) => i) : e.message}`)
            }
        })
    }

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
                // console.log(res);

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
                        <PenaltyReports reservation={reservation} button={true} />
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
                                                    return <Tag color="red">Voided</Tag>;
                                                    break;
                                                default:
                                                    break;
                                            }
                                        }
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

                    message.success("Download complete!");
                },
                onError: (e) => {
                    message.warning("Failed.");
                }
            }
        )
    }

    return (
        <div className="mt-4">
            {/* {reservation_number ?? ''} */}

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
                            </>
                        }
                        {
                            reservation.payment_terms_type === 'cash' &&
                            <>
                                <Select.Option value="reservation_fee_payment">Reservation</Select.Option>
                                <Select.Option value="title_fee">Title Fee</Select.Option>
                                {
                                    reservation.with_five_percent_retention_fee &&
                                    <Select.Option value="retention_fee">Retention Fee</Select.Option>
                                }
                                <Select.Option value="redocs_fee">Redocumentation Fee</Select.Option>
                                <Select.Option value="docs_fee">Documentation Fee</Select.Option>
                            </>
                        }
                    </Select>
                </Form.Item>

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
                        <Select.Option value='Direct Payment'>Direct Payment</Select.Option>
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

            <Modal
                visible={penaltyModalVisible}
                onCancel={()=>setPenaltyModalVisible(false)}
                footer={null}
                title="Add Penalty"
            >
                <Descriptions bordered size="small">
                    <Descriptions.Item span={4} label="Reservation #">{addPenaltyData.reservation_number}</Descriptions.Item>
                    <Descriptions.Item span={4} label="Date due">{addPenaltyData.due_date}</Descriptions.Item>
                    <Descriptions.Item span={4} label="Amortization number">{addPenaltyData.number}</Descriptions.Item>
                    <Descriptions.Item span={4} label="Amount">{addPenaltyData.amount}</Descriptions.Item>
                </Descriptions>

                <div style={{padding: '10px 0'}}>Penalty (PHP): <InputNumber min={0} style={{width: 200}} size="large" defaultValue={(addPenaltyData.penalty_amount ? addPenaltyData.penalty_amount : 0)} onChange={(e) => setAddPenaltyData({ ...addPenaltyData, penalty_amount: e })} /></div>

                <Button block type="primary" onClick={()=>handleAddPenaltySaveClick(addPenaltyData)}>Save</Button>
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
                            <Space>
                            <ExcelFile filename={`Reservation_Agreement - ${reservation_number}`} element={<Button className="ml-2" size="small" icon={<PrinterOutlined/>}> Print Reservation Agreement</Button>}>
                                <ExcelSheet data={[reservation]} name="reservation_agreement">
                                    <ExcelColumn label="Name" value={ (r) => `${r.client.last_name}, ${r.client.first_name}`}/>
                                </ExcelSheet>
                            </ExcelFile>
                            {/* <Button size="small" onClick={() => handleDownloadBIS()}>Export Buyer's Information Sheet as PDF</Button> */}
                            <Button size="small" onClick={() => handleDownloadBIS()}>Export RIS as PDF</Button>

                            {/* <Button size="small" onClick={() => handleAddPaymentClick()}>Add payment</Button> */}
                            <Button size="small" type="primary" className='mr-2' onClick={()=>handleAddPaymentClick()}>Make Payment</Button>
                            </Space>
                        </Col>
                        <Col xl={24}>
                            Status:
                            <Select disabled={reservation.status == 'cancelled'} onChange={e => updateReservationStatus(e)} value={reservation.status} style={{width: 300, marginLeft: 8}}>
                                <Select.Option value="pending">Pending</Select.Option>
                                <Select.Option value="pending_with_payments">Pending with payments</Select.Option>
                                <Select.Option value="for_review">For review</Select.Option>
                                <Select.Option value="reviewed">Reviewed</Select.Option>
                                <Select.Option value="for_client_number_encoding">For client number encoding</Select.Option>
                                <Select.Option value="approved">Approved</Select.Option>
                                <Select.Option value="cancelled">Cancelled</Select.Option>
                                <Select.Option value="void">Void</Select.Option>
                            </Select>
                        </Col>
                        <Col xl={12}>
                            Client number: <Typography.Text editable={{ onChange: (e) => updateReservationClientNumber(e) }}>{reservation.client_number}</Typography.Text>{reservation.client_number ? '' : <span className="ml-2 text-secondary">Type client number</span>}
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
                <PaymentTerms reservation={reservation} 
                    params={{
                        view: 'view_reservation',
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
                            return (
                                <Card key={key} style={{marginTop: 8,}}>
                                    <div style={{float:'left'}}>
                                        <a href={item.file_path} target="_blank">{item.file_name}</a>
                                    </div>
                                    <Button style={{float:'right'}} icon={<DeleteOutlined/>} onClick={() => handleRemoveFileAttachment(item.id, item.file_path)} />
                                </Card>
                            )
                        })
                    }
                </Col>
            </Row>

            {/* Payment Details */}
            { showPaymentDetilsUI && 
                <PaymentDetails 
                    params={{
                        view: 'view_reservation',
                        type: 'ledger',
                        reservation: reservation,
                        is_old_reservation: reservation.old_reservation,
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
                reservation.payment_terms_type == 'in_house' && reservation.old_reservation === 0 &&
                <Row gutter={[48,48]}>
                    <Col xl={24}>
                        <Divider orientation="left">Amortization Schedule</Divider>
                        <Button size="small" onClick={()=>handleViewPenalties()}>View penalties</Button>
                        {/* <Checkbox style={{marginLeft: 16}} onChange={(e) => setShowPenalties(e.target.checked)}>Show penalties</Checkbox> */}
                        <ExcelFile filename={`Amortization_schedule - ${reservation_number}`} element={<Button className="ml-2" size="small" style={{float: 'right'}} icon={<ExportOutlined />}> Export Amortization Schedule</Button>}>
                            <ExcelSheet data={_.filter(reservation?.amortization_schedule, {'is_sales': 1})} name="amortization_schedule">
                                <ExcelColumn label="Amortization" value={ (col) => `${col.number}`}/>
                                <ExcelColumn label="Date due" value={ (col) => `${moment(col.due_date).format('M/D/YYYY')}`}/>
                                <ExcelColumn label="Amount" value={ (col) => `${numberWithCommas(col.amount)}`}/>
                                <ExcelColumn label="Principal" value={ (col) => (typeof col.generated_principal !== 'undefined' && col.generated_principal != 0) ? `${numberWithCommas(parseFloat(col.generated_principal).toFixed(2))}` : `${numberWithCommas(parseFloat(col.principal).toFixed(2))}`}/>
                                <ExcelColumn label="Interest" value={ (col) => (typeof col.generated_interest !== 'undefined' && col.generated_interest != 0) ? `${numberWithCommas(parseFloat(col.generated_interest).toFixed(2))}` : `${numberWithCommas(parseFloat(col.interest).toFixed(2))}`}/>
                                <ExcelColumn label="Balance" value={ (col) => (typeof col.generated_balance !== 'undefined' && col.generated_balance != 0) ? `${numberWithCommas(parseFloat(col.generated_balance).toFixed(2))}` : `${numberWithCommas(parseFloat(col.balance).toFixed(2))}`}/>
                            </ExcelSheet>
                        </ExcelFile>

                        <Table
                            rowKey="id"
                            pagination={{
                                pageSizeOptions: [10, 20, 50, 100, 120, 240],
                                defaultPageSize: 10,
                                defaultCurrent: 1,
                                showSizeChanger: (total) => {return total > 10}
                            }}
                            scroll={{ x: 1500 }}
                            columns={[
                                {
                                    title: 'Amortization',
                                    dataIndex: 'number',
                                    key: 'number'
                                },
                                {
                                    title: 'Date due',
                                    dataIndex: 'due_date',
                                    key: 'due_date',
                                    render: (text) => moment(text).format('M/D/YYYY')
                                },
                                {
                                    title: 'Amount',
                                    dataIndex: 'amount',
                                    key: 'amount',
                                    render: (text) => numberWithCommas(text)
                                },
                                {
                                    title: 'Principal',
                                    render: (text, record) => {
                                        let value = numberWithCommas(parseFloat(record.principal).toFixed(2));
                                        if( typeof record.generated_principal !== 'undefined' && record.generated_principal != 0 ) {
                                            value = numberWithCommas(parseFloat(record.generated_principal).toFixed(2));
                                        }
                                        return value;
                                    }
                                },
                                {
                                    title: 'Interest',
                                    render: (text, record) => {
                                        let value = numberWithCommas(parseFloat(record.interest).toFixed(2));
                                        if( typeof record.generated_interest !== 'undefined' && record.generated_interest != 0 ) {
                                            value = numberWithCommas(parseFloat(record.generated_interest).toFixed(2));
                                        }
                                        return value;
                                    }
                                },
                                {
                                    title: 'Balance',
                                    render: (text, record) => {
                                        let value = numberWithCommas(parseFloat(record.balance).toFixed(2));
                                        if( typeof record.generated_balance !== 'undefined' && record.generated_balance != 0 ) {
                                            value = numberWithCommas(parseFloat(record.generated_balance).toFixed(2));
                                        }
                                        return value;
                                    }
                                },
                                {
                                    title: showPenalties ? <span className="text-danger">Penalties</span> : '',
                                    render: (text, record) => showPenalties ? (record.penalty_records[0] ? record.penalty_records[0].penalty_amount : '') : ''
                                },
                                {
                                    title: showPenalties ? <span className="text-danger">Balance<br/>with Penalties</span> : '',
                                    render: (text, record) => showPenalties ? '' : ''
                                },
                                {
                                    // title: 'Action',
                                    // render: (text, record) => {
                                    //     return <Dropdown overlay={
                                    //         <Menu>
                                    //             <Menu.Item onClick={() => handleAddPenaltyClick(record)}>Add penalty</Menu.Item>
                                    //         </Menu>
                                    //     }>
                                    //         <Button icon={<EllipsisOutlined />} />
                                    //     </Dropdown>
                                    // }
                                },
                            ]}
                            dataSource={_.filter(reservation?.amortization_collections, {'is_sales': 1})}
                        />
                    </Col>
                </Row>
            }

            {/* Related Payment Details */}
            { showPaymentDetilsUI && 
                <PaymentDetails 
                    params={{
                        view: 'view_reservation',
                        type: 'others',
                        reservation: reservation,
                        is_old_reservation: reservation.old_reservation,
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