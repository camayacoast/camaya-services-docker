import React from 'react'

import { Upload, Modal } from 'antd';
import { PlusOutlined } from '@ant-design/icons';

import BookingProductService from 'services/Booking/Product'

const getBase64 = (file) => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.readAsDataURL(file);
      reader.onload = () => resolve(reader.result);
      reader.onerror = error => reject(error);
    });
}

function ImageUpload(props) {

    const [removeImageQuery, {isLoading: removeImageQueryIsLoading, error: removeImageQueryError}] = BookingProductService.imageUploadRemove();

    const [uploadedImages, setuploadedImages] = React.useState([]);

    const [uploadData, setUploadData] = React.useState({
            previewVisible: false,
            previewImage: '',
            previewTitle: '',
            fileList: [],
    });

    // Functions
    const handleCancel = () => { 
        setUploadData( prev => ({...prev, previewVisible: false}) );
    };

    const handlePreview = async file => {
        if (!file.url && !file.preview) {
          file.preview = await getBase64(file.originFileObj);
        }
    
        setUploadData( prev => ({
                fileList: [...prev.fileList],
                previewImage: file.url || file.preview,
                previewVisible: true,
                previewTitle: file.name || file.url.substring(file.url.lastIndexOf('/') + 1),
            })
        );
    };

    const handleChange = ({ fileList, file, event }) => {
        console.log(event, fileList);

        setUploadData( prev => ({...prev, fileList: fileList }));
        if (file.status == 'done') {
            setuploadedImages( prev => [...prev, file.response.path]);
        }
    };

    const handleRemove = async file => {
        console.log(file);
        removeImageQuery({ file_name: file.response.file_name },{
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

    return (
        <>
        <Upload
          action={`${process.env.APP_URL}/api/booking/product/image-upload`}
          headers={
              {
                  Authorization: 'Bearer '+localStorage.getItem('token'),
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
              }
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
          <img alt="example" style={{ width: '100%' }} src={uploadData.previewImage} />
        </Modal>
      </>
    )

}

export default ImageUpload;