import React from 'react'
import img from 'assets/placeholder-1-1.jpg'
import { queryCache } from 'react-query'
import ProductService from 'services/Booking/Product'

import { Typography, Row, Col, Card, Button, Drawer, Form, Grid, Input, Select, InputNumber, notification, Upload, Modal } from 'antd'
import { PlusOutlined, MinusOutlined, UploadOutlined } from '@ant-design/icons'

import HotelPropertyService from 'services/Hotel/Property'

const { useBreakpoint } = Grid;

const normFile = (e, formInstance, propertyListQuery, deleteImage) => {

    console.log('Upload event:', e);

    if (e.file.status === 'removed' && formInstance.getFieldValue('id')) { // file is saved in database
        deleteImage(e.file.uid, {
            onSuccess: (res) => {
                console.log(res);
                // console.log(e.file.propertyListQuery);

                propertyListQuery.data.forEach(function (plqdProperty, index) {
                    if (plqdProperty.id === e.file.propertyId) {
                        plqdProperty.room_types.forEach(function(roomType, roomTypeIndex){
                            if (roomType.id === e.file.roomTypeId) {
                                const plqdPropertyImages = roomType.images;
                                const newPropertyImages = _.reject(plqdPropertyImages, { uid: e.file.uid });

                                propertyListQuery.data[index].room_types[roomTypeIndex].images = newPropertyImages;
                            }
                        });                                               
                    }
                });

                console.log('image deleted', e);
            },
            onError: (err) => {
                console.log(err);
            }
        });                   
    } else if (formInstance.getFieldValue('id') && e.file.status === 'done') {        
        propertyListQuery.data.forEach(function (plqdProperty, index) {
            if (plqdProperty.id === formInstance.getFieldValue('id')) {
                plqdProperty.room_types.forEach(function(roomType, roomTypeIndex){
                    if (roomType.id === e.file.response.room_type_id) {
                        const plqdPropertyImages = roomType.images;
                        const newPropertyImages = _.reject(plqdPropertyImages, { uid: e.file.response.id });
                        
                        const newImage = {
                            uid: e.file.response.id,
                            name: '',
                            status: 'done',
                            url: e.file.response.image_path,
                            propertyId: formInstance.getFieldValue('id'),
                            roomTypeId: e.file.response.room_type_id,  
                        }

                        propertyListQuery.data[index].room_types[roomTypeIndex].images = newPropertyImages;
                        propertyListQuery.data[index].room_types[roomTypeIndex].images.push(newImage);

                        e.fileList = _.dropRight(e.fileList);
                        e.fileList.push(newImage);
                    }
                });                                               
            }
        });
    }
    
    if (Array.isArray(e)) {
      return e;
    }
    return e && e.fileList;
};

