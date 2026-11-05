<template>
    <div class="filter panel">
        <div class="filter__inner row">
            <div class="col-12 col-xl-6 col-2xl-3">
                <div class="form-group form-group--lg mb-0">
                    <select class="select" v-model="params.status">
                        <option value="">{{ $t('status') }}</option>

                        <option
                            v-for="(value, key) in formFields.statuses"
                            :key="key"
                            :value="key"
                        >{{ value }}</option>
                    </select>
                </div>
            </div>

            <div class="col-12 col-xl-6 col-2xl-3">
                <div class="form-group form-group--lg mb-0">
                    <input
                        v-model.trim="params.name"
                        type="text"
                        :placeholder="$t('enterName')"
                        class="input"
                    >
                </div>
            </div>

            <div class="col-12 col-xl-6 col-2xl-3">
                <div class="form-group form-group--lg mb-0">
                    <select class="select" v-model="params.form">
                        <option value="">{{ $t('legalForm') }}</option>

                        <option
                            v-for="(value, key) in formFields.forms"
                            :key="key"
                            :value="key"
                        >{{ value }}</option>
                    </select>
                </div>
            </div>

            <div class="col-12 col-xl-6 col-2xl-3">
                <div class="form-group form-group--lg mb-0">
                    <select class="select" v-model="params.currency">
                        <option value="">{{ $t('currency') }}</option>

                        <option
                            v-for="(value, key) in formFields.currencies"
                            :key="key"
                            :value="key"
                        >{{ value }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { FORM_DATA } from '@/config/app';

const { currencies, forms, projectStatuses: statuses } = FORM_DATA;

/**
 * Минимальное количество вводимых символов в поле name
 *
 * @type {number}
 */
const NAME_LIMIT = 2;

export default {
    name: 'ProjectFilter',
    props: {
        params: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            formFields: {
                forms,
                statuses,
                currencies
            }
        }
    },
    methods: {
        /**
         * Обработчик
         *
         * @return {void}
         */
        onSubmit() {
            this.$emit('submit');
        }
    },
    watch: {
        'params.name'(newValue) {
            if (newValue.length > NAME_LIMIT || newValue.length === 0) {
                this.onSubmit();
            }
        },
        'params.status'() {
            this.onSubmit();
        },
        'params.form'() {
            this.onSubmit();
        },
        'params.currency'() {
            this.onSubmit();
        }
    }
}
</script>
