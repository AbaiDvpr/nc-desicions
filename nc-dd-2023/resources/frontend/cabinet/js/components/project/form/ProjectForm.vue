<template>
    <div class="stepper">
        <form @submit="save($event)">
            <div class="stepper__header">
                <ul class="stepper__progress">
                    <li v-for="step in steps">
                        <a
                            :class="['stepper__progress-item', step === currentStep ? 'is-active is-disabled' : '', disableNextStep(step)]"
                            :id="'tab-step-' + step"
                            data-bs-toggle="tab"
                            @click.prevent="getFormByStep(step)"
                            >
                            <div class="stepper__progress-head">
                                <div class="stepper__progress-point">
                                    <AppIcon name="check" />
                                </div>

                                <div class="stepper__progress-line"></div>
                            </div>

                            <div class="stepper__progress-body">
                                <span class="stepper__progress-title">Шаг - {{ step }}</span>

                                <span class="stepper__progress-status">Статус</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="stepper__body">
                <div class="stepper__items">
                    <div class="stepper__item">
                        <div v-if="fields" class="row">
                            <div
                                v-for="(field, name) in fields"
                                :class="[field.column ? field.column: 'col-12']"
                            >
                                <component
                                    :is="getNameComponent(field.type)"
                                    :name="name"
                                    :errors="errors"
                                    :field="field"
                                ></component>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                :class="[
                    'stepper__footer',
                    currentStep !== 1 ? 'stepper__footer--between' : 'stepper__footer--end'
                ]"
            >
                <button
                    v-if="currentStep !== 1"
                    type="button"
                    class="btn btn--primary btn--w-150"
                    @click="prevStep($event)"
                ><span>Назад</span></button>

                <button type="submit" class="btn btn--primary btn--w-150">
                    <span v-if="currentStep !== steps">Вперед</span>

                    <span v-else>Завершить</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import axios from '@/api/axios';
import TextComponent from './inputs/TextComponent';
import SelectComponent from './inputs/SelectComponent';
import CalendarRangeComponent from './inputs/CalendarRangeComponent';
import CalendarComponent from './inputs/CalendarComponent';
import TurnoverComponent from './inputs/TurnoverComponent';
import OwnInvestmentComponent from './inputs/OwnInvestmentComponent';
import InvestmentAmountComponent from './inputs/InvestmentAmountComponent';
import EquipmentListComponent from './inputs/EquipmentListComponent';
import AttractingInvestmentComponent from './inputs/AttractingInvestmentComponent';
import VolumeSalesComponent from './inputs/VolumeSalesComponent';
import DelimiterComponent from './inputs/DelimiterComponent';
import SalaryFundComponent from './inputs/SalaryFundComponent';
import AdministrativeExpensesComponent from './inputs/AdministrativeExpensesComponent';
import MarketingExpensesComponent from './inputs/MarketingExpensesComponent';
import VariableCostsComponent from './inputs/VariableCostsComponent';
import ReadyPremisesListComponent from './inputs/ReadyPremisesListComponent';
import SmrListComponent from './inputs/SmrListComponent';
import OverhaulListComponent from './inputs/OverhaulListComponent';
import NumericComponent from './inputs/NumericComponent';
import PlannedCapacityComponent from './inputs/PlannedCapacityComponent';
import InvestPanelComponent from './inputs/InvestPanelComponent';
import projectFormMixin from '@/mixins/projectFormMixin';
import notyf from '@/plugins/notyf';
import { scrollToTop } from '@/helpers/helpers';
import investmentMixin from '@/mixins/project/investmentMixin';

/**
 * Перехватчик для запросов фин модели
 */
axios.interceptors.response.use(response => response, error => {
    const { status, data } = error.response;

    if (status === 404 && data.message) {
        notyf.error(data.message);
    }

    return Promise.reject(error);
});