const PropertyForm = ({type, formInstance, propertyListQuery, deleteImage=null}) => (
    // 'name',
    // 'code',
    // 'type',
    // 'address',
    // 'phone_number',
    // 'floors',
    // 'cover_image_path',
    // 'description',
    // 'status',
    <>
    <Form.Item name="id" noStyle>
        <Input type="hidden" />
    </Form.Item>
    <Row gutter={[12,12]}>        
        <Col xl={12}>
            <Form.Item
                name="name"
                label="Name"
                // extra={<ShowFormItemError name="name" />}
                rules={[
                    {
                        required: true
                    }
                ]}
            >
                <Input/>
            </Form.Item>
        </Col>
        <Col xl={12}>
            <Form.Item name="code" label="Code"
                // extra={<ShowFormItemError name="code" />}
                rules={[
                {
                    pattern: /^\S*$/,
                    message: 'No spaces allowed. Use underscore (_) instead.'
                },
                {
                    required: true
                }
            ]}>
                <Input style={{textTransform: 'uppercase'}}/>
            </Form.Item>
        </Col>
        <Col xl={12}>
            <Form.Item name="type" label="Type"
            rules={[
                {
                    required: true
                }
            ]}>
                <Select>
                    <Select.Option value="hotel">Hotel</Select.Option>
                </Select>
            </Form.Item>
        </Col>
        <Col xl={12}>
            <Form.Item name="status" label="Status">
                <Select>
                    <Select.Option value="open">Open</Select.Option>
                    <Select.Option value="closed">Closed</Select.Option>
                </Select>
            </Form.Item>
        </Col>
        <Col xl={12}>
            <Form.Item
                name="address"
                label="Address"
            >
                <Input/>
            </Form.Item>
        </Col>
        <Col xl={12}>
            <Form.Item
                name="phone_number"
                label="Phone number"
            >
                <Input/>
            </Form.Item>
        </Col>
        <Col xl={12}>
            <Form.Item
                name="floors"
                label="Floors"
            >
                <InputNumber/>
            </Form.Item>
        </Col>
        {/* <Col xl={12}>
            <Form.Item
                name="cover_image_path"
                label="Cover image"
            >
                <Input/>
            </Form.Item>
        </Col> */}
        <Col xl={24}>
            <Form.Item
                name="description"
                label="Description"
            >
                <Input.TextArea style={{borderRadius: '12px'}}/>
            </Form.Item>
        </Col>
        <Col xl={24}>
            <Typography.Title level={5} className="mb-4">Room Types</Typography.Title>

            <Form.List name="room_types">
                {
                    (fields, { add, remove }) => {
                        // addPax['adultPax'] = add; removePax['adultPax'] = remove;
                        return (
                            <div>
                                {fields.map(field => (
                                    <Card size="small" className="mb-2" key={field.name}>
                                        <Form.Item
                                            {...field} 
                                            name={[field.name, 'id']} 
                                            fieldKey={[field.fieldKey, 'id']}  
                                            noStyle>
                                            <Input type="hidden" />
                                        </Form.Item>
                                        <Row gutter={[8,8]}>
                                            <Col xl={9}>
                                                <Form.Item
                                                    {...field}
                                                    name={[field.name, 'name']}
                                                    fieldKey={[field.fieldKey, 'name']}
                                                    label={`New Room type (${field.name+1})`}
                                                    rules={[
                                                        {
                                                            required: true
                                                        }
                                                    ]}
                                                >
                                                    <Input/>
                                                </Form.Item>
                                            </Col>
                                            <Col xl={5}>
                                                <Form.Item
                                                    {...field}
                                                    name={[field.name, 'code']}
                                                    fieldKey={[field.fieldKey, 'code']}
                                                    label="Code"
                                                    rules={[
                                                        {
                                                            required: true
                                                        }
                                                    ]}
                                                >
                                                    <Input/>
                                                </Form.Item>
                                            </Col>
                                            <Col xl={5}>
                                                <Form.Item
                                                    {...field}
                                                    name={[field.name, 'capacity']}
                                                    fieldKey={[field.fieldKey, 'capacity']}
                                                    label="Capacity"
                                                    rules={[
                                                        {
                                                            required: true
                                                        }
                                                    ]}
                                                >
                                                    <InputNumber min={1}/>
                                                </Form.Item>
                                            </Col>
                                            <Col xl={5}>
                                                <Form.Item
                                                    {...field}
                                                    name={[field.name, 'max_capacity']}
                                                    fieldKey={[field.fieldKey, 'max_capacity']}
                                                    label="Max"
                                                >
                                                    <InputNumber/>
                                                </Form.Item>
                                            </Col>
                                            <Col xl={12}>
                                                <Form.Item
                                                    {...field}
                                                    name={[field.name, 'rack_rate']}
                                                    fieldKey={[field.fieldKey, 'rack_rate']}
                                                    label="Rack rate"
                                                    rules={[
                                                        {
                                                            required: true
                                                        }
                                                    ]}
                                                >
                                                    <InputNumber/>
                                                </Form.Item>
                                            </Col>
                                            {/* <Col xl={12}> */}
                                                {/* <Form.Item
                                                    {...field}
                                                    name={[field.name, 'cover_image_path']}
                                                    fieldKey={[field.fieldKey, 'cover_image_path']}
                                                    label="Cover image"
                                                >
                                                    <Input/>
                                                </Form.Item> */}
                                                {/* </Col> */}
                                                <Col xl={24}>
                                                    {/* <Typography.Text className="mb-2">Room type images</Typography.Text> */}

                                                {/* { type == 'new' && */}
                                                        <Form.Item
                                                            {...field}
                                                            name={[field.name, 'images']}
                                                            fieldKey={[field.fieldKey, 'images']}
                                                            label="Room type images"
                                                            valuePropName="fileList"
                                                            getValueFromEvent={e => normFile(e, formInstance, propertyListQuery, deleteImage)}                                                            
                                                        >
                                                            <Upload
                                                                action={
                                                                    type === 'new' 
                                                                    ? `${process.env.APP_URL}/api/booking/product/image-upload`
                                                                    : `${process.env.APP_URL}/api/hotel/property/add-image`
                                                                }
                                                                listType="picture"
                                                                headers={   
                                                                    {
                                                                        Authorization: 'Bearer '+localStorage.getItem('token'),
                                                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                                                    }
                                                                }
                                                                data={
                                                                    type === 'new'
                                                                    ? null
                                                                    : { roomTypeId: formInstance.getFieldValue('room_types')[field.name]&&formInstance.getFieldValue('room_types')[field.name]['id'] }
                                                                }
                                                                
                                                                // defaultFileList={[]}
                                                            >
                                                                <Button icon={<UploadOutlined />}>Upload</Button>
                                                            </Upload>
                                                        </Form.Item>
                                                {/* } */}
                                                    
                                                        {/* <div className="mt-2">
                                                        <Upload
                                                            action={`${process.env.APP_URL}/api/booking/product/image-upload`}
                                                            headers={   
                                                                {
                                                                    Authorization: 'Bearer '+localStorage.getItem('token')
                                                                }
                                                            }
                                                            name={`file_${field.name}`}
                                                            listType="picture-card"
                                                            fileList={uploadData.fileList}
                                                            onPreview={handlePreview}
                                                            // onChange={e => console.log(e)}
                                                            onChange={(e) => handleChange(e, field.name)}
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
                                                                <img alt="room type image" style={{ width: '100%' }} src={uploadData.previewImage} />
                                                            </Modal>
                                                            </div> */}
                                                </Col>
                                            <Col xl={24}>
                                                <Form.Item
                                                    {...field}
                                                    name={[field.name, 'description']}
                                                    fieldKey={[field.fieldKey, 'description']}
                                                    label="Description"
                                                >
                                                    <Input.TextArea style={{borderRadius: '12px'}}/>
                                                </Form.Item>
                                                <Button type="dashed" size="small" block className="mt-2" onClick={()=>remove(field.name)} danger><MinusOutlined/> Remove</Button>
                                            </Col>
                                        </Row>
                                    </Card>
                                ))}
                                <Button type="dashed" block className="mt-2" onClick={()=>add()}><PlusOutlined/> Add Room Type</Button>
                            </div>
                        )
                    }
                }
            </Form.List>                
        </Col>
    </Row>
    </>
)

