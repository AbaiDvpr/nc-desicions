<template>
    <div class="filter panel">
        <div class="filter__inner row">
            <div class="col-12 col-xl-3">
                <div class="form-group form-group--lg mb-0">
                    <input
                        type="text"
                        class="input"
                        v-model="filter.name"
                        placeholder="Компания/физ лицо"
                    >
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="form-group form-group--lg mb-0">
                    <input
                        type="text"
                        class="input"
                        v-model="filter.email"
                        placeholder="E-mail"
                    >
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="form-group form-group--lg mb-0">
                    <input
                        type="text"
                        class="input"
                        v-model="filter.bin"
                        placeholder="БИН/ИИН"
                    >
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="form-group form-group--lg mb-0">
                    <AppCustomSelect
                        :options="banks"
                        :has-filter="false"
                        :show-search="false"
                        v-model="filter.bankId"
                        placeholder="Выберите банк"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import AppCustomSelect from '@/components/base/AppCustomSelect';
import API_BANKS from '@/api/v1/handbook/bank';

/**
 * Минимальное количество вводимых символов в поле name
 *
 * @type {number}
 */
const MIN_LENGTH = 2;

/**
 * Компонент фильтра контрагентов
 */
export default {
    name: "CounterpartyFilter",
    components: {AppCustomSelect},
    props: {
        filter: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            banks: [],
        };
    },
    watch: {
        'filter.name'(newValue) {
            if (newValue.length > MIN_LENGTH || newValue.length === 0) {
                this.onSubmit();
            }
        },
        'filter.email'(newValue) {
            if (newValue.length > MIN_LENGTH || newValue.length === 0) {
                this.onSubmit();
            }
        },
        'filter.bin'(newValue) {
            if (newValue.length > MIN_LENGTH || newValue.length === 0) {
                this.onSubmit();
            }
        },
        'filter.bankId'() {
            this.onSubmit();
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
         * Отправляет событие на срабатывание фильтра
         *
         * @return {void}
         */
        onSubmit() {
            this.$emit('submit');
        },
    },
    created() {
        this.getBanks();
    }
};
</script>
