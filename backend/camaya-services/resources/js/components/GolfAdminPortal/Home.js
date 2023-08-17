import React from 'react'
import moment from 'moment-timezone'

import { Input, Table, Select, message, Button } from 'antd'

import GolfAdminService from 'services/GolfAdminPortal'

import ReactExport from "react-export-excel";
const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;

import {PrinterOutlined} from '@ant-design/icons'

const paddingLeft = (paddingValue) => {
    return String("0000" + paddingValue).slice(-4);
 };

export default function Page(props) {

    const [sources, setSources] = React.useState([]);

    const paymentListQuery = GolfAdminService.payments();
    const [updatePaymentRecordQuery, { isLoading: updatePaymentRecordQueryIsLoading, reset: updatePaymentRecordQueryReset}] = GolfAdminService.updatePaymentRecord();
    const [saveSourceQuery, { isLoading: saveSourceQueryIsLoading, reset: saveSourceQueryReset}] = GolfAdminService.saveSource();

    const handleUpdateDropdown = (record, type, value) => {
        // console.log(record,type);

        if (updatePaymentRecordQueryIsLoading) {
            return false;
        }

        updatePaymentRecordQuery({
            id: record.id,
            type: type,
            value: value
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Updated payment transaction record');
                paymentListQuery.refetch();
            },
            onError: (e) => {
                message.info(e.message);
            }
        })

    }

    const handleSourceChange = (record, value) => {
        // console.log(record,value);
        setSources( prev => {

            let newData = [...prev].filter( i => i.id != record.id);

            newData = [...newData, {
                    id: record.id,
                    value: value
                }
            ];

            // console.log(newData);
            return newData;
        });
    }

    const saveSource = (id) => {

        const source = _.find(sources, i => i.id == id);

        // console.log(source);

        if (saveSourceQueryIsLoading) {
            return false;
        }

        saveSourceQuery({
            id: id,
            value: source.value
        }, {
            onSuccess: (res) => {
                message.success('Saved source');
                paymentListQuery.refetch();
            }
            ,
            onError: (e) => {
                message.info(e.message);
            }
        });

    }

    return (
        <div className="mt-4">
            <ExcelFile filename={`Golf_payments-${moment().format('YYYY-MM-DD HH:mm:ss')}`} element={<Button className="ml-2" size="small"><PrinterOutlined/> Print golf payments</Button>}>
                <ExcelSheet data={paymentListQuery.data && paymentListQuery.data} name="golf_payments">
                    <ExcelColumn label="Membership No." value={ col => `GM-${paddingLeft(col.payer.id)}`}/>
                    <ExcelColumn label="Client" value={ col => `${col.payer.first_name} ${col.payer.last_name}`}/>
                    <ExcelColumn label="Client type" value="client_type"/>
                    <ExcelColumn label="Amount" value={ col => parseFloat(col.amount) }/>
                    <ExcelColumn label="Status" value="status" />
                    <ExcelColumn label="Mode of payment" value={ col => `${col.payment_channel}`}/>
                    <ExcelColumn label="Item" value={ col => `${col.item}`}/>
                    <ExcelColumn label="Payment date" value={ col => `${col.paid_at}`}/>
                    <ExcelColumn label="Source" value={ col => `${col.source}`}/>
                    <ExcelColumn label="BPO" value={ col => `${col.bpo}`}/>
                </ExcelSheet>
            </ExcelFile>  
            <Table
                rowKey="id"
                dataSource={paymentListQuery.data && paymentListQuery.data}
                scroll={{ x: 1300 }}
                columns={[
                    {
                        title: 'Membership No.',
                        render: (text, record) => <>GM-{paddingLeft(record.payer.id)}</>
                    },
                    {
                        title: 'Client',
                        render: (text, record) => <>{record.payer.first_name} {record.payer.last_name}</>
                    },
                    {
                        title: 'Client type',
                        render: (text, record) => <Select onChange={(value)=>handleUpdateDropdown(record, 'client_type', value)} style={{width: 170}} defaultValue={record.client_type}>
                            <Select.Option value="Homeowner">Homeowner</Select.Option>
                            <Select.Option value="Commercial">Commercial</Select.Option>
                        </Select>
                    },
                    {
                        title: 'Amount',
                        render: (text, record) => <>P {record.amount}</>
                    },
                    {
                        title: 'Status',
                        render: (text, record) => <>{record.status}</>
                    },
                    {
                        title: 'Mode of payment',
                        render: (text, record) => <>{record.payment_channel}</>
                    },
                    {
                        title: 'Item',
                        render: (text, record) => <>{record.item}</>
                    },
                    {
                        title: 'Payment date',
                        render: (text, record) => <>{record.paid_at}</>
                    },
                    {
                        title: 'Source',
                        render: (text, record) => <>
                            <Select onChange={(value)=> handleUpdateDropdown(record, 'source', value)} style={{width: 170}} defaultValue={record.source != 'Referral' ? 'Others' : record.source}>
                                <Select.Option value="Referral">Referral</Select.Option>
                                <Select.Option value="Others">Others</Select.Option>
                            </Select>
                            {
                                record.source != 'Referral' ?
                                <>
                                    <Input placeholder="Source" defaultValue={record.source == 'Others' ? '' : record.source} onChange={(e)=>handleSourceChange(record, e.target.value)} />
                                    <Button size="small" onClick={()=>saveSource(record.id)}>Save</Button>
                                </>
                                 : ''
                            }
                            </>
                    },
                    {
                        title: 'BPO',
                        render: (text, record) => <Select onChange={(value)=>handleUpdateDropdown(record, 'bpo', value)} style={{width: 170}} defaultValue={record.bpo}>
                        <Select.Option value="Charles Magallanes">Charles Magallanes</Select.Option>
                        <Select.Option value="Mikee Velasquez">Mikee Velasquez</Select.Option>
                    </Select>
                    },
                ]}
            />
        </div>
    )
}