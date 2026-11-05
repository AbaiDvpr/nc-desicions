<template>
    <h1 class="section-title">Расчет финансовой модели</h1>

    <AppLoader v-if="isLoading" />

    <AppAlert v-else-if="isError" type="danger" :message="message" />

    <AppAlert v-else-if="!isPossibleToCorrect" type="info" :message="message" />

    <template v-else>
        <ProjectForm />
    </template>
</template>

<script>
import { defineAsyncComponent } from 'vue';
import API from '@/api/projects';
import AppLoader from '@/components/base/AppLoader';

export default {
    name: 'ProjectShow',
    components: {
        ProjectForm: defineAsyncComponent({
            loader: () => import(/* webpackChunkName: "ProjectForm" */ '@/components/project/form/ProjectForm.vue'),
            loadingComponent: AppLoader
        })
    },
    data() {
        return {
            isLoading:           false,
            isPossibleToCorrect: false,
            isError:             false,
            message:             '',
        }
    },
    computed: {
        /**
         * Возвращает uuid проекта
         *
         * @return {string}
         */
        uuid() {
            return this.$route.params.uuid;
        },
    },
    methods: {
        /**
         * Инициализация фин модели
         *
         * @return {Promise<void>}
         */
        async initialize() {
            this.isLoading = true;
            this.isError   = false;
            this.message   = '';

            try {
                const { status, message } = await API.checkCorrect(this.uuid);

                this.isPossibleToCorrect = status;
                this.message             = message;
            } catch (error) {
                const { data } = error.response;

                this.isError = true;
                this.message = data.message;

                throw error;
            } finally {
                this.isLoading = false;
            }
        },
    },
    created() {
        this.initialize();
    }
}
</script>
