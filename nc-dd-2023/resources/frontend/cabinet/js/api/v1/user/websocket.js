import axios from '@/api/v1/axios';

/**
 * Получение данных пользователя для подключения к Centrifugo
 *
 * @param params
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
const getConfig = (params = {}) => {
    return axios
        .get(`/user/websocket/getConfig`, { params })
        .then(response => response.data);
};

export default {
    getConfig
}
