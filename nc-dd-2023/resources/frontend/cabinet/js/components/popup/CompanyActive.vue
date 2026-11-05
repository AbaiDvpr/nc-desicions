<template>
    <AppButtonLoader
        type="btn--info"
        modify="btn--size-md"
        :is-loading="isMainLoading"
        :method="init"
    >
        <template #icon>
            <AppIcon name="swap" />
        </template>
        Актив / Пассив
    </AppButtonLoader>

    <AppSidePopup
        name="popup-company-active"
        :isLoading="isLoading"
        @close="closePopup"
    >
        <template #header>
            <h2 class="side-popup__title">
                Актив / Пассив
            </h2>
        </template>

        <template #content>
            <div class="content">
                <div class="row">
                    <div :class="['col-12', { 'col-md-6': banks.length }]">
                        <div :class="['form-group', { 'is-error': errors.accountCash }]">

                            <div class="form-group__label">Счета</div>

                            <div class="form-group__input">
                                <AppCustomSelect
                                    :options="handbooks.accountCashList"
                                    :has-filter="false"
                                    :show-search="false"
                                    v-model="form.accountCash"
                                />
                            </div>

                            <div
                                v-if="errors.accountCash"
                                class="form-group__error"
                            >{{ errors.accountCash[0] }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div
                            v-if="banks.length"
                            :class="['form-group', { 'is-error': errors.bankId }]"
                        >
                            <div class="form-group__label">Выберите банк</div>

                            <div class="form-group__input">
                                <AppCustomSelect
                                    :options="banks"
                                    :has-filter="false"
                                    :show-search="false"
                                    v-model="form.bankId"
                                    :placeholder="'Укажите банк проведения транзакции'"
                                />
                            </div>

                            <div
                                v-if="errors.bankId"
                                class="form-group__error"
                            >{{ errors.bankId[0] }}</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6">
                        <div :class="['form-group', { 'is-error': errors.direction }]">

                            <div class="form-group__label">Выберите направление деятельности</div>

                            <div class="form-group__input">
                                <AppCustomSelect
                                    :options="handbooks.directionList"
                                    :has-filter="true"
                                    :show-search="true"
                                    @add-option="addDirection"
                                    v-model="form.direction"
                                    :placeholder="'Укажите направление'"
                                />
                            </div>

                            <div
                                v-if="errors.direction"
                                class="form-group__error"
                            >{{ errors.direction[0] }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div :class="['form-group', { 'is-error': errors.active }]">

                            <div class="form-group__label">Актив / Пассив</div>

                            <div class="form-group__input">
                                <AppCustomSelect
                                    :options="handbooks.activeList"
                                    :has-filter="false"
                                    :show-search="false"
                                    v-model="form.active"
                                />
                            </div>

                            <div
                                v-if="errors.active"
                                class="form-group__error"
                            >{{ errors.active[0] }}</div>
                        </div>
                    </div>
                </div>

                <div :class="['form-group', { 'is-error': errors.activeType }]">

                    <div class="form-group__label">Статья актива / пассива</div>

                    <div class="form-group__input">
                        <AppCustomSelect
                            :options="handbooks.activeTypeList"
                            :show-search="false"
                            v-model="form.activeType"
                            placeholder="Выберите статью актива / пассива"
                        />
                    </div>

                    <div
                        v-if="errors.activeType"
                        class="form-group__error"
                    >{{ errors.activeType[0] }}</div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6">
                        <div :class="['form-group', { 'is-error': errors.price }]">

                            <div class="form-group__label">Сумма</div>

                            <div class="form-group__input">
                                <VueNumber
                                    v-model="form.price"
                                    v-bind="numberConfig"
                                    @input="({ target }) => form.price = target.unmasked"
                                    placeholder="100 000"
                                />
                            </div>

                            <div
                                v-if="errors.price"
                                class="form-group__error"
                            >{{ errors.price[0] }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div :class="['form-group', { 'is-error': errors.currency }]">

                            <div class="form-group__label">Валюта</div>

                            <div class="form-group__input">
                                <AppCustomSelect
                                    :options="currencies"
                                    :has-filter="false"
                                    :show-search="false"
                                    v-model="form.currency"
                                    :placeholder="'Выберите валюту'"
                                />
                            </div>

                            <div
                                v-if="errors.currency"
                                class="form-group__error"
                            >{{ errors.currency[0] }}</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6">
                        <div :class="['form-group', { 'is-error': errors.receiptDate }]">

                            <div class="form-group__label">Дата поступления денег</div>

                            <DatePicker
                                class="form-group__input"
                                v-model:value="form.receiptDate"
                                format="YYYY/MM/DD HH:mm"
                                value-type="YYYY-MM-DD HH:mm"
                                type="datetime"
                            >
                                <template v-slot:input >
                                    <input
                                        :value="form.receiptDate"
                                        name="date"
                                        type="text"
                                        autocomplete="off"
                                        class="input"
                                        v-maska
                                        data-maska="####/##/## ##:##"
                                        @input="event => form.receiptDate = event.target.value.replaceAll('/', '-')"
                                        placeholder="____/__/__ __:__"
                                    >
                                </template>
                            </DatePicker>

                            <div
                                v-if="errors.receiptDate"
                                class="form-group__error"
                            >{{ errors.receiptDate[0] }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div :class="['form-group', { 'is-error': errors.dealDate }]">

                            <div class="form-group__label">Дата сделки отличается</div>

                            <DatePicker
                                class="form-group__input"
                                v-model:value="form.dealDate"
                                format="YYYY/MM/DD HH:mm"
                                value-type="YYYY-MM-DD HH:mm"
                                type="datetime"
                            >
                                <template v-slot:input >
                                    <input
                                        :value="form.dealDate"
                                        name="date"
                                        type="text"
                                        autocomplete="off"
                                        class="input"
                                        v-maska
                                        data-maska="####/##/## ##:##"
                                        @input="event => form.dealDate = event.target.value.replaceAll('/', '-')"
                                        placeholder="____/__/__ __:__"
                                    >
                                </template>
                            </DatePicker>

                            <div
                                v-if="errors.dealDate"
                                class="form-group__error"
                            >{{ errors.dealDate[0] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <AppButtonLoader
                :is-loading="isLoading"
                modify="btn--w-150 ml-auto"
                :method="onSubmit"
            >
                Сохранить
            </AppButtonLoader>
        </template>
    </AppSidePopup>
</template>

<script>
import API_BANKS from '@/api/v1/handbook/bank';
import API_DIRECTION from '@/api/v1/handbook/direction';
import ACCOUNTING from '@/api/v1/company/accounting';
import DatePicker from '@/plugins/datePicker';
import {FORM_DATA} from '@/config/app';
import notyf from '@/plugins/notyf';
import AppCustomSelect from '@/components/base/AppCustomSelect';
import {convertOptionsForSelect} from '@/helpers/helpers';
import {getFormatDate} from '@/plugins/dayjs';
import { numberConfig } from '@/plugins/numberFormat';
import AppSidePopup from '@/components/base/AppSidePopup';

/**
 * Списки дефолтных полей
 */
const { accountCashList, activeList, activeTypeList } = FORM_DATA.company;

/**
 * Преобразованный список валют для селекта
 *
 * @type {{title: *, value: *}[]}
 */
const currencies = convertOptionsForSelect(FORM_DATA.currencies);

/**
 * Оплата банковским переводом
 *
 * @type {number}
 */
const IS_BANK = 2;

/**
 * Валюта по умолчанию KZT
 *
 * @type {string}
 */
const CURRENCY_KZT = 'kzt';

/**
 * Дефолтные параметры формы
 *
 * @type {Object}
 */
const FORM = {
    operation:    'active',
    accountCash:  1,
    bankId:       null,
    direction:    null,
    active:       1,
    activeType:   '',
    type:         0,
    articleId:    null,
    price:        '',
    currency:     CURRENCY_KZT,
    receiptDate:  null,
    dealDate:     null,
};

export default {
    name: 'CompanyActivePopup',
    emits: ['update', 'close'],
    components: {
        AppSidePopup,
        AppCustomSelect,
        DatePicker
    },
    props: {
        companyUuid: {
            type:     String,
            required: true,
        },
        accounting:  {
            type:     Object,
            required: false,
            default:  {
                operation: '',
                id:        '',
            },
        },
    },
    data() {
        return {
            isMainLoading: false,
            isLoading:     false,
            isError:       false,
            handbooks:     {
                accountCashList:  accountCashList,
                activeList:       activeList,
                activeTypeList:   activeTypeList[activeList[0]?.value],
                directionList:    [],
            },
            banks:         [],
            articles:      [],
            form:          {...FORM},
            errors:        {},
            currencies,
            numberConfig
        };
    },
    computed: {
        isEdit() {
            return Boolean(this.accounting?.id);
        },

        isActive() {
            return this.accounting?.operation === 'active';
        }
    },
    watch: {
        /**
         * Слушатель для состояния переменной accountCash
         *
         * @param newValue
         */
        "form.accountCash": function (newValue) {
            this.banks       = []
            this.form.bankId = null;

            if (newValue === IS_BANK) {
                this.getBanks();
            }
        },

        /**
         * Слушатель для состояния вида деятельности
         *
         * @param newValue
         */
        "form.active": function (newValue) {
            this.form.activeType          = '';
            this.handbooks.activeTypeList = activeTypeList[newValue];
        },

        "accounting.id": function () {
            if (this.isActive) {
                this.init();
            }
        },
    },
    methods: {
        /**
         * Инициализация и открытие формы
         *
         * @returns {Promise<void>}
         */
        async init() {
            this.isMainLoading = true;

            try {
                this.handbooks.directionList = await API_DIRECTION.getList({
                    companyUuid: this.companyUuid
                });

                if (this.isEdit) {
                    await this.getItem();
                }

                this.showSidePopup('popup-company-active');
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isMainLoading = false;
            }
        },

        /**
         * Добавляет направление компании в справочник если таковой не было найдено
         *
         * @param value
         * @returns {Promise<void>}
         */
        async addDirection(value) {
            try {
                const { data } = await API_DIRECTION.create({
                    companyUuid: this.companyUuid,
                    title:       value
                });

                this.handbooks.directionList.unshift(data);
                this.form.direction = data.value;
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            }
        },

        /**
         * Обработчик сохранения формы
         *
         * @returns {Promise<void>}
         */
        async onSubmit() {
            this.errors    = {};
            this.isLoading = true;

            try {
                const { id } = await ACCOUNTING.save({
                    ...(this.isEdit ? {uuid: this.accounting.id} : {}),
                    companyUuid: this.companyUuid,
                    ...this.form
                });

                this.$emit('update', id);

                notyf.success(this.$i18n.t('outcomeSuccess'));

                this.closePopup();
            } catch (error) {
                if (error.response.status === 422) {
                    this.errors = error.response.data.errors;

                    return;
                }

                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isLoading = false;
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
         * Событие для закрытия popup и очистки формы
         */
        closePopup() {
            this.form = {...FORM};
            this.$emit('close');
            this.hideSidePopup();
        },

        /**
         * Запрос на получение записи
         *
         * @returns {Promise<void>}
         */
        async getItem() {
            const {
                operation,
                accountCash,
                bankId,
                direction,
                active,
                type,
                activeType,
                price,
                currency,
                receiptDate,
                dealDate
            } = await ACCOUNTING.getOne({
                uuid:        this.accounting.id,
                companyUuid: this.companyUuid
            });

            this.form = {
                operation,
                accountCash,
                bankId,
                direction,
                active,
                type,
                activeType,
                price,
                currency,
                receiptDate: getFormatDate(receiptDate, 'YYYY-MM-DD HH:mm'),
                dealDate: getFormatDate(dealDate, 'YYYY-MM-DD HH:mm'),
            };
        }
    }
}
</script>
