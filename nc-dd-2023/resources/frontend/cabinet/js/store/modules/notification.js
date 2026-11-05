import * as types from '@/store/mutation-types';
import API from '@/api/v1/notifications';
import notyf from '@/plugins/notyf';
import i18n from '@/plugins/i18n';
import {getNotifierTemplate} from "@/helpers/helpers";

const state = {
    data:      [],
    isLoading: false,
    isEmpty:   false,
    isError:   false,
};
const getters = {
    notifications: state => state.data,
    isLoading:     state => state.isLoading,
    isError:       state => state.isError,
    isEmpty:       state => state.isEmpty,
};
const mutations = {
    [types.GET_NOTIFICATION_START](state) {
        state.data = [];
        state.isLoading = true;
        state.isEmpty = false;
        state.isError = false;
    },
    [types.GET_NOTIFICATION_SUCCESS](state, payload) {
        state.data = payload;
        state.isLoading = false;
        state.isEmpty = !Boolean(payload.length);
    },
    [types.GET_NOTIFICATION_FAILURE](state) {
        state.isError = true;
        state.isLoading = false;
    },

    [types.SET_READ_NOTIFICATION_START]() {},
    [types.SET_READ_NOTIFICATION_SUCCESS](state, payload) {
        state.data = payload;
        state.isEmpty = !Boolean(payload.length);
    },
    [types.SET_READ_NOTIFICATION_FAILURE]() {},

    [types.ADD_ITEM_NOTIFICATION](state, payload) {
        state.data.unshift(payload);
        state.isEmpty = false;
        state.isError = false;
    }
};

const actions = {
    /**
     * Возвращает список уведомлений
     *
     * @param  commit
     * @return {Promise}
     */
    getList({ commit }) {
        return new Promise(resolve => {
            commit(types.GET_NOTIFICATION_START);

            API.getList()
                .then(data => {
                    commit(types.GET_NOTIFICATION_SUCCESS, data);

                    resolve(data);
                })
                .catch(error => {
                    commit(types.GET_NOTIFICATION_FAILURE);

                    throw error;
                });
        });
    },

    /**
     * Помечает уведомление как прочитанное
     *
     * @param  commit
     * @param  id
     * @return {Promise}
     */
    setAsRead({ commit }, { id }) {
        return new Promise(resolve => {
            commit(types.SET_READ_NOTIFICATION_START);

            API.setAsRead(id)
                .then(data => {
                    if (data.mark) {
                        commit(types.SET_READ_NOTIFICATION_SUCCESS, data.notifications);
                    }

                    resolve(data);
                })
                .catch(error => {
                    commit(types.SET_READ_NOTIFICATION_FAILURE);

                    throw error;
                });
        });
    },

    /**
     * Добавляет новое уведомление
     *
     * @param  commit
     * @param  payload
     * @return {void}
     */
    addItem({ commit }, payload) {
        commit(types.ADD_ITEM_NOTIFICATION, payload);

        notyf.open({
            type: 'notifier',
            message: getNotifierTemplate(i18n.global.t('notifier'), payload.data.message),
            icon: false,
            position: {
                x: 'right',
                y: 'bottom',
            },
            dismissible: true
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
