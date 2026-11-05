import axios from '@/api/v1/axios';

/**
 * Возвращает данные одного банка
 *
 * @param data
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const getOne = uuid => {
    return axios
        .get(`/handbook/banks/${uuid}`)
        .then(response => response.data);
};

const getBanks = (params = {}) => {
    return axios
        .get(`/handbook/banks`, { params })
        .then(response => response.data);
};

export default {
    getBanks,
    getOne
}
