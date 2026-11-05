import * as types from '@/store/mutation-types';
import i18n from '@/plugins/i18n';
import API from '@/api/company';
import store from '@/store/store';
import notyf from '@/plugins/notyf';

const state = {
    data:            [],
    isLoading:       false,
    isError:         false,
    isDeleteLoading: false,
};
const getters = {
    indicators:      state => state.data,
    isLoading:       state => state.isLoading,
    isError:         state => state.isError,
    isDeleteLoading: state => state.isDeleteLoading,
};
const mutations = {
    [types.GET_COMPANY_INDICATORS_START](state) {
        state.data = [];
        state.isLoading = true;
        state.isError = false;
    },
    [types.GET_COMPANY_INDICATORS_SUCCESS](state, payload) {
        state.data = payload;
        state.isLoading = false;
    },
    [types.GET_COMPANY_INDICATORS_FAILURE](state) {
        state.isError = true;
        state.isLoading = false;
    },

    [types.CREATE_COMPANY_INDICATORS_START]() {},
    [types.CREATE_COMPANY_INDICATORS_SUCCESS](state, payload) {
        state.data = [...state.data, payload];
    },
    [types.CREATE_COMPANY_INDICATORS_FAILURE]() {},

    [types.SAVE_COMPANY_INDICATORS_START]() {},
    [types.SAVE_COMPANY_INDICATORS_SUCCESS]() {},
    [types.SAVE_COMPANY_INDICATORS_FAILURE]() {},

    [types.DELETE_COMPANY_INDICATORS_START](state) {
        state.isDeleteLoading = true;
    },
    [types.DELETE_COMPANY_INDICATORS_SUCCESS](state, id) {
        state.data = state.data.filter(item => item.id !== id);
        state.isDeleteLoading = false;
    },
    [types.DELETE_COMPANY_INDICATORS_FAILURE](state) {
        state.isDeleteLoading = false;
    },
};
const actions = {
    /**
     * Возвращает список показателей
     *
     * @param  commit
     * @param  companyUuid
     * @return {Promise}
     */
    getList({ commit }, { companyUuid }) {
        return new Promise(resolve => {
            commit(types.GET_COMPANY_INDICATORS_START);

            API.getIndicators({ companyUuid })
                .then(data => {
                    commit(types.GET_COMPANY_INDICATORS_SUCCESS, data);

                    resolve(data);
                })
                .catch(error => {
                    commit(types.GET_COMPANY_INDICATORS_FAILURE);

                    throw error;
                });
        });
    },

    /**
     * Создает показатель
     *
     * @param  commit
     * @param  companyUuid
     * @return {Promise}
     */
    create({ commit }, { companyUuid }) {
        return new Promise(resolve => {
            store.dispatch('loader/setLoading', true);

            commit(types.CREATE_COMPANY_INDICATORS_START);

            API.createIndicator({ companyUuid })
                .then(data => {
                    commit(types.CREATE_COMPANY_INDICATORS_SUCCESS, data);

                    resolve(data);
                })
                .catch(error => {
                    commit(types.CREATE_COMPANY_INDICATORS_FAILURE);

                    notyf.error(i18n.global.t('whoops'));

                    throw error;
                })
                .finally(() => store.dispatch('loader/setLoading', false));
        });
    },

    /**
     * Обновляет показатель
     *
     * @param  commit
     * @param  id
     * @param  form
     * @return {Promise}
     */
    save({ commit }, { id, form }) {
        return new Promise(resolve => {
            notyf.dismissAll();

            store.dispatch('loader/setLoading', true);

            commit(types.SAVE_COMPANY_INDICATORS_START);

            API.updateIndicator(id, form)
                .then(data => {
                    if (data) {
                        commit(types.SAVE_COMPANY_INDICATORS_SUCCESS, data);

                        notyf.success(i18n.global.t('indicatorSuccessSave'));
                    }

                    resolve(data);
                })
                .catch(error => {
                    commit(types.SAVE_COMPANY_INDICATORS_FAILURE);

                    const { status, data } = error.response;

                    if (status === 422) {
                        Object.keys(data.errors).map(item => notyf.error(data.errors[item][0]));

                        return;
                    }

                    notyf.error(i18n.global.t('whoops'));

                    throw error;
                })
                .finally(() => store.dispatch('loader/setLoading', false));
        });
    },

    /**
     * Удаляет показатель
     *
     * @param  commit
     * @param  id
     * @param  companyUuid
     * @return {Promise}
     */
    delete({ commit }, { id, companyUuid }) {
        return new Promise(resolve => {
            notyf.dismissAll();

            commit(types.DELETE_COMPANY_INDICATORS_START);

            API.deleteIndicator(id, { companyUuid })
                .then(data => {
                    commit(types.DELETE_COMPANY_INDICATORS_SUCCESS, id);

                    if (data) {
                        notyf.success(i18n.global.t('indicatorSuccessDelete'));
                    }

                    resolve(data);
                })
                .catch(error => {
                    commit(types.DELETE_COMPANY_INDICATORS_FAILURE);

                    notyf.error(i18n.global.t('whoops'));

                    throw error;
                });
        });
    }
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}
