<template>
    <h1 class="title-primary">{{ $t($route.meta.title) }}</h1>

    <div class="row tariff">
        <div class="col-12 col-2xl-6">
            <div class="panel tariff-details">
                <h2 class="tariff-details__title">{{ $t('tariffDetails.selectedTariff') }}</h2>

                <div class="tariff-details__body">
                    <div class="tariff-details__info">
                        <h2 class="tariff-details__name">{{ getName() }}</h2>

                        <ul class="tariff-details__list">
                            <li><strong>{{ $t('tariffDetails.cost') }}:</strong> {{ getPrice() }} ₸ / {{ $t('shortMonth') }}</li>

                            <li><strong>{{ $t('tariffDetails.expire') }}:</strong> {{ getExpireDate() }}</li>

                            <li><strong>{{ $t('tariffDetails.balance') }}:</strong> {{ numberFormat(balance) }} ₸</li>
                        </ul>

                        <div class="tariff-details__controls">
                            <RequestRefill />

                            <router-link
                                :to="{name: 'tariffs'}"
                                class="btn btn--primary btn--size-lg"
                            >{{ $t('changeTariff') }}</router-link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-2xl-6">
            <div class="panel tariff-remaining">
                <h2 class="tariff-remaining__title">{{ $t('tariffRemaining.tariffRemaining') }}</h2>

                <AppLoader v-if="isRemainingLoader" />

                <template v-else>
                    <div class="tariff-progress">
                        <div class="tariff-progress__header">
                            <span class="tariff-progress__title">{{ $t('tariffRemaining.projectsAvailable') }}</span>

                            <strong class="tariff-progress__subtitle">
                                <AppIcon
                                    v-if="fullAccess"
                                    name="infinity"
                                    modify="icon--size-lg"
                                />

                                <template v-else>
                                    <span
                                        class="tariff-progress__mark">{{ projectsLeft }}</span> / {{ tariff.project.count }}
                                </template>
                            </strong>
                        </div>

                        <div class="tariff-progress__body">
                            <div class="tariff-progress__line">
                                <div class="tariff-progress__fill" :style="`width:${getProjectsProgress}%;`"></div>
                            </div>

                            <span class="tariff-progress__info">{{ $t('tariffRemaining.numberOfProjects') }}</span>
                        </div>
                    </div>

                    <div class="tariff-progress">
                        <div class="tariff-progress__header">
                            <span class="tariff-progress__title">{{ $t('tariffRemaining.tariffExpire') }}</span>

                            <strong class="tariff-progress__subtitle">
                                <AppIcon
                                    v-if="fullAccess || tariff.expire === null"
                                    name="infinity"
                                    modify="icon--size-lg"
                                />

                                <template v-else> {{ getLeftTime(tariff.expire) }} {{ $t('days') }} </template>
                            </strong>
                        </div>

                        <div class="tariff-progress__body">
                            <div class="tariff-progress__line">
                                <div class="tariff-progress__fill" :style="`width:${getExpireProgress()}%`"></div>
                            </div>

                            <span class="tariff-progress__info">{{ $t('tariffRemaining.tariffValidUntil') }}: {{ getExpireDate() }}</span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <PaymentsHistory/>
</template>

<script>
import RequestRefill from '@/components/modal/RequestRefill';
import PaymentsHistory from '@/components/payments/PaymentsHistory';
import {mapGetters} from 'vuex';
import API_PROJECT from '@/api/projects';
import {getFormatDate, getLeftTime} from '@/plugins/dayjs';


export default {
    name:       'Tariff',
    components: {
        PaymentsHistory,
        RequestRefill,
    },
    data() {
        return {
            isRemainingLoader: false,
            projectsLeft:  0,
        };
    },
    computed:   {
        ...mapGetters({
            balance:    'profile/balance',
            tariff:     'profile/tariff',
            fullAccess: 'profile/fullAccess',
        }),

        /**
         * Получаем прогресс остатка проектов
         *
         * @return {number}
         */
        getProjectsProgress() {
            if (this.fullAccess) {
                return 100;
            }

            const left = this.projectsLeft;
            const count = this.tariff.project.count;

            return left / count * 100;
        }
    },
    methods:    {
        getFormatDate,
        getLeftTime,

        /**
         * Получаем остаток проектов
         *
         * @return {string}
         */
        async initialize() {
            this.isRemainingLoader = true;

            try {
                const { left } = await API_PROJECT.checkCreate();

                if (left) {
                    this.projectsLeft = left;
                }

            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isRemainingLoader = false;
            }
        },

        /**
         * Получаем название тарифа
         *
         * @return {string}
         */
        getName() {
            if (this.fullAccess) {
                return 'Unlimited';
            }

            return this.tariff.name;
        },

        /**
         * Получаем цену тарифа
         *
         * @return {string}
         */
        getPrice() {
            if (this.tariff.expire === null) {
                 return this.$t('free');
            }

            return this.numberFormat(this.tariff.price);
        },

        /**
         * Получаем дату истечения тарифа
         *
         * @return {string}
         */
        getExpireDate() {
            if (this.fullAccess || this.tariff.expire === null) {
                return this.$t('everlasting');
            }

            return getFormatDate(this.tariff.expire, 'DD.MM.YY');
        },

        /**
         * Получаем прогресс истечения тарифа
         *
         * @return {number}
         */
        getExpireProgress() {
            if (this.fullAccess || this.tariff.expire === null) {
                return 100;
            }

            const created = new Date(this.tariff.created);
            const expire = new Date(this.tariff.expire);
            const current = new Date();

            return 100 - ((current - created) / (expire - created)) * 100 ;
        }
    },
    created() {
        this.initialize();
    },
};
</script>
