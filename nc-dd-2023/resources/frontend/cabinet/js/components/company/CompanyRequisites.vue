<template>
    <div class="title-wrap mb-0">
        <h2 class="title-secondary">Реквизиты</h2>

        <button
            v-if="forms.length < MAX_REQUISITES"
            class="btn btn--primary btn--size-md ml-auto"
            @click.stop="createRequisite"
        >Добавить</button>
    </div>

    <div class="panel">
        <AppLoader v-if="isMainLoading"/>

        <AppAlert
            v-else-if="isError"
            type="danger"
            :message="$t('whoops')"
        />

        <div class="tabs-nav-compact">
            <div
                v-for="({ id }, index) in forms"
                :class="['tabs-nav-compact__item', { 'is-active': id === currentRequisite }]"
                :key="index"
                @click="changeRequisite(id)"
            >Реквизит {{ id }}</div>
        </div>

        <div
            v-for="({ id, fields }) in forms"
            v-show="id === currentRequisite"
            :key="id"
            class="row"
        >
            <div :class="['form-group col-12', { 'is-error': errors.name }]">
                <div class="form-group__label">Наименование компании/физ лица</div>
                <input class="input" v-model.trim="fields.name" type="text">

                <div
                    v-if="errors.name"
                    class="form-group__error"
                >{{ errors.name[0] }}</div>
            </div>

            <div :class="['form-group col-12 col-md-6', { 'is-error': errors.legal_address }]">
                <div class="form-group__label">Юридический адрес</div>

                <input class="input" v-model.trim="fields.legal_address" type="text">

                <div
                    v-if="errors.legal_address"
                    class="form-group__error"
                >{{ errors.name[0] }}</div>
            </div>

            <div :class="['form-group col-12 col-md-6', { 'is-error': errors.actual_address }]">
                <div class="form-group__label">Фактический адрес</div>

                <input class="input" v-model.trim="fields.actual_address" type="text">

                <div
                    v-if="errors.actual_address"
                    class="form-group__error"
                >{{ errors.name[0] }}</div>
            </div>

            <div :class="['form-group col-12 col-xl-6', { 'is-error': errors.email }]">
                <div class="form-group__label">E-mail</div>

                <input class="input" v-model.trim="fields.email" type="text">

                <div
                    v-if="errors.email"
                    class="form-group__error"
                >{{ errors.name[0] }}</div>
            </div>

            <div :class="['form-group col-12 col-xl-6', { 'is-error': errors.phone }]">
                <div class="form-group__label">Телефон</div>

                <input class="input" v-model.trim="fields.phone" type="text">

                <div
                    v-if="errors.phone"
                    class="form-group__error"
                >{{ errors.name[0] }}</div>
            </div>

            <div :class="['form-group col-12 col-md-6', { 'is-error': errors.director }]">
                <div class="form-group__label">Директор</div>

                <input class="input" v-model.trim="fields.director" type="text">

                <div
                    v-if="errors.director"
                    class="form-group__error"
                >{{ errors.name[0] }}</div>
            </div>

            <div
                v-if="banks.length"
                :class="['form-group col-12 col-md-6', { 'is-error': errors.bankId }]">
                <div class="form-group__label">Банк</div>

                <div class="form-group__input">
                    <AppCustomSelect
                        :options="banks"
                        :has-filter="false"
                        :show-search="false"
                        v-model="fields.bankId"
                        :placeholder="'Укажите банк'"
                    />
                </div>

                <div
                    v-if="errors.bankId"
                    class="form-group__error"
                >{{ errors.bankId[0] }}</div>
            </div>

            <div :class="['form-group col-12 col-xl-4', { 'is-error': errors.bin }]">
                <div class="form-group__label">БИН / ИИН</div>

                <input class="input" v-model.trim="fields.bin" type="text">

                <div
                    v-if="errors.bin"
                    class="form-group__error"
                >{{ errors.name[0] }}</div>
            </div>

            <div :class="['form-group col-12 col-xl-4', { 'is-error': errors.iik }]">
                <div class="form-group__label">ИИК</div>

                <input class="input" v-model.trim="fields.iik" type="text">

                <div
                    v-if="errors.iik"
                    class="form-group__error"
                >{{ errors.name[0] }}</div>
            </div>

            <div :class="['form-group col-12 col-xl-4', { 'is-error': errors.bik }]">
                <div class="form-group__label">БИК</div>

                <input class="input" v-model.trim="fields.bik" disabled type="text">

                <div
                    v-if="errors.bik"
                    class="form-group__error"
                >{{ errors.name[0] }}</div>
            </div>

            <div class="btn-group col-12">
                <AppButton
                    v-if="forms.length"
                    type="btn--outline-danger btn--w-150"
                    @click.stop="showModal('modal-requisite-delete')"
                >Удалить</AppButton>

                <AppButtonLoader
                    :is-loading="isLoading"
                    modify="btn--w-150 ml-auto"
                    :method="onSubmitRequisite"
                >Сохранить</AppButtonLoader>
            </div>
        </div>

        <AppModal
            name="modal-requisite-delete"
            :is-loading="isDeleteLoading"
            @close="hideModal"
            type="danger"
        >
            <template #header>
                <h2 class="modal__title">Удаление реквизита</h2>
            </template>

            <template #content>
                <div class="modal__content-wrap">
                    <AppIcon name="warning" />

                    <div class="modal__text">
                        <span class="modal__subtitle">
                            Вы уверены что хотите удалить данный реквизит?
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
                        :method="onDeleteRequisite"
                    >Удалить</AppButtonLoader>
                </div>
            </template>
        </AppModal>
    </div>
