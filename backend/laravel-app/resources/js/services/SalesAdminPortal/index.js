import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'
import { ConsoleSqlOutlined } from '@ant-design/icons';


const salesClientsList = () => {

    return useQuery("sales-clients", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/sales-clients-list`);
        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const salesTeamList = () => {

    return useQuery("sales-teams", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/sales-team-list`);
        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const newSalesTeam = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/new-sales-team', formData)
    });

}

// has_na: add N/A into options if needed
const salesAgentList = (has_na) => {

    return useQuery("sales-agents", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/sales-agent-list`);

        if( typeof has_na !== 'undefined' ) {
            data.unshift({
                id: 0,
                first_name: 'N/A',
                sub_team: {
                    id: 0
                },
            });
        }

        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const updateSalesTeam = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-sales-team', formData)
    });

}

const addNewClientRecord = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/new-client-record', formData)
    });

}

const lotCondoInventoryList = () => {

    return useQuery("lot-inventory", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/lot-inventory-list`);

        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const lotInventoryList = (type) => {

    return useQuery("lot-inventory", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/lot-inventory-listing/${type}`);

        let lotData = _.filter(data, function(e){
            return e['property_type'] === 'lot';
        });

        // console.log(lotData);

        return lotData;
    }, {
        enabled: 'type',
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}


const inventoryCustomFilter = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/inventory-custom-filter', formData)
    });
}

const subdivisionList = (type) => {
    return useQuery("subdivision-list", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/subdivision-list/${type}`);
        return data;
    }, {
        enabled: 'type',
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });
}
const inventoryDashoardCounts = (type) => {
    return useQuery("dashboard-counts", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/dashboard-counts/${type}`);
        return data;
    }, {
        enabled: 'type',
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}


const viewlotInventoryList = (current_page) => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/view-inventory-list?page=' + current_page, formData)
    });
}

const condoInventoryList = (type) => {

    return useQuery("lot-inventory", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/lot-inventory-listing/${type}`);

        let condoData = _.filter(data, function(e){
            return e['property_type'] === 'condo';
        });

        return condoData;
    }, {
        enabled: 'type',
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const updateLotDetails = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-lot-details', formData)
    });

}

const deleteLotDetails = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/delete-lot-details', formData)
    });

}

const inventoryActivityLogs = (type) => {

    return useQuery("inventory-logs", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/inventory-activity-logs/${type}`);
        return data;
    }, {
        enabled: type,
        refetchOnWindowFocus: false,
    });
}

const addInventoryActivityLogs = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/add-inventory-activity-log', formData)
    });
}

const newReservation = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/new-reservation', formData)
    });

}

const updateReservation = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-reservation', formData)
    });

}

const deleteReservation = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/delete-reservation', formData)
    });

}

const reservationList = () => {

    return useQuery("reservation-list", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/reservation-list`);
        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const viewReservationList = (current_page) => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/view-reservation-list?page=' + current_page, formData)
    });
}

const viewReservation = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/view-reservation', formData)
    });

}

const updateReservationClientNumber = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-reservation-client-number', formData)
    });

}

const updateReservationStatus = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-reservation-status', formData)
    });

}

const updateDefaultPenaltyDiscount = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-default-penalty-discount', formData)
    });

}


const viewClient = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/view-client', formData)
    });

}

const lotInventoryDashboard = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/lot-inventory-dashboard', formData)
    });

}

const dashboard = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/get-dashboard-data', formData)
    });

}

const newLotRecord = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/new-lot-record', formData)
    });

}

const priceUpdate = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/lot-price-update', formData)
    });

}

const uploadFile = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/upload-file-attachment', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
    });

}

const addAttachment = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/add-file-attachment', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
    });
}

const updateAttachmentType = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-attachment-type', formData)
    });
}

const removeFile = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/remove-file-attachment', formData)
    });

}

const updateAttachmentStatus = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-attachment-status', formData)
    });

}

const addPenalty = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/add-penalty', formData)
    });

}

