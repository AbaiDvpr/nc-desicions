document.addEventListener('DOMContentLoaded', function () {

    const preloader = document.querySelector('.preloader');

    if (!preloader) return;

    setTimeout(() => {
        preloader.style.opacity = 0;
        preloader.addEventListener('transitionend', () => preloader.remove());
    },1000);

});
