<template>
    <AppButtonLoader
        type="btn--info"
        modify="btn--size-md"
        :is-loading="isMainLoading"
        :method="init"
    >
        <template #icon>
            <AppIcon name="outcome" />
        </template>
        Расход
    </AppButtonLoader>

    <AppSidePopup
        name="popup-company-outcome"
        :isLoading="isLoading"
        @close="closePopup"
    >
        <template #header>
            <h2 class="side-popup__title">
                <template v-if="isEdit">
                    Расход
                </template>

                <template v-else>
                    Новый расход
                </template>
            </h2>
        </template>

        <template #content>
            <div class="content">
                <div class="row row--align-center">
                    <div :class="['col-12', { 'col-md-6': banks.length, 'col-md-5' : isTmz }]">
                        <div :class="['form-group', { 'is-error': errors.accountCash }]">
                            <div class="form-group__label">Со счета</div>
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

                    <div
                        v-if="banks.length"
                        class="col-12 col-md-6"
                    >
                        <div :class="['form-group', { 'is-error': errors.bankId }]">

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

                    <div v-if="isTmz" class="col-6 col-md-4">
                        <div class="switch-wrap switch-wrap--v-margin">
                            <span class="switch-wrap__item">Товар</span>

                            <div class="switch">
                                <input class="switch__input" type="checkbox" id="sl1">
                                <label class="switch__slider" for="sl1"></label>
                            </div>

                            <span class="switch-wrap__item">Услуга</span>
                        </div>
                    </div>

                    <div
                        v-if="isTmz"
                        class="col-6 col-md-3"
                    >
                        <div class="checkbox checkbox--v-margin">
                            <input class="checkbox__input" type="checkbox" id="debtPayment">

                            <label class="checkbox__label" for="debtPayment">Выплата долга</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div :class="['form-group', { 'is-error': errors.counterpartyId }]">
                            <div class="form-group__label">Укажите контрагента</div>

                            <div class="form-group__input">
                                <AppCustomSelect
                                    :options="counterparties"
                                    :has-filter="true"
                                    :show-search="true"
                                    v-model="form.counterpartyId"
                                    placeholder="Выберите контрагента"
                                >
                                    <template #button>
                                        <button
                                            @click="showModal('modal-form-counterparty')"
                                            class="btn btn--info btn--size-md"
                                        >Добавить контрагента</button>
                                    </template>
                                </AppCustomSelect>

                                <SaveCounterparty @update="addCounterparty" />
                            </div>

                            <div
                                v-if="errors.counterpartyId"
                                class="form-group__error"
                            >{{ errors.counterpartyId[0] }}</div>
                        </div>
                    </div>

                    <!-- Удалить -->
                    <div v-if="false" class="col-12 col-md-6">
                        <div :class="['form-group', { 'is-error': errors.type }]">

                            <div class="form-group__label">Укажите тип расходов</div>

                            <div class="form-group__input">
                                <AppCustomSelect
                                    :options="handbooks.typesList"
                                    :has-filter="false"
                                    :show-search="false"
                                    v-model="form.type"
                                />
                            </div>

                            <div
                                v-if="errors.type"
                                class="form-group__error"
                            >{{ errors.type[0] }}</div>
                        </div>
                    </div>

                    <div
                        v-if="!isTmz"
                        class="col-12"
                    >
                        <div :class="['form-group', { 'is-error': errors.kindActivity }]">

                            <div class="form-group__label">Вид деятельности</div>

                            <div class="form-group__input">
                                <AppCustomSelect
                                    :options="handbooks.kindActivityList"
                                    :has-filter="false"
                                    :show-search="false"
                                    v-model="form.kindActivity"
                                />
                            </div>

                            <div
                                v-if="errors.kindActivity"
                                class="form-group__error"
                            >{{ errors.kindActivity[0] }}</div>
                        </div>
                    </div>

                    <div
                        v-if="isOperatingActivity"
                         class="col-12 col-md-6"
                    >
                        <div :class="['form-group', { 'is-error': errors.operatingActivity }]">

                            <div class="form-group__label">Вид операционной деятельности</div>

                            <div class="form-group__input">
                                <AppCustomSelect
                                    :options="operatingActivityList"
                                    :has-filter="false"
                                    :show-search="false"
                                    @add-option=""
                                    v-model="form.operatingActivity"
                                    :placeholder="'Выберите вид операционной деятельности'"
                                />
                            </div>

                            <div
                                v-if="errors.operatingActivity"
                                class="form-group__error"
                            >{{ errors.operatingActivity[0] }}</div>
                        </div>
                    </div>

                    <div
                        v-if="isOperatingActivity"
                        class="col-12 col-md-6">
                        <div :class="['form-group', { 'is-error': errors.salary }]">

                            <div class="form-group__label">Salary</div>

                            <div class="form-group__input">
                                <AppCustomSelect
                                    :options="salaryList"
                                    :has-filter="false"
                                    :show-search="false"
                                    @add-option=""
                                    v-model="form.salary"
                                    :placeholder="'Введите salary'"
                                />
                            </div>

                            <div
                                v-if="errors.salary"
                                class="form-group__error"
                            >{{ errors.salary[0] }}</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div :class="['form-group', { 'is-error': errors.articleId }]">

                            <div class="form-group__label">Укажите статью расходов</div>

                            <div class="form-group__input">
                                <AppCustomSelect
                                    :options="articles"
                                    :has-filter="true"
                                    :show-search="true"
                                    @add-option="addArticle"
                                    v-model="form.articleId"
                                    :placeholder="'Укажите статью расходов'"
                                />
                            </div>

                            <div
                                v-if="errors.articleId"
                                class="form-group__error"
                            >{{ errors.articleId[0] }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div :class="['form-group', { 'is-error': errors.price }]">

                            <div class="form-group__label">Сумма</div>

                            <div class="form-input-group">
                                <VueNumber
                                    v-model="form.price"
                                    v-bind="numberConfig"
                                    @input="({ target }) => form.price = target.unmasked"
                                    placeholder="100 000"
                                />

                                <div
                                    v-if="isOperatingActivity"
                                    class="form-input-group__append"
                                >
                                    <select class="select">
                                        <option value="шт">шт</option>
                                        <option value="ед">ед</option>
                                        <option value="кг">кг</option>
                                    </select>
                                </div>
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
                <template v-if="isEdit">Сохранить</template>

                <template v-else>Добавить расход</template>
            </AppButtonLoader>
        </template>
    </AppSidePopup>
</template>

<script>
import API_COMMON from '@/api/v1/handbook/common';
import API_BANKS from '@/api/v1/handbook/bank';
import API_ARTICLE from '@/api/v1/handbook/article';
import API_DIRECTION from '@/api/v1/handbook/direction';
import API_COUNTERPARTY from '@/api/v1/counterparty';
import ACCOUNTING from '@/api/v1/company/accounting';
import DatePicker from '@/plugins/datePicker';
import {FORM_DATA} from '@/config/app';
import notyf from '@/plugins/notyf';
import AppCustomSelect from '@/components/base/AppCustomSelect';
import {convertOptionsForSelect} from '@/helpers/helpers';
import {getFormatDate} from '@/plugins/dayjs';
import { numberConfig } from '@/plugins/numberFormat';
import AppSidePopup from '@/components/base/AppSidePopup';
import SaveCounterparty from '@/components/modal/SaveCounterparty';

/**
 * Списки дефолтных полей
 */
const { accountCashList, kindActivityList } = FORM_DATA.company;

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
    operation:    'outcome',
    accountCash:  1,
    bankId:       null,
    kindActivity: 1,
    operatingActivity: 1,
    salary: 1,
    tmzService: 1,
    type:         1,
    articleId:    null,
    price:        '',
    currency:     CURRENCY_KZT,
    receiptDate:  null,
    dealDate:     null,
    counterpartyId: null
};

