import React from 'react'
// import { Spin } from 'antd';
import { LoadingOutlined } from '@ant-design/icons'

const Loading = ({isHeightFull = true}) => (
    <div className="loading" style={{display: 'flex', justifyContent: 'center', alignItems: 'center', height: isHeightFull == true ? '100vh' : 'auto', padding: isHeightFull ? 0:8, flexDirection: 'column'}}>
        <LoadingOutlined style={{fontSize: '2rem', marginBottom: 12}} />
        <div>Loading...</div>
    </div>
)

export default Loading;