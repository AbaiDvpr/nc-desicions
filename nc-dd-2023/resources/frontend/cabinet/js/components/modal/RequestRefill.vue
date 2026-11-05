<template>
    <AppButton
        @click="showModal('modal-request-refill')"
        type="btn--success"
    >
        Пополнение
    </AppButton>

    <AppModal
        name="modal-request-refill"
        :is-loading="isLoading"
        @close="hideModal"
    >
        <template #header>
            <h2 class="modal__title">Запрос на пополнение</h2>
        </template>

        <template #content>
            <div class="formatted mb-16">
                <p>
                    Здесь вы можете оставить заявку на пополнение лицевого счёта посредством выставления счёта вам на <b>Kaspi.kz</b> по номеру телефона.
                </p>

                <p>
                    Укажите номер телефона к которому привязан счёт Kaspi.
                </p>
            </div>

            <div class="form-group" :class="{ 'is-error': errors.fullName }">
                <div class="form-group__label">ФИО</div>

                <div class="form-group__input">
                    <input class="input" v-model.trim="form.fullName" type="text">
                </div>

                <div
                    v-if="errors.fullName"
                    class="form-group__error"
                >{{ errors.fullName[0] }}</div>
            </div>

            <div class="form-group" :class="{ 'is-error': errors.phone }">
                <div class="form-group__label">Телефон</div>

                <div class="form-group__input">
                    <input class="input" v-model.trim="form.phone" type="text" v-maska data-maska="+7 (###) ###-##-##">
                </div>

                <div
                    v-if="errors.phone"
                    class="form-group__error"
                >{{ errors.phone[0] }}</div>
            </div>

            <div class="form-group" :class="{ 'is-error': errors.comment }">
                <div class="form-group__label">Комментарий</div>

                <div class="form-group__input">
                    <textarea class="textarea" v-model.trim="form.comment"></textarea>
                </div>

                <div
                    v-if="errors.comment"
                    class="form-group__error"
                >{{ errors.comment[0] }}</div>
            </div>
        </template>

        <template #footer>
            <div class="btn-group btn-group--end">
                <button
                    class="btn btn--outline-primary btn--w-150"
                    @click="hideModal"
                    :disabled="isLoading"
                >Отменить</button>

                <AppButtonLoader
                    :is-loading="isLoading"
                    modify="btn--w-150"
                    :method="onSubmit"
                >Добавить</AppButtonLoader>
            </div>
        </template>
    </AppModal>
</template>

<script>
import API from '@/api/payments';
import notyf from '@/plugins/notyf';

export default {
    name: 'RequestRefill',
    data() {
        return {
            isLoading: false,
            form: {
                fullName: '',
                phone:    '',
                comment:  '',
            },
            errors: {}
        }
    },
    methods: {
        /**
         * Обработчик запроса на пополнение кабинета
         *
         * @returns {Promise<void>}
         */
        async onSubmit() {
            this.isLoading = true;
            this.errors = {};

            try {
                const { success } = await API.requestRefill(this.form);

                this.hideModal();
                notyf.success(success);
            } catch (error) {
                this.errors = error.response.data.errors;
            } finally {
                this.isLoading = false;
            }
        }
    }
}
</script>
