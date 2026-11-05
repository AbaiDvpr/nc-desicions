<template>
    <div class="filter panel">
        <div class="filter__inner row">
            <div class="col-12 col-xl-3">
                <div class="form-group form-group--lg mb-0">
                    <select class="select" v-model="filter.form.operation">
                        <option value="">Все операции</option>

                        <option
                            v-for="(operation, key) in filter.fields.operationList"
                            :key="key"
                            :value="operation.value"
                        >{{ operation.title }}</option>
                    </select>
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="form-group form-group--lg mb-0">
                    <select class="select" v-model="filter.form.accountCash">
                        <option value="">Все поступления</option>

                        <option
                            v-for="(operation, key) in filter.fields.accountCashList"
                            :key="key"
                            :value="operation.value"
                        >{{ operation.title }}</option>
                    </select>
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="form-group form-group--lg mb-0">
                    <select class="select" v-model="filter.form.kindActivity">
                        <option value="">{{ $t('accounting.kindActivity') }}</option>

                        <option
                            v-for="(kindActivity, key) in filter.fields.kindActivityList"
                            :key="key"
                            :value="kindActivity.value"
                        >{{ kindActivity.title }}</option>
                    </select>
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <DatePicker
                    class="form-group form-group--lg mb-0"
                    v-model:value="filter.form.receiptDate"
                    format="YYYY/MM/DD"
                    value-type="YYYY-MM-DD"
                    type="date"
                    input-class="input"
                    :placeholder="$t('accounting.receiptDate')"
                ></DatePicker>
            </div>
        </div>
    </div>

    <AppLoader v-if="isMainLoading" />

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

    <template v-else>
        <div v-if="accountingList.length" class="custom-table" style="--cell-count: 5">
            <div class="custom-table__header">
                <div class="custom-table__cell custom-table__cell--icon">↑↓</div>

                <div class="custom-table__cell">{{ $t('accounting.article') }}</div>

                <div class="custom-table__cell">{{ $t('accounting.type') }}</div>

                <div class="custom-table__cell">{{ $t('accounting.kindActivity') }}</div>

                <div class="custom-table__cell">{{ $t('accounting.price') }}</div>

                <div class="custom-table__cell">{{ $t('accounting.receiptDate') }}</div>
            </div>

            <div class="custom-table__row" v-for="accounting in accountingList">
                <div class="custom-table__cell custom-table__cell--icon" data-label="">
                    <AppIcon
                        v-if="accounting.operation === 'outcome'"
                        :name="accounting.operation"
                        modify="icon--danger"
                    />

                    <AppIcon
                        v-else
                        :name="accounting.operation"
                        modify="icon--success"
                    />
                </div>

                <div class="custom-table__cell" :data-label="$t('accounting.article')">
                    {{ accounting.article ?? accounting.activeType }}
                </div>

                <div class="custom-table__cell" :data-label="$t('accounting.type')">
                    {{ accounting.type ?? accounting.active }}
                </div>

                <div class="custom-table__cell" :data-label="$t('accounting.kindActivity')">
                    {{ accounting.kindActivity }}
                </div>

                <div
                    :class="['custom-table__cell', `custom-table__cell--${accounting.operation}`]"
                    :data-label="$t('accounting.price')"
                >
                    <span v-if="accounting.operation === 'income'">
                        {{ numberFormat(accounting.price) }}
                    </span>

                    <span v-else-if="accounting.operation === 'outcome'">
                        -{{ numberFormat(accounting.price) }}
                    </span>

                    <span v-else>
                        {{ numberFormat(accounting.price) }}
                    </span>
                </div>

                <div class="custom-table__cell" :data-label="$t('accounting.receiptDate')">
                    {{ accounting.receiptDate }}
                </div>

                <div class="custom-table__cell custom-table__cell--controls" :data-label="$t('accounting.control')">
                    <div class="btn-group">
                        <button class="btn btn--danger btn--size-sm" @click="deleteItem(accounting)">
                            <AppIcon name="trash" />
                        </button>

                        <button class="btn btn--info btn--size-sm" @click="editItem(accounting)">
                            <AppIcon name="edit" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isShowLoadBtn" class="projects__more">
            <AppButtonLoader
                :isLoading="isLoading"
                :method="loadMore"
            >{{ $t('loadMore') }}</AppButtonLoader>
        </div>

        <AppModal
            name="modal-accounting-delete"
            :isLoading="isDeleteLoading"
            @close="hideModal"
            type="danger"
        >
            <template #header>
                <h2 class="modal__title">Удаление статьи</h2>
            </template>

            <template #content>
                <p><b>Вы действительно желаете удалить статью?</b></p>
                <p>{{ deletedAccounting.article }}</p>
            </template>

            <template #footer>
                <AppButtonLoader
                    :is-loading="isDeleteLoading"
                    modify="btn--w-150"
                    type="btn--danger"
                    :method="onDeleteAccounting"
                >Удалить</AppButtonLoader>
            </template>
        </AppModal>
    </template>
</template>