const penaltyPayment = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/penalty-payment', formData)
    });

}

const updateAmortization = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-amortization-details', formData)
    });
}

const waivePenalty = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/waive-penalty', formData)
    });
}

const recomputeAccount = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/account-recomputation', formData)
    });
}

const updatePaymentDetail = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-payment-detail', formData)
    });
}

const deletePaymentDetail = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/delete-payment-detail', formData)
    });
}

const addPayment = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/add-payment', formData)
    });

}

const updatePaymentRecord = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-payment', formData);
    })
}

const REupdatePaymentRecord = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/re-dashboard-update-payment', formData);
    })
}

const viewPenalties = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/view-penalties', formData)
    });

}

const uploadReservationAttachmentFile = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/upload-file-reservation-attachment', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
    });

}

const approveAttachments = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/approve-attachments', formData)
    });
}

// const addAttachment = () => {
//     return useMutation(async formData => {
//         return await Http.post('/api/sales-admin-portal/add-file-attachment', formData, {
//             headers: {
//                 'Content-Type': 'multipart/form-data'
//             }
//         })
//     });
// }

const removeReservationAttachmentFile = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/remove-file-reservation-attachment', formData)
    });

}

const paymentDetailList = (transaction_id, callback) => {
    return useQuery("view-account", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/payment-attachment-list/${transaction_id}`);
        return data;
    }, {
        onSuccess: () => {
            setTimeout(function(){
                if(document.querySelector('.loader') !== null) {
                    document.querySelector('.loader').style.display = 'none';
                }
            }, 300)
        },
        enabled: transaction_id,
        refetchOnWindowFocus: false,
        refetchOnMount: false,
    });
}

const uploadPaymentDetailsAttachmentFile = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/upload-file-payment-attachment', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
    });
}

const importReservationData = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/import-reservation-data', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
    });
}

const generateImportReport = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/import-reports', formData, {
            responseType: 'blob'
        })
    });
}

const removePaymentDetailsAttachmentFile = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/remove-file-payment-attachment', formData)
    });

}

const downloadCRF = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/download-crf', formData, {
            responseType: 'blob'
        })
    });

}

const downloadBIS = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/download-bis', formData, {
            responseType: 'blob'
        })
    });

}

const downloadImportTemplate = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/export-import-template', formData, {
            responseType: 'blob'
        })
    });
}

const bulkUploadPayments = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/bulk-upload-payments', formData)
    });
}

const downloadInventoryTemplate = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/import-inventory-template', formData, {
            responseType: 'blob'
        })
    })
}

const updateClientRecord = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-client-record', formData)
    });

}

const exportReservationData = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/export-reservation-data', formData, {
            responseType: 'blob'
        })
    });

}

const exportPenaltyReports = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/export-penalty-reports', formData, {
            responseType: 'blob'
        })
    });

}

const exportAmortizationReports = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/export-amortization-reports', formData, {
            responseType: 'blob'
        })
    });
}

const exportCashLedgerReports = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/export-cash-ledger-reports', formData, {
            responseType: 'blob'
        })
    });
}

const exportBISReport = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/export-bis-report', formData, {
            responseType: 'blob'
        })
    });

}

const addSubTeam = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/add-sub-team', formData)
    });

}

const updateTeamLead = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-team-lead', formData)
    });

}

const updateTeamMembers = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-team-members', formData)
    });

}

const updateTeamName = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-team-name', formData)
    });

}

const deleteClientAttachment = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/delete-client-attachment', formData)
    });

}

const exportInventoryStatusReport = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/export-inventory-status-report', formData, {
            responseType: 'blob'
        })
    });

}

const exportCondoInventoryStatusReport = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/export-inventory-status-report', formData, {
            responseType: 'blob'
        })
    });
}


const accountList = () => {

    return useQuery("account-list", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/account-list`);
        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const viewAccountList = (current_page) => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/view-account-list?page=' + current_page, formData)
    });
}


const addFees = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/add-fees', formData)
    });

}

