import axios from '@/api/axios';
import store from '@/store/store';

/**
 * Возвращает список проектов
 *
 * @param  params
 * @return {Promise<axios.AxiosResponse<any>>}
 */
const getList = (params = {}) => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/projects`, { params })
        .then(response => response.data);
};

/**
 * Проверяет на возможность создания проекта
 *
 * @param  params
 * @return {Promise<axios.AxiosResponse<any>>}
 */
const checkCreate = (params = {}) => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/projects/checkCreate`, { params })
        .then(response => response.data);
};

/**
 * Создает проект
 *
 * @param  data
 * @return {Promise<axios.AxiosResponse<any>>}
 */
const create = (data = {}) => {
    return axios
        .post(`/${store.getters['lang/locale']}/cabinet/projects/create`, data)
        .then(response => response.data);
};

/**
 * Проверяет на возможность редактирования проекта
 *
 * @param  {string} uuid
 * @return {Promise<axios.AxiosResponse<any>>}
 */
const checkCorrect = uuid => {
    return axios
        .get(`/${store.getters['lang/locale']}/cabinet/projects/p/${uuid}/checkCorrect`)
        .then(response => response.data);
};

export default {
    getList,
    checkCreate,
    create,
    checkCorrect
}
