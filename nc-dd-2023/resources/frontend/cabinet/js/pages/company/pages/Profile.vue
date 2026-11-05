<template>
    <h1 class="title-primary">{{ $t($route.meta.title) }}</h1>

    <div class="row">
        <div class="col-12 col-xl-6">
            <CompanyProfile :companyUuid="uuid" />
        </div>

        <div class="col-12 col-xl-6">
            <CompanyRequisites :companyUuid="uuid" />
        </div>
    </div>
</template>

<script>
import CompanyProfile from '@/components/company/CompanyProfile';
import CompanyRequisites from '@/components/company/CompanyRequisites';

export default {
    name: 'CompanyPageProfile',
    components: {
        CompanyProfile,
        CompanyRequisites
    },
    computed: {
        /**
         * uuid компании
         *
         * @return {string}
         */
        uuid() {
            return this.$route.params.uuid;
        }
    }
}
</script>