export default {
    name: 'ProjectForm',
    mixins: [projectFormMixin, investmentMixin],
    components: {
        TextComponent,
        SelectComponent,
        CalendarComponent,
        CalendarRangeComponent,
        TurnoverComponent,
        OwnInvestmentComponent,
        InvestmentAmountComponent,
        EquipmentListComponent,
        AttractingInvestmentComponent,
        VolumeSalesComponent,
        DelimiterComponent,
        SalaryFundComponent,
        AdministrativeExpensesComponent,
        MarketingExpensesComponent,
        VariableCostsComponent,
        ReadyPremisesListComponent,
        NumericComponent,
        PlannedCapacityComponent,
        SmrListComponent,
        OverhaulListComponent,
        InvestPanelComponent
    },
    data() {
        return {
            steps:       0,
            currentStep: 1,
            months:      [],
            fields:      {},
            errors:      {},
        };
    },
    methods: {
        /**
         * Получить текущий шаг
         */
        getCurrentStep() {
            this.setLoading(true);

            axios.post(this.getBaseUrl() + '/get-current-step')
                .then(response => {
                    this.currentStep = response.data;
                    this.getFormByStep(this.currentStep);
                })
                .catch(error => {
                    throw error;
                })
                .finally(() => this.setLoading(false));
        },

        /**
         * Получить список полей определенного шага
         *
         * @param step
         */
        getFormByStep(step = 1) {
            this.currentStep = step;
            this.setLoading(true);

            notyf.dismissAll();

            axios.post(this.getBaseUrl() + '/step-' + this.currentStep)
                .then(response => {
                    this.fields = response.data;

                    this.investmentInitial();

                    scrollToTop();
                })
                .finally(() => this.setLoading(false));
        },

        /**
         * Сохранить форму
         *
         * @param event
         * @param backStep
         */
        save(event, backStep = false) {
            event.preventDefault();

            this.setLoading(true);
            this.errors = {};

            notyf.dismissAll();

            axios.put(this.getBaseUrl() + '/step' + this.currentStep, this.fields)
                .then(response => {
                    const { step, status, downloadLink, message } = response.data.success;

                    this.currentStep = !backStep ? step : this.currentStep - 1;

                    if (this.isCompleted(status, step) && !backStep) {
                        this.downloadFile(downloadLink);

                        notyf.success(message);

                        this.$router.push({ name: 'models.home' });
                    } else {
                        this.getFormByStep(this.currentStep);
                    }
                })
                .catch(error => {
                    const { status, data } = error.response;

                    if (status === 422) {
                        this.errors = data.errors;

                        return;
                    }

                    throw error;
                })
                .finally(() => this.setLoading(false));
        },

        /**
         * Проверяет проект на завершенность
         *
         * @param   status
         * @param   step
         * @returns {boolean}
         */
        isCompleted(status, step) {
            return status === 'completed' && step > this.steps;
        },

        /**
         * Скачивает отчет
         *
         * @param url
         */
        downloadFile(url) {
            const a = document.createElement('a');

            a.style.display = 'none';
            a.href = url;
            a.target = '_blank';
            a.download = null;

            document.body.appendChild(a);

            a.click();
            a.remove();
        },

        /**
         * Получить следующий шаг
         */
        nextStep() {
            if (this.currentStep < this.steps) {
                this.save();
                this.getFormByStep(this.currentStep);
            }
        },

        /**
         * Получить предыдущий шаг
         */
        prevStep(event) {
            this.save(event, true);
        },

        /**
         * Отключить шаги до которых еще не дошло заполнение
         *
         * @param   step текущий номер шага
         * @returns {string}
         */
        disableNextStep(step) {
            return (step > this.currentStep) ? 'is-disabled' : '';
        },

        /**
         * Получает максимальное кол-во шагов
         */
        getCountSteps() {
            axios.post(this.getBaseUrl() + '/steps')
                .then((response) => this.steps = response.data)
                .catch(error => {
                    throw error;
                });
        }
    },
    mounted() {
        this.getCountSteps();
        this.getCurrentStep();
    }
}
</script>
