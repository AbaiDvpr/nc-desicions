import axios from '@/api/v1/axios';

/**
 * Получение списка записей с параметрами фильтрации
 *
 * @param   params
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const getList = (params= {}) => {
    return axios
        .get(`/cabinet/counterparties`, { params })
        .then(response => response.data);
};

/**
 * Запрос на сохранение/добавление записи
 *
 * @param   data
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const save = (data = {}) => {
    return axios
        .post(`/cabinet/counterparties/save`, data)
        .then(response => response.data);
};

/**
 * Удаление записи
 *
 * @returns {Promise<axios.AxiosResponse<any>>}
 * @param uuid
 */
const deleteItem = uuid => {
    return axios
        .delete(`/cabinet/counterparties/${uuid}`, data)
        .then(response => response.data);
};

export default {
    getList,
    save,
    deleteItem
}
