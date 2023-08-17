import React from 'react'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');
import GolfService from 'services/Golf/GolfService'


import { Row, Col, Button, Table, Modal, DatePicker, Form, TimePicker, Select, message} from 'antd'

import Icon, { PlusOutlined } from '@ant-design/icons'

const {RangePicker} = DatePicker;


export default function Page(props) {

    const [addScheduleModalVisible, setAddScheduleModalVisible] = React.useState(false);
    const [dateFilter, setdateFilter] = React.useState(moment());

    const [addScheduleForm] = Form.useForm();

    // GET
    const teeTimeScheduleListQuery = GolfService.teeTimeScheduleList();

    // POST
    const [newTeeTimeScheduleQuery, { isLoading: newTeeTimeScheduleQueryIsLoading, reset: newTeeTimeScheduleQueryReset}] = GolfService.create();
    const [statusChangeQuery, { isLoading: statusChangeQueryIsLoading, reset: statusChangeQueryReset}] = GolfService.statusChange();
    const [updateAllocationQuery, {isLoading: updateAllocationQueryisLoading, error: updateAllocationQueryError}] = GolfService.allocationUpdate();


    const handleAddScheduleClick = () => {

        setAddScheduleModalVisible(true);

    }

    const handleAddScheduleFormFinish = (values) => {
        console.log(values);

        if (newTeeTimeScheduleQueryIsLoading) {
            return false;
        }

        newTeeTimeScheduleQuery(values, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Success! Saved records: '+ res.data.saved_records);

                if (res.data.existing_schedules) {
                    Modal.info({
                        title: "Existing records",
                        content: <>
                            {res.data.existing_schedules.length}
                        </>
                    });
                }
                addScheduleForm.resetFields();
                setAddScheduleModalVisible(false);
            },
            onError: (e) => {
                console.log(e);
                newTeeTimeScheduleQueryReset();
            }
        })
    }

    const handleAllocationChange = (id, allocation, record) => {
        // console.log(id, first_name);
    
        updateAllocationQuery({
            id: id,
            allocation: allocation,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success("Update allocation successful!");
                teeTimeScheduleListQuery.refetch(record);
            },
            onError: (e) => {
                console.log(e);
            }
        })
      }

    const handleStatusChange = (status, id) => {
        // console.log(e, id);

        if (statusChangeQueryIsLoading) {
            return false;
        }

        statusChangeQuery({
            id: id,
            status: status
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Success! status changed.');

            },
            onError: (e) => {
                message.danger('Failed to update status.');
            }
        })
    }



    return (
        <>

        <Modal
            title="Add Tee Time Schedule"
            visible={addScheduleModalVisible}
            onCancel={()=>setAddScheduleModalVisible(false)}
            footer={null}
        >
            <Form form={addScheduleForm}
                onFinish={handleAddScheduleFormFinish}
                layout="vertical"
                >
                <Row gutter={[8,8]}>
                    <Col xl={24}>
                        <Form.Item label="Date range" name="date_range" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <RangePicker style={{width: '100%'}}/>
                        </Form.Item>
                    </Col>

                    <Col xl={8}>
                        <Form.Item label="Time" name="time" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <TimePicker use12Hours  format="h:mm a" minuteStep={5} />
                        </Form.Item>
                    </Col>
                    <Col xl={8}>
                        <Form.Item label="Entity" name="entity" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <Select mode="multiple">
                                <Select.Option value="BPO">BPO</Select.Option>
                                <Select.Option value="RE">RE</Select.Option>
                                <Select.Option value="HOA">HOA</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>

                    <Col xl={8}>
                        <Form.Item label="Transportation" name="mode_of_transportation" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <Select>
                                <Select.Option value="all">All</Select.Option>
                                <Select.Option value="land">Land</Select.Option>
                                <Select.Option value="ferry">Ferry</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>

                    <Col xl={12}>
                        <Form.Item label="Allocation" name="allocation" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <Select>
                                <Select.Option value="0">0</Select.Option>
                                <Select.Option value="1">1</Select.Option>
                                <Select.Option value="2">2</Select.Option>
                                <Select.Option value="3">3</Select.Option>
                                <Select.Option value="4">4</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>


                </Row>
                <Row gutter={[8,8]}>
                    <Col xl={8}>
                        <Form.Item label="Save as approved" name="save_as_approved">
                            <Select>
                                <Select.Option value="no">No</Select.Option>
                                <Select.Option value="yes">Yes</Select.Option>
                            </Select>
                        </Form.Item>
                        <Button htmlType="submit">Save</Button>
                    </Col>
                </Row>
            </Form>
        </Modal>
        <Row gutter={[48,48]} className="mt-4">
            <Col xl={24}>
                <Button icon={<PlusOutlined/>} onClick={()=>handleAddScheduleClick()}>Add Schedule</Button>
            </Col>
            <Col xl={24}>
                <Table 
                    dataSource={teeTimeScheduleListQuery.data && teeTimeScheduleListQuery.data.filter(i => (dateFilter ? moment(i.date).format('YYYY-MM-DD') == moment(dateFilter).format('YYYY-MM-DD') : true))}
                    rowKey="id"
                    columns={[
                        {
                            title: "Tee off schedule",
                            render: (text, record) => <b>{moment(moment(record.date).format('YYYY-MM-DD') +' '+ record.time).format('h:mm A')}</b>
                        },
                        {
                            // title: "Date",
                            title: <>Date <DatePicker size="small" defaultValue={moment()} onChange={e => setdateFilter(e)}/></>,
                            dataIndex: 'date',
                            key: 'date',
                            render: (text) => moment(text).format('YYYY-MM-DD')
                        },
                        {
                            title: "Entity",
                            dataIndex: 'entity',
                            key: 'entity',
                        },
                        {
                            title: "Allocation",
                            dataIndex: 'allocation',
                            key: 'allocation',
                            render: (text, record) => 
                                <Select defaultValue={record.allocation} onChange={(e) =>  handleAllocationChange(record.id, e)}>
                                    <Select.Option value="1">1</Select.Option>
                                    <Select.Option value="2">2</Select.Option>
                                    <Select.Option value="3">3</Select.Option>
                                    <Select.Option value="4">4</Select.Option>
                                </Select>
                        },
                        {
                            title: "Used",
                            dataIndex: 'guests_count',
                            key: 'guests_count',
                        },
                        {
                            title: "Mode of transpo",
                            dataIndex: 'mode_of_transportation',
                            key: 'mode_of_transportation',
                        },
                        {
                            title: "Status",
                            dataIndex: 'status',
                            key: 'status',
                            render: (text, record) => {
                                return <Select defaultValue={record.status} className={ record.status == 'approved' ? 'text-success' : ''} onChange={(e) => handleStatusChange(e, record.id)}>
                                    <Select.Option value="for_review">For Review</Select.Option>
                                    <Select.Option value="approved" className="text-success">Approved</Select.Option>
                                    <Select.Option value="denied">Denied</Select.Option>
                                    <Select.Option value="disabled">Disabled</Select.Option>
                                </Select>
                            }
                        },
                    ]}
                />
            </Col>
        </Row>
        </>
    )
}