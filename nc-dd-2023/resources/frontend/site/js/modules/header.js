import {onClickOutside} from './helpers';

document.addEventListener('DOMContentLoaded', () => {

    const header = document.querySelector('.header');

    if (!header) return;

    const body = document.body;
    const burger = document.querySelector('.burger');
    const menu = document.querySelector('.header__menu');
    const menuItems = document.querySelectorAll('.header__menu .header__link');
    const hideMenu = ()=> {
        if (window.innerWidth < 992) {
            body.classList.toggle('blocker');
            burger.classList.toggle('is-active');
            menu.classList.toggle('is-active');
        }
    }

    burger.addEventListener('click', hideMenu);

    menuItems.forEach(item => {
        item.addEventListener('click', hideMenu);
    });

    /**
     * Скрывает / Показывает меню пользователя
     */
    const userMenu = document.querySelector('#userMenu');
    const userMenuBtn = document.querySelector('#userMenuBtn');
    const userMenuItems = document.querySelector('#userMenuItems');

    if (!userMenu) return;

    userMenuBtn.addEventListener('click', ()=> {
        userMenuItems.classList.toggle('is-active');
    })

    onClickOutside(userMenu, () => userMenuItems.classList.remove('is-active'));

});
