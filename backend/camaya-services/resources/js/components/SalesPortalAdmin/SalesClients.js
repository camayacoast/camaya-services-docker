import React from 'react'
import moment from 'moment-timezone'
import {Link} from 'react-router-dom'
moment.tz.setDefault('Asia/Manila');

import SalesAdminPortalService from 'services/SalesAdminPortal'

import NewClientComponent from 'components/SalesPortalAdmin/NewClient'

import { Table, Button, Modal, Input, Select } from 'antd'
import { EllipsisOutlined, UserAddOutlined, EditOutlined, ImportOutlined, PrinterOutlined } from '@ant-design/icons';

export default function Page(props) {

    const [newClientModalVisible, setNewClientModalVisible] = React.useState(false);
    const [newClientWithBIS, setNewClientWithBIS] = React.useState(false);

    const [searchString, setSearchString] = React.useState('');


    const salesClientsListQuery = SalesAdminPortalService.salesClientsList();
    const [exportBISReportQuery, { isLoading: exportBISReportQueryIsLoading, reset: exportBISReportQueryReset}] = SalesAdminPortalService.exportBISReport();

    const columns = [
        // {
        //     title: 'ID',
        //     key: 'id',
        //     dataIndex: 'id'
        // },
        {
            title: 'Account #',
            render: (text, record) => <>C{String(record.id).padStart(9, '0') ?? '-'}</>
        },
        {
            title: 'Status',
            render: (text, record) => <Select style={{width: 160}} defaultValue={record.information?.status ?? ''}>
                    <Select.Option value="">No status</Select.Option>
                    <Select.Option value="for_review">For review</Select.Option>
                    <Select.Option value="approved">Approved</Select.Option>
                </Select>
        },
        {
            title: 'BIS Status',
            render: (text, record) => record.information ? 'OK' : 'Incomplete'
        },
        {
            title: 'First name',
            key: 'first_name',
            dataIndex: 'first_name'
        },
        {
            title: 'Middle name',
            key: 'middle_name',
            dataIndex: 'middle_name'
        },
        {
            title: 'Last name',
            key: 'last_name',
            dataIndex: 'last_name'
        },
        {
            title: 'Email',
            key: 'email',
            dataIndex: 'email'
        },
        {
            title: 'Created at',
            render: (text, record) => moment(record.created_at).format('DD MMM YYYY')
        },
        {
            title: 'Action',
            render: (text, record) => <Link to={`view-client/${record.id}`}><Button icon={<EditOutlined/>} /></Link>
        }
    ];

    const handleSearch = (e) => {
        // console.log(e.target.value);
        setSearchString(e.target.value.toLowerCase());
    }

    const handleExportBISReport = () => {
        if (exportBISReportQueryIsLoading) {
            return false;
        }

        exportBISReportQuery({
            id: 1
        }, {
            onSuccess: (res) => {

                console.log(res);
                // var bin = atob(unescape(res.data));
                // var ab = s2ab(bin); // from example above
                var file = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;' });
                //Build a URL from the file
                const fileURL = URL.createObjectURL(file);
                //Download fileURL
                var a = document.createElement("a");
                a.href = fileURL;
                a.download = `BIS Report ${moment().format('YYYY-MM-DD HH:mm:ss')}`;
                a.click();
                window.URL.revokeObjectURL(fileURL);
            },
            onError: (e) => {
                message.info("Error");
            }
        })
    }

    return (
        <>
            <Modal
                visible={newClientModalVisible}
                onCancel={() => setNewClientModalVisible(false)}
                title="New Client"
                width={newClientWithBIS ? '100%' : 500}
                footer={null}
            >
                <NewClientComponent setNewClientModalVisible={setNewClientModalVisible} setNewClientWithBIS={setNewClientWithBIS} withBIS={newClientWithBIS}/>
            </Modal>

            <div className="mt-4 mb-4" style={{display:'flex', justifyContent:'space-between'}}>
                <Button icon={<UserAddOutlined />} onClick={()=>setNewClientModalVisible(true)}>New client</Button>

                <Button disabled icon={<ImportOutlined/>}>Import Clients Data</Button>
                {/* <Button disabled icon={<PrinterOutlined/>}>Export Clients Data</Button> */}
                <Button onClick={()=>handleExportBISReport()} icon={<PrinterOutlined/>}>Export BIS Report</Button>

                <Input style={{width: 300}} type="search" placeholder="Search client" onChange={(e)=>handleSearch(e)} />
            </div>

            <Table
                rowKey="id"
                columns={columns}
                dataSource={salesClientsListQuery.data ? salesClientsListQuery.data.filter(item => {
                    if (item && searchString) {
                        const searchValue =  item.first_name.toLowerCase() + " " + item.last_name.toLowerCase() + " " + item.email.toLowerCase() + " c"+String(item.id).padStart(9, '0');
                        console.log(searchValue);
                        return searchString ? searchValue.indexOf(searchString) !== -1 : true;
                    }
                    return true;
                })
                : []}
            />
        </>
    )
}