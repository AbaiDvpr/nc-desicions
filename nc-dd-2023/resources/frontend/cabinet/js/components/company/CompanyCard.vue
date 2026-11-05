<template>
    <div class="project-card">
        <div class="row">
            <div class="col-12 col-2xl-4">
                <span class="project-card__title">
                    <b v-if="form">{{ form }}&nbsp;</b>
                    {{ name }}
                </span>
            </div>

            <div class="col-12 col-2xl-2">
                <span class="project-card__text">{{ taxRegime }}</span>
            </div>

            <div class="col-12 col-2xl-2">
                <span class="project-card__text">{{ nds }}</span>
            </div>

            <div class="col-12 col-2xl-2">
                <span class="project-card__text">{{ currency }}</span>
            </div>

            <div class="col-12 col-2xl-2">
                <ul class="project-card__controls">
                    <li>
                        <router-link
                            :to="{ name: 'companies.show', params: { uuid } }"
                            class="btn btn--primary btn--size-sm"
                            title="Управление"
                        >
                            <AppIcon name="grid-2" />
                        </router-link>
                    </li>

                    <li>
                        <router-link
                            :to="{ name: 'companies.page.profile', params: { uuid } }"
                            class="btn btn--info btn--size-sm"
                            title="Профиль компании"
                        >
                            <AppIcon name="personal-card" />
                        </router-link>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
import { FORM_DATA } from '@/config/app';

const { currencies } = FORM_DATA;

export default {
    name: 'CompanyCard',
    props: {
        company: {
            type: Object,
            required: true
        }
    },
    computed: {
        uuid() {
            return this.company.uuid;
        },
        form() {
            return this.company.form;
        },
        name() {
            return this.company.name ?? this.$i18n.t('notFilled');
        },
        taxRegime() {
            return this.company.taxRegime ?? this.$i18n.t('notFilled');
        },
        nds() {
            return this.company.nds ?? this.$i18n.t('notFilled');
        },
        currency() {
            return currencies[this.company.currency] ?? this.$i18n.t('notFilled');
        },
    }
}
</script>
