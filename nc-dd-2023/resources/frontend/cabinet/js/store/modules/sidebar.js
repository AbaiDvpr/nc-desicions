import * as types from '@/store/mutation-types';

const state = {
    isActive: true,
    isCompact: false
};

const getters = {
    isActive: state => state.isActive,
    isCompact: state => state.isCompact
};

const mutations = {
    [types.SET_SIDEBAR_ACTIVE](state, payload) {
        state.isActive = payload;
    },

    [types.SET_SIDEBAR_COMPACT](state, payload) {
        state.isCompact = payload;
    }
};

const actions = {
    /**
     * Показывает сайдбар
     *
     * @param commit
     * @param payload
     */
    show({ commit }, payload) {
        commit(types.SET_SIDEBAR_ACTIVE, payload);
    },

    /**
     * Меняет стандартный сайдбар на компактный
     *
     * @param commit
     * @param payload
     */
    compact({ commit }, payload) {
        commit(types.SET_SIDEBAR_COMPACT, payload);

        window.dispatchEvent(new Event('resize'));
    }
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}
