import VueNumberFormat from '@coders-tm/vue-number-format';

export const numberConfig = {
    separator: ' ',
    decimal: '.',
    precision: 3,
    class: 'input'
};

export const percentConfig = {
    suffix: ' %',
    separator: '',
    decimal: '.',
    precision: 3,
    max: 1000,
    class: 'input'
};

export default VueNumberFormat;
