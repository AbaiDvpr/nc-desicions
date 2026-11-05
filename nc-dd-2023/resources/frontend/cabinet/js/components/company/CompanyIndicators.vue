<template>
    <button
        class="btn btn--primary btn--size-md"
        @click.stop="toggle"
    >
        <AppIcon name="settings" />
    </button>

    <Teleport to="body">
        <div :class="['company-settings panel', { 'is-active': isShowSettings }]">
            <div class="company-settings__header">
                <h2 class="company-settings__title">Плановые показатели</h2>

                <p class="company-settings__subtitle">
                    Здесь Вы можете указать плановые показатели
                </p>

                <button
                    class="company-settings__close"
                    @click="toggle"
                >
                    <AppIcon name="close" />
                </button>
            </div>

            <AppLoader v-if="isLoading" />

            <AppAlert
                v-else-if="isError"
                type="danger"
                :message="$t('whoops')"
            />

            <template v-else>
                <div class="company-settings__tabs">
                    <button
                        v-for="({ id }, index) in indicators"
                        class="company-settings__tab"
                        :class="{ 'is-active': id === currentIndicator }"
                        :key="index"
                        @click="changeIndicator(id)"
                    >Конфиг {{ index + 1 }}</button>

                    <button
                        v-if="indicators.length < 5"
                        class="btn btn--primary company-settings__btn"
                        @click.stop="createIndicator"
                    >Добавить</button>
                </div>

                <ul
                    v-for="({ id, fields }) in indicators"
                    v-show="id === currentIndicator"
                    class="company-settings__list"
                    :key="id"
                >
                    <li class="company-settings__list-item">
                        <div class="company-settings__list-item-text">
                            <span class="company-settings__list-item-title">Доля переменных затрат</span>
                            <span class="company-settings__list-item-subtitle">Себестоимость в выручке</span>
                        </div>

                        <VueNumber
                            v-model="fields.proportionVariableCosts"
                            v-bind="percentConfig"
                            @input="({ target }) => fields.proportionVariableCosts = target.unmasked"
                            placeholder="%"
                            class="company-settings__list-item-input"
                        />
                    </li>

                    <li class="company-settings__list-item">
                        <div class="company-settings__list-item-text">
                            <span class="company-settings__list-item-title">
                                Потребность в долгосрочных активах на тенге выручки
                            </span>

                            <span class="company-settings__list-item-subtitle">
                                Первоначальная стоимость долгосрочных активов конца периода к выручке периода
                            </span>
                        </div>

                        <VueNumber
                            v-model="fields.longTermAssets"
                            v-bind="percentConfig"
                            @input="({ target }) => fields.longTermAssets = target.unmasked"
                            placeholder="%"
                            class="company-settings__list-item-input"
                        />
                    </li>

                    <li class="company-settings__list-item">
                        <div class="company-settings__list-item-text">
                            <span class="company-settings__list-item-title">
                                Потребность в оборотном капитале на тенге выручки
                            </span>

                            <span class="company-settings__list-item-subtitle">
                                Оборотный капитал конца периода к выручке периода
                            </span>
                        </div>

                        <VueNumber
                            v-model="fields.workingCapital"
                            v-bind="percentConfig"
                            @input="({ target }) => fields.workingCapital = target.unmasked"
                            placeholder="%"
                            class="company-settings__list-item-input"
                        />
                    </li>

                    <li class="company-settings__list-item">
                        <div class="company-settings__list-item-text">
                            <span class="company-settings__list-item-title">
                                Потребность в денежных средства на тенге выручки
                            </span>

                            <span class="company-settings__list-item-subtitle">
                                Денежные средства конца периода к выручке периода
                            </span>
                        </div>

                        <VueNumber
                            v-model="fields.needCash"
                            v-bind="percentConfig"
                            @input="({ target }) => fields.needCash = target.unmasked"
                            placeholder="%"
                            class="company-settings__list-item-input"
                        />
                    </li>

                    <li class="company-settings__list-item">
                        <div class="company-settings__list-item-text">
                            <span class="company-settings__list-item-title">Процентная ставка по кредитам</span>
                            <span class="company-settings__list-item-subtitle">В % от объема кредита на конец периода</span>
                        </div>

                        <VueNumber
                            v-model="fields.interestRateOnLoans"
                            v-bind="percentConfig"
                            @input="({ target }) => fields.interestRateOnLoans = target.unmasked"
                            placeholder="%"
                            class="company-settings__list-item-input"
                        />
                    </li>

                    <li class="company-settings__list-item">
                        <div class="company-settings__list-item-text">
                            <span class="company-settings__list-item-title">Ставка налога на прибыль</span>
                        </div>

                        <VueNumber
                            v-model="fields.incomeTaxRate"
                            v-bind="percentConfig"
                            @input="({ target }) => fields.incomeTaxRate = target.unmasked"
                            placeholder="%"
                            class="company-settings__list-item-input"
                        />
                    </li>

                    <li class="company-settings__list-item">
                        <div class="company-settings__list-item-text">
                            <span class="company-settings__list-item-title">Коэффициент дивидендных выплат</span>
                        </div>

                        <VueNumber
                            v-model="fields.dividendPayoutRatio"
                            v-bind="percentConfig"
                            @input="({ target }) => fields.dividendPayoutRatio = target.unmasked"
                            placeholder="%"
                            class="company-settings__list-item-input"
                        />
                    </li>

                    <li class="company-settings__list-item">
                        <div class="company-settings__list-item-text">
                            <span class="company-settings__list-item-title">Индекс инфляции</span>
                            <span class="company-settings__list-item-subtitle">Темп прироста постоянных расходов</span>
                        </div>

                        <VueNumber
                            v-model="fields.inflationIndex"
                            v-bind="percentConfig"
                            @input="({ target }) => fields.inflationIndex = target.unmasked"
                            placeholder="%"
                            class="company-settings__list-item-input"
                        />
                    </li>

                    <li class="company-settings__list-item">
                        <div class="company-settings__list-item-text">
                            <span class="company-settings__list-item-title">Темп прироста выручки</span>
                            <span class="company-settings__list-item-subtitle">REV/REV (-1) - 1</span>
                        </div>

                        <VueNumber
                            v-model="fields.revenueGrowthRate"
                            v-bind="percentConfig"
                            @input="({ target }) => fields.revenueGrowthRate = target.unmasked"
                            placeholder="%"
                            class="company-settings__list-item-input"
                        />
                    </li>

                    <li class="company-settings__list-item">
                        <div class="company-settings__list-item-text">
                            <span class="company-settings__list-item-title">
                                Объем привлекаемого/погашаемого кредита в n периоде
                            </span>
                        </div>

                        <VueNumber
                            v-model="fields.loanVolume"
                            v-bind="percentConfig"
                            @input="({ target }) => fields.loanVolume = target.unmasked"
                            placeholder="%"
                            class="company-settings__list-item-input"
                        />
                    </li>

                    <li class="company-settings__list-item">
                        <div class="company-settings__list-item-text">
                            <span class="company-settings__list-item-title">Приобретение Активов</span>
                        </div>

                        <VueNumber
                            v-model="fields.acquisitionAssets"
                            v-bind="numberConfig"
                            @input="({ target }) => fields.acquisitionAssets = target.unmasked"
                            class="company-settings__list-item-input"
                        />
                    </li>
                </ul>
            </template>

            <div v-if="indicators.length" class="company-settings__footer">
                <AppButton
                    type="btn--outline-danger"
                    @click.stop="showModal('modal-indicator-delete')"
                >Удалить конфигурацию</AppButton>

                <AppButton
                    type="btn--success"
                    @click.stop="onSaveIndicator"
                >Применить настройки</AppButton>
            </div>
        </div>
    </Teleport>

    <AppModal
        name="modal-indicator-delete"
        :is-loading="isDeleteLoading"
        @close="hideModal"
        type="danger"
    >
        <template #header>
            <h2 class="modal__title">Удаление планового показателя</h2>
        </template>

        <template #content>
            <div class="modal__content-wrap">
                <AppIcon name="warning" />

                <div class="modal__text">
                    <span class="modal__subtitle">
                        Вы уверены что хотите удалить данный плановый показатель?
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
                    :method="onDeleteIndicator"
                >Удалить</AppButtonLoader>
            </div>
        </template>
    </AppModal>
