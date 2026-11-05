import axios from '@/api/v1/axios';

const getOpuNetProfitDonut = (params = {}) => {
    return axios
        .get(`/cabinet/widgets/opu/netProfitDonut`, { params })
        .then(response => response.data);
};

const getOpuNetProfitArea = (params = {}) => {
    return axios
        .get(`/cabinet/widgets/opu/netProfitArea`, { params })
        .then(response => response.data);
};

const getDdsCashByEndPeriodDonut = (params = {}) => {
    return axios
        .get(`/cabinet/widgets/dds/cashByEndPeriodDonut`, { params })
        .then(response => response.data);
};

const getDdsCashByEndPeriodArea = (params = {}) => {
    return axios
        .get(`/cabinet/widgets/dds/cashByEndPeriodArea`, { params })
        .then(response => response.data);
};

export default {
    getOpuNetProfitDonut,
    getOpuNetProfitArea,
    getDdsCashByEndPeriodDonut,
    getDdsCashByEndPeriodArea
}
