import axios from '@/api/v1/axios';

/**
 * Возвращает данные одной записи
 *
 * @param data
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const getOne = data => {
    return axios
        .post(`/cabinet/company/accounting/getOne`, data)
        .then(response => response.data);
};

/**
 * Получение списка записей с параметрами фильтрации
 *
 * @param uuid
 * @param params
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const getList = (uuid, params) => {
    return axios
        .get(`/cabinet/company/accounting/${uuid}`, { params })
        .then(response => response.data);
};

/**
 * Запрос на сохранение/добавление записи
 *
 * @param data
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const save = (data = {}) => {
    return axios
        .post(`/cabinet/company/accounting/save`, data)
        .then(response => response.data);
};

/**
 * Удаление статьи доходов/расходов
 *
 * @param data
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const deleteItem = (data = {}) => {
    return axios
        .put(`/cabinet/company/accounting/delete`, data)
        .then(response => response.data);
};

export default {
    getOne,
    getList,
    save,
    deleteItem
}
