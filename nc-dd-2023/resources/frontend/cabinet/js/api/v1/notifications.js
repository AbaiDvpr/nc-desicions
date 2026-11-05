import axios from '@/api/v1/axios';

const getList = (params = {}) => {
    return axios
        .get(`/cabinet/notifications/unread`, { params })
        .then(response => response.data);
};

const setAsRead = id => {
    return axios
        .put(`/cabinet/notifications/${id}`)
        .then(response => response.data);
};

export default {
    getList,
    setAsRead
}