</template>

<script>
import API from '@/api/company';
import notyf from '@/plugins/notyf';
import API_BANKS from '@/api/v1/handbook/bank';
import AppCustomSelect from '@/components/base/AppCustomSelect';

/**
 *  Максимальное количество реквизитов
 *
 * @type {number}
 */
const MAX_REQUISITES = 5;

export default {
    name: 'CompanyRequisites',
    components: {AppCustomSelect},
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
            currentRequisite: 1,
            MAX_REQUISITES,
            banks: [],
            forms: [
                {
                    id: 1,
                    fields: {
                        name: 'Реквизит 1',
                        legal_address: 'Адрес 1',
                        actual_address: 'Адрес 1 1',
                        email: 'some1@mail.ru',
                        phone: '79816906455',
                        director: 'Директор 1',
                        bank: 'Банк 1',
                        bin: '123',
                        iik: '321',
                        bik: '231'
                    }
                },
                {
                    id: 2,
                    fields: {
                        name: 'Реквизит 2',
                        legal_address: 'Адрес 1',
                        actual_address: 'Адрес 1 1',
                        email: 'some1@mail.ru',
                        phone: '79816906455',
                        director: 'Директор 1',
                        bank: 'Банк 1',
                        bin: '123',
                        iik: '321',
                        bik: '231'
                    }
                },
            ],
            errors: {}
        }
    },
    methods: {
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
         * Обработчик
         *
         * @returns {Promise<void>}
         */
        onSubmitRequisite() {
            console.log(this.forms);
        },

        /**
         * Переключение реквизитов
         *
         * @param  {number} id
         * @return {void}
         */
        changeRequisite(id = 0) {
            if (id) {
                this.currentRequisite = id;

            } else {
                this.currentRequisite = this.forms.length ? this.forms[0].id : 0;
            }
        },

        /**
         * Создает реквизит
         *
         * @return {void}
         */
        createRequisite() {
            const newRequisite = {
                id: this.forms.length + 1,
                fields: {...this.forms[0].fields}
            };

            this.forms.push(newRequisite);
            this.currentRequisite = newRequisite.id;
        },

        /**
         * Удаляет реквизит
         *
         * @return {void}
         */
        onDeleteRequisite() {
            const index = this.forms.findIndex(({ id }) => id === this.currentRequisite);

            this.forms.splice(index, 1);
            this.currentRequisite = this.forms.length ? this.forms[0].id : 0;
            this.hideModal();
        }
    },
    created() {
        this.getBanks();
    }
}
</script>
