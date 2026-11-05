import * as types from '@/store/mutation-types';
import i18n from '@/plugins/i18n';
import projectsAPI from '@/api/projects';

const state = {
    projects: {
        data:    [],
        params:  {
            limit: 7,
        },
        isEmpty: false,
        isError: false,
        message: null
    }
};
const getters = {
    projects: state => state.projects
};
const mutations = {
    [types.GET_DASHBOARD_PROJECTS](state, { data, isEmpty, isError, message }) {
        state.projects.data = data;
        state.projects.isEmpty = isEmpty;
        state.projects.isError = isError;
        state.projects.message = message;
    },
    [types.GET_DASHBOARD_PROJECTS_FAILURE](state) {
        state.projects.data = [];
        state.projects.isEmpty = false;
        state.projects.isError = true;
        state.projects.message = null;
    }
};
const actions = {
    getProjects({ commit, state }) {
        return new Promise(resolve => {
            projectsAPI.getList({ ...state.projects.params })
                .then(data => {
                    const isEmpty = !Boolean(data.length);
                    let message = isEmpty ? i18n.global.t('notFinModels') : '';

                    commit(types.GET_DASHBOARD_PROJECTS, {
                        data,
                        isEmpty,
                        isError: false,
                        message
                    });

                    resolve(data);
                })
                .catch(() => commit(types.GET_DASHBOARD_PROJECTS_FAILURE));
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