export default {
    name: 'CompanyOutcomePopup',
    emits: ['update', 'close'],
    components: {
        SaveCounterparty,
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
                kindActivityList: kindActivityList,
                typesList:        [],
            },
            banks:         [],
            articles:      [],
            counterparties: [],

            // Удалить
            operatingActivityList: [
                {
                    value: 1,
                    title: "Постоянные / Административные"
                },
                {
                    value: 2,
                    title: "Переменные"
                }
            ],

            // Удалить
            salaryList: [
                {
                    value: 1,
                    title: 'Прочее'
                },
                {
                    value: 2,
                    title: 'ФОТ'
                }
            ],

            // Удалить
            tmzServiceList: [
                {
                    value: 1,
                    title: 'Товар'
                },
                {
                    value: 2,
                    title: 'Услуга'
                }
            ],

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

        isOutcome() {
            return this.accounting?.operation === 'outcome';
        },

        isTmz() {
            return this.form.accountCash == 3;
        },

        isOperatingActivity() {
            return this.form.accountCash !== 3 && this.form.kindActivity == 1
        },
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
        "accounting.id": function () {
            if (this.isOutcome) {
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
                this.handbooks.typesList     = await API_COMMON.getOutcomeTypes();
                this.handbooks.directionList = await API_DIRECTION.getList({companyUuid: this.companyUuid});
                this.articles                = await API_ARTICLE.getArticles();
                const counterparties         = await API_COUNTERPARTY.getList();

                this.counterparties = counterparties.map(({uuid, name}) => ({value: uuid, title: name}));

                if (this.isEdit) {
                    await this.getItem();
                }

                this.showSidePopup('popup-company-outcome');
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isMainLoading = false;
            }
        },

        /**
         * Добавляет контрагента
         *
         * @param id
         * @param name
         * @return {void}
         */
        addCounterparty({id, name}) {
            this.counterparties.unshift({
                value: id,
                title: name
            });
            this.form.counterpartyId = id;
        },

        /**
         * Добавляет статью в справочник если таковой не было найдено
         *
         * @param value
         * @returns {Promise<void>}
         */
        async addArticle(value) {
            try {
                const { data } = await API_ARTICLE.create({
                    title: value
                });

                this.articles.unshift(data);
                this.form.articleId = data.value;
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
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
                kindActivity,
                type,
                articleId,
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
                kindActivity,
                type,
                articleId,
                price,
                currency,
                receiptDate: getFormatDate(receiptDate, 'YYYY-MM-DD HH:mm'),
                dealDate: getFormatDate(dealDate, 'YYYY-MM-DD HH:mm'),
            };
        }
    }
}
</script>
