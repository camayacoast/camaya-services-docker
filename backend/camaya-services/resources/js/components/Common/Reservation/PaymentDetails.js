import React, { useState, useEffect } from 'react';
import { useParams } from "react-router-dom";
import { queryCache } from 'react-query'

import SalesAdminPortalService from 'services/SalesAdminPortal'

import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');

import { Typography, Row, Col, Descriptions, Divider, Select, Table, Input, message, Dropdown, Menu, Button, Modal, InputNumber, Tag, Tooltip, Upload, Form, DatePicker, Popconfirm, Card } from 'antd'
import { EllipsisOutlined, UploadOutlined, FileOutlined, LoadingOutlined } from '@ant-design/icons'

const { TextArea } = Input;

import { numberWithCommas, twoDecimalPlace } from 'utils/Common';
import { useForm } from 'rc-field-form';

function PaymentDetails(props) {

    const [searchString, setSearchString] = useState('');
    const [updatePaymentModal, setUpdatePaymentModal] = useState(false);
    const [updatePaymentData, setUpdatePaymentData] = useState([]);
    const [updatePaymentQuery, {isLoading: updatePaymentQueryIsLoading, reset: updatePaymentQueryReset}] = SalesAdminPortalService.updatePaymentRecord();
    const [viewReservationQuery, { IsLoading: viewReservationQueryIsLoading, reset: viewReservationQueryReset}] = SalesAdminPortalService.viewReservation();
    const [deletePaymentDetailQuery, { isLoading: deletePaymentDetailQueryIsLoading, reset: deletePaymentDetailQueryReset}] = SalesAdminPortalService.deletePaymentDetail();

    let viewPaymentAttachmentsQuery = SalesAdminPortalService.paymentDetailList(props.params.transactionId);

    let { reservation_number } = useParams();
    const [formAddPayment] = Form.useForm();
    const [formUpdatePayment] = Form.useForm();

    let reservation = props.params.reservation;

    if( props.params.type === 'others' ) {
        var filter_payments = ['hoa_fees', 'others', 'camaya_air_payment',];
    } else {
        var filter_payments = ['reservation_fee_payment', 'downpayment', 'title_fee', 'retention_fee', 'partial_cash', 'full_cash', 'split_cash', 'redocs_fee', 'docs_fee'];

        if(( props.params.is_old_reservation === 1 )) {
            filter_payments.push('monthly_amortization_payment');
            filter_payments.push('penalty');
        }

        if(( props.params.payment_terms_type === 'cash' )) {
            filter_payments.push('penalty');
        }

        if( reservation.client_number === null || reservation.client_number === '' ) {
            filter_payments.push('monthly_amortization_payment');
            filter_payments.push('penalty');
        }
    }

    // const reservation = props.params.reservation;
    const paymentTypeLabel = {
        'reservation_fee_payment' : 'Reservation',
        'downpayment' : 'Downpayment',
        'monthly_amortization_payment' : 'Monthly Amortization',
        'title_fee' : 'Title Transfer Fee',
        'retention_fee' : 'Retention Fee',
        'full_cash' : 'Full Cash',
        'partial_cash' : 'Partial Cash',
        'split_cash' : 'Split Cash',
        'penalty' : 'Penalty',
        'redocs_fee' : 'Redocumentation Fee',
        'docs_fee' : 'Documentation Fee',
        'hoa_fees' : 'HOA',
        'camaya_air_payment' : 'Camaya Air Payment',
        'others' : 'Others'
    }

    const paymentMethodLabel = {
        'admin' : 'Admin',
        'online_payment' : 'Online',
    }

    const updateReservationDetails = () => {
        viewReservationQuery({
            reservation_number: reservation_number
        }, {
            onSuccess: (res) => {
                props.params.reservationSetter(res.data);
                props.params.reservation = res.data;
            },
            onError: (e) => {
                console.log(e)
                if (e.error == 'RESERVATION_NOT_FOUND') {
                    props.history.goBack();
                }
            },
        });
    }

    const checkOldReservation = (payment) => {
        return (props.params.is_old_reservation !== 1) ? filter_payments.includes(payment.payment_type) : true;
    }

    const handlePaymentAttachmentClick = (data) => {
        if( props.params.transactionId !== data.transaction_id ) {
            setTimeout(function() {
                viewPaymentAttachmentsQuery.refetch(data.transaction_id);
                document.querySelector('.loader').style.display = 'block';
    
                if( document.querySelector('#paymentDetailAttachments').parentElement.querySelector('.ant-pagination-item-1') !== null ) {
                    document.querySelector('#paymentDetailAttachments').parentElement.querySelector('.ant-pagination-item-1').click();
                }
    
                if( typeof document.getElementsByClassName('payment-attach')[0] !== 'undefined' ) {
                    document.getElementsByClassName('payment-attach')[0].style.display = 'none';
                } else {
                    document.querySelector('#paymentDetailAttachments').parentElement.classList.add('payment-attach');
                    document.querySelector('#paymentDetailAttachments').parentElement.style.display = 'none';
                }
            }, 100)
    
            setTimeout(function() {
                if( typeof document.getElementsByClassName('payment-attach')[0] !== 'undefined' ) {
                    document.getElementsByClassName('payment-attach')[0].style.display = 'block';
                }
            }, 2000)
        }
        
        props.params.transactionIdSetter(data.transaction_id);
        props.params.transactionId = data.transaction_id;
        props.params.modal.setPaymentAttachementModal(true);
    }

    const handlePreviewPaymentClick = (data) => {
        props.params.transactionIdSetter(data.transaction_id);
        props.params.modal.transactionSetter([data]);
        props.params.modal.visibilitySetter(true);
        props.params.modal.transaction = [data];
        props.params.transactionId = data.transaction_id;
    }

    const handleAddPaymentClick = () => {
        props.params.modal.paymentFormVisibilitySetter(true);
    }

    const openUpdatePaymentModal = (record) => {

        setUpdatePaymentData(record);
        setUpdatePaymentModal(true);
    }

    const deletePaymentDetail = (record) => {
        console.log(record);

        if (deletePaymentDetailQueryIsLoading) {
            return false;
        }

        deletePaymentDetailQuery({
            id: record.id,
            reservation_number: reservation_number,
            payment_terms_type: reservation.payment_terms_type,
        }, {
            onSuccess: (res) => {
                message.success('Payment detail deleted')
                props.params.addActivityLog({description: `Delete payment`, action: 'delete_transaction'});
                props.params.setActivityLogRefetch(true);
                updateReservationDetails();
                props.params.modal.visibilitySetter(false)
            },
            onError: (e) => message.info(e.message),
        })
    }

    const updatePaymentFinish = () => {

        if( updatePaymentQueryIsLoading ) {
            return false;
        }

        updatePaymentQuery({
            reservation_number: reservation_number,
            transaction_id: updatePaymentData.transaction_id,
            payment_id: updatePaymentData.id,
            payment_amount: updatePaymentData.payment_amount,
            discount: updatePaymentData.discount,
            payment_type: updatePaymentData.payment_type,
            cr_number: updatePaymentData.cr_number,
            or_number: updatePaymentData.or_number,
            payment_gateway: updatePaymentData.payment_gateway,
            paid_at: updatePaymentData.paid_at ? moment(updatePaymentData.paid_at).format('YYYY-MM-DD HH:mm:ss') : null,
            remarks: updatePaymentData.remarks,
            check_number: updatePaymentData.check_number,
            check_account_number: updatePaymentData.check_account_number,
            bank: updatePaymentData.bank,
            bank_account_number: updatePaymentData.bank_account_number,
            payment_gateway_reference_number: updatePaymentData.payment_gateway_reference_number,
            payment_terms_type: reservation.payment_terms_type,
            cash_term_ledger_id: updatePaymentData.cash_term_ledger_id
        }, {
            onSuccess: (res) => {
                message.success("Payment Updated!");
                props.params.modal.transactionSetter([updatePaymentData]);
                props.params.modal.transaction = [updatePaymentData];
                props.params.addActivityLog({description: `Update ${paymentTypeLabel[updatePaymentData.payment_type]} payment details with transaction number: ${updatePaymentData.transaction_id}`, action: 'update_transaction'});
                props.params.setActivityLogRefetch(true);
                updateReservationDetails();
                setUpdatePaymentModal(false);
            },
            onError: (e) => {
                updatePaymentQueryReset();
                message.warning(`Updating payment failed: ${e.errors ? _.map(e.errors, (i) => i) : e.message}`)
            }
        })
    }

    const addPaymentFinish = (values) => {

        if (props.params.api.addPaymentDetailsQueryIsLoading) {
            return false;
        }

        props.params.api.addPaymentDetailsQuery({
            reservation_number: reservation_number,
            payment_amount: values.payment_amount,
            payment_type: values.payment_type,
            cr_number: values.cr_number,
            or_number: values.or_number,
            payment_gateway: values.payment_gateway,
            paid_at: values.paid_at ? moment(values.paid_at).format('YYYY-MM-DD HH:mm:ss') : null,
            remarks: values.remarks,

            check_number: values.check_number,
            check_account_number: values.check_account_number,
            bank: values.bank,
            bank_account_number: values.bank_account_number,

            payment_gateway_reference_number: values.payment_gateway_reference_number,
        }, {
            onSuccess: (res) => {
                message.success("Payment added!");
                props.params.modal.paymentFormVisibilitySetter(false);

                props.params.modal.addPaymentDetailsDataSetter({
                    payment_amount: 0
                });

                props.params.reservationSetter(res.data);
                props.params.reservation = res.data;
                formAddPayment.resetFields();
            },
            onError: (e) => {
                props.params.api.addPaymentDetailsQueryReset();
                message.warning(`Adding payment failed: ${e.errors ? _.map(e.errors, (i) => i) : e.message}`)
            }
        })

    }

    const handleSearch = (e) => {
        // console.log(e.target.value);
        setSearchString(e.target.value.toLowerCase());
    }

    let columns = [
        {
            title: 'Transaction #',
            dataIndex: 'transaction_id',
            key: 'due_date',
            filterMode: 'tree',
            render: (text) => text
        },
        {
            title: 'Date Paid',
            render: (text, record) => {
                return (record.paid_at !== null)  ? moment(record.paid_at).format('M/D/YYYY') : ''
            }
        },
        {
            title: 'Payment Type',
            dataIndex: 'payment_type',
            key: 'payment_type',
            render: (text, record) => {

                if( props.params.is_old_reservation !== 1 ) {

                    return paymentTypeLabel[text];
                    
                } else {

                    let typeContainer = [];
                    let types = text.split(',');

                    types = _.map(types, function(value){
                        let type = value.trim();
                        if( typeof paymentTypeLabel[type] !== 'undefined' ) {
                            typeContainer.push(paymentTypeLabel[type]);
                        } else {
                            typeContainer.push(type);
                        }
                    });

                    return typeContainer.join(', ');
                }
            }
        },
        {
            title: 'Payment Method',
            render: (text, record) => {
                return record.payment_gateway + ' | ' + paymentMethodLabel[record.payment_encode_type]
            }
        },
        {
            title: 'Amount',
            dataIndex: 'payment_amount',
            key: 'payment_amount',
            render: (text) => numberWithCommas(text)
        },
        {
            title: 'Remarks',
            dataIndex: 'remarks',
            key: 'remarks',
            render: (text) => text
        },
    ];

    if( props.params.type === 'ledger' ) {
        columns.push({
            title: 'Action',
            render: (text, record) => {
                return <Dropdown overlay={
                    <Menu>
                        <Menu.Item onClick={() => handlePaymentAttachmentClick(record)}>Attachments</Menu.Item>
                        <Menu.Item onClick={() => handlePreviewPaymentClick(record)}>Details</Menu.Item>
                    </Menu>
                }>
                    <Button icon={<EllipsisOutlined />} />
                </Dropdown>
            }
        });
    }

    return (
        <>
            <Row gutter={[48,48]}>
                <Col xl={24}>
                    {
                        props.params.type === 'others' ? <Divider orientation="left">Related Transactions</Divider> : <Divider orientation="left">Payments</Divider>
                    }
                    <div className="mt-4 mb-4" style={{display:'flex', justifyContent:'space-between'}}>
                        <Input style={{width: 300}} type="search" placeholder="Search payment" onChange={(e)=>handleSearch(e)} />
                    </div>
                    {/* <Button size="small" type="primary" className='mr-2' onClick={()=>handleAddPaymentClick()}>Make Payment</Button> */}
                    <Table
                        rowKey="id"
                        scroll={{ x: 1500 }}
                        columns={columns}
                        dataSource={
                            _.filter(reservation?.payment_details, payment => 
                                checkOldReservation(payment) && 
                                // (payment.payment_type === 'reservation_fee_payment' || payment.payment_type === 'downpayment' || payment.payment_type === 'title_fee' || payment.payment_type === 'penalty_fee') &&  
                                (payment.payment_statuses.length > 0)  &&
                                (payment.payment_statuses[payment.payment_statuses.length-1].status === 'SUCCESS_ADMIN' || payment.payment_statuses[payment.payment_statuses.length-1].status === 'SUCCESS') && 
                                payment.is_verified === 1
                            ).filter(item => {
                                if (item && searchString) {
                                    const searchValue =  item.transaction_id.toLowerCase() + ' ' + item.payment_type.toLowerCase();
                                    return searchString ? searchValue.indexOf(searchString.toLowerCase()) !== -1 : true;
                                }
                                return true;
                            })
                        }
                    />

                </Col>
            </Row>

            {/* Payment Details Modal */}
            <Modal
                visible={props.params.modal.visibility}
                onCancel={()=>props.params.modal.visibilitySetter(false)}
                footer={null}
                title="Payment Details"
            >
                {
                    props.params.modal.transaction != {} ?
                    _.map(props.params.modal.transaction, function(record, i) {

                        return <div key={i}>
                            <Descriptions bordered size="small">
                                <Descriptions.Item span={4} label="Date">{moment(record.paid_at).format('MMM D, YYYY')}</Descriptions.Item>
                                <Descriptions.Item span={4} label="Transaction #">{record.transaction_id}</Descriptions.Item>
                                <Descriptions.Item span={4} label="Payment Type">{paymentTypeLabel[record.payment_type]}</Descriptions.Item>
                                <Descriptions.Item span={4} label="Amount">{numberWithCommas(record.payment_amount)}</Descriptions.Item>
                                {
                                    (record.payment_type == 'penalty_fee') &&
                                    <Descriptions.Item span={4} label="Discount">{(record.discount != 0) ? `${record.discount}%` : 'No Discount' }</Descriptions.Item>
                                }
                                {
                                    (record.payment_type == 'penalty_fee') &&
                                    <Descriptions.Item span={4} label="Actual Amount">
                                        {
                                            numberWithCommas(twoDecimalPlace(record.payment_amount - (( record.discount / 100 ) * record.payment_amount)))
                                        }
                                    </Descriptions.Item>
                                }
                                <Descriptions.Item span={4} label="Payment Gateway">{record.payment_gateway}</Descriptions.Item>
                                {
                                    (record.payment_gateway_reference_number) &&
                                    <Descriptions.Item span={4} label="Gateway Reference #">{record.payment_gateway_reference_number}</Descriptions.Item>
                                }
                                {
                                    (record.or_number) &&
                                    <Descriptions.Item span={4} label="OR#">{record.or_number}</Descriptions.Item>
                                }
                                {
                                    (record.cr_number) &&
                                    <Descriptions.Item span={4} label="PR#">{record.cr_number}</Descriptions.Item>
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
                                    (record.remarks) &&
                                    <Descriptions.Item span={4} label="Remarks">{record.remarks}</Descriptions.Item>
                                }
                            </Descriptions>
                            <div style={{textAlign: 'right'}}>
                                <Button type="default" className='mt-4' style={{marginRight: '10px'}} onClick={() => openUpdatePaymentModal(record)}>Update</Button>
                                {
                                    record.payment_encode_type !== 'online_payment' && props.params.view == 'view_account' && !['full_cash', 'partial_cash', 'split_cash'].includes(record.payment_type) &&
                                    <Popconfirm
                                        title="Are you sure you want to delete this Payment?"
                                        onConfirm={() => deletePaymentDetail(record)}
                                        onCancel={() => console.log("cancelled delete payment")}
                                        okText="Yes"
                                        cancelText="No"
                                    >
                                        <Button danger className='mt-4'>Delete</Button>
                                    </Popconfirm>
                                }
                                
                            </div>
                        </div>
                    }) : 'No Payment found'
                }

            </Modal>

            {/* Add Payment Modal */}
            <Modal
                visible={props.params.modal.paymentFormVisibility}
                onCancel={()=>props.params.modal.paymentFormVisibilitySetter(false)}
                footer={null}
                title="Payment Details - Add Payment"
            >
                <Descriptions bordered size="small">
                    <Descriptions.Item span={4} label="Reservation #">{reservation_number}</Descriptions.Item>
                </Descriptions>
                
                <br />

                <Form
                    layout={"horizontal"}
                    labelCol={{ span: 8 }}
                    wrapperCol={{ span: 14 }}
                    form={formAddPayment}
                    onFinish={addPaymentFinish}
                    initialValues={{
                        reservation_number: reservation_number,
                        payment_amount: 0,
                        payment_type: 'downpayment'
                    }}
                >

                    <Form.Item label="Payment (PHP)" name="payment_amount" rules={[{required:true}]}>
                        <InputNumber min={0}
                            style={{width: 200}}
                            size="large"
                        />
                    </Form.Item>

                    <Form.Item label="OR#" name="or_number">
                        <Input
                            style={{width: 200}}
                            size="large"
                            value={props.params.modal.addPaymentDetailsData.or_number}
                            onChange={(e) => props.params.modal.addPaymentDetailsDataSetter({ ...props.params.modal.addPaymentDetailsData, or_number: e.target.value })}
                        />
                    </Form.Item>

                    <Form.Item label="PR#" name="cr_number">
                        <Input
                            style={{width: 200}}
                            size="large"
                        />
                    </Form.Item>

                    <Form.Item label="Date paid" name="paid_at" rules={[{required: true}]}>
                        <DatePicker
                            style={{width: 200}}
                            size="large"
                            showTime
                        />
                    </Form.Item>

                    <Form.Item label="Remarks" name="remarks">
                        <TextArea
                            style={{width: 200, borderRadius: 8}}
                            size="large"
                        />
                    </Form.Item>

                    <Form.Item label="Payment type" name="payment_type" rules={[{required: true}]}>
                        <Select style={{marginLeft: 4, width: '100%'}}>
                            <Select.Option value="downpayment">Downpayment</Select.Option>
                            <Select.Option value="reservation_fee_payment">Reservation</Select.Option>
                        </Select>
                    </Form.Item>

                    <Form.Item label="Payment gateway" rules={[{required: true}]} name="payment_gateway">
                        <Select style={{marginLeft: 4, width: '100%'}}
                            onChange={(e) => props.params.modal.addPaymentDetailsDataSetter({ ...props.params.modal.addPaymentDetailsData, payment_gateway: e })}
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
                        ['DragonPay', 'PayMaya', 'PesoPay'].includes(props.params.modal.addPaymentDetailsData.payment_gateway) &&
                        <Form.Item label="Payment Gateway Reference #" name="payment_gateway_reference_number" rules={[{required: true}]}>
                            <Input
                                style={{width: 200}}
                                size="large"
                            />
                        </Form.Item>
                    }

                    {
                        ['PDC', 'Direct Payment', 'Direct Deposit', 'Bank Transfer'].includes(props.params.modal.addPaymentDetailsData.payment_gateway) &&
                            <Form.Item label="Bank" name="bank" rules={[{required: true}]}>
                                <Input
                                    style={{width: 200}}
                                    size="large"
                                />
                            </Form.Item>
                    }

                    {
                        ['PDC'].includes(props.params.modal.addPaymentDetailsData.payment_gateway) &&
                        <Form.Item label="Check #" name="check_number" rules={[{required: true}]}>
                            <Input
                                style={{width: 200}}
                                size="large"
                            />
                        </Form.Item>
                    }

                    {
                        ['PDC', 'Direct Payment', 'Direct Deposit', 'Bank Transfer'].includes(props.params.modal.addPaymentDetailsData.payment_gateway) &&
                        <Form.Item label="Bank Account #" name="bank_account_number" rules={[{required: true}]}>
                            <Input
                                style={{width: 200}}
                                size="large"
                            />
                        </Form.Item>
                    }

                </Form>

                <Popconfirm
                    title="Are you sure you want to apply Payment?"
                    onConfirm={() => formAddPayment.submit()}
                    onCancel={() => console.log("cancelled payment")}
                    okText="Yes"
                    cancelText="No"
                >
                    <Button block type="primary" className='mt-2'>Save</Button>
                </Popconfirm>

            </Modal>

            {/* Attachment Modal  */}
                <Modal
                    title={<Typography.Title level={4}>Attachments</Typography.Title>}
                    visible={props.params.modal.paymentAttachementModal}
                    onCancel={(e)=> {
                        props.params.modal.setPaymentAttachementModal(false);
                    }}
                    afterClose={()=> {
                        document.getElementsByClassName('ant-upload-list-picture')[0].innerHTML = '';
                    }}
                    width={800}
                    bodyStyle={{minHeight: '200px'}}
                    footer={null}
                >

                    <div>

                        <Upload
                            action={`${process.env.APP_URL}/api/sales-admin-portal/upload-file-payment-attachment`}
                            showUploadList={{
                                showRemoveIcon: false
                            }}
                            data={(file) => {
                                return {
                                    reservation_number: reservation_number,
                                    transaction_id: props.params.transactionId,
                                    file_type: file.type,
                                    file_size: file.size,
                                    file_name: file.name,
                                }
                            }}
                            headers={
                                {
                                    Authorization: `Bearer ${localStorage.getItem('token')}`,
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            }
                            onChange={({file}) => {
                                if (file.status !== 'uploading') {
                                    // viewBookingQuery.data.attachments.push(file.response);
                                    if (file.status !== 'removed') {
                                        queryCache.setQueryData(['view-account', file.response.transaction_id], (data) => {
                                            viewPaymentAttachmentsQuery.refetch(file.response.transaction_id);
                                            props.params.setActivityLogRefetch(true);
                                            document.getElementsByClassName('ant-upload-list-picture')[0].innerHTML = '';
                                        })
                                    }
                                }
                            }}
                            listType="picture"
                        >
                            <Button icon={<UploadOutlined/>}>Add Attachment</Button>
                        </Upload>
                        
                        <div style={{textAlign: 'center', marginTop: '20px', marginBottom: '20px', display: 'none'}} className="loader">
                            <LoadingOutlined style={{fontSize: '40px'}} />
                        </div>

                        <Table
                            dataSource={viewPaymentAttachmentsQuery.data}
                            size="small"
                            rowKey="id"
                            id="paymentDetailAttachments"
                            columns={[
                                {
                                    title: 'File',
                                    dataIndex: 'file',
                                    key: 'file',
                                    render: (text, record) => {
                                        return <a href={record.file_path} target="_blank">{ _.includes(['image/png', 'image/jpeg'], record.content_type) ? <img style={{width: 25}} src={record.file_path} /> : <FileOutlined/> }</a>
                                    }
                                },
                                {
                                    title: 'File name',
                                    dataIndex: 'file_name',
                                    key: 'file_name',
                                    render: (text, record) => {
                                        return <a href={record.file_path} style={record.justAdded ? {fontWeight: 'bold'} : {}} target="_blank">{record.file_name}{record.justAdded && <Tag color="blue" className="ml-2">new</Tag> }</a>
                                    }
                                },
                                {
                                    title: 'File size',
                                    dataIndex: 'file_size',
                                    key: 'file_size',
                                    render: (text, record) => {
                                        return <small>{_.round(text / Math.pow(1024,1))} KB</small>
                                    }
                                },
                                {
                                    title: 'Uploaded at',
                                    dataIndex: 'created_at',
                                    key: 'created_at',
                                    render: (text, record) => {
                                        return <Tooltip title={moment(record.created_at).format('YYYY-MM-DD HH:mm:ss')}><small>{moment(record.created_at).fromNow()}</small></Tooltip>
                                    }
                                },
                                {
                                    title: 'Uploaded by',
                                    dataIndex: 'created_by',
                                    key: 'created_by',
                                    render: (text, record) => {
                                        return <>{text}</>
                                    }
                                },
                            ]}
                        />
                    </div>

                </Modal>
            
            <Modal
                visible={updatePaymentModal}
                onCancel={() => {
                    formUpdatePayment.resetFields();
                    setUpdatePaymentModal(false);
                    setUpdatePaymentData([]);
                }}
                afterClose={(e) => {
                    formUpdatePayment.resetFields();
                    setUpdatePaymentModal(false)
                    setUpdatePaymentData([]);
                }}
                footer={null}
                title="Update Payment"
                destroyOnClose={true}
            >
                <Descriptions bordered size="small">
                    <Descriptions.Item span={4} label="Transaction #">{updatePaymentData.transaction_id}</Descriptions.Item>
                    <Descriptions.Item span={4} label="Payment Type">{paymentTypeLabel[updatePaymentData.payment_type]}</Descriptions.Item>
                    { updatePaymentData.payment_type === 'penalty_fee' &&
                    <Descriptions.Item span={4} label="Discounted Amount">
                        {
                            numberWithCommas(twoDecimalPlace(
                                parseFloat(updatePaymentData.payment_amount) - ((parseFloat(updatePaymentData.discount) / 100) * parseFloat(updatePaymentData.payment_amount))
                            ))
                        }
                    </Descriptions.Item>
                    }
                </Descriptions>

                <Form
                    layout={"horizontal"}
                    labelCol={{ span: 8 }}
                    wrapperCol={{ span: 14 }}
                    form={formUpdatePayment}
                    onFinish={updatePaymentFinish}
                ><br />

                    <Form.Item label="Payment (PHP)" name="payment_amount" rules={[{required:true}]} initialValue={updatePaymentData.payment_amount}>
                        <InputNumber min={0}
                            style={{width: 200}}
                            size="large"
                            onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, payment_amount: e })}
                        />
                    </Form.Item>
                    {
                        updatePaymentData.payment_type === 'penalty_fee' &&
                        <Form.Item label="Discount" name="discount" initialValue={updatePaymentData.discount}>
                            <InputNumber
                                formatter={value => `${value}%`}
                                parser={value => value.replace('%', '')}
                                min={0}
                                max={100}
                                style={{width: 100}}
                                size="large"
                                onChange={(e)=> {
                                    let discount = ( e === '' || parseFloat(e) < 0 ) ? 0 : e;
                                    setUpdatePaymentData({ ...updatePaymentData, discount: discount })
                                }}
                            />
                        </Form.Item>
                    }

                    <Form.Item label="PR#" name="cr_number" initialValue={updatePaymentData.cr_number}>
                        <Input
                            style={{width: 200}}
                            size="large"
                            onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, cr_number: e.target.value })}
                        />
                    </Form.Item>

                    <Form.Item label="OR#" name="or_number" initialValue={updatePaymentData.or_number}>
                        <Input
                            style={{width: 200}}
                            size="large"
                            onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, or_number: e.target.value })}
                        />
                    </Form.Item>

                    <Form.Item label="Date paid" name="paid_at" rules={[{required: true}]}
                        initialValue={moment(updatePaymentData.paid_at)}
                        >
                        <DatePicker
                            style={{width: 200}}
                            size="large"
                            showTime
                            onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, paid_at: e ? moment(e) : undefined })}
                        />
                    </Form.Item>

                    <Form.Item label="Remarks" name="remarks" initialValue={updatePaymentData.remarks}>
                        <TextArea
                            style={{width: '100%', borderRadius: 8}}
                            size="large"
                            onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, remarks: e.target.value })}
                        />
                    </Form.Item>

                    <Form.Item label="Payment gateway" rules={[{required: true}]} name="payment_gateway" initialValue={updatePaymentData.payment_gateway}>
                        <Select style={{marginLeft: 4, width: '100%'}}
                            onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, payment_gateway: e})}
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
                        ['DragonPay', 'PayMaya', 'PesoPay'].includes(updatePaymentData.payment_gateway) &&
                        <Form.Item label="Payment Gateway Reference #" name="payment_gateway_reference_number" rules={[{required: true}]} initialValue={updatePaymentData.payment_gateway_reference_number}>
                            <Input
                                style={{width: 200}}
                                size="large"
                                value={updatePaymentData.payment_gateway_reference_number}
                                onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, payment_gateway_reference_number: e.target.value })}
                            />
                        </Form.Item>
                    }

                    {
                        ['PDC', 'Direct Payment', 'Direct Deposit', 'Bank Transfer'].includes(updatePaymentData.payment_gateway) &&
                            <Form.Item label="Bank" name="bank" rules={[{required: true}]} initialValue={updatePaymentData.bank}>
                                <Input
                                    style={{width: 200}}
                                    size="large"
                                    value={updatePaymentData.bank}
                                    onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, bank: e.target.value })}
                                />
                            </Form.Item>
                    }

                    {
                        ['PDC'].includes(updatePaymentData.payment_gateway) &&
                        <Form.Item label="Check #" name="check_number" rules={[{required: true}]} initialValue={updatePaymentData.check_number}>
                            <Input
                                style={{width: 200}}
                                size="large"
                                value={updatePaymentData.check_number}
                                onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, check_number: e.target.value })}
                            />
                        </Form.Item>
                    }

                    {
                        ['PDC', 'Direct Payment', 'Direct Deposit', 'Bank Transfer'].includes(updatePaymentData.payment_gateway) &&
                        <Form.Item label="Bank Account #" name="bank_account_number" rules={[{required: true}]} initialValue={updatePaymentData.bank_account_number}>
                            <Input
                                style={{width: 200}}
                                size="large"
                                value={updatePaymentData.bank_account_number}
                                onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, bank_account_number: e.target.value })}
                            />
                        </Form.Item>
                    }

                </Form>

                <Popconfirm
                    title="Are you sure you want to apply Payment?"
                    onConfirm={() => formUpdatePayment.submit()}
                    onCancel={() => console.log("cancelled update payment")}
                    okText="Yes"
                    cancelText="No"
                >
                    <Button block type="primary" className='mt-2'>Save</Button>
                </Popconfirm>

            </Modal>
        </>
    )
}

export default PaymentDetails;