</template>

<script>
import { mapGetters } from 'vuex';
import { numberConfig, percentConfig } from '@/plugins/numberFormat';

export default {
    name: 'CompanyIndicators',
    props: {
        companyUuid: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            currentIndicator: 0,
            isShowSettings: false,
            numberConfig,
            percentConfig
        }
    },
    computed: {
        ...mapGetters({
            indicators:      'companyIndicators/indicators',
            isLoading:       'companyIndicators/isLoading',
            isError:         'companyIndicators/isError',
            isDeleteLoading: 'companyIndicators/isDeleteLoading',
        }),
    },
    methods: {
        /**
         * Скрывает/показывает панель
         *
         * @return {void}
         */
        toggle() {
            this.isShowSettings = !this.isShowSettings;
        },

        /**
         * Переключение показателей
         *
         * @param  {number} id
         * @return {void}
         */
        changeIndicator(id = 0) {
            if (id) {
                this.currentIndicator = id;
            } else {
                this.currentIndicator = this.indicators.length ? this.indicators[0].id : 0;
            }
        },

        /**
         * Создает показатель
         *
         * @return {void}
         */
        createIndicator() {
            this.$store
                .dispatch('companyIndicators/create', { companyUuid: this.companyUuid })
                .then(indicator => {
                    if (indicator) this.currentIndicator = indicator.id;
                });
        },

        /**
         * Обновляет показатель
         *
         * @return {void}
         */
        onSaveIndicator() {
            const id = this.currentIndicator;
            const index = this.indicators.findIndex(item => item.id === id);

            this.$store.dispatch('companyIndicators/save', {
                id,
                form: {
                    companyUuid: this.companyUuid,
                    ...this.indicators[index].fields
                }
            });
        },

        /**
         * Удаляет показатель
         *
         * @return {void}
         */
        onDeleteIndicator() {
            const id = this.currentIndicator;

            this.$store
                .dispatch('companyIndicators/delete', { id, companyUuid: this.companyUuid })
                .then(() => {
                    this.changeIndicator();
                    this.hideModal();
                });
        },
    },
    created() {
        this.$store
            .dispatch('companyIndicators/getList', { companyUuid: this.companyUuid })
            .then(() => this.changeIndicator());
    }
}
</script>
