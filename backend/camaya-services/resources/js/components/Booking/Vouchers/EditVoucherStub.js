import React from 'react'

import VoucherService from 'services/Booking/VoucherService'
import { Form, Input, Row, Col, DatePicker, InputNumber, Select, Button, notification, message } from 'antd'
import moment from 'moment-timezone'
import { queryCache } from 'react-query'
moment.tz.setDefault('Asia/Manila');


export default function Page(props) {

    const initialValues = {
        id: props.voucherStub.id,
        name: props.voucherStub.name,
        type: props.voucherStub.type,
        availability: props.voucherStub.availability,
        category: props.voucherStub.category,
        mode_of_transportation: props.voucherStub.mode_of_transportation,
        allowed_days: props.voucherStub.allowed_days,
        price: props.voucherStub.price,
        exclude_days: props.voucherStub.exclude_days,
        tags: props.voucherStub.tags,
        selling_start_date: moment(props.voucherStub.selling_start_date),
        selling_end_date: moment(props.voucherStub.selling_end_date),
        booking_start_date: moment(props.voucherStub.booking_start_date),
        booking_end_date: moment(props.voucherStub.booking_end_date),
        status: props.voucherStub.status,
        quantity_per_day: props.voucherStub.quantity_per_day,
        stocks: props.voucherStub.stocks,
        description: props.voucherStub.description,
    };

    const [editVoucherStubForm] = Form.useForm();
    const [updateVoucherQuery, {isLoading: updateVoucherQueryIsLoading, reset: updateVoucherQueryReset, error: updateVoucherQueryError}] = VoucherService.updateVoucherStub();

    React.useEffect( () => {
        editVoucherStubForm.setFieldsValue(initialValues);
    }, []);

    const ShowTagItemError = ({name}) => {
        let errors = [];

        if (updateVoucherQueryError && updateVoucherQueryError.errors) {
            _.map(updateVoucherQueryError.errors, (error, key) => {
                if (key.split('.')[0] == name) {
                    errors.push(error);
                }
            });

            return <div role="alert" style={{color: '#ff4d4f'}}>{errors.join(', ')}</div>
        }
        return null;
    }

    const editVoucherFormOnFinish = (values) => {

        if (updateVoucherQueryIsLoading) {
            return false;
        }

        const newValues = {
            ...values,
            // voucher_images: uploadedImages,
        }
        
        updateVoucherQuery(newValues, {
            onSuccess: (res) => {
                console.log(res);
                message.success('Updated voucher successfully!');

                queryCache.setQueryData("vouchers", prev => {

                    const index = prev.findIndex(i => i.id == res.data.id);

                    prev[index] = res.data;

                    return [...prev];
                });
                // props.voucherListQuery.refetch();

                //Reset fields
                // editVoucherStubForm.resetFields();
                updateVoucherQueryReset();
                props.setEditVoucherStubRecord({});

                notification.success({
                    message: `Update Package - ${res.data.name}!`,
                    description:
                        ``,
                });

                props.setEditVoucherStubModalVisible(false);

                // setUploadedImages([]);
                // setUploadData(initialUploadData);
            },
            onError: (e) => {
                console.log(e);
                message.info(e.message+" Please check for errors.");
                updateVoucherQueryReset();
            }
        })
    }

    return <Form
            layout="vertical"
            form={editVoucherStubForm}
            // initialValues={initialValues}
            onFinish={editVoucherFormOnFinish}
        >
        
            <Row gutter={[12,12]}>
                <Form.Item name="id" noStyle>
                    <Input type="hidden" />
                </Form.Item>
                <Col xl={12}>
                    <Form.Item
                        name="name"
                        label="Name"
                        rules={[
                            {
                                required: true
                            }
                        ]}
                    >
                        <Input/>
                    </Form.Item>
                </Col>
                {/* <Col xl={24}>
                    <Typography.Text className="mb-2">Voucher images</Typography.Text>
                        <Upload
                            action={
                                props.type === 'new' 
                                ? `${process.env.APP_URL}/api/booking/voucher/image-upload`
                                : `${process.env.APP_URL}/api/booking/voucher/add-image`
                            }
                            headers={
                                {
                                    Authorization: 'Bearer '+localStorage.getItem('token'),
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            }
                            data={
                                props.type === 'new'
                                ? null
                                : { id: viewPackageForm.getFieldValue('id') }
                            }
                            listType="picture-card"
                            fileList={uploadData.fileList}
                            onPreview={handlePreview}
                            onChange={handleChange}
                            onRemove={handleRemove}
                            >
                            {uploadData.fileList.length >= 8 ? null : uploadButton}
                            </Upload>
                            <Modal
                                visible={uploadData.previewVisible}
                                title={uploadData.previewTitle}
                                footer={null}
                                onCancel={handleCancel}
                                >
                                <img alt="product image" style={{ width: '100%' }} src={uploadData.previewImage} />
                            </Modal>
                </Col> */}
                <Col xl={12}>
                    <Form.Item name="type" label="Type"
                    rules={[
                        {
                            required: true
                        }
                    ]}>
                        <Select>
                            <Select.Option value="per_booking">Per booking</Select.Option>
                            <Select.Option value="per_guest">Per guest</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item name="availability" label="Availability"
                    rules={[
                        {
                            required: true
                        }
                    ]}>
                        <Select>
                            <Select.Option value="for_dtt">For day tour</Select.Option>
                            <Select.Option value="for_overnight">For overnight</Select.Option>
                            <Select.Option disabled value="for_dtt_and_overnight">For day tour and overnight</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item name="category" label="Category">
                        <Select>
                            <Select.Option value="">None</Select.Option>
                            <Select.Option value="golf">Golf</Select.Option>
                            <Select.Option value="manual">Manual</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item name="mode_of_transportation" label="Mode of transportation"
                    onChange={val => setModeOfTransportation(val)}
                    rules={[
                        {
                            required: true
                        }
                    ]}>
                        <Select>
                            <Select.Option value="own_vehicle">Own vehicle</Select.Option>
                            <Select.Option value="camaya_transportation">Camaya transportation</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item name="allowed_days" label="Allowed days"
                    rules={[
                        {
                            required: true
                        }
                    ]}>
                        <Select mode="multiple">
                            <Select.Option value="mon">Monday</Select.Option>
                            <Select.Option value="tue">Tueday</Select.Option>
                            <Select.Option value="wed">Wednesday</Select.Option>
                            <Select.Option value="thu">Thursday</Select.Option>
                            <Select.Option value="fri">Friday</Select.Option>
                            <Select.Option value="sat">Saturday</Select.Option>
                            <Select.Option value="sun">Sunday</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item name="price" label="Price"
                        rules={[
                            {
                                required: true
                            }
                        ]}
                    >
                        <InputNumber min="1"/>
                    </Form.Item>
                </Col>
                {/* <Col xl={24}>
                    Package Price Ranges
                </Col> */}
                <Col xl={24}>
                    <Form.Item name="exclude_days" label="Exclude days"
                        extra={<ShowTagItemError name="exclude_days" />}
                    // rules={[
                    //     {
                    //         pattern: /^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$/,
                    //         message: 'Format not allowed.'
                    //     },
                    // ]}
                    >
                        <Select mode="tags" placeholder="format:YYYY-MM-DD" tokenSeparators={[',',';',' ']}/>
                    </Form.Item>
                </Col>

                <Col xl={12}>
                    <Form.Item name="selling_start_date" label="Selling start date"
                    >
                        <DatePicker/>
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item name="selling_end_date" label="Selling end date"
                    >
                        <DatePicker/>
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item name="booking_start_date" label="Booking start date"
                    >
                        <DatePicker/>
                    </Form.Item>
                </Col>
                <Col xl={12}>
                    <Form.Item name="booking_end_date" label="Booking end date"
                    >
                        <DatePicker/>
                    </Form.Item>
                </Col>

                <Col xl={12}>
                    <Form.Item name="status" label="Status"
                        rules={[{required: true}]}
                    >
                        <Select>
                            <Select.Option value="unpublished">Unpublished</Select.Option>
                            <Select.Option value="published">Published</Select.Option>
                            <Select.Option value="expired">Expired</Select.Option>
                            <Select.Option value="ended">Ended</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item name="quantity_per_day" label="Quantity per day">
                        <InputNumber/>
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item name="stocks" label="Stocks">
                        <InputNumber/>
                    </Form.Item>
                </Col>
                <Col xl={24}>
                    <Form.Item name="description" label="Description">
                        <Input.TextArea style={{borderRadius:'12px'}}/>
                    </Form.Item>
                </Col>

                <Col xl={24}>
                    <Button htmlType="submit">Update</Button>
                </Col>

            </Row>

    </Form>

}