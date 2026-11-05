import axios from 'axios';
import store from '@/store/store';
import { API_DOMAIN } from '@/config/app';

const instance = axios.create({
    baseURL: `${API_DOMAIN}/v1`,
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
});

instance.interceptors.request.use(config => {
    config.headers['X-APP-LANG'] = store.getters['lang/locale'] ?? 'ru';
    config.headers['Authorization'] = `Bearer ${store.getters['profile/apiToken']}`;

    return config;
});

export default instance;
