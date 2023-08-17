import React from 'react'
import moment from 'moment-timezone'
import Loading from 'common/Loading'

import { Row, Col, Card, Statistic, Button, message, Space, Table, Typography, Input, Tag, DatePicker } from 'antd'

import { PrinterOutlined } from '@ant-design/icons'


export default function Page(props) {

    // States
    const [selectedDate, setselectedDate] = React.useState(moment());

    return (
        <Row gutter={[48,48]} className="mt-4">
            <Col xl={24}>
                Jump to date: <DatePicker allowClear={false} value={selectedDate} onChange={(e)=>setselectedDate(e)} className="mx-2" />
            </Col>
            <Col xl={12}>
                Taps
            </Col>
        </Row>
    )
}