<template>
    <h1 class="title-primary">{{ $t($route.meta.title) }}</h1>

    <div v-if="data.length" class="tariff-nav">
        <button
            :class="['tariff-nav__btn', { 'is-active': this.type === monthly }]"
            @click="changeType(monthly)"
        >{{ $t('month') }}</button>

        <button
            :class="['tariff-nav__btn', { 'is-active': this.type === annual }]"
            @click="changeType(annual)"
        >
            {{ $t('year') }}
            <span class="tariff-nav__badge">-50%</span>
        </button>
    </div>

    <AppLoader v-if="isLoading" />

    <AppAlert
        v-else-if="isEmpty"
        type="info"
        :message="$t('noData')"
    />

    <AppAlert
        v-else-if="isError"
        type="danger"
        :message="$t('whoops')"
    />

    <section v-else class="tariffs">
        <div class="tariffs-cards">
            <div
                v-for="tariff in data"
                class="tariff-card"
                :key="tariff.id"
            >
                <div class="tariff-card__heading">
                    <div class="tariff-card__img-wrap">
                        <img
                            :src="getImage(tariff.id)"
                            :alt="tariff.name"
                            width="80"
                            height="80"
                        >
                    </div>

                    <h2 class="tariff-card__name">{{ tariff.name }}</h2>
                </div>

                <div class="tariff-card__content" v-html="tariff.description"></div>

                <div class="tariff-card__cost-wrap">
                    <div class="tariff-card__cost">
                        {{ numberFormat(type === annual ? tariff.price / 12 : tariff.price) }} ₸
                    </div>

                    <div
                        v-if="type === annual"
                        class="tariff-card__period"
                    >{{ $t('monthlyPayment') }}</div>
                </div>

                <AppButton
                    type="tariff-card__submit btn--primary"
                    :link="true"
                    :to="{ name: 'payments.confirmation', params: { id: tariff.id } }"
                >Выбрать тариф</AppButton>
            </div>
        </div>
    </section>
</template>

<script>
import API from '@/api/payments';
import { mapGetters } from 'vuex';
import notyf from '@/plugins/notyf';
import { FORM_DATA } from '@/config/app';

const { tariffTypes }     = FORM_DATA;
const { monthly, annual } = tariffTypes;

export default {
    name: 'Tariffs',
    data() {
        return {
            data:      [],
            isLoading: false,
            isEmpty:   false,
            isError:   false,
            type:      monthly,
            monthly,
            annual
        }
    },
    computed: {
        ...mapGetters({
            locale: 'lang/locale'
        })
    },
    methods: {
        /**
         * Возвращает список тарифов
         *
         * @returns {Promise<void>}
         */
        async getData() {
            this.isLoading = true;
            this.isError   = false;
            this.isEmpty   = false;

            try {
                const data = await API.getTariffs({ type: this.type });

                this.data    = data;
                this.isEmpty = !Boolean(data.length);
            } catch (error) {
                this.isError = true;

                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Возвращает путь к изображению тарифа
         *
         * @param  {number} id
         * @return {string}
         */
        getImage(id) {
            return `/static/images/common/prices/price-item-img-${id}.svg`;
        },

        /**
         * Переключение тарифа
         *
         * @param {number} type
         */
        changeType(type = 1) {
            if (type === this.type) return;

            this.type = type;
            this.getData();
        }
    },
    created() {
        this.getData();
    }
}
</script>
