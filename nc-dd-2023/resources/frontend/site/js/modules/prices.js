document.addEventListener('DOMContentLoaded', () => {

    const wrap = document.querySelector('#prices');

    if (!wrap) return;

    const nav         = document.querySelector('#tariffNav');
    const navList     = document.querySelectorAll('#tariffNav .tariff-nav__btn');
    const contentList = document.querySelectorAll('#prices .prices__list');
    const onClick     = event => {
        const element = event.target.closest('.tariff-nav__btn');

        if (!element) return;

        if (element.classList.contains('is-active')) return;

        navList.forEach(item => item.classList.remove('is-active'));
        contentList.forEach(item => item.classList.add('hidden'));

        const content = document.querySelector(element.dataset.target);

        element.classList.add('is-active');
        content?.classList.remove('hidden');
    };

    nav.addEventListener('click', onClick);

});
