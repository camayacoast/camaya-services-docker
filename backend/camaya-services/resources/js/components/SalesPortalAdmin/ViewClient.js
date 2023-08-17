import React from 'react'
import { Form, Input, Row, Col, Typography, Button, Checkbox, DatePicker, Select, Card, message, Upload, Modal, Space, Popconfirm } from 'antd'

import { UploadOutlined, LockOutlined, DeleteOutlined } from '@ant-design/icons'

import { useParams } from "react-router-dom";

import moment from 'moment'

import countries from 'common/countries.json';

import SalesAdminPortalServices from 'services/SalesAdminPortal';

export default function Page(props) {

    let { client_number } = useParams();

    // States
    const [withSpouse, setWithSpouse] = React.useState(false);
    const [formErrors, setFormErrors] = React.useState({});
    const [fileList, setFileList] = React.useState([]);
    const [uploading, setUploading] = React.useState(false);
    const [attachmentType, setAttachmentType] = React.useState('');
    const [attachmentTypeModalVisible, setAttachmentTypeModalVisible] = React.useState(false);
    const [fileToUpload, setFileToUpload] = React.useState([]);
    const [client, setClient] = React.useState({});
    const [internationalAddress, setInternationalAddress] = React.useState(false);
    const [sameAsPermanentAddress, setSameAsPermanentAddress] = React.useState(false);
    const [clientAssuanceNA, setClientAssuanceNA] = React.useState(false);
    const [clientPlaceAssuanceOrig, setClientPlaceAssuanceOrig] = React.useState('');
    const [clientPlaceAssuance, setClientPlaceAssuance] = React.useState(false);
    const [clientDateAssuanceOrig, setClientDateAssuanceOrig] = React.useState('');
    const [clientDateAssuance, setClientDateAssuance] = React.useState(false);
    const [clientSpouseAssuanceNA, setClientSpouseAssuanceNA] = React.useState(false);
    const [clientSpousePlaceAssuance, setClientSpousePlaceAssuance] = React.useState(false);
    const [clientSpousePlaceAssuanceOrig, setClientSpousePlaceAssuanceOrig] = React.useState('');
    const [clientSpouseDateAssuance, setClientSpouseDateAssuance] = React.useState(false);
    const [clientSpouseDateAssuanceOrig, setClientSpouseDateAssuanceOrig] = React.useState('');

    // Form
    const [editClientForm] = Form.useForm();

    // Queries
    const salesAgentListQuery = SalesAdminPortalServices.salesAgentList(true);
    const [viewClientQuery, { IsLoading: viewClientQueryIsLoading, reset: viewClientQueryReset}] = SalesAdminPortalServices.viewClient();
    const [updateAttachmentStatusQuery, { IsLoading: updateAttachmentStatusQueryIsLoading, reset: updateAttachmentStatusQueryReset}] = SalesAdminPortalServices.updateAttachmentStatus();
    const [addAttachmentQuery, { isLoading: addAttachmentQueryIsLoading, reset: addAttachmentQueryReset}] = SalesAdminPortalServices.addAttachment();
    const [downloadCRFQuery, { isLoading: downloadCRFQueryIsLoading, reset: downloadCRFQueryReset}] = SalesAdminPortalServices.downloadCRF();
    const [updateClientRecordQuery, { isLoading: updateClientRecordQueryIsLoading, reset: updateClientRecordQueryReset}] = SalesAdminPortalServices.updateClientRecord();
    const [deleteClientAttachmentQuery, { isLoading: deleteClientAttachmentQueryIsLoading, reset: deleteClientAttachmentQueryReset}] = SalesAdminPortalServices.deleteClientAttachment();
    const [updateAttachmentTypeQuery, { isLoading: updateAttachmentTypeQueryIsLoading, reset: updateAttachmentTypeQueryReset}] = SalesAdminPortalServices.updateAttachmentType();
    const [approveAttachmentsQuery, { isLoading: approveAttachmentsQueryIsLoading, reset: approveAttachmentQueryReset}] = SalesAdminPortalServices.approveAttachments();

    React.useEffect( () => {

        viewClientQuery({
            id: client_number
        }, {
            onSuccess: (res) => {
                // console.log(res.data);
                updateData(res.data);
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
        // setWithBIS(props.withBIS);

        editClientForm.setFieldsValue({ current_home_address_international: internationalAddress });

    }, [internationalAddress]);

    React.useEffect( () => {

        if (!attachmentTypeModalVisible && attachmentType) {

            if (addAttachmentQueryIsLoading) {
                return false;
            }

            let files = fileToUpload;
            files.attachment_type = attachmentType;
    
            let formData = new FormData();
    
            _.map(files, function(file, i){
                formData.append('file[]', file);
            });
    
            formData.append('attachment_type', attachmentType);
            formData.append('client_information_id', client.information.id);
    
            addAttachmentQuery(formData, {
                onSuccess: (res) => {
                    console.log(res);
                    setAttachmentType('');
                    updateData(res.data);
                    setFileList([]);
                    formData.delete('file[]');
                    formData.delete('attachment_type');
                    formData.delete('client_information_id');
                },
                onError: (e) => {
                    console.log(e);
                    setFileList([]);
                    setAttachmentType('');
                    formData.delete('file[]');
                    formData.delete('attachment_type');
                    formData.delete('client_information_id');
                }
            });

        }
    }, [attachmentTypeModalVisible]);

    React.useEffect( () => {
        if (sameAsPermanentAddress) {
            setInternationalAddress(false);

            editClientForm.setFieldsValue({
                ...editClientForm.getFieldsValue(),
                current_home_address_house_number: editClientForm.getFieldValue('permanent_home_address_house_number'),
                current_home_address_street: editClientForm.getFieldValue('permanent_home_address_street'),
                current_home_address_baranggay: editClientForm.getFieldValue('permanent_home_address_baranggay'),
                current_home_address_city: editClientForm.getFieldValue('permanent_home_address_city'),
                current_home_address_province: editClientForm.getFieldValue('permanent_home_address_province'),
                current_home_address_zip_code: editClientForm.getFieldValue('permanent_home_address_zip_code'),
            });
        }
    }, [sameAsPermanentAddress]);

    const updateData = (data) => {

        const res = {
            data: data
        };

        setClient(res.data);

        setWithSpouse(res.data.spouse != null ? true : false);
        setInternationalAddress(res.data.information && res.data.information.current_home_address_international == 1 ? true : false);

        setClientPlaceAssuanceOrig(res.data.information ? res.data.information.government_issued_id_issuance_place : '');
        setClientDateAssuanceOrig(res.data.information && res.data.information.government_issued_id_issuance_date ? moment(res.data.information.government_issued_id_issuance_date) : null);
        setClientSpousePlaceAssuanceOrig(res.data.spouse ? res.data.spouse.spouse_id_issuance_place : '');
        setClientSpouseDateAssuanceOrig(res.data.spouse && res.data.spouse.spouse_id_issuance_date ? moment(res.data.information.spouse_id_issuance_date) : null);

        if( res.data.information && res.data.information.government_issued_id_issuance_date == null && res.data.information.government_issued_id_issuance_place == null ) {
            setClientAssuanceNA(true);
            setClientPlaceAssuance(true);
            setClientDateAssuance(true);
        }

        if( res.data.spouse && res.data.spouse.spouse_id_issuance_date == null && res.data.spouse.spouse_id_issuance_place == null ) {
            setClientSpouseAssuanceNA(true);
            setClientSpousePlaceAssuance(true);
            setClientSpouseDateAssuance(true);
        }
        
        editClientForm.setFieldsValue({
            ...res.data,
            contact_number: res.data.information ? res.data.information.contact_number : null,
            extension: res.data.information ? res.data.information.extension : null,
            assigned_agent: res.data.agent ? res.data.agent.sales_id : null,
            with_spouse: res.data.spouse != null ? true : false,
            // ...res.data.information
            gender: res.data.information ? res.data.information.gender : null,
            birth_date: res.data.information ? moment(res.data.information.birth_date) : null,
            birth_place: res.data.information ? res.data.information.birth_place : null,
            citizenship: res.data.information ? res.data.information.citizenship : null,
            monthly_household_income: res.data.information ? res.data.information.monthly_household_income : null,
            government_issued_identification: res.data.information ? res.data.information.government_issued_id : null,
            // id_issuance_place: res.data.information.government_issued_id_issuance_place,
            id_issuance_date: res.data.information && res.data.information.government_issued_id_issuance_date ? moment(res.data.information.government_issued_id_issuance_date) : null,
            tax_identification_number: res.data.information ? res.data.information.tax_identification_number : null,
            occupation: res.data.information ? res.data.information.occupation : null,
            company_name: res.data.information ? res.data.information.company_name : null,
            company_address: res.data.information ? res.data.information.company_address : null,
            home_phone: res.data.information ? res.data.information.home_phone: null,
            business_phone: res.data.information ? res.data.information.business_phone : null,
            mobile_number: res.data.information ? res.data.information.mobile_number : null,

            spouse_first_name: res.data.spouse ? res.data.spouse.spouse_first_name : '',
            spouse_middle_name: res.data.spouse ? res.data.spouse.spouse_middle_name : '',
            spouse_last_name: res.data.spouse ? res.data.spouse.spouse_last_name : '',
            spouse_gender: res.data.spouse ? res.data.spouse.spouse_gender : '',
            spouse_birth_date: res.data.spouse ? moment(res.data.spouse.spouse_birth_date) : '',
            spouse_birth_place: res.data.spouse ? res.data.spouse.spouse_birth_place : '',
            spouse_citizenship: res.data.spouse ? res.data.spouse.spouse_citizenship : '',
            spouse_monthly_household_income: res.data.spouse ? res.data.spouse.spouse_monthly_household_income : '',
            spouse_government_issued_identification: res.data.spouse ? res.data.spouse.spouse_government_issued_identification : '',
            spouse_id_issuance_place: res.data.spouse ? res.data.spouse.spouse_id_issuance_place : '',
            spouse_id_issuance_date: res.data.spouse && res.data.spouse.spouse_id_issuance_date ? moment(res.data.information.spouse_id_issuance_date) : null,
            spouse_tax_identification_number: res.data.spouse ? res.data.spouse.spouse_tax_identification_number : '',
            spouse_occupation: res.data.spouse ? res.data.spouse.spouse_occupation : '',
            spouse_company_name: res.data.spouse ? res.data.spouse.spouse_company_name : '',
            spouse_company_address: res.data.spouse ? res.data.spouse.spouse_company_address : '',

            permanent_home_address_house_number: res.data.information ? res.data.information.permanent_home_address_house_number : null,
            permanent_home_address_street: res.data.information ?  res.data.information.permanent_home_address_street : null,
            permanent_home_address_baranggay: res.data.information ?  res.data.information.permanent_home_address_baranggay : null,
            permanent_home_address_city: res.data.information ?  res.data.information.permanent_home_address_city : null,
            permanent_home_address_province: res.data.information ?  res.data.information.permanent_home_address_province : null,
            permanent_home_address_zip_code: res.data.information ?  res.data.information.permanent_home_address_zip_code : null,

            current_home_address_international: res.data.information && res.data.information.current_home_address_international ? true : false,
            current_home_address_house_number: res.data.information ?  res.data.information.current_home_address_house_number : null,
            current_home_address_street: res.data.information ?  res.data.information.current_home_address_street : null,
            current_home_address_baranggay: res.data.information ?  res.data.information.current_home_address_baranggay : null,
            current_home_address_city: res.data.information ?  res.data.information.current_home_address_city : null,
            current_home_address_province: res.data.information ?  res.data.information.current_home_address_province: null,
            current_home_address_zip_code: res.data.information ?  res.data.information.current_home_address_zip_code: null,
            current_home_address_country: res.data.information ?  res.data.information.current_home_address_country: null,

            preferred_mailing_address: res.data.information ?  res.data.information.preferred_mailing_address: null,
        });
    }

    const handleChangeAttachmentStatus = (id, status, client_information_id) => {
        // console.log(id);

        if (updateAttachmentStatusQueryIsLoading) {
            return false;
        }

        updateAttachmentStatusQuery(
            {
                id: id,
                status: status,
                client_information_id: client_information_id,
            }, {
                onSuccess: (res) => {
                    message.success("Attachment status updated!");
                    updateData(res.data);
                },
                onError: (e) => {
                    message.warning("Attachment status update failed.");
                }
            }
        )
    }

    const updateAttachmentType = (param) => {

        if (updateAttachmentTypeQueryIsLoading) {
            return false;
        }

        updateAttachmentTypeQuery(
            {
                id: param.id,
                attachment_type: param.attachment_type,
                client_information_id: param.client_id,
            }, {
                onSuccess: (res) => {
                    message.success("Attachment type updated!");
                    updateData(res.data);
                },
                onError: (e) => {
                    message.warning("Attachment type update failed.");
                }
            }
        )
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
        beforeUpload: (file, fileList) => {
            setAttachmentTypeModalVisible(true);
            setAttachmentType('');
            setFileToUpload(fileList);
            return false;
        },
        fileList,
        multiple: true,
    };

    const onCheck = async () => {
        try {
          const values = await editClientForm.validateFields();
          console.log('Success:', values);

          if (updateClientRecordQueryIsLoading) {
            return false;
          }

          setClientPlaceAssuanceOrig(values.id_issuance_place);
          setClientDateAssuanceOrig(values.id_issuance_date);

          console.log(values.id_issuance_place, values.id_issuance_date);

          if( (values.id_issuance_place == null || values.id_issuance_place == '') && values.id_issuance_date == null ) {
            setClientAssuanceNA(true);
            setClientPlaceAssuance(true);
            setClientDateAssuance(true);
          }
          

          setClientSpousePlaceAssuanceOrig(values.spouse_id_issuance_place);
          setClientSpouseDateAssuanceOrig(values.spouse_id_issuance_date);

          if( (values.spouse_id_issuance_place == null || values.spouse_id_issuance_place == '') && values.spouse_id_issuance_date == null ) {
            setClientSpouseAssuanceNA(true);
            setClientSpousePlaceAssuance(true);
            setClientSpouseDateAssuance(true);
          }

          updateClientRecordQuery(
                {
                    ...values,
                    current_home_address_international: internationalAddress,
                    client_id: client_number
                }
                , {
                onSuccess: (res) => {
                    // console.log(res);

                    message.success('Updated ('+ res.data.client.first_name +' '+ res.data.client.last_name +') client record saved!');
                },
                onError: (e) => {
                    // console.log(e.error);
                    if (e.error == 'EMAIL_EXISTS') {
                        setFormErrors({
                            email: e.message
                        })
                    }

                    if (e.message == 'Unauthorized.') {
                        message.error("You don't have access to do this action.");
                    }
                    // setFormErrors(e.errors);

                },
            })
        } catch (errorInfo) {
          setFormErrors({ email: _.find(errorInfo.errorFields, i => i.name == 'email')?.errors })
          console.log('Failed:', errorInfo);
        }
    };

    const handleDownloadCRF = () => {
        if (downloadCRFQueryIsLoading) {
            return false;
        }

        downloadCRFQuery(
            {
                client_number: client_number,
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
                    a.download = `CRF - ${client.first_name} ${client.last_name}`;
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

    const handlePermanentAddressChange = (value, input) => {
        if (sameAsPermanentAddress) {
            editClientForm.setFieldsValue({
                ...editClientForm.getFieldsValue(),
                ['current_'+input]: value,
            });
        }
    }

    const handleConfirmDeleteAttachment = (id) => {
        console.log(id);

        if (deleteClientAttachmentQueryIsLoading) {
            return false;
          }

          deleteClientAttachmentQuery(
                {
                    id: id
                }
                , {
                onSuccess: (res) => {
                    // console.log(res);
                    message.success('Attachment deleted!');
                    updateData(res.data);
                },
                onError: (e) => {
                    // console.log(e.error);
                    message.error(e.message);
                    deleteClientAttachmentQueryReset();

                    if (e.message == 'Unauthorized.') {
                        message.error("You don't have access to do this action.");
                    }
                    // setFormErrors(e.errors);

                },
            })
    }

    const approveAllAttachments = (client_number) => {

        if (approveAttachmentsQueryIsLoading) {
            return false;
        }

        approveAttachmentsQuery({
            client_number: client_number,
        }, {
            onSuccess: (res) => {
                let msg = res.data.message;
                message.success(msg);
                refetchViewClient();
            },
            onError: (e) => {
                approveAttachmentQueryReset();
                message.warning(`Updating payment failed: ${e.errors ? _.map(e.errors, (i) => i) : e.message}`)
            }
        })
    }

    const refetchViewClient = () => {

        viewClientQuery({
            id: client_number
        }, {
            onSuccess: (res) => {
                // console.log(res.data);
                updateData(res.data);
            },
            onError: (e) => {
                console.log(e)
                if (e.error == 'RESERVATION_NOT_FOUND') {
                    props.history.goBack();
                }
            },
        });

    }

    if (!client) { 
        return <>Loading...</>;
    }

    return <>
        <Modal
            title="Upload Files"
            visible={attachmentTypeModalVisible}
            onOk={() => {
                setAttachmentType('0');
                setAttachmentTypeModalVisible(false);
            }}
            onCancel={() => setAttachmentTypeModalVisible(false)}
            okText="Okay"
            cancelText="Cancel"
        >
             Are you sure you want to upload the files?
        </Modal>
        <Form
            form={editClientForm}
            layout="vertical"
            // onFinish={handleFormFinish}
            initialValues={{
                with_spouse: withSpouse,
                current_home_address_international: internationalAddress ? true : false
            }}
        >
            <Typography.Title level={3} className="mb-4 mt-2">
                {client.first_name} {client.last_name}
            </Typography.Title>
    <Row gutter={[32,32]}>
        <Col xl={6}>
            <Typography.Title level={4}>
                Client Registration Form
            </Typography.Title>

            <Form.Item label="Assigned agent" name="assigned_agent" rules={[
                    {
                        required: true
                    }]}
                >
                <Select 
                    placeholder="Assigned agent"
                    showSearch
                    optionFilterProp="children"
                    filterOption={true}>
                    {
                        salesAgentListQuery.data && salesAgentListQuery.data.map(
                            (item, key) => {
                                if( item.id == 0 ) {
                                    return <Select.Option key={key} value={item.id}>{item.first_name}</Select.Option>
                                } else {
                                    return <Select.Option key={key} value={item.id}>{item.first_name} {item.last_name} - {item.email}</Select.Option>
                                } 
                        })
                        
                    }
                </Select>
            </Form.Item>

            <Form.Item label="First name" name="first_name" rules={[
                    {
                        required: true
                    }]}
                >
                <Input />
            </Form.Item>

            <Form.Item label="Middle name" name="middle_name" extra={<small>Optional</small>}>
                <Input />
            </Form.Item>

            <Form.Item label="Last name" name="last_name" rules={[
                    {
                        required: true
                    }]}
                >
                <Input />
            </Form.Item>

            <Form.Item label="Extension" name="extension" extra={<small>Optional</small>}>
                <Input placeholder="Jr. / Sr. / III" />
            </Form.Item>

            <Form.Item label="Contact number" name="contact_number"
                rules={[
                    {
                        required: true
                    }]}
                >
                <Input />
            </Form.Item>

            <Form.Item label="Email address" name="email" extra={<small>Client e-mail address should be unique per client.</small>}
                hasFeedback
                validateStatus={formErrors.email ? 'error' : ''}
                help={formErrors.email ? formErrors.email : ''}
                rules={[
                    {
                        required: true
                    },
                    {
                        type: 'email'
                    }
                ]}
            >
                <Input />
            </Form.Item>

            {/* <Form.Item name="with_bis">
                <Checkbox checked={props.withBIS} onClick={()=>props.setNewClientWithBIS(!props.withBIS)} className="mr-2">Register client with BIS</Checkbox>
            </Form.Item> */}
        </Col>
        <Col xl={18}>
            <Typography.Title level={4}>
                Buyer's Information Sheet
            </Typography.Title>
            <div style={{width: 300}}>
                {/* Gender, Birth date, Birth place, Citizenship
                Monthly household income,
                Government issued identification, Government issued identification date of issuance, Government issued identification place of issuance
                Tax identification number, occupation, company name, company address

                Spouse Information:
                Last name, First name, Middle name, Extension, Gender, Birth date, Birth place, Citizenship
                Government issued identification, Government issued identification date of issuance, Government issued identification place of issuance
                Tax identification number, occupation, company name, company address

                Contact information:
                Home phone, Business phone, Email address, Mobile number

                Complete address:
                Permanent home address, Current home address, Office address, Preferred mailing address,  Preferred mailing address type

                Signature:
                Buyer signature,
                Spouse signature */}
            </div>
            <Row gutter={[8, 8]}>
                <Col xl={8}>
                    <Form.Item label="Gender" name="gender" rules={[{
                        required: true
                    }]}>
                        <Select placeholder="Gender">
                            <Select.Option value="male">Male</Select.Option>
                            <Select.Option value="female">Female</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Birth date" name="birth_date" rules={[{
                        required: true
                    }]}>
                        <DatePicker placeholder="Birth date" />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Birth place" name="birth_place" rules={[{
                        required: true
                    }]}>
                        <Input placeholder="Birth place" />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Citizenship" name="citizenship" rules={[{
                        required: true
                    }]}>
                        <Input placeholder="" />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Monthly household income" name="monthly_household_income" rules={[{
                        required: true
                    }]}>
                        <Select placeholder="">
                            <Select.Option value="below_50000">Below P50,000</Select.Option>
                            <Select.Option value="50001_80000">P50,001 - P80,000</Select.Option>
                            <Select.Option value="80001_120000">P80,001 - P120,000</Select.Option>
                            <Select.Option value="120001_150000">P120,001 - P150,000</Select.Option>
                            <Select.Option value="150001_180000">P150,001 - P180,000</Select.Option>
                            <Select.Option value="above_180000">Above P180,000</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Government issued identification" name="government_issued_identification" rules={[{
                        required: true
                    }]}>
                        <Select placeholder="">
                            <Select.OptGroup label="Local/Filipino Primary ID">
                                <Select.Option value="Seaman's Book">Seaman's Book</Select.Option>
                                <Select.Option value="UMID">UMID</Select.Option>
                                <Select.Option value="Social Security System">Social Security System</Select.Option>
                                <Select.Option value="Driver's License">Driver's License</Select.Option>
                                <Select.Option value="Professional ID">Professional ID</Select.Option>
                                <Select.Option value="Firearm's">Firearm's</Select.Option>
                                <Select.Option value="Integrated Bar of the Phils.">Integrated Bar of the Phils.</Select.Option>
                                <Select.Option value="Postal ID">Postal ID</Select.Option>
                                <Select.Option value="Voter's ID">Voter's ID</Select.Option>
                            </Select.OptGroup>
                            <Select.OptGroup label="Local/Filipino Secondary ID">
                                <Select.Option value="Senior ID">Senior ID</Select.Option>
                                <Select.Option value="Police Clearance">Police Clearance</Select.Option>
                                <Select.Option value="Philhealth ID">Philhealth ID</Select.Option>
                                <Select.Option value="TIN ID">TIN ID</Select.Option>
                                <Select.Option value="Brgy. Resident ID">Brgy. Resident ID</Select.Option>
                            </Select.OptGroup>

                            <Select.OptGroup label="Foreigner Primary ID">
                                <Select.Option value="Passport">Passport</Select.Option>
                                <Select.Option value="Driver's License">Driver's License</Select.Option>
                            </Select.OptGroup>

                            <Select.OptGroup label="Foreigner Secondary ID">
                                    <Select.Option value="Resident ID">Resident ID</Select.Option>
                                    <Select.Option value="VISA ID">VISA ID</Select.Option>
                                    <Select.Option value="Permanent Resident ID/Card">Permanent Resident ID/Card</Select.Option>
                                    <Select.Option value="Health Card">Health Card</Select.Option>
                            </Select.OptGroup>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Place of issuance" name="id_issuance_place">
                        <Input disabled={clientPlaceAssuance} placeholder="" />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Row gutter={[8,8]}>
                        <Col xl={12}>
                            <Form.Item label="Date of issuance" name="id_issuance_date">
                                <DatePicker allowClear placeholder="" disabled={clientDateAssuance} />
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item label=" " name="is_available_issuance_details">
                                <Checkbox checked={clientAssuanceNA} onChange={(e) => { 
                                    let is_checked = e.target.checked;
                                    setClientAssuanceNA(is_checked); 
                                    setClientPlaceAssuance(is_checked);
                                    setClientDateAssuance(is_checked);
                                    
                                    if( is_checked ) {
                                        editClientForm.setFieldsValue({
                                            id_issuance_place: null,
                                            id_issuance_date: null
                                        });
                                    } else {
                                        editClientForm.setFieldsValue({
                                            id_issuance_place: clientPlaceAssuanceOrig,
                                            id_issuance_date: clientDateAssuanceOrig
                                        });
                                    }

                                }}>Not Applicable</Checkbox>
                            </Form.Item>
                        </Col>
                    </Row>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Tax identification number" name="tax_identification_number" rules={[{
                        required: true
                    }]}>
                        <Input placeholder="" />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Occupation" name="occupation" rules={[{
                        required: true
                    }]}>
                        <Input placeholder="" />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Company name" name="company_name" rules={[{
                        required: true
                    }]}>
                        <Input placeholder="" />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Company address" name="company_address" rules={[{
                        required: true
                    }]}>
                        <Input placeholder="" />
                    </Form.Item>
                </Col>

                {/* Spouse */}

                <Col xl={24}>
                    <Form.Item valuePropName="checked" defaultChecked={withSpouse} name="with_spouse">
                        <Checkbox onChange={(e) => setWithSpouse(!withSpouse)}> WITH SPOUSE</Checkbox>
                    </Form.Item>
                </Col>

                { withSpouse == true &&
                <>
                <Col xl={24}>
                    <Typography.Title level={5}>Spouse information</Typography.Title>
                </Col>

                <Col xl={24}>
                    <Card size="small">
                    <Row gutter={[8,8]}>
                        <Col xl={8}>
                            <Form.Item label="First name" name="spouse_first_name" rules={[
                                {
                                    required: withSpouse
                                }]}
                            >
                                <Input />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Middle name" name="spouse_middle_name" extra={<small>Optional</small>}>
                                <Input />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Last name" name="spouse_last_name" rules={[
                                    {
                                        required: withSpouse
                                    }]}
                                >
                                <Input />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Extension" name="spouse_extension" extra={<small>Optional</small>}>
                                <Input placeholder="Jr. / Sr. / III" />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Gender" name="spouse_gender" rules={[{
                                required: withSpouse
                            }]}>
                                <Select placeholder="Gender">
                                    <Select.Option value="male">Male</Select.Option>
                                    <Select.Option value="female">Female</Select.Option>
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Birth date" name="spouse_birth_date" rules={[{
                                required: withSpouse
                            }]}>
                                <DatePicker placeholder="Birth date" />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Birth place" name="spouse_birth_place" rules={[{
                                required: withSpouse
                            }]}>
                                <Input placeholder="Birth place" />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Citizenship" name="spouse_citizenship" rules={[{
                                required: withSpouse
                            }]}>
                                <Input placeholder="" />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Government issued identification" name="spouse_government_issued_identification" rules={[{
                                required: withSpouse
                            }]}>
                                <Select placeholder="">
                                    <Select.OptGroup label="Local/Filipino Primary ID">
                                        <Select.Option value="Seaman's Book">Seaman's Book</Select.Option>
                                        <Select.Option value="UMID">UMID</Select.Option>
                                        <Select.Option value="Social Security System">Social Security System</Select.Option>
                                        <Select.Option value="Driver's License">Driver's License</Select.Option>
                                        <Select.Option value="Professional ID">Professional ID</Select.Option>
                                        <Select.Option value="Firearm's">Firearm's</Select.Option>
                                        <Select.Option value="Integrated Bar of the Phils.">Integrated Bar of the Phils.</Select.Option>
                                        <Select.Option value="Postal ID">Postal ID</Select.Option>
                                        <Select.Option value="Voter's ID">Voter's ID</Select.Option>
                                    </Select.OptGroup>
                                    <Select.OptGroup label="Local/Filipino Secondary ID">
                                        <Select.Option value="Senior ID">Senior ID</Select.Option>
                                        <Select.Option value="Police Clearance">Police Clearance</Select.Option>
                                        <Select.Option value="Philhealth ID">Philhealth ID</Select.Option>
                                        <Select.Option value="TIN ID">TIN ID</Select.Option>
                                        <Select.Option value="Brgy. Resident ID">Brgy. Resident ID</Select.Option>
                                    </Select.OptGroup>

                                    <Select.OptGroup label="Foreigner Primary ID">
                                        <Select.Option value="Passport">Passport</Select.Option>
                                        <Select.Option value="Driver's License">Driver's License</Select.Option>
                                    </Select.OptGroup>

                                    <Select.OptGroup label="Foreigner Secondary ID">
                                            <Select.Option value="Resident ID">Resident ID</Select.Option>
                                            <Select.Option value="VISA ID">VISA ID</Select.Option>
                                            <Select.Option value="Permanent Resident ID/Card">Permanent Resident ID/Card</Select.Option>
                                            <Select.Option value="Health Card">Health Card</Select.Option>
                                    </Select.OptGroup>
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Place of issuance" name="spouse_id_issuance_place">
                                <Input disabled={clientSpousePlaceAssuance} placeholder="" />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Row gutter={[8,8]}>
                                <Col xl={12}>
                                    <Form.Item label="Date of issuance" name="spouse_id_issuance_date">
                                        <DatePicker disabled={clientSpouseDateAssuance} allowClear placeholder="" />
                                    </Form.Item>
                                </Col>
                                <Form.Item label=" " name="is_available_spouse_issuance_details">
                                    <Checkbox checked={clientSpouseAssuanceNA} onChange={(e) => { 
                                        let is_checked = e.target.checked;
                                        setClientSpouseAssuanceNA(is_checked); 
                                        setClientSpousePlaceAssuance(is_checked);
                                        setClientSpouseDateAssuance(is_checked);

                                        if( is_checked ) {
                                            editClientForm.setFieldsValue({
                                                spouse_id_issuance_place: null,
                                                spouse_id_issuance_date: null
                                            });
                                        } else {
                                            editClientForm.setFieldsValue({
                                                spouse_id_issuance_place: clientSpousePlaceAssuanceOrig,
                                                spouse_id_issuance_date: clientSpouseDateAssuanceOrig
                                            });
                                        }
                                    }}>Not Applicable</Checkbox>
                                </Form.Item> 
                            </Row>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Tax identification number" name="spouse_tax_identification_number" rules={[{
                                required: withSpouse
                            }]}>
                                <Input placeholder="" />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Occupation" name="spouse_occupation" rules={[{
                                required: withSpouse
                            }]}>
                                <Input placeholder="" />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Company name" name="spouse_company_name" rules={[{
                                required: withSpouse
                            }]}>
                                <Input placeholder="" />
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item label="Company address" name="spouse_company_address" rules={[{
                                required: withSpouse
                            }]}>
                                <Input placeholder="" />
                            </Form.Item>
                        </Col>
                    </Row>
                    </Card>
                </Col>

                </>
                }
                <Col xl={24}>
                    <Typography.Title level={5}>Contact information</Typography.Title>
                </Col>

                <Col xl={8}>
                    <Form.Item label="Home phone" name="home_phone" rules={[{
                                required: true
                            }]}>
                        <Input placeholder="" />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Business phone" name="business_phone" rules={[{
                                required: true
                            }]}>
                        <Input placeholder="" />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Email address" name="email">
                        <Input placeholder="" />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item label="Mobile number" name="mobile_number" rules={[{
                                required: true
                            }]}>
                        <Input placeholder="" />
                    </Form.Item>
                </Col>

                <Col xl={24}>
                    <Typography.Title level={4}>Complete address</Typography.Title>
                </Col>

                {/* Address */}

                <Col xl={24}>
                    <Typography.Title level={5}>Permanent home address</Typography.Title>
                </Col>

                <Col xl={8}>
                    <Form.Item label="House no." name="permanent_home_address_house_number" rules={[{
                                required: true
                            }]}>
                        <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_house_number")} placeholder="" />
                    </Form.Item>
                </Col>

                <Col xl={8}>
                    <Form.Item label="Street" name="permanent_home_address_street" rules={[{
                                required: true
                            }]}>
                        <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_street")} placeholder="" />
                    </Form.Item>
                </Col>

                <Col xl={8}>
                    <Form.Item label="Baranggay" name="permanent_home_address_baranggay" rules={[{
                                required: true
                            }]}>
                        <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_baranggay")} placeholder="" />
                    </Form.Item>
                </Col>

                <Col xl={8}>
                    <Form.Item label="City" name="permanent_home_address_city" rules={[{
                                required: true
                            }]}>
                        <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_city")} placeholder="" />
                    </Form.Item>
                </Col>

                <Col xl={8}>
                    <Form.Item label="Province" name="permanent_home_address_province" rules={[{
                                required: true
                            }]}>
                        <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_province")} placeholder="" />
                    </Form.Item>
                </Col>

                <Col xl={8}>
                    <Form.Item label="Zip code" name="permanent_home_address_zip_code" rules={[{
                                required: true
                            }]}>
                        <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_zip_code")} placeholder="" />
                    </Form.Item>
                </Col>

                <Col xl={24}>
                    <Typography.Title level={5}>Current home address</Typography.Title>
                    <Checkbox onClick={()=>setSameAsPermanentAddress(!sameAsPermanentAddress)} className="mr-2">Same as permanent address</Checkbox>
                    <Form.Item valuePropName="checked" defaultChecked={internationalAddress} name="current_home_address_international">
                        <Checkbox disabled={sameAsPermanentAddress} onClick={()=>setInternationalAddress(!internationalAddress)} className="mr-2">International address</Checkbox>
                    </Form.Item>
                </Col>

                { !internationalAddress &&
                <Col xl={8}>
                    <Form.Item label="House no." name="current_home_address_house_number" rules={[{
                                required: true
                            }]}>
                        <Input prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress} placeholder="" />
                    </Form.Item>
                </Col>
                }

                <Col xl={8}>
                    <Form.Item label="Street" name="current_home_address_street" rules={[{
                                required: true
                            }]}>
                        <Input prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress} placeholder="" />
                    </Form.Item>
                </Col>

                { !internationalAddress &&
                    <Col xl={8}>
                        <Form.Item label="Baranggay" name="current_home_address_baranggay" rules={[{
                                    required: true
                                }]}>
                            <Input prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress} placeholder="" />
                        </Form.Item>
                    </Col>
                }

                <Col xl={8}>
                    <Form.Item label="City" name="current_home_address_city" rules={[{
                                required: true
                            }]}>
                        <Input prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress} placeholder="" />
                    </Form.Item>
                </Col>

                { !internationalAddress &&
                    <Col xl={8}>
                        <Form.Item label="Province" name="current_home_address_province" rules={[{
                                    required: true
                                }]}>
                            <Input prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress} placeholder="" />
                        </Form.Item>
                    </Col>
                }

                <Col xl={8}>
                    <Form.Item label={`${internationalAddress ? 'State' : ''} Zip code`} name="current_home_address_zip_code" rules={[{
                                required: true
                            }]}>
                        <Input prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress} placeholder="" />
                    </Form.Item>
                </Col>

                { internationalAddress &&
                    <Col xl={8}>
                        <Form.Item label={`Country`} name="current_home_address_country" rules={[{
                                    required: true
                        }]}>
                            <Select prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress}>
                                {
                                    countries.list.map( (i, key) => {
                                        return <Select.Option key={key} value={i}>{i}</Select.Option>
                                    })
                                }
                            </Select>
                        </Form.Item>
                    </Col>
                }

                <Col xl={8}>
                    <Form.Item label="Preferred mailing address" name="preferred_mailing_address" rules={[{
                                required: true
                            }]}>
                        <Select placeholder="">
                            <Select.Option value="permanent_home_address">Permanent home address</Select.Option>
                            <Select.Option value="current_home_address">Current home address</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                {/* <Col xl={24}>
                    <Typography.Title level={5}>Signature</Typography.Title>
                </Col> */}

                <Col xl={24}>
                    <Typography.Title level={5}>Attachments</Typography.Title>
                    <Row>
                        <Col xl={12}>
                            <Upload {...uploadProps}
                                name="files" 
                                itemRender={(originNode, file, currFileList) => {
                                    console.log(file);
                                    return <Card style={{marginTop: 8}}>{originNode} Attachment type: <strong>{file.attachment_type}</strong></Card>
                                }}
                            >
                                <Button icon={<UploadOutlined />}>Select File</Button>
                            </Upload>
                        </Col>
                        <Col xl={12}>
                            <Popconfirm
                                title="Are you sure you want to approve all attachments?"
                                onConfirm={() => approveAllAttachments(client_number)}
                                onCancel={() => console.log("approve attachment cancelled")}
                                okText="Yes"
                                cancelText="No"
                            >
                                <Button size="small" style={{borderRadius: '5px', float: 'right'}}>Approve All Attachments</Button>
                            </Popconfirm>
                        </Col>
                    </Row>
                    {
                        (client.information && client.information.attachments) && client.information?.attachments.map( (item, key) => {
                            return <Card style={{marginTop: 8}} key={key}>
                                    <div>
                                        <a href={item.file_path} target="_blank">{item.file_name}</a>
                                    </div>
                                    <div style={{float:'left'}}>
                                        
                                        <Space style={{marginTop: '20px'}}>
                                            <span>Attachment type:</span>
                                            <Select style={{width: '180px'}} onChange={(e) => {updateAttachmentType({
                                                id: item.id, 
                                                attachment_type: e,
                                                client_id: item.client_information_id,
                                            })}} value={item.type}>
                                                    <Select.Option value="ID">ID</Select.Option>
                                                    <Select.Option value="Birth Certificate">Birth Certificate</Select.Option>
                                                    <Select.Option value="CRF">CRF</Select.Option>
                                                    <Select.Option value="RIS1">RIS1 (RA)</Select.Option>
                                                    <Select.Option value="RIS2">RIS2 (BIS)</Select.Option>
                                                    <Select.Option value="1904">1904</Select.Option>
                                                    <Select.Option value="POI">POI</Select.Option>
                                                    <Select.Option value="MC">MC</Select.Option>
                                                    <Select.Option value="SPA">SPA</Select.Option>
                                            </Select>
                                        </Space>
                                    </div>
                                    <div  style={{float:'right', width: 300, textAlign:'right'}}>
                                        <Space>
                                            <Select className={ item.status == 'approved' ? 'text-success' : ''} onChange={(e) => handleChangeAttachmentStatus(item.id, e, item.client_information_id)} value={item.status}>
                                                <Select.Option value="for_review">For review</Select.Option>
                                                <Select.Option value="approved">Approved</Select.Option>
                                            </Select>
                                            <Popconfirm
                                                title="Are you sure to delete this attachment?"
                                                onConfirm={() => handleConfirmDeleteAttachment(item.id)}
                                                onCancel={() => message.info('Attachment not deleted.')}
                                                okText="Yes"
                                                cancelText="No"
                                                className="text-danger"
                                            >
                                                <Button danger size="small"><DeleteOutlined/></Button>
                                            </Popconfirm>
                                        </Space>
                                    </div>
                                    
                                </Card>
                        })
                    }
                </Col>
            </Row>
        </Col>
       
    </Row>


    <Space>
    <Button htmlType="submit" onClick={onCheck}>Save Client Record</Button>
    <Button onClick={() => handleDownloadCRF()}>Export Client Record as PDF</Button>
    </Space>
</Form>
</>
}