const activityLogs = (reservation_number) => {
    return useQuery("reservation-activity-logs", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/activity-logs/${reservation_number}`);
        return data;
    }, {
        refetchOnWindowFocus: false,
    });
}

const addActivityLogs = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/add-activity-log', formData)
    });
}

const updateRFDPDetails = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/update-rf-dp-detils', formData)
    });
}

const realestatePromos = () => {
    return useQuery("realestate-promos", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/realestate-promos`);
        return data;
    }, {
        refetchOnWindowFocus: false,
    });
}

const realestatePromosViaField = (params) => {
    return useQuery("realestate-promos", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/realestate-promos/${params.column}/${params.value}`);
        return data;
    }, {
        enabled: params,
        refetchOnWindowFocus: false,
    });
}

const updateRealestatePromo = () => {
    return useMutation(async formData => {
        return await Http.put('/api/sales-admin-portal/update-realestate-promo', formData)
    });
}

const addRealestatePromo = () => {
    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/add-realestate-promo', formData)
    });
}

const deleteRealestatePromo = (record) => {
    return useMutation(async formData => {
        return await Http.delete('/api/sales-admin-portal/delete-realestate-promo/' + formData.id, formData)
    });
}

const collectionReceivable = () => {
    return useMutation(async formData => {
        return await Http.get('/api/sales-admin-portal/collection-receivables/' + formData.year + '/' + formData.term, formData)
    });
}

const collectionRevenue = () => {
    return useMutation(async formData => {
        return await Http.get('/api/sales-admin-portal/collection-revenues/' + formData.month + '/' + formData.year, formData)
    });
}

const clientReservation = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/client-reservation', formData)
    });

}

export default {
    salesClientsList,
    salesTeamList,
    newSalesTeam,
    salesAgentList,
    updateSalesTeam,
    addNewClientRecord,
    lotCondoInventoryList,
    lotInventoryList,
    viewlotInventoryList,
    subdivisionList,
    inventoryCustomFilter,
    inventoryDashoardCounts,
    condoInventoryList,
    updateLotDetails,
    deleteLotDetails,
    newReservation,
    updateReservation,
    deleteReservation,
    reservationList,
    viewReservationList,
    viewReservation,
    updateReservationClientNumber,
    updateReservationStatus,
    viewClient,
    lotInventoryDashboard,
    dashboard,
    newLotRecord,
    priceUpdate,
    uploadFile,
    removeFile,
    updateAttachmentStatus,
    addAttachment,
    addPenalty,
    waivePenalty,
    viewPenalties,
    addPayment,
    recomputeAccount,
    updatePaymentDetail,
    deletePaymentDetail,

    uploadReservationAttachmentFile,
    removeReservationAttachmentFile,
    uploadPaymentDetailsAttachmentFile,
    removePaymentDetailsAttachmentFile,
    paymentDetailList,

    downloadCRF,
    downloadBIS,

    updateClientRecord,
    exportReservationData,
    exportPenaltyReports,
    exportAmortizationReports,
    exportCashLedgerReports,
    exportBISReport,
    importReservationData,
    downloadImportTemplate,
    bulkUploadPayments,
    downloadInventoryTemplate,

    addSubTeam,
    updateTeamLead,
    updateTeamMembers,
    updateTeamName,
    deleteClientAttachment,
    exportInventoryStatusReport,
    exportCondoInventoryStatusReport,
    generateImportReport,

    accountList,
    viewAccountList,
    addFees,
    penaltyPayment,
    updatePaymentRecord,
    activityLogs,
    addActivityLogs,
    updateAttachmentType,
    updateDefaultPenaltyDiscount,
    REupdatePaymentRecord,
    updateAmortization,
    updateRFDPDetails,
    approveAttachments,
    inventoryActivityLogs,
    addInventoryActivityLogs,
    realestatePromos,
    realestatePromosViaField,
    updateRealestatePromo,
    addRealestatePromo,
    deleteRealestatePromo,
    collectionReceivable,
    collectionRevenue,
    clientReservation,
}