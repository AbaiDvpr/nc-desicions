/**
 * Миксин для полей ownInvestments & investmentAmount
 */

export default {
    watch: {
        'fields.own_investments.value': function(value) {
            if (Number(value) === 0) {
                this.investmentOwnClearFields();
            }
        },
        'fields.own_investments.inputs.1.smr.value': function() {
            this.investmentCalculation('smr');
        },
        'fields.own_investments.inputs.1.overhaul.value': function() {
            this.investmentCalculation('overhaul');
        },
        'fields.own_investments.inputs.1.readyPremises.value': function() {
            this.investmentCalculation('readyPremises');
        },
        'fields.own_investments.inputs.1.equipment.value': function() {
            this.investmentCalculation('equipment');
        },
        'fields.own_investments.inputs.1.raw.value': function() {
            this.investmentCalculation('raw');
        },
        'fields.own_investments.inputs.1.wages.value': function() {
            this.investmentCalculation('wages');
        },
        'fields.own_investments.inputs.1.advertising.value': function() {
            this.investmentCalculation('advertising');
        },
        'fields.own_investments.inputs.1.rentalExpenses.value': function() {
            this.investmentCalculation('rentalExpenses');
        },
        'fields.own_investments.inputs.1.otherExpenses.value': function() {
            this.investmentCalculation('otherExpenses');
        },

        'fields.investmentAmount.inputs.smr.value': function() {
            this.investmentCalculation('smr');
        },
        'fields.investmentAmount.inputs.overhaul.value': function() {
            this.investmentCalculation('overhaul');
        },
        'fields.investmentAmount.inputs.readyPremises.value': function() {
            this.investmentCalculation('readyPremises');
        },
        'fields.investmentAmount.inputs.equipment.value': function() {
            this.investmentCalculation('equipment');
        },
        'fields.investmentAmount.inputs.raw.value': function() {
            this.investmentCalculation('raw');
        },
        'fields.investmentAmount.inputs.wages.value': function() {
            this.investmentCalculation('wages');
        },
        'fields.investmentAmount.inputs.advertising.value': function() {
            this.investmentCalculation('advertising');
        },
        'fields.investmentAmount.inputs.rentalExpenses.value': function() {
            this.investmentCalculation('rentalExpenses');
        },
        'fields.investmentAmount.inputs.otherExpenses.value': function() {
            this.investmentCalculation('otherExpenses');
        },
    },
    methods: {
        /**
         * Инициализация полей ownInvestments & investmentAmount
         *
         * @return {void}
         */
        investmentInitial() {
            if (this.currentStep !== 4) {
                return;
            }

            const data = this.fields.investPanel.data;

            Object.keys(data).map(key => {
                if (data[key] === 0) {
                    this.fields.own_investments.inputs[1][key].value    = null;
                    this.fields.own_investments.inputs[1][key].disabled = true;
                    this.fields.investmentAmount.inputs[key].value      = null;
                    this.fields.investmentAmount.inputs[key].disabled   = true;
                }
            });
        },

        /**
         * Очищает поля компонента собственные вложения (OwnInvestment)
         *
         * @return {void}
         */
        investmentOwnClearFields() {
            if (this.currentStep !== 4) {
                return;
            }

            const ownInvestment = this.fields.own_investments.inputs[1];

            Object.keys(ownInvestment).map(key => ownInvestment[key].value = null);
        },

        /**
         * Вычисляет распределенную сумму инвестиций (investPanel)
         *
         * @return {void}
         */
        investmentCalculation(field = '') {
            if (this.currentStep !== 4) {
                return;
            }

            const currentValue = this.fields.investmentAmount.inputs[field].value;
            let firstValue     = 0;

            if (Number(this.fields.own_investments.value) === 1) {
                firstValue = this.fields.own_investments.inputs[1][field].value;
            }

            this.fields.investPanel.distributed[field] = Number(firstValue) + Number(currentValue);
        }
    }
}