function Page(props) {

    const screen = useBreakpoint();

    // let propertyListQuery = {
    //     data: [
    //         {
    //             name: 'Camaya Sands Hotel',
    //             code: 'CSH',
    //         },
    //         {
    //             name: 'Aqua Fun Hotel',
    //             code: 'AFH',
    //         },
    //     ]
    // };
    const initialUploadData = {
        previewVisible: false,
        previewImage: '',
        previewTitle: '',
        fileList: [],
    };

    const [newPropertyQuery, {isLoading: newPropertyQueryIsLoading, error: newPropertyQueryError}] = HotelPropertyService.create();
    const [updatePropertyQuery, {isLoading: updatePropertyQueryIsLoading, error: updatePropertyQueryError}] = HotelPropertyService.update();
    const propertyListQuery = HotelPropertyService.list();

    const [newPropertyDrawerVisible, setnewPropertyDrawerVisible] = React.useState(false);
    const [viewPropertyDrawerVisible, setviewPropertyDrawerVisible] = React.useState(false);

    const [removeImageQuery, {isLoading: removeImageQueryIsLoading, error: removeImageQueryError}] = ProductService.imageUploadRemove();    
    const [deleteImage, {isLoading: deleteImageIsLoading, error: deleteImageError}] = HotelPropertyService.deleteImage();    
    const [uploadedImages, setuploadedImages] = React.useState([]);
    const [uploadData, setUploadData] = React.useState(initialUploadData);

    const [newPropertyForm] = Form.useForm();
    const [viewPropertyForm] = Form.useForm();

    // Functions Image
    const handleCancel = () => { 
        setUploadData( prev => ({...prev, previewVisible: false}) );
    };

    const handlePreview = async file => {
        if (!file.url && !file.preview) {
          file.preview = await getBase64(file.originFileObj);
        }

        if (!file.preview) {
            console.log(file);

            const image = new Image();
            image.src = file.url;
            const imgWindow = window.open(file.url);
            imgWindow.document.write(image.outerHTML);

            return;
        }
    
        setUploadData( prev => ({
                // fileList: [...prev.fileList],
                previewImage: file.url || file.preview,
                previewVisible: true,
                previewTitle: file.name || file.url.substring(file.url.lastIndexOf('/') + 1),
            })
        );
    };

    const handleChange = ({ fileList, file, event }, index) => {
        // console.log(fileList, index);
        // console.log(fileList);
        setUploadData( prev => ({...prev, fileList: fileList }));

        if (file.status == 'done') {
            const viewIdSet = viewPropertyForm.getFieldValue('id');

            if (viewIdSet) {
                const response = file.response;

                propertyListQuery.data.forEach(function (plqdProperty, index) {
                    if (plqdProperty.id === viewIdSet) {
                        const plqdPropertyImage = propertyListQuery.data[index].images || [];
                        plqdPropertyImage.push(response);
                        propertyListQuery.data[index].images = plqdPropertyImage;
                    }
                });
            } else {
                setUploadedImages( prev => [...prev, file.response.path]);
            }
        }
    };

    const handleRemove = async file => {

        if (file.url) { // file is saved in database
            deleteImage(file.uid, {
                onSuccess: (res) => {
                    console.log(res);
                    const viewIdSet = viewPropertyForm.getFieldValue('id');

                    propertyListQuery.data.forEach(function (plqdProperty, index) {
                        if (plqdProperty.id === viewIdSet) {
                            const plqdPropertyImages = plqdProperty.images;
                            const newPropertyImages = _.reject(plqdPropertyImages, { id: file.uid });

                            propertyListQuery.data[index].images = newPropertyImages;
                        }
                    });
                },
                onError: (e) => {
                    console.log(e);
                }
            })            

            return;
        }

        setuploadedImages( prev => _.filter(prev, (item) =>  item.path != file.response.path));

        removeImageQuery({ files_to_remove: ["public/"+file.response.file_name] },{
            onSuccess: (res) => {
                console.log(res);
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const uploadButton = (
        <div>
          <PlusOutlined />
          <div style={{ marginTop: 8 }}>Upload</div>
        </div>
      );

    // Image upload

    const onNewPropertyFormFinish = (values) => {
        console.log(values);

        const newValues = {
            ...values,
        }

        newPropertyQuery(newValues, {
            onSuccess: (res) => {
                console.log(res.data);

                queryCache.setQueryData(['properties', { id: res.data.id }], res.data);

                propertyListQuery.data.push({...res.data});

                newPropertyForm.resetFields();

                setnewPropertyDrawerVisible(false);

                notification.success({
                    message: `New Property - ${res.data.code} Added!`,
                    description:
                        ``,
                });
            },
        });
    }

    const onViewPropertyFormFinish = (values) => {
        console.log(values);

        const newValues = {
            ...values,
        }

        updatePropertyQuery(newValues, {
            onSuccess: (res) => {
                console.log(res.data);

                queryCache.setQueryData(['properties', { id: res.data.id }], res.data);

                propertyListQuery.data.forEach(function (plqdProperty, index) {
                    if (plqdProperty.id === res.data.id) {
                        propertyListQuery.data[index] = res.data;
                    }
                });

                viewPropertyForm.resetFields();

                setviewPropertyDrawerVisible(false);

                notification.success({
                    message: `Property - ${res.data.code} Updated!`,
                    description:
                        ``,
                });
            },
        });
    }

    const handleViewProperty = (item) => {

        console.log(item);
        // return false;

        if (item.room_types.length) {
            let savedData = [];
            const savedImages = [];

            item.room_types.forEach(roomType => {
                if (roomType.images.length) {
                    const roomTypeSavedData = [];

                    roomType.images.forEach(image => {
                        if (_.hasIn(image, 'uid')) {
                            roomTypeSavedData.push(image);
                            savedImages.push(image.url);
                        } else {
                            roomTypeSavedData.push({
                                uid: image.id,
                                name: '',
                                status: 'done',
                                url: image.image_path,
                                propertyId: item.id,
                                roomTypeId: roomType.id,                                
                            });
                            savedImages.push(image.image_path);
                        }

                        
                    });

                    roomType.images = roomTypeSavedData;

                    savedData = [...savedData, ...roomTypeSavedData];
                }

                return roomType;
            });    
            
            const savedFileList = { ...uploadData, fileList: savedData };

            setUploadData(savedFileList)
            setuploadedImages(savedImages);
        }        

        console.log(item, propertyListQuery, uploadData, uploadedImages);    

        viewPropertyForm.resetFields();
        viewPropertyForm.setFieldsValue(item);
        setviewPropertyDrawerVisible(true);
    }

    const onViewPropertyDrawerClose = () => {
        console.log('close', propertyListQuery);
        viewPropertyForm.resetFields();
        setviewPropertyDrawerVisible(false);
    }

    const ShowFormItemError = ({name}) => {
        if (newPropertyQueryError && newPropertyQueryError.errors && newPropertyQueryError.errors[name]) {
            return newPropertyQueryError.errors[name].map( (item, key) => (
                    <div role="alert" style={{color: '#ff4d4f'}} key={key}>{item}</div>
                ))
        }

        return <></>
    }    

    return (
        <>
        <Drawer
                title="New Property"
                width={ screen.xs == false ? 520 : '95%'}
                closable={false}
                visible={newPropertyDrawerVisible}
                onClose={()=>setnewPropertyDrawerVisible(false)}
                footer={<Button type="primary" style={{float:'right'}} onClick={()=>newPropertyForm.submit()}>Save</Button>}
            >
                <Form
                    form={newPropertyForm}
                    onFinish={onNewPropertyFormFinish}
                    layout="vertical"
                    scrollToFirstError={true}
                    initialValues={{
                        status: 'open'
                    }}
                >
                    <PropertyForm 
                        type="new" 
                        formInstance={newPropertyForm}
                        propertyListQuery={propertyListQuery} />
                </Form>

        </Drawer>

        <Drawer
            title="View Property"
            width={ screen.xs == false ? 520 : '95%'}
            closable={false}
            visible={viewPropertyDrawerVisible}
            onClose={()=>onViewPropertyDrawerClose()}
            footer={<Button type="primary" style={{float:'right'}} onClick={()=>viewPropertyForm.submit()}>Save</Button>}
        >
            <Form
                form={viewPropertyForm}
                onFinish={onViewPropertyFormFinish}
                layout="vertical"
                scrollToFirstError={true}
                // initialValues={{
                //     status: 'open'
                // }}
            >
                <PropertyForm 
                    type="view" 
                    formInstance={viewPropertyForm} 
                    propertyListQuery={propertyListQuery}
                    deleteImage={deleteImage} />
            </Form>
        </Drawer>

        <Typography.Title level={4}>Properties</Typography.Title>

        <Row gutter={[12,12]}>
            {
                (propertyListQuery && propertyListQuery.data) && propertyListQuery.data.map( (item, key) => (
                    <Col xl={6} xs={12} key={key}>
                        <Card
                            bordered={false}
                            hoverable={true}
                            className="card-shadow"
                            size="small"
                            // cover={<img src={img} style={{height: '100%'}} />}
                            onClick={()=>handleViewProperty(item)}
                        >
                            <Card.Meta
                                title={<><Typography.Title level={5} className="mb-1">{item.name}</Typography.Title><small><Typography.Text type="secondary">{item.code}</Typography.Text></small></>}
                                // description={item.price}
                            />
                        </Card>
                    </Col>
                ))
            }
            <Col xl={6} xs={12}>
                <Card
                    bordered={true}
                    // hoverable={true}
                    size="small"
                    onClick={()=>setnewPropertyDrawerVisible(true)}
                    className="card-add-button"
                >
                    <Button type="link"><PlusOutlined/> Add Property</Button>
                </Card>
            </Col>
        </Row>
        </>
    )
}

export default Page;