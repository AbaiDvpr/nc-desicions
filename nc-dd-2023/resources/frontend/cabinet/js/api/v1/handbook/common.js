import axios from '@/api/v1/axios';

/**
 * Типы справочника
 */
const TYPE_GROUP = 1; // Группа

/**
 * Получение списка групп
 *
 * @param   params
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const getGroups = (params= {}) => {
    params = {...params, type: TYPE_GROUP};

    return axios
        .get(`/handbook`, { params })
        .then(response => response.data);
};

/**
 * Добавляет новую группу в справочник
 *
 * @param data
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const createGroup = data => {
    return axios
        .post(`/handbook/create`,  {
            ...data,
            type: TYPE_GROUP
        })
        .then(response => response.data);
};

/**
 * Получение списка типов для формы добавления доходов
 *
 * @param params
 * @return {Promise<axios.AxiosResponse<any>>}
 */
const getIncomeTypes = (params = {}) => {
    return axios
        .get(`/handbook/income/types`, { params })
        .then(response => response.data);
};

/**
 * Получение списка типов для формы добавления расходов
 *
 * @param params
 * @return {Promise<axios.AxiosResponse<any>>}
 */
const getOutcomeTypes = (params = {}) => {
    return axios
        .get(`/handbook/outcome/types`, { params })
        .then(response => response.data);
};

export default {
    getGroups,
    createGroup,
    getIncomeTypes,
    getOutcomeTypes
}
