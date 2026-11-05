import axios from 'axios';
import { Notyf } from 'notyf';

const notyfErrorOptions = {
    backgroundColor: 'rgba(220, 53, 69, .9)',
    ripple: false,
    duration: 3000,
    dismissible: true,
    position: {
        x: 'right',
        y: 'top',
    }
};

document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('feedback-form');

    if (!form) return;

    const notyf              = new Notyf();
    const getSuccessTemplate = text => `<div class="alert alert--success text-center">${text}</div>`;
    const submitHandler      = event => {
        event.preventDefault();

        const form       = event.currentTarget;
        const formAction = form.dataset.action;
        const submit     = form.querySelector('[type="submit"]');
        const formData   = new FormData(form);

        submit.classList.add('is-loading');

        axios
            .post(formAction, formData)
            .then(response => {
                const { success, message } = response.data;

                if (success) {
                    form.reset();
                    form.classList.add('is-success');
                    form.insertAdjacentHTML('beforeend', getSuccessTemplate(message));
                } else {
                    notyf.error({ message, ...notyfErrorOptions });
                }
            })
            .catch(error => {
                const { status, data }  = error.response;

                if (status === 422) {
                    Object.keys(data.errors).map(item => {
                        notyf.error({ message: data.errors[item][0], ...notyfErrorOptions });
                    });

                    return;
                }

                if (data.message) {
                    notyf.error({ message: data.message, ...notyfErrorOptions });
                }
            })
            .finally(() => {
                if (grecaptcha?.getResponse()) grecaptcha.reset();

                submit.classList.remove('is-loading');
            });
    };

    form.addEventListener('submit', submitHandler);

});
