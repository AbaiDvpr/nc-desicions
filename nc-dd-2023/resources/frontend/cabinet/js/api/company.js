import axios from '@/api/axios';
import store from '@/store/store';

/**
 * Возвращает список компаний
 *
 * @param  params
 * @return {Promise<axios.AxiosResponse<any>>}
 */
const getList = (params = {}) => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/companies`, { params })
        .then(response => response.data);
};

/**
 * Возвращает компанию
 *
 * @param  params
 * @return {Promise<axios.AxiosResponse<any>>}
 */
const getOne = params => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/companies/getOne`, { params })
        .then(response => response.data);
};

/**
 * Создает новую или обновляет существующую компанию
 *
 * @param  data
 * @return {Promise<axios.AxiosResponse<any>>}
 */
const save = (data = {}) => {
    return axios
        .post(`/${store.getters['lang/locale']}/cabinet/companies/save`, data)
        .then(response => response.data);
};

const getOpuReport = params => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/companies/opu/report`, { params })
        .then(response => response.data);
};

const getOpuReportPlan = params => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/companies/opu/report/plan`, { params })
        .then(response => response.data);
};

const getDdsReport = params => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/companies/dds/report`, { params })
        .then(response => response.data);
};

const getDdsReportPlan = params => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/companies/dds/report/plan`, { params })
        .then(response => response.data);
};

const getBalanceReport = params => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/companies/balance/report`, { params })
        .then(response => response.data);
};

const getBalanceReportPlan = params => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/companies/balance/report/plan`, { params })
        .then(response => response.data);
};

const uploadImportFile = (data = {}) => {
    return axios
        .post(`/${store.getters['lang/locale']}/cabinet/companies/import`, data)
        .then(response => response.data);
};

const getImportFileStatus = params => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/companies/import/status`, { params })
        .then(response => response.data);
};

const getIndicators = params => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/companies/indicators`, { params })
        .then(response => response.data);
};

const createIndicator = (data = {}) => {
    return axios
        .post(`/${store.getters['lang/locale']}/cabinet/companies/indicators/create`, data)
        .then(response => response.data);
};

const updateIndicator = (id, data) => {
    return axios
        .put(`/${store.getters['lang/locale']}/cabinet/companies/indicators/${id}`, data)
        .then(response => response.data);
};

const deleteIndicator = (id, params) => {
    return axios
        .delete(`/${store.getters['lang/locale']}/cabinet/companies/indicators/${id}`, { params })
        .then(response => response.data);
};

export default {
    getOne,
    save,
    getList,
    getOpuReport,
    getOpuReportPlan,
    getDdsReport,
    getDdsReportPlan,
    getBalanceReport,
    getBalanceReportPlan,
    uploadImportFile,
    getImportFileStatus,
    getIndicators,
    createIndicator,
    updateIndicator,
    deleteIndicator
}
