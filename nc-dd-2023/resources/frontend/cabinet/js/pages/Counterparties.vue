<template>
    <div class="title-wrap m-0">
        <h1 class="title-primary">{{ $t($route.meta.title) }}</h1>

        <button
            class="btn btn--primary ml-auto"
            @click="createItem"
        >Добавить</button>
    </div>

    <CounterpartyFilter
        :filter="filter"
        @submit="getDataByFilter"
    />

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

    <div v-else class="custom-table" style="--cell-count: 7;">
        <div class="custom-table__header">
            <div class="custom-table__cell">Компания/физ л.</div>

            <div class="custom-table__cell">Директор</div>

            <div class="custom-table__cell">Телефон</div>

            <div class="custom-table__cell">E-mail</div>

            <div class="custom-table__cell">БИН/ИИН</div>

            <div class="custom-table__cell">ИИК</div>

            <div class="custom-table__cell">Банк</div>

            <div class="custom-table__cell"></div>
        </div>

        <div
            v-for="(item, index) in this.counterparties"
            :key="index"
            class="custom-table__row"
        >
            <div  class="custom-table__cell">{{ item.name }}</div>

            <div  class="custom-table__cell">{{ item.director }}</div>

            <div  class="custom-table__cell">{{ item.phone }}</div>

            <div  class="custom-table__cell">{{ item.email}}</div>

            <div  class="custom-table__cell">{{ item.bin }}</div>

            <div  class="custom-table__cell">{{ item.iik }}</div>

            <div  class="custom-table__cell">{{ item.bankName }}</div>

            <div class="custom-table__cell custom-table__cell--controls">
                <div class="btn-group btn-group--end">
                    <button
                        class="btn btn--success btn--size-sm"
                        @click="editItem(item)"
                    >
                        <AppIcon name="edit" />
                    </button>

                    <button
                        class="btn btn--danger btn--size-sm"
                        @click="deleteItem(item)"
                    >
                        <AppIcon name="trash" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div v-if="isShowLoadBtn" class="text-center">
        <AppButtonLoader
            :isLoading="isMoreLoading"
            :method="loadMore"
        >{{ $t('loadMore') }}</AppButtonLoader>
    </div>

    <SaveCounterparty :counterparty="editCounterparty" @update="getList" />

    <AppModal
        name="modal-delete-counterparty"
        :is-loading="isDeleteLoading"
        @close="hideModal"
        type="danger"
    >
        <template #header>
            <h2 class="modal__title">Удаление контрагента</h2>
        </template>
        <template #content>
            <div class="modal__content-wrap">
                <AppIcon name="warning" />

                <div class="modal__text">
                    <span class="modal__subtitle">
                        Вы уверены что хотите удалить контрагента <strong>{{ deletedCounterparty.name }}</strong>?
                    </span>
                </div>
            </div>
        </template>
        <template #footer>
            <div class="btn-group btn-group--end">
                <AppButton
                    type="btn--outline-danger"
                    modify="btn--w-150"
                    :disabled="isDeleteLoading"
                    @click="hideModal"
                >Отменить</AppButton>

                <AppButtonLoader
                    type="btn--danger"
                    modify="btn--w-150"
                    :is-loading="isDeleteLoading"
                    :method="onDeleteItem"
                >Удалить</AppButtonLoader>
            </div>
        </template>
    </AppModal>
</template>

<script>
import API from '@/api/v1/counterparty';
import notyf from '@/plugins/notyf';
import SaveCounterparty from '@/components/modal/SaveCounterparty';
import {debounce} from 'lodash';
import CounterpartyFilter from '@/components/counterparty/CounterpartyFilter';

/**
 * @type {Object}
 */
const DEFAULT_FILTER = {
    page:   1,
    limit:  10,
    name:   '',
    email:  '',
    bin:    '',
    bankId: '',
}

/**
 * Интервал запроса на получение проектов
 *
 * @type {number}
 */
const SEARCH_TIMEOUT = 700;

export default {
    name: 'Counterparties',
    components: {CounterpartyFilter, SaveCounterparty},
    data() {
        return {
            isEmpty:             false,
            isError:             false,
            isLoading:           false,
            isMoreLoading:       false,
            isDeleteLoading:     false,
            isShowLoadBtn:       false,
            deletedCounterparty: {},
            editCounterparty:    {},
            counterparties:      [],
            filter:              {...DEFAULT_FILTER},
        };
    },
    watch: {
        'deletedCounterparty': {
            handler: function (value) {
                if (!Object.keys(value).length) {
                    this.getList();
                }
            },
            deep: true
        },
    },
    methods: {
        /**
         * Возваращает список записей
         *
         * @returns {Promise<void>}
         */
        async getList() {
            this.isLoading = true;
            this.isError   = false;
            this.filter    = {...DEFAULT_FILTER};

            try {
                this.counterparties = await API.getList();

                this.isEmpty = !Boolean(this.counterparties.length);
                this.isShowLoadBtn = this.counterparties.length >= this.filter.limit;
            } catch(error) {
                this.isError = true;

                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Подгружает список записей
         *
         * @returns {Promise<void>}
         */
        async loadMore() {
            this.isMoreLoading = true;
            this.filter.page++;

            try {
                const data = await API.getList(this.filter);

                this.counterparties = [...this.counterparties, ...data];
                this.isShowLoadBtn  = data.length >= this.filter.limit;
            } catch(error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isMoreLoading = false;
            }
        },

        /**
         * Возвращает отфильтрованные проекты
         *
         * @return {void}
         */
        getDataByFilter: debounce(async function() {
            this.isLoading = true;
            this.filter.page = 1;

            notyf.dismissAll();

            try {
                const data = await API.getList(this.filter);

                this.counterparties = data;
                this.isEmpty = !Boolean(data.length);
                this.isError = false;
                this.isShowLoadBtn = data.length >= this.filter.limit;

                if (this.isEmpty) {
                    this.message = this.$i18n.t('noData');
                }

                notyf.success(this.$i18n.t('dataLoaded'));
            } catch (error) {
                this.isError = true;

                throw error;
            } finally {
                this.isLoading = false;
            }
        }, SEARCH_TIMEOUT),

        /**
         * Открывает модальное окно создания записи
         *
         * @return {void}
         */
        createItem() {
            this.editCounterparty = {};
            this.showModal('modal-form-counterparty');
        },

        /**
         * Открывает модальное окно редактирования записи
         *
         * @param  counterparty
         * @return {void}
         */
        editItem(counterparty) {
            this.editCounterparty = {
                uuid:           counterparty.uuid,
                name:           counterparty.name,
                legalAddress:   counterparty.legalAddress,
                actualAddress:  counterparty.actualAddress,
                email:          counterparty.email,
                phone:          counterparty.phone,
                director:       counterparty.director,
                bankId:         counterparty.bankId,
                bin:            counterparty.bin,
                iik:            counterparty.iik,
                bik:            counterparty.bik
            };

            this.showModal('modal-form-counterparty');
        },

        /**
         * Открывает модальное окно удаления записи
         *
         * @param counterparty
         * @return {void}
         */
        deleteItem(counterparty) {
            this.deletedCounterparty = counterparty;
            this.showModal('modal-delete-counterparty');
        },

        /**
         * Удаляет запись
         *
         * @return {void}
         */
        async onDeleteItem() {
            this.isDeleteLoading = true;

            try {
                await API.deleteItem(this.deletedCounterparty.uuid);

                this.deletedCounterparty = {};
            } catch(error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isDeleteLoading = false;
                this.hideModal();
            }
        },
    },
    created() {
        this.getList();
    }
}
</script>
