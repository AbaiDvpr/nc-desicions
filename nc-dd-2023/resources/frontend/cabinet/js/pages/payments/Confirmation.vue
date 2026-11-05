<template>
    <h1 class="section-title">{{ $t($route.meta.title) }}</h1>

    <div v-if="Object.keys(tariff).length" class="row">
        <div class="col-12 col-2xl-5">
            <div class="panel">
                <div class="tariff-card tariff-card--compact">
                    <div class="tariff-card__heading">
                        <div class="tariff-card__img-wrap">
                            <img
                                :src="`/static/images/common/prices/price-item-img-${tariff.id}.svg`"
                                width="64"
                                height="64"
                                :alt="tariff.name"
                            >
                        </div>

                        <h2 class="tariff-card__name">
                            Тариф: {{ tariff.name }}
                        </h2>
                    </div>

                    <div v-html="tariff.description" class="tariff-card__content"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-2xl-7">
            <div v-if="paymentUrl" class="panel">
                <AppAlert type="success">
                    Сейчас Вы будете перенаправлены на страницу проведения оплаты.
                    Если Вас не перенаправило, то перейдите пожалуйста по
                    <a :href="paymentUrl" target="_blank">ссылке</a>
                </AppAlert>
            </div>

            <div v-else class="panel">
                <div class="row">
                    <div class="col-12 col-md-12">
                        <div class="form-group form-group--lg">
                            <div class="form-group__label">Тарифный план</div>

                            <div class="form-group__input">
                                <input class="input" type="text" v-model="tariff.name" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div :class="['form-group form-group--lg', errors.email ? 'is-error' : '']">
                            <div class="form-group__label">E-mail</div>

                            <div class="form-group__input">
                                <input class="input" type="text" v-model="form.email">
                            </div>

                            <div v-if="errors.email" class="form-group__error">
                                {{ errors.email[0] }}
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div :class="['form-group form-group--lg', errors.phone ? 'is-error' : '']">
                            <div class="form-group__label">Телефон</div>

                            <div class="form-group__input">
                                <input
                                    type="text"
                                    v-model="form.phone"
                                    v-maska
                                    data-maska="+7 (###) ###-##-##"
                                    class="input"
                                >
                            </div>

                            <div v-if="errors.phone" class="form-group__error">
                                {{ errors.phone[0] }}
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-12">
                        <div :class="['form-group form-group--lg', errors.description ? 'is-error' : '']">
                            <div class="form-group__label">Комментарий</div>

                            <div class="form-group__input">
                                <input class="input" type="text" v-model="form.description">
                            </div>

                            <div v-if="errors.description" class="form-group__error">
                                {{ errors.description[0] }}
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-12">
                        <div class="form-group form-group--lg">
                            <div class="form-group__label">
                                Цена тарифа <span>(в месяц)</span>
                            </div>

                            <div class="form-group__input">
                                <input
                                    type="text"
                                    :value="numberFormat(tariff.price) + ' ₸'"
                                    disabled
                                    class="input"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-12">
                        <div class="form-group">
                            <label class="checkbox">
                                <input
                                    class="checkbox__input"
                                    type="checkbox"
                                    v-model="form.verification"
                                />

                                <span class="checkbox__label">Подтвердить и оплатить</span>
                            </label>
                        </div>
                    </div>

                    <div v-if="form.verification" class="col-12 col-md-12">
                        <button class="btn btn--primary w-100" @click="onSubmit">
                            Оплатить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import API from '@/api/payments';
import notyf from '@/plugins/notyf';

export default {
    name: 'PaymentsConfirmation',
    data() {
        return {
            tariff: {},
            form: {
                email: null,
                phone: null,
                description: null,
                verification: false
            },
            errors: {},
            paymentUrl: null,
        }
    },
    computed: {
        ...mapGetters({
            locale: 'lang/locale',
            userEmail: 'profile/userEmail'
        }),
        id() {
            return this.$route.params.id;
        }
    },
    methods: {
        /**
         * Получение данных по выбранному тарифному планы
         *
         * @returns {Promise<void>}
         */
        async getTariff() {
            this.setLoading(true);

            try {
                const data = await API.getTariff(this.id);

                if (data) {
                    this.tariff = data;
                    this.form.email = this.userEmail;
                }
            } finally {
                this.setLoading(false);
            }
        },

        /**
         * Отправка формы оплаты
         *
         * @returns {Promise<void>}
         */
        async onSubmit() {
            try {
                this.setLoading(true);

                const { url } = await API.submitPay(this.id, this.form);

                if (url) {
                    this.form = {};
                    this.paymentUrl = url;
                    this.errors = {};

                    setTimeout(() => {
                        window.open(url, '_blank').focus();
                    }, 5000);
                }
            } catch (error) {
                const response = error.response;

                if (response.status === 422) {
                    this.errors = response.data.errors;
                } else {
                    notyf.error(response.data.message);
                }
            } finally {
                this.setLoading(false);
            }
        }
    },
    mounted() {
        this.getTariff();
    }
}
</script>
