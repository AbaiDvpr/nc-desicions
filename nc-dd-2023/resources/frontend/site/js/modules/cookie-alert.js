document.addEventListener('DOMContentLoaded', () => {

    const cookieAlert = document.querySelector('.cookie-alert');
    const cookieBtn = document.querySelector('.cookie-alert .btn');

    if (localStorage.getItem('cookieConsent') !== 'true') {
        cookieAlert.style.display = 'flex';
    }

    cookieBtn.addEventListener('click', ()=> {
        localStorage.setItem('cookieConsent', 'true');
        cookieAlert.style.display = 'none';
    });

});
