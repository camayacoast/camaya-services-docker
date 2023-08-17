import React, {useState, useEffect} from 'react';
import { Upload, Button, message, Modal, Table, Alert } from 'antd';
import {ImportOutlined, ExportOutlined} from '@ant-design/icons';
import SalesAdminPortalService from 'services/SalesAdminPortal';

const ImportInventory = (props) => {

    const [downloadTemplateQuery, { isLoading: downloadTemplateQueryIsLoading, reset: downloadTemplateQueryReset}] = SalesAdminPortalService.downloadInventoryTemplate();

    const [fileList, setFilelist] = useState();
    const [reportModal, setReportModal] = useState(false);
    const [reportData, setReportData] = useState([]);

    const handleDownloadTemplate = () => {

        if (downloadTemplateQueryIsLoading) {
            return false;
        }

        downloadTemplateQuery(
            {
                property_type: props.property_type,
            }, {
                onSuccess: (res) => {

                    props.addActivityLog({
                        type: props.property_type,
                        action: 'download',
                        description: 'Download template',
                        details: []
                    });

                    var file = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;' });
                    //Build a URL from the file
                    const fileURL = URL.createObjectURL(file);
                    //Download fileURL
                    var a = document.createElement("a");
                    a.href = fileURL;
                    a.download = `${props.property_type}_inventory_template`;
                    a.click();
                    window.URL.revokeObjectURL(fileURL);
                },
                onError: (e) => {
                    message.warning("Failed.");
                }
            }
        )
    }

    const uploadProps = {
        onRemove: file => {
            setFilelist( prev => {
                const index = prev.indexOf(file);
                const newFileList = prev.slice();
                newFileList.splice(index, 1);
                return newFileList;
            });
        },
        method: 'post',
        name: 'import_inventory',
        action: `${process.env.APP_URL}/api/sales-admin-portal/import-inventory-data/${props.property_type}`,
        headers:{
            'property_type': props.property_type,
            Authorization: `Bearer ${localStorage.getItem('token')}`,
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        accept: 'application/xlxs',
        beforeUpload: () => {
            message.info('Uploading data please wait....');
        },
        onSuccess: (res) => {
            if( typeof res === 'string' ) {

                props.addActivityLog({
                    type: props.property_type,
                    action: 'import',
                    description: 'Import inventory data',
                    details: {}
                })

                message.success(res);
            } else if(typeof res === 'object') {
                if( typeof res[0] !== 'undefined' ) {
                    message.error( 'Error in Row #' + res[0].row + ': ' + res[0].message);
                }
            } else {
                let property_type = props.property_type.charAt(0).toUpperCase() + props.property_type.slice(1);
                message.success( property_type + ' inventory records are imported but with warning. Will generate list of warning, please wait...');
                setReportModal(true);
                setReportData(res.existing_records);
                // TODO: generate lists of existing records and nulled subdivision, block and lot

            }
            props.refetchData();
        },
        onChange: (info) => {

            if (info.file.status === 'done') {
                message.success(`${info.file.name} file imported successfully`);
            } else if (info.file.status === 'error') {
                if( typeof info.file.response.message !== 'undefined' ) {
                    message.error(`${info.file.response.message}`);
                } else {
                    message.error(`${info.file.response}`);
                }
            }
            return false;
        },
        itemRender(originNode, file, currFileList){},
        fileList,
    };

    return (
        <>
            <div style={{display: 'inline-block', marginRight: '10px'}}>
                <Upload {...uploadProps}>
                    <Button icon={<ImportOutlined/>}>Import Data</Button>
                </Upload>
            </div>

            <div style={{display: 'inline-block'}}>
                <Button icon={<ExportOutlined/>} onClick={() => handleDownloadTemplate()}>Download Template</Button>
            </div>
            
            {/* Lists of record that not uploaded in the database, this only appear if there are inserted data ELSE "All entries are already exists" is prompted */}
            <Modal
                visible={reportModal}
                onCancel={()=>setReportModal(false)}
                onOk={()=>setReportModal(false)}
                title={"List of records that already exists in the system."}
                width={1500}
            >
                <Alert
                    message={`The list below are not saved to the system due to existing combination of property attributes fields. 'phase', ${(props.property_type == 'lot') ? '\'subdivision\'' : '\'project acronym\''} , 'block', 'lot' and 'type'`}
                    type="warning"
                />
                <Table
                    rowKey="counter"
                    dataSource={reportData}
                    columns={[
                        {
                            title: 'Phase',
                            key: 'phase',
                            dataIndex: 'phase',
                            render: (text) => text
                        },
                        {
                            title: (props.property_type == 'lot' ) ? 'Subdivision Name' : 'Project',
                            key: 'subdivision_name',
                            dataIndex: 'subdivision_name',
                            render: (text) => text
                        },
                        {
                            title: (props.property_type == 'lot' ) ? 'Subdivision' : 'Project Acronym',
                            key: 'subdivision',
                            dataIndex: 'subdivision',
                            render: (text) => text
                        },
                        {
                            title: 'Block',
                            key: 'block',
                            dataIndex: 'block',
                            render: (text) => text
                        },
                        {
                            title: 'Lot',
                            key: 'lot',
                            dataIndex: 'lot',
                            render: (text) => text
                        },
                        {
                            title: 'Client #',
                            key: 'client_number',
                            dataIndex: 'client_number',
                            render: (text) => text
                        },
                        {
                            title: 'Status 2',
                            key: 'status2',
                            dataIndex: 'status2',
                            render: (text) => text
                        },
                        {
                            title: 'Status',
                            key: 'status',
                            dataIndex: 'status',
                            render: (text) => text
                        },
                        {
                            title: 'Color',
                            key: 'color',
                            dataIndex: 'color',
                            render: (text) => text
                        },
                        {
                            title: 'Remarks',
                            key: 'remarks',
                            dataIndex: 'remarks',
                            render: (text) => text
                        },
                        {
                            title: 'Area',
                            key: 'area',
                            dataIndex: 'area',
                            render: (text) => text
                        },
                        {
                            title: 'Type',
                            key: 'type',
                            dataIndex: 'type',
                            render: (text) => text
                        },
                        {
                            title: (props.property_type == 'lot' ) ? 'Price Per SQM' : 'TSP',
                            key: 'price_per_sqm',
                            dataIndex: 'price_per_sqm',
                            render: (text, record) => {
                                let ppsqm = record.price_per_sqm;
                                if( props.property_type == 'condo'  )  {
                                    ppsqm = Math.round(parseFloat(record.area) * parseFloat(record.price_per_sqm)).toFixed(2);
                                }
                                return ppsqm;
                            }
                        },
                    ]}
                ></Table>
            </Modal>
        </>
    )
}

export default ImportInventory;