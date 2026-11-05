import axios from '@/api/v1/axios';

/**
 * Получение списка модулей
 *
 * @param   params
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const getModules = (params= {}) => {
    return axios
        .get(`/cabinet/courses/modules`, { params })
        .then(response => response.data);
};

/**
 * Получение модуля
 *
 * @param   moduleId
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const getOneModule = moduleId => {
    return axios
        .get(`/cabinet/courses/modules/${moduleId}`)
        .then(response => response.data);
};

/**
 * Получение списка уроков
 *
 * @param   moduleId
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const getLessons = moduleId => {
    return axios
        .get(`/cabinet/courses/modules/${moduleId}/lessons`)
        .then(response => response.data);
};

/**
 * Оценить урок
 *
 * @param   moduleId
 * @param   lessonId
 * @param   data
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const setRateLesson = (moduleId, lessonId, data = {}) => {
    return axios
        .post(`/cabinet/courses/modules/${moduleId}/lessons/${lessonId}/rate`, data)
        .then(response => response.data);
};

/**
 * Завершить урок
 *
 * @param   moduleId
 * @param   lessonId
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const setCompletedLesson = (moduleId, lessonId) => {
    return axios
        .post(`/cabinet/courses/modules/${moduleId}/lessons/${lessonId}/complete`)
        .then(response => response.data);
};

export default {
    getOneModule,
    getModules,
    getLessons,
    setRateLesson,
    setCompletedLesson
}