<script>
import DatePicker from '@/plugins/datePicker';
import ACCOUNTING from '@/api/v1/company/accounting';
import notyf from '@/plugins/notyf';
import { debounce } from 'lodash';
import { FORM_DATA } from '@/config/app';
import AppCustomSelect from '@/components/base/AppCustomSelect';

const { operationList, accountCashList, kindActivityList } = FORM_DATA.company;

/**
 * Интервал запроса на получение проектов
 *
 * @type {number}
 */
const SEARCH_TIMEOUT = 500;

/**
 * Поля по умолчанию формы фильтра
 *
 * @type {Object}
 */
const FILTER_FORM = {
    operation:    '',
    accountCash:  '',
    kindActivity: '',
    receiptDate:  '',
};

export default {
    name: 'Accounting',
    emits:['editItem'],
    components: {AppCustomSelect, DatePicker},
    props: {
        companyUuid: {
            type: String,
            required: true,
        },
        addedAccountingUuid: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            isEmpty:           false,
            isError:           false,
            isLoading:         false,
            isMainLoading:     false,
            isShowLoadBtn:     false,
            filter:            {
                fields: {
                    operationList:    operationList,
                    accountCashList:  accountCashList,
                    kindActivityList: kindActivityList,
                },
                form:   {...FILTER_FORM},
                params: {
                    page:  1,
                    limit: 10,
                },
            },
            accountingList:    [],
            isNewAccounting:   false,
            isDeleteLoading:   false,
            deletedAccounting: {},
        };
    },
    watch: {
        'addedAccountingUuid'(newValue) {
            if (newValue) {
                this.isNewAccounting = true;
                this.updateList();
            }
        },
        'filter.form': {
            handler: function () {
                if (!this.isNewAccounting) {
                    this.getDataByFilter();
                }
            },
            deep: true
        }
    },
    methods: {
        /**
         * Обновляем список после добавления новых записей
         */
        updateList() {
            this.filter.form = { ...FILTER_FORM };
            this.getList();
        },

        /**
         * Получает список ручного ввода
         *
         * @returns {Promise<void>}
         */
        async getList() {
            this.isMainLoading = true;
            this.isError       = false;

            try {
                const { data } = await ACCOUNTING.getList(this.companyUuid, {
                    ...this.filter.params,
                    ...this.filter.form
                });

                this.accountingList = data;
                this.isEmpty        = !Boolean(data.length);
                this.isShowLoadBtn  = data.length >= this.filter.params.limit;

                if (this.addedAccountingUuid && !this.isNewAccounting) {
                    notyf.success(this.$i18n.t('dataLoaded'));
                }

                this.isNewAccounting = false;
            } catch (error) {
                this.isError = true;

                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isMainLoading = false;
            }
        },

        /**
         * Возвращает отфильтрованные проекты
         *
         * @return {void}
         */
        getDataByFilter: debounce(async function() {
            this.isLoading          = true;
            this.filter.params.page = 1;

            try {
                const { data } = await ACCOUNTING.getList(this.companyUuid, {
                    ...this.filter.params,
                    ...this.filter.form
                });

                this.accountingList = data;
                this.isShowLoadBtn  = data.length >= this.filter.params.limit;

                if (!Boolean(data.length)) {
                    notyf.open({
                        type: 'info',
                        message: 'Нет данных по выбранным параметрам',
                        icon: false
                    });
                } else {
                    notyf.success(this.$i18n.t('dataLoaded'));
                }
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isLoading = false;
            }
        }, SEARCH_TIMEOUT),

        /**
         * Подгружает проекты
         *
         * @return {Promise<void>}
         */
        async loadMore() {
            this.isLoading = true;
            this.filter.params.page += 1;

            try {
                const { data } = await ACCOUNTING.getList(this.companyUuid, {
                    ...this.filter.params,
                    ...this.filter.form
                });

                this.accountingList = [...this.accountingList, ...data];
                this.isShowLoadBtn  = data.length >= this.filter.params.limit;

                if (data.length) {
                    notyf.success(this.$i18n.t('dataLoaded'));
                }
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Отправляет событие на открытие формы редактирования
         *
         * @param id
         * @param operation
         */
        editItem({id, operation}) {
            this.$emit('editItem', {id, operation});
        },

        /**
         * Открытие формы удаления статьи
         *
         * @param  accounting
         * @return {void}
         */
        deleteItem(accounting = {}) {
            this.deletedAccounting = accounting;
            this.showModal('modal-accounting-delete');
        },

        /**
         * Отправляет запрос на удаление статьи
         *
         * @return {Promise<void>}
         */
        async onDeleteAccounting() {
            this.isDeleteLoading = true;

            try {
                const { status } = await ACCOUNTING.deleteItem({
                    uuid: this.deletedAccounting.id,
                    companyUuid: this.companyUuid
                });

                if (status) {
                    this.deletedAccounting = {};
                    this.hideModal();
                    notyf.success('Запись успешно удалена');
                    this.filter.form = { ...FILTER_FORM };
                }

            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isDeleteLoading = false;
            }
        }
    },
    created() {
        this.getList();
    }
}
</script>
