import { MaskInput } from 'maska';

document.addEventListener('DOMContentLoaded', () => {

    if (MaskInput) {
        new MaskInput('[data-maska]');
    }

});
