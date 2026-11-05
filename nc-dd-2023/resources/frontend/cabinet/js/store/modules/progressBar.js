import * as types from '@/store/mutation-types';

const state = {
    isShow:     false,
    isLoading:  false,
    isLoaded:   false,
    timerId:    0
};

const getters = {
    isShow:    state => state.isShow,
    isLoading: state => state.isLoading,
    isLoaded:  state => state.isLoaded,
};

const mutations = {
    [types.PROGRESS_BAR_START](state) {
        state.isShow    = true;
        state.isLoading = true;
        state.isLoaded  = false;
    },

    [types.PROGRESS_BAR_STOP](state, timerId) {
        state.isShow    = true;
        state.isLoading = false;
        state.isLoaded  = true;
        state.timerId   = timerId;
    },

    [types.PROGRESS_BAR_RESET](state) {
        state.isShow    = false;
        state.isLoading = false;
        state.isLoaded  = false;
    },
};

const actions = {
    /**
     * Показывает progress bar
     *
     * @param commit
     * @param state
     * @return {void}
     */
    start({ commit, state }) {
        if (state.timerId) {
            clearTimeout(state.timerId);
        }

        commit(types.PROGRESS_BAR_START);
    },

    /**
     * Скрывает progress bar
     *
     * @param commit
     * @return {void}
     */
    stop({ commit }) {
        commit(
            types.PROGRESS_BAR_STOP,
            setTimeout(() => commit(types.PROGRESS_BAR_RESET), 500)
        );
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
