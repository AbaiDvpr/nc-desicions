import axios from '@/api/v1/axios';

/**
 * Получение списка статей из справочника
 *
 * @param params
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const getArticles = (params = {}) => {
    return axios
        .get(`/handbook/articles`, { params })
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
        .post(`/handbook/articles`,  data)
        .then(response => response.data);
};

export default {
    getArticles,
    create
}
