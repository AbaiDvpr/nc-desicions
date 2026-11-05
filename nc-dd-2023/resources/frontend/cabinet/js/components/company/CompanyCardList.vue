<template>
    <AppAlert
        v-if="isEmpty"
        type="info"
        :message="message"
    />

    <AppAlert
        v-else-if="isError"
        type="danger"
        :message="$t('whoops')"
    />

    <template v-else>
        <div class="projects__header">
            <div class="row">
                <div class="col-12 col-2xl-4">
                    <b class="projects__header-title">{{ $t('companyName') }}</b>
                </div>

                <div class="col-12 col-2xl-2">
                    <b class="projects__header-title">{{ $t('taxRegime') }}</b>
                </div>

                <div class="col-12 col-2xl-2">
                    <b class="projects__header-title">{{ $t('nds') }}</b>
                </div>

                <div class="col-12 col-2xl-2">
                    <b class="projects__header-title">{{ $t('currency') }}</b>
                </div>

                <div class="col-12 col-2xl-2"></div>
            </div>
        </div>

        <div class="projects__list">
            <CompanyCard
                v-if="companies.length"
                v-for="company in companies"
                :key="company.uuid"
                :company="company"
            />

            <CompanyCardSkeleton
                v-else
                v-for="index in skeletons"
                :key="index"
            />
        </div>
    </template>
</template>

<script>
import CompanyCard from '@/components/company/CompanyCard';
import CompanyCardSkeleton from '@/components/company/CompanyCardSkeleton';

export default {
    name: 'CompanyCardList',
    components: {
        CompanyCard,
        CompanyCardSkeleton
    },
    props: {
        companies: {
            type: Array,
            required: true
        },
        isEmpty: {
            type: Boolean,
            required: true
        },
        isError: {
            type: Boolean,
            required: true
        },
        message: {
            type: String,
            required: false,
            default: ''
        },
        skeletons: {
            type: Number,
            required: false,
            default: 6
        }
    }
}
</script>
