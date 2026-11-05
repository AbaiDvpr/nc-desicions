import axios from '@/api/axios';
import store from '@/store/store';

const save = data => {
    return axios
        .post(`/${store.getters['lang/locale']}/cabinet/profile`, data)
        .then(response => response.data);
};

const uploadAvatar = data => {
    return axios
        .post(`/${store.getters['lang/locale']}/cabinet/profile/upload-avatar`, data)
        .then(response => response.data);
};

const applyCourseEula = () => {
    return axios
        .put(`/${store.getters['lang/locale']}/cabinet/profile/course-eula`)
        .then(response => response.data);
};

export default {
    save,
    uploadAvatar,
    applyCourseEula
}
