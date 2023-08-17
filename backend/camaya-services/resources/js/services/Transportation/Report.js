import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const ferryPassengersManifesto = (start_date, end_date) => {    
    
    return useQuery(['reports', 'ferry-passengers-manifesto', start_date, end_date], async () => {  
        if (! start_date || ! end_date) return [];
        
        const { data } = await Http.get(`/api/transportation/reports/ferry-passengers-manifesto/${start_date}/${end_date}`);
        return data.data;
    },{
        refetchOnWindowFocus: false,
    });

}

const ferryPassengersManifestoDownload = (formData, date) => {

    Http.post(`/api/transportation/reports/ferry-passengers-manifesto/download`, formData, {responseType: 'blob'}).then(({data}) => {
        const downloadUrl = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', `Ferry-Passengers-Manifest-${date}.xlsx`); //any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

}

const ferrySeatsPerSD = (start_date, end_date) => {    
    
    return useQuery(['reports', 'ferry-seats-per-sd', start_date, end_date], async () => {  
        if (! start_date || ! end_date) return [];
        
        const { data } = await Http.get(`/api/transportation/reports/ferry-seats-per-sd/${start_date}/${end_date}`);
        return data.data;
    },{
        refetchOnWindowFocus: false,
    });

}

const ferrySeatsPerSDDownload = (start_date, end_date) => {    
    
    Http.get(`/api/transportation/reports/ferry-seats-per-sd/${start_date}/${end_date}/download`, {responseType: 'blob'}).then(({data}) => {
        const downloadUrl = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', `Ferry-Seats-Per-Sales-Director-${start_date}-${end_date}.xlsx`); //any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

}

////
const ferryPassengersManifestoBPO = (start_date, end_date) => {    
    
    return useQuery(['reports', 'ferry-passengers-manifesto', start_date, end_date], async () => {  
        if (! start_date || ! end_date) return [];
        
        const { data } = await Http.get(`/api/transportation/reports/ferry-passengers-manifesto-bpo/${start_date}/${end_date}`);
        return data.data;
    },{
        refetchOnWindowFocus: false,
    });

}

const ferryPassengersManifestoDownloadBPO = (formData, date) => {

    Http.post(`/api/transportation/reports/ferry-passengers-manifesto-bpo/download`, formData, {responseType: 'blob'}).then(({data}) => {
        const downloadUrl = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', `Ferry-Passengers-Manifest-${date}.xlsx`); //any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

}

const ferryPassengersManifestoConcierge = (start_date, end_date) => {    
    
    return useQuery(['reports', 'ferry-passengers-manifesto', start_date, end_date], async () => {  
        if (! start_date || ! end_date) return [];
        
        const { data } = await Http.get(`/api/transportation/reports/ferry-passengers-manifesto-concierge/${start_date}/${end_date}`);
        return data.data;
    },{
        refetchOnWindowFocus: false,
    });

}

const ferryPassengersManifestoDownloadConcierge = (formData, date) => {

    Http.post(`/api/transportation/reports/ferry-passengers-manifesto-concierge/download`, formData, {responseType: 'blob'}).then(({data}) => {
        const downloadUrl = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', `Ferry-Passengers-Manifest-${date}.xlsx`); //any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

}


export default {
    ferryPassengersManifesto,
    ferryPassengersManifestoDownload,
    ferrySeatsPerSD,
    ferrySeatsPerSDDownload,
    
    ferryPassengersManifestoBPO,
    ferryPassengersManifestoDownloadBPO,

    ferryPassengersManifestoConcierge,
    ferryPassengersManifestoDownloadConcierge
}