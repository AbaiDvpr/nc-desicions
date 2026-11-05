import axios from 'axios';
import store from '@/store/store';
import notyf from '@/plugins/notyf';
import i18n from '@/plugins/i18n';

axios.interceptors.request.use(config => {
    config.headers['X-Requested-With'] = 'XMLHttpRequest';
    config.headers['X-APP-LANG'] = store.getters['lang/locale'] ?? 'ru';

    return config;
});

/**
 * Проверка сессии пользователя
 */
axios.interceptors.response.use(response => {
    const { status, request } = response;

    if (status === 200) {
        const url = new URL(request.responseURL);

        if (url.pathname.includes('/auth/login')) {
            notyf.error(i18n.global.t('sessionExpired'));

            setTimeout(() => window.location.href = url.pathname, 5000);
        }
    }

    return response;
}, error => Promise.reject(error));

export default axios;
