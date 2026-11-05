<template>
    <div class="title-wrap">
        <AppTitle :title="$route.meta.title" />
        <CreateProject />
    </div>

    <section class="projects">
        <ProjectFilter
            :params="params"
            @submit="getDataByFilter"
        />

        <ProjectCardList
            :projects="data"
            :isEmpty="isEmpty"
            :isError="isError"
            :message="message"
        />

        <div v-if="isShowLoadBtn" class="projects__more">
            <AppButtonLoader
                :isLoading="isLoading"
                :method="loadMore"
            >{{ $t('loadMore') }}</AppButtonLoader>
        </div>
    </section>
</template>

<script>
import API from '@/api/projects';
import { scrollToBottom } from '@/helpers/helpers';
import ProjectCardList from '@/components/project/ProjectCardList';
import CreateProject from '@/components/modal/CreateProject';
import ProjectFilter from "@/components/project/ProjectFilter.vue";
import notyf from '@/plugins/notyf';
import { debounce } from 'lodash';

/**
 * Интервал запроса на получение проектов
 *
 * @type {number}
 */
const SEARCH_TIMEOUT = 500;

export default {
    name: 'ProjectHome',
    components: {
        ProjectFilter,
        ProjectCardList,
        CreateProject
    },
    data() {
        return {
            data:          [],
            isLoading:     false,
            isEmpty:       false,
            isError:       false,
            isShowLoadBtn: false,
            params:        {
                page:     1,
                limit:    6,
                name:     '',
                status:   '',
                form:     '',
                currency: '',
            },
            message:       '',
        }
    },
    methods: {
        /**
         * Возвращает список проектов
         *
         * @return {Promise<void>}
         */
        async initialize() {
            this.isLoading = true;

            try {
                const data = await API.getList();

                this.data = data;
                this.isEmpty = !Boolean(data.length);
                this.isShowLoadBtn = data.length >= this.params.limit;

                if (this.isEmpty) {
                    this.message = this.$i18n.t('notFinModels');
                }
            } catch (error) {
                this.isError = true;

                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Подгружает проекты
         *
         * @return {Promise<void>}
         */
        async loadMore() {
            this.isLoading = true;
            this.params.page += 1;

            try {
                const data = await API.getList(this.params);

                this.data = [...this.data, ...data];
                this.isShowLoadBtn = data.length >= this.params.limit;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Возвращает отфильтрованные проекты
         *
         * @return {void}
         */
        getDataByFilter: debounce(async function() {
            this.isLoading = true;
            this.params.page = 1;

            notyf.dismissAll();

            try {
                const data = await API.getList(this.params);

                this.data = data;
                this.isEmpty = !Boolean(data.length);
                this.isError = false;
                this.isShowLoadBtn = data.length >= this.params.limit;

                if (this.isEmpty) {
                    this.message = this.$i18n.t('noData');
                }

                notyf.success(this.$i18n.t('dataLoaded'));
            } catch (error) {
                this.isError = true;

                throw error;
            } finally {
                this.isLoading = false;
            }
        }, SEARCH_TIMEOUT)
    },
    created() {
        this.initialize();
    },
    updated() {
        if (this.params.page !== 1) {
            scrollToBottom();
        }
    }
}
</script>
