<template>
    <AppButton
        type="btn--primary"
        modify="btn--size-md"
        @click="showModal('modal-company-transfer')"
    >
        <AppIcon name="income" />
        Перевод
    </AppButton>

    <AppModal
        name="modal-company-transfer"
        :isLoading="isLoading"
        @close="hideModal"
    >
        <template #header>
            <h2 class="modal__title">Новый перевод</h2>
        </template>

        <template #content>
            <div class="content">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group" :class="{ 'is-error': '' }">
                            <DatePicker
                                class="form-group__input"
                                format="YYYY/MM"
                                value-type="YYYY-MM-DD"
                                type="month"
                                input-class="input"
                                :placeholder="['Дата перевода денег']"
                            ></DatePicker>

                            <div class="form-group__error"></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group" :class="{ 'is-error': '' }">
                            <div class="form-group__input">
                                <select class="select" name="" id="">
                                    <option value="">Со счета</option>
                                </select>
                            </div>

                            <div class="form-group__error"></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group" :class="{ 'is-error': '' }">
                            <div class="form-group__input">
                                <select class="select" name="" id="">
                                    <option value="">На счет</option>
                                </select>
                            </div>

                            <div class="form-group__error"></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group" :class="{ 'is-error': '' }">
                            <div class="form-group__input">
                                <input class="input" type="text" placeholder="Сумма, $">
                            </div>

                            <div class="form-group__error"></div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <AppButtonLoader
                    :is-loading="isLoading"
                    modify="btn--w-150"
                    :method="onSubmit"
                >Добавить перевод</AppButtonLoader>
        </template>
    </AppModal>
</template>

<script>
import API from '@/api/company';
import DatePicker from '@/plugins/datePicker';

export default {
    name: 'CompanyTransfer',
    components: {
        DatePicker
    },
    props: {
        uuid: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            isLoading: false,
            isError: false,
        }
    },
    methods: {
        /**
         * Обработчик
         *
         * @return {void}
         */
        onSubmit() {
            console.log('onSubmit');
        }
    }
}
</script>
