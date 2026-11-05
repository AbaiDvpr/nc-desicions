<template>
    <AppButton
        @click="showModal('modal-add-company')"
        modify="ml-auto"
    >Создать компанию</AppButton>

    <AppModal
        name="modal-add-company"
        :is-loading="isLoading"
        @close="hideModal"
    >
        <template #header>
            <h2 class="modal__title">Новая компания</h2>
        </template>

        <template #content>
            <div class="row">
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
                                >{{ value }}</option>
                            </select>
                        </div>
                    </div>

                    <div
                        v-if="errors.name"
                        class="form-group__error"
                    >{{ errors.name[0] }}</div>
                </div>

                <div :class="['form-group col-12 col-md-6', { 'is-error': errors.tax_regime }]">
                    <div class="form-group__label">Форма налогообложения</div>

                    <div class="form-group__input">
                        <select class="select" v-model="form.tax_regime">
                            <option
                                v-for="(value, key) in taxRegime"
                                :value="key"
                                :key="key"
                            >{{ value }}</option>
                        </select>
                    </div>

                    <div
                        v-if="errors.tax_regime"
                        class="form-group__error"
                    >{{ errors.tax_regime[0] }}</div>
                </div>

                <div :class="['form-group col-6 col-md-3', { 'is-error': errors.currency }]">
                    <div class="form-group__label">Валюта</div>

                    <div class="form-group__input">
                        <select class="select" v-model="form.currency">
                            <option
                                v-for="(value, key) in currencies"
                                :key="key"
                                :value="key"
                            >{{ value }}</option>
                        </select>
                    </div>

                    <div
                        v-if="errors.currency"
                        class="form-group__error"
                    >{{ errors.currency[0] }}</div>
                </div>

                <div :class="['form-group col-6 col-md-3', { 'is-error': errors.nds }]">
                    <div class="form-group__label">НДС</div>

                    <div class="form-group__input">
                        <select class="select" v-model="form.nds">
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
                        <textarea class="textarea" v-model.trim="form.description"></textarea>
                    </div>

                    <div
                        v-if="errors.description"
                        class="form-group__error"
                    >{{ errors.description[0] }}</div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="btn-group btn-group--end">
                <button
                    class="btn btn--outline-primary btn--w-150"
                    :disabled="isLoading"
                    @click="hideModal"
                >Отменить</button>

                <AppButtonLoader
                    :is-loading="isLoading"
                    modify="btn--w-150"
                    :method="onSubmit"
                >Создать</AppButtonLoader>
            </div>
        </template>
    </AppModal>
</template>

<script>
import API from '@/api/company';
import { FORM_DATA } from '@/config/app';
import notyf from '@/plugins/notyf';

const { currencies, forms, company } = FORM_DATA;
const { taxRegime } = company;

export default {
    name: 'CreateCompany',
    data() {
        return {
            isLoading: false,
            form: {
                name:        '',
                form:        Object.keys(forms)[0],
                currency:    Object.keys(currencies)[0],
                tax_regime:  Object.keys(taxRegime)[0],
                nds:         0,
                description: '',
            },
            currencies,
            forms,
            taxRegime,
            errors: {}
        }
    },
    methods: {
        /**
         * Обработчик
         *
         * @returns {Promise<void>}
         */
        async onSubmit() {
            this.isLoading = true;
            this.errors    = {};

            try {
                const { uuid } = await API.save({ ...this.form });

                if (uuid) {
                    this.hideModal();
                    this.$router.push({ name: 'companies.show', params: { uuid } });
                }
            } catch (error) {
                const { status, data } = error.response;

                if (status === 422) {
                    this.errors = data.errors;

                    return;
                }

                notyf.error(data.message);

                throw error;
            } finally {
                this.isLoading = false;
            }
        }
    }
}
</script>
