<template>
    <h2 class="title-secondary">Профиль</h2>

    <div class="panel">
        <AppLoader v-if="isMainLoading"/>

        <AppAlert
            v-else-if="isError"
            type="danger"
            :message="$t('whoops')"
        />

        <div v-else class="row">
            <div :class="['form-group col-12', { 'is-error': errors.name }]">
                <div class="form-group__label">Наименование</div>

                <div :class="['form-input-group', { 'is-error': errors.name }]">
                    <input class="input" v-model.trim="form.name" type="text">

                    <div class="form-input-group__append">
                        <select class="select" v-model="form.form">
                            <option
                                v-for="(value, key) in forms"
                                :value="key"
                                :key="key"
                            >{{ value }}
                            </option>
                        </select>
                    </div>
                </div>

                <div
                    v-if="errors.name"
                    class="form-group__error"
                >{{ errors.name[0] }}</div>
            </div>

            <div :class="['form-group col-12', { 'is-error': errors.tax_regime }]">
                <div class="form-group__label">Форма налогообложения</div>

                <div class="form-group__input">
                    <select class="select" v-model="form.tax_regime">
                        <option
                            v-for="(value, key) in taxRegime"
                            :value="key"
                            :key="key"
                        >{{ value }}
                        </option>
                    </select>
                </div>

                <div
                    v-if="errors.tax_regime"
                    class="form-group__error"
                >{{ errors.tax_regime[0] }}</div>
            </div>

            <div :class="['form-group col-6', { 'is-error': errors.currency }]">
                <div class="form-group__label">Валюта</div>

                <div class="form-group__input">
                    <select class="select" v-model="form.currency">
                        <option
                            v-for="(value, key) in currencies"
                            :key="key"
                            :value="key"
                        >{{ value }}
                        </option>
                    </select>
                </div>

                <div
                    v-if="errors.currency"
                    class="form-group__error"
                >{{ errors.currency[0] }}</div>
            </div>

            <div :class="['form-group col-6', { 'is-error': errors.nds }]">
                <div class="form-group__label">НДС</div>

                <div class="form-group__input">
                    <select
                        class="select"
                        v-model="form.nds"
                    >
                        <option value="0" selected>Нет</option>
                        <option value="1">Да</option>
                    </select>
                </div>

                <div
                    v-if="errors.nds"
                    class="form-group__error"
                >{{ errors.nds[0] }}</div>
            </div>

            <div :class="['form-group col-12', { 'is-error': errors.description }]">
                <div class="form-group__label">Описание</div>

                <div class="form-group__input">
                    <textarea
                        class="textarea"
                        style="min-height: 180px;"
                        v-model.trim="form.description"
                    >
                    </textarea>
                </div>

                <div
                    v-if="errors.description"
                    class="form-group__error"
                >{{ errors.description[0] }}</div>
            </div>

            <div class="btn-group col-12">
                <AppButtonLoader
                    :is-loading="isLoading"
                    modify="btn--w-150 ml-auto"
                    :method="onSubmit"
                >Сохранить
                </AppButtonLoader>
            </div>
        </div>
    </div>
</template>

<script>
import API from '@/api/company';
import {FORM_DATA} from '@/config/app';
import notyf from '@/plugins/notyf';

const {currencies, forms, company} = FORM_DATA;
const {taxRegime} = company;

export default {
    name: 'CompanyProfile',
    props: {
        companyUuid: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            isLoading: false,
            isMainLoading: false,
            isError: false,
            form: {
                name:        '',
                form:        0,
                currency:    0,
                tax_regime:  0,
                nds:         0,
                description: '',
            },
            errors: {},
            currencies,
            forms,
            taxRegime,
        }
    },
    methods: {
        /**
         * Получаем данные компании
         *
         * @returns {Promise<void>}
         */
        async getCompany() {
            this.isMainLoading = true;
            this.isError = false;

            try {
                const {
                    name,
                    form,
                    currency,
                    tax_regime,
                    nds,
                    description
                } = await API.getOne({uuid: this.companyUuid});

                this.form = {
                    name,
                    form,
                    currency,
                    tax_regime,
                    nds,
                    description
                };
            } catch (error) {
                this.isError = true;

                throw error;
            } finally {
                this.isMainLoading = false;
            }
        },

        /**
         * Обработчик
         *
         * @returns {Promise<void>}
         */
        async onSubmit() {
            this.isLoading = true;
            this.errors = {};

            try {
                const {uuid} = await API.save({
                    uuid: this.companyUuid,
                    ...this.form
                });

                if (uuid) {
                    notyf.success(this.$i18n.t('dataUpdated'));
                }
            } catch (error) {
                const {status, data} = error.response;

                if (status === 422) {
                    this.errors = data.errors;

                    return;
                }

                notyf.error(data.message);

                throw error;
            } finally {
                this.isLoading = false;
            }
        },
    },
    created() {
        this.getCompany();
    }
}
</script>
