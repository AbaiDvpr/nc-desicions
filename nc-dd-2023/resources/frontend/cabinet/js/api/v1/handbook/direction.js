import axios from '@/api/v1/axios';

/**
 * Получение списка направлений компании из справочника
 *
 * @param data
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const getList = (data = {}) => {
    return axios
        .post(`/handbook/direction`, data)
        .then(response => response.data);
};

/**
 * Добавляет новую статью в справочник
 *
 * @param data
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const create = data => {
    return axios
        .post(`/handbook/direction/create`,  data)
        .then(response => response.data);
};

export default {
    getList,
    create
}
