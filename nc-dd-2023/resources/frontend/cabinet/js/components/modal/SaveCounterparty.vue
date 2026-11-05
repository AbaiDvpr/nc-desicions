<template>
    <AppModal
        name="modal-form-counterparty"
        :is-loading="isLoading"
        @close="closeModal"
    >
        <template #header>
            <h2 class="modal__title">Форма контрагента</h2>
        </template>

        <template #content>
            <div class="row">
                <div :class="['form-group col-12', { 'is-error': formErrors.name }]">
                    <div class="form-group__label">Наименование компании/физ лица</div>

                    <input class="input" v-model.trim="form.name" type="text">

                    <div
                        v-if="formErrors.name"
                        class="form-group__error"
                    >{{ formErrors.name[0] }}</div>
                </div>

                <div :class="['form-group col-12 col-md-6', { 'is-error': formErrors.legalAddress }]">
                    <div class="form-group__label">Юридический адрес</div>

                    <input class="input" v-model.trim="form.legalAddress" type="text">

                    <div
                        v-if="formErrors.legalAddress"
                        class="form-group__error"
                    >{{ formErrors.legalAddress[0] }}</div>
                </div>

                <div :class="['form-group col-12 col-md-6', { 'is-error': formErrors.actualAddress }]">
                    <div class="form-group__label">Фактический адрес</div>

                    <input class="input" v-model.trim="form.actualAddress" type="text">

                    <div
                        v-if="formErrors.actualAddress"
                        class="form-group__error"
                    >{{ formErrors.actualAddress[0] }}</div>
                </div>

                <div :class="['form-group col-12 col-xl-6', { 'is-error': formErrors.email }]">
                    <div class="form-group__label">E-mail</div>

                    <input class="input" v-model.trim="form.email" type="text">

                    <div
                        v-if="formErrors.email"
                        class="form-group__error"
                    >{{ formErrors.email[0] }}</div>
                </div>

                <div :class="['form-group col-12 col-xl-6', { 'is-error': formErrors.phone }]">
                    <div class="form-group__label">Телефон</div>

                    <input
                        v-model.trim="form.phone"
                        class="input"
                        type="text"
                        v-maska data-maska="+7 (###) ###-##-##"
                    >

                    <div
                        v-if="formErrors.phone"
                        class="form-group__error"
                    >{{ formErrors.phone[0] }}</div>
                </div>

                <div :class="['form-group col-12 col-md-6', { 'is-error': formErrors.director }]">
                    <div class="form-group__label">Директор</div>

                    <input class="input" v-model.trim="form.director" type="text">

                    <div
                        v-if="formErrors.director"
                        class="form-group__error"
                    >{{ formErrors.director[0] }}</div>
                </div>

                <div
                    v-if="banks.length"
                    :class="['form-group col-12 col-md-6', { 'is-error': formErrors.bankId }]"
                >
                    <div class="form-group__label">Банк</div>

                    <div class="form-group__input">
                        <AppCustomSelect
                            :options="banks"
                            :has-filter="false"
                            :show-search="false"
                            v-model="form.bankId"
                            placeholder="Выберите банк"
                        />
                    </div>

                    <div
                        v-if="formErrors.bankId"
                        class="form-group__error"
                    >{{ formErrors.bankId[0] }}</div>
                </div>

                <div :class="['form-group col-12 col-xl-4', { 'is-error': formErrors.bin }]">
                    <div class="form-group__label">БИН / ИИН</div>

                    <input class="input" v-model.trim="form.bin" type="text">

                    <div
                        v-if="formErrors.bin"
                        class="form-group__error"
                    >{{ formErrors.bin[0] }}</div>
                </div>

                <div :class="['form-group col-12 col-xl-4', { 'is-error': formErrors.iik }]">
                    <div class="form-group__label">ИИК</div>

                    <input class="input" v-model.trim="form.iik" type="text">

                    <div
                        v-if="formErrors.iik"
                        class="form-group__error"
                    >{{ formErrors.iik[0] }}</div>
                </div>

                <div :class="['form-group col-12 col-xl-4', { 'is-error': formErrors.bik }]">
                    <div class="form-group__label">БИК</div>

                    <input class="input" v-model.trim="form.bik" disabled type="text">

                    <div
                        v-if="formErrors.bik"
                        class="form-group__error"
                    >{{ formErrors.bik[0] }}</div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="btn-group btn-group--end">
                <AppButtonLoader
                    :is-loading="isLoading"
                    modify="btn--w-150"
                    :method="save"
                >Сохранить</AppButtonLoader>
            </div>
        </template>
    </AppModal>
</template>

<script>
import API from '@/api/v1/counterparty';
import API_BANKS from '@/api/v1/handbook/bank';
import AppCustomSelect from '@/components/base/AppCustomSelect';
import notyf from '@/plugins/notyf';

/**
 * @type {Object}
 */
const DEFAULT_FIELDS = {
    name:           '',
    legalAddress:   '',
    actualAddress:  '',
    email:          '',
    phone:          '',
    director:       '',
    bankId:         '',
    bin:            '',
    iik:            '',
    bik:            '',
};

export default {
    name: 'SaveCounterparty',
    components: {AppCustomSelect},
    emits: ['update'],
    props: {
        counterparty: {
            type: Object,
            required: false,
            default: () => {}
        }
    },
    data() {
        return {
            isLoading: false,
            banks:      [],
            form:       {...DEFAULT_FIELDS},
            formErrors: {},
        }
    },
    watch: {
        'form.bankId': function(value) {
            this.getBank(value);
        },
        'counterparty': function(value) {
            if (Object.keys(value).length) {
                this.form = {...this.counterparty};
            } else {
                this.form = {...DEFAULT_FIELDS};
            }
        }
    },
    methods: {
        /**
         * Создает или обновляет существующую запись
         *
         * @returns {Promise<void>}
         */
        async save() {
            this.isLoading = true;
            this.formErrors = {};

            try {
                const { id } = await API.save(this.form);

                notyf.success(this.$i18n.t('dataSaved'));

                this.$emit('update', {
                    id,
                    name: this.form.name
                });
                this.closeModal();
            } catch (error) {
                const { status, data } = error.response;

                if (status === 422) {
                    this.formErrors = data.errors;

                    return;
                }

                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Получение банков для справочника
         *
         * @returns {Promise<void>}
         */
        async getBank(uuid) {
            try {
                const { bik } = await API_BANKS.getOne(uuid);

                this.form.bik = bik;
            } catch (error) {
                notyf.error(this.$i18n.t('whoopsBank'));

                throw error;
            }
        },

        /**
         * Получение списка банков для справочника
         *
         * @returns {Promise<void>}
         */
        async getBanks() {
            try {
                this.banks = await API_BANKS.getBanks();
            } catch (error) {
                notyf.error(this.$i18n.t('whoopsBanks'));

                throw error;
            }
        },

        /**
         * Скрывает и очищает модальное окно
         *
         * @returns {void}
         */
        closeModal() {
            this.formErrors = {};
            this.form = {...DEFAULT_FIELDS};
            this.hideModal();
        }
    },
    created() {
        this.getBanks();
    }
};
</script>
