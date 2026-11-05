<template>
    <div class="title-wrap">
        <AppTitle :title="$route.meta.title" />
        <CreateCompany />
    </div>

    <section class="projects">
        <CompanyCardList
            :companies="data"
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
import API from '@/api/company';
import { scrollToBottom } from '@/helpers/helpers';
import CompanyCardList from '@/components/company/CompanyCardList';
import CreateCompany from '@/components/modal/CreateCompany';

export default {
    name: 'CompanyHome',
    components: {
        CreateCompany,
        CompanyCardList
    },
    data() {
        return {
            data:          [],
            isLoading:     true,
            isEmpty:       false,
            isError:       false,
            isShowLoadBtn: false,
            params:        {
                page:  1,
                limit: 6,
            },
            message:       '',
        }
    },
    methods: {
        /**
         * Возвращает список компаний
         *
         * @return {Promise<void>}
         */
        async initData() {
            this.isLoading = true;

            try {
                const data = await API.getList();

                if (data) {
                    this.data = data;
                    this.isEmpty = !Boolean(data.length);
                    this.isShowLoadBtn = data.length >= this.params.limit;
                }

                if (this.isEmpty) {
                    this.message = this.$i18n.t('notCompanies');
                }
            } catch (error) {
                this.isError = true;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Подгружает компании
         *
         * @return {Promise<void>}
         */
        async loadMore() {
            this.isLoading = true;
            this.params.page += 1;

            try {
                const data = await API.getList(this.params);

                if (data) {
                    this.data = [...this.data, ...data];
                    this.isShowLoadBtn = data.length >= this.params.limit;
                }
            } finally {
                this.isLoading = false;
            }
        }
    },
    mounted() {
        this.initData();
    },
    updated() {
        if (this.params.page !== 1) {
            scrollToBottom();
        }
    }
}
</script>
