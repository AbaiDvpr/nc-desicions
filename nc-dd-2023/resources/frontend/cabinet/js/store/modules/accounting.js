import * as types from '@/store/mutation-types';

const state = {
    addedAccountingUuid: '',
    editAccounting:      {
        operation: '',
        id:        '',
    }
};

const getters = {
    addedAccountingUuid: state => state.addedAccountingUuid,
    editAccounting:      state => state.editAccounting
};

const mutations = {
    [types.UPDATE_LIST_ACCOUNTING](state, payload) {
        state.addedAccountingUuid = payload;
    },

    [types.EDIT_ITEM_ACCOUNTING](state, { operation, id }) {
        state.addedAccountingUuid = '';
        state.editAccounting      = { operation, id };
    },

    [types.CLEAR_EDIT_ACCOUNTING](state) {
        state.editAccounting.operation = '';
        state.editAccounting.id        = '';
    },

    [types.CLEAR_ACCOUNTING](state) {
        state.addedAccountingUuid      = '';
        state.editAccounting.operation = '';
        state.editAccounting.id        = '';
    }
};

const actions = {
    /**
     * Обновляем список после добавления новых записей
     *
     * @param  commit
     * @param  uuid
     * @return {void}
     */
    updateList({ commit }, uuid) {
        commit(types.UPDATE_LIST_ACCOUNTING, uuid);
    },

    /**
     * Установка параметров редактирования записи
     *
     * @param  commit
     * @param  operation
     * @param  id
     * @return {void}
     */
    editItem({ commit }, { operation, id }) {
        commit(types.EDIT_ITEM_ACCOUNTING, { operation, id });
    },

    /**
     * Очистка параметров редактируемой записи
     *
     * @param  commit
     * @return {void}
     */
    clearEditItem({ commit }) {
        commit(types.CLEAR_EDIT_ACCOUNTING);
    },

    /**
     * Очистка параметров
     *
     * @param  commit
     * @return {void}
     */
    clear({ commit }) {
        commit(types.CLEAR_ACCOUNTING);
    }
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}
