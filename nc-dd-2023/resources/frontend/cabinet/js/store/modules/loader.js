import * as types from '@/store/mutation-types';

const state = {
    isLoading: false
};
const getters = {};
const mutations = {
    [types.SET_LOADING](state, payload) {
        state.isLoading = payload
    }
};
const actions = {
    setLoading({ commit }, payload) {
        commit(types.SET_LOADING, payload);
    }
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}
