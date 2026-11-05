import * as types from '@/store/mutation-types';
import API from '@/api/profile';
import notyf from '@/plugins/notyf';
import store from  '@/store/store';
import i18n from '@/plugins/i18n';

const { user } = window.data;

const state = {
    data:      user,
    isLoading: false,
    errors:    {},
    avatar:    {
        isLoading: false,
        url:       user.avatar,
        default:   '/static/images/cabinet/default-image.svg',
    },
};

const getters = {
    data:         state => state.data,
    userName:     state => state.data.name,
    cabinetId:    state => state.data.cabinetId,
    userEmail:    state => state.data.email,
    avatar:       state => state.avatar,
    tariff:       state => state.data.tariff,
    errors:       state => state.errors,
    balance:      state => state.data.balance,
    apiToken:     state => state.data.apiToken,
    fullAccess:   state => state.data.fullAccess,
    courseAccess: state => state.data.courseAccess,
    courseEula:   state => state.data.courseEula,
};

const mutations = {
    [types.UPDATE_PROFILE_START]() {},
    [types.UPDATE_PROFILE_SUCCESS](state, payload) {
        state.data = payload;
        state.errors = {};
    },
    [types.UPDATE_PROFILE_FAILURE](state, payload) {
        state.errors = payload
    },

    [types.UPDATE_PROFILE_AVATAR_START](state) {
        state.avatar.isLoading = true;
    },
    [types.UPDATE_PROFILE_AVATAR_SUCCESS](state, payload) {
        state.avatar.isLoading = false;
        state.avatar.url = payload;
    },
    [types.UPDATE_PROFILE_AVATAR_FAILURE](state) {
        state.avatar.isLoading = false;
    },

    [types.UPDATE_PROFILE_COURSE_EULA_START]() {},
    [types.UPDATE_PROFILE_COURSE_EULA_SUCCESS](state, payload) {
        state.data.courseEula = payload;
    },
    [types.UPDATE_PROFILE_COURSE_EULA_FAILURE]() {}
};

const actions = {
    /**
     * Обновляет данные
     *
     * @param  commit
     * @param  form
     * @return {Promise}
     */
    update({ commit }, form) {
        return new Promise(resolve => {
            store.dispatch('loader/setLoading', true);

            API.save(form)
                .then(data => {
                    const { user, redirectUrl } = data;

                    commit(types.UPDATE_PROFILE_SUCCESS, user);

                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }

                    notyf.success('Данные профиля успешно обновлены');

                    resolve(data);
                })
                .catch(error => {
                    const { status } = error.response;

                    if (status === 422) {
                        commit(types.UPDATE_PROFILE_FAILURE, error.response.data.errors);

                        return;
                    }

                    notyf.error(i18n.global.t('whoops'));

                    throw error;
                })
                .finally(() => store.dispatch('loader/setLoading', false));
        });
    },

    /**
     * Загружает аватар
     *
     * @param  commit
     * @return {void}
     */
    uploadAvatar({ commit }) {
        const input = document.createElement('input');

        input.type = 'file';
        input.accept = '.jpg, .jpeg, .png';
        input.setAttribute('data-profile-avatar', '');

        input.addEventListener('change', async () => {
            const [file] = input.files;

            if (file) {
                commit(types.UPDATE_PROFILE_AVATAR_START);

                const formData = new FormData();

                formData.append('file', file);

                try {
                    const { url } = await API.uploadAvatar(formData);

                    commit(types.UPDATE_PROFILE_AVATAR_SUCCESS, url);
                } catch (error) {
                    commit(types.UPDATE_PROFILE_AVATAR_FAILURE);

                    const { status, data } = error.response;

                    if (status === 422) {
                        Object.keys(data.errors).map(item => notyf.error(data.errors[item][0]));

                        return;
                    }

                    notyf.error(i18n.global.t('whoops'));

                    throw error;
                } finally {
                    input.remove();
                }
            }
        });

        input.hidden = true;
        document.body.append(input);

        input.click();
    },

    /**
     * Принимает пользовательское соглашение для курсов
     *
     * @param  commit
     * @param  form
     * @return {Promise}
     */
    applyCourseEula({ commit }) {
        return new Promise(resolve => {
            API.applyCourseEula()
                .then(data => {
                    const { success } = data;

                    commit(types.UPDATE_PROFILE_COURSE_EULA_SUCCESS, success);

                    resolve(data);
                })
                .catch(error => {
                    notyf.error(i18n.global.t('whoops'));

                    throw error;
                });
        });
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}
