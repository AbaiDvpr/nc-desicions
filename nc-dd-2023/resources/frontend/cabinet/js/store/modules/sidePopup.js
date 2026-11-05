import * as types from '@/store/mutation-types';
import { setBlocker } from '@/helpers/helpers';

const state = {
    current: null
};
const getters = {};
const mutations = {
    [types.SET_SIDE_POPUP](state, payload) {
        state.current = payload
    }
};
const actions = {
    setPopup({ commit }, payload) {
        commit(types.SET_SIDE_POPUP, payload);

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
