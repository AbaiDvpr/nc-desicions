import * as types from '@/store/mutation-types';
import { setBlocker } from '@/helpers/helpers';

const state = {
    current: null
};
const getters = {};
const mutations = {
    [types.SET_MODAL](state, payload) {
        state.current = payload
    }
};
const actions = {
    setModal({ commit }, payload) {
        commit(types.SET_MODAL, payload);

        setBlocker(payload);
    }
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}
