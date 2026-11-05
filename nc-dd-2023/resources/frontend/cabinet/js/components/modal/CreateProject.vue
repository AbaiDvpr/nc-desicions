<template>
    <AppButtonLoader
        modify="ml-auto btn--w-150"
        :method="initialize"
        :isLoading="isMainLoading"
    >Рассчитать</AppButtonLoader>

    <AppModal
        name="modal-project-create"
        :is-loading="isLoading"
        @close="hideModal"
    >
        <template #header>
            <h2 class="modal__title">Рассчитать финансовую модель</h2>
        </template>

        <template #content>
            <p>
                Вы собираетесь воспользоваться нашим сервисом для расчета финансовой модели.
            </p>

            <template v-if="fullAccess">
                <p><b>Внимание: У вас безлимитный тариф</b></p>
            </template>

            <template v-else>
                <p>
                    В соответствии с выбранным вами тарифным планом <b>{{ tariff.name }}</b>,
                    у вас есть возможность произвести расчет <span v-html="counter"></span> финансовых моделей.
                    Более того, вам доступна возможность внести до <b>{{ project.correction }}</b>
                    изменений в создаваемую финансовую модель.
                </p>

                <p>
                    После нажатия на кнопку "Приступить" вы перейдете к процессу расчета вашей финансовой модели.
                    Мы хотели бы убедиться, что вы полностью уверены в своем решении и готовы начать.
                </p>

                <p>
                    <b>Внимание: После нажатия на кнопку "Приступить" будет израсходован один доступный расчёт.</b>
                </p>
            </template>
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
                >Приступить</AppButtonLoader>
            </div>
        </template>
    </AppModal>

    <AppModal
        name="modal-project-info"
        @close="hideModal"
    >
        <template #header>
            <h2 class="modal__title">Рассчитать финансовую модель</h2>
        </template>

        <template #content>
            <p v-if="tariff.isExpired">
                Срок действия вашего тарифа <b>{{ tariff.name }}</b> истек
                <b>{{ getFormatDate(tariff.expire, 'DD.MM.YYYY г., HH:mm') }}</b>.
            </p>

            <p v-else>
                Исчерпан лимит расчета финансовых моделей по тарифному плану <b>{{ tariff.name }}</b>.
            </p>

            <p>
                Для получения возможности расчета финансовых моделей рекомендуем
                переподключить текущий тарифный план или обновить его на более подходящий.
            </p>
        </template>

        <template #footer>
            <div class="btn-group btn-group--end">
                <router-link
                    class="btn btn--primary btn--w-150"
                    :to="{ name: 'tariffs' }"
                >Выбрать тариф</router-link>
            </div>
        </template>
    </AppModal>
</template>

<script>
import { mapGetters } from 'vuex';
import API from '@/api/projects';
import notyf from '@/plugins/notyf';
import { getFormatDate } from '@/plugins/dayjs';

export default {
    name: 'CreateProject',
    data() {
        return {
            isMainLoading:      false,
            isLoading:          false,
            isPossibleToCreate: false,
            projectsLeft:       0,
        }
    },
    computed: {
        ...mapGetters({
            tariff:     'profile/tariff',
            fullAccess: 'profile/fullAccess',
        }),

        /**
         * Ограничения по фин моделям
         *
         * @return {Object}
         */
        project() {
            return this.tariff.project;
        },

        /**
         * Количество доступных фин моделей
         *
         * @return {string}
         */
        counter() {
            return `<b>${this.projectsLeft}</b> из <b>${this.project.count}</b>`;
        }
    },
    methods: {
        getFormatDate,

        /**
         * Инициализация модального окна и проверка ограничений
         *
         * @return {Promise<void>}
         */
        async initialize() {
            this.isMainLoading = true;

            try {
                const { status, left } = await API.checkCreate();

                if (status) {
                    this.projectsLeft = left;
                    this.showModal('modal-project-create');
                } else {
                    this.showModal('modal-project-info');
                }
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isMainLoading = false;
            }
        },

        /**
         * Обработчик для создания проекта
         *
         * @returns {Promise<void>}
         */
        async onSubmit() {
            this.isLoading = true;

            try {
                const { uuid } = await API.create();

                if (uuid) {
                    this.hideModal();
                    this.$router.push({ name: 'models.show', params: { uuid } });
                }
            } catch (error) {
                notyf.error(error.response.data.message);

                throw error;
            } finally {
                this.isLoading = false;
            }
        }
    },
    unmounted() {
        this.hideModal();
    }
}
</script>
