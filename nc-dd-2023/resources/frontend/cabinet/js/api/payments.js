import axios from '@/api/axios';
import store from '@/store/store';

const getHistories = (params = {}) => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/payments`, { params })
        .then(response => response.data);
};

const getTariff = id => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/payments/getTariff/${id}`)
        .then(response => response.data);
};

const getTariffs = params => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/payments/getTariffs`, { params })
        .then(response => response.data);
};

const requestRefill = data => {
    return axios
        .post(`/${store.getters['lang/locale']}/cabinet/payments/request-refill`, data)
        .then(response => response.data);
};

const submitPay = (id, data) => {
    return axios
        .post(`/${store.getters['lang/locale']}/cabinet/payments/pay/${id}`, data)
        .then(response => response.data);
};

export default {
    getHistories,
    getTariff,
    getTariffs,
    requestRefill,
    submitPay
}
