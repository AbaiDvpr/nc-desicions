import { Notyf } from 'notyf';

/**
 * Notyf - библиотека для всплывающих уведомлений
 *
 * @see   {@link https://github.com/caroso1222/notyf}
 * @param options {Object}
 */
const notyf = new Notyf({
    types: [
        {
            type: 'success',
            className: 'notyf__toast--success',
            backgroundColor: 'rgba(40, 167, 69, .9)',
            icon: {
                className: 'notyf__icon--success',
                tagName: 'i',
            },
        },
        {
            type: 'error',
            className: 'notyf__toast--error',
            backgroundColor: 'rgba(220, 53, 69, .9)',
            icon: {
                className: 'notyf__icon--error',
                tagName: 'i',
            },
        },
        {
            type: 'notifier',
            className: 'notyf__toast--custom',
            backgroundColor: 'rgb(66, 68, 70, .8)',
            icon: {
                className: 'notyf__icon--custom',
                tagName: 'i',
            },
        },
        {
            type: 'info',
            className: 'notyf__toast--info',
            backgroundColor: 'rgba(105, 91, 214, .9)',
            icon: {
                className: 'notyf__icon--info',
                tagName: 'i',
            },
        },
        {
            type: 'hint',
            className: 'notyf__toast--hint',
            backgroundColor: '#ebe9fc',
            icon: {
                className: 'notyf__icon--hint',
                tagName: 'i',
            },
        },
        {
            type: 'warning',
            className: 'notyf__toast--warning',
            backgroundColor: 'rgba(255, 193, 7, .9)',
            icon: {
                className: 'notyf__icon--warning',
                tagName: 'i',
            },
        },
    ],
    ripple: false,
    duration: 5000,
    position: {
        x: 'right',
        y: 'top',
    },
});

export default notyf;
