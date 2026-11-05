import { NAME } from '@/config/app';
import store from '@/store/store';
import allowedUsers from '@/config/allowedUsers';

/**
 * Изменение заголовка страницы
 *
 * @param value
 */
export function setPageTitle(value) {
    window.document.title = NAME;

    if (value) {
        window.document.title = value + ' | ' + NAME;
    }
}

/**
 * Изменение локали в адресной строке
 *
 * @param oldLocale
 */
export function setPageLocaleInUrl(oldLocale) {
    const url = window.location.href.replace(
        `/${oldLocale}/`,
        `/${store.getters['lang/locale']}/`
    );

    window.history.pushState({}, '', url);
}

/**
 * Скроллит страницу вниз
 */
export function scrollToBottom() {
    window.scrollTo({
        top: document.documentElement.scrollHeight,
        behavior: 'smooth'
    });
}

/**
 * Скроллит страницу вверх
 */
export function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

/**
 * Показать/скрыть скролл страницы
 */
export function setBlocker(state) {
    if (state) {
        document.body.classList.add('blocker');
    } else {
        document.body.classList.remove('blocker');
    }
}

/**
 * Проверяет пользователя в списке разрешенных пользователей
 *
 * @return {Boolean}
 */
export function checkUserInAllowedUsers() {
    return allowedUsers.includes(store.getters['profile/userEmail']);
}

/**
 * Возвращает навигацию, отсортированную по email
 *
 * @param  nav
 * @param  email
 * @return {[{}]}
 */
export function getNavByFilteredUserEmail(nav, email) {
    return nav.filter(({ checkUserEmail }) => {
        if (checkUserEmail) {
            return allowedUsers.includes(email);
        }

        return true;
    });
}

/**
 * Возвращает навигацию, отсортированную по ограничениям
 *
 * @param  nav
 * @return {[{}]}
 */
export function getNavByFilteredAccess(nav) {
    const courseAccess = store.getters['profile/courseAccess'];

    return nav.filter(({ to }) => {
        if (to.name === 'courses.home') {
            return courseAccess;
        }

        return true;
    });
}

/**
 * Преобразуем объект из ключа и значение в массив опций для select
 *
 * @param   {Object} data - данные для преобразования
 * @returns {{value: string|number, title: string}[]}
 */
export function convertOptionsForSelect(data) {
    return Object.entries(data).map(([value, title]) => ({ title, value }));
}

/**
 * Возвращает шаблон типа notifier для notyf
 *
 * @param  title
 * @param  text
 * @return {string}
 */
export function getNotifierTemplate(title = '', text = '') {
    return `
        <div class="notyf__message-header">${title}</div>
        <div class="notyf__message-text">${text}</div>
    `;
}

/**
 * Сокращает запись больших чисел, добавляя именные название степеней тысячи
 * TODO: Сделать красиво
 *
 * @example 1 000 000 => 1 млн
 * @param   {number} n
 * @returns {string|number}
 */
export function nFormatter(n) {
    const isNegative = n < 0;
    const symbolMinus = isNegative ? '-' : '';

    n = Math.abs(n);

    if (n < 1e3) return symbolMinus + n;
    if (n >= 1e3 && n < 1e6) return symbolMinus + (n / 1e3).toFixed(0) + ' тыс';
    if (n >= 1e6 && n < 1e9) return symbolMinus + (n / 1e6).toFixed(0) + ' млн';
    if (n >= 1e9 && n < 1e12) return symbolMinus + (n / 1e9).toFixed(0) + ' млрд';
    if (n >= 1e12) return symbolMinus + (n / 1e12).toFixed(0) + ' трлн';
}

/**
 * Конвертирует цвета в формате hex в формат rgba
 *
 * @example '#27B8F0' => 'rgba(39,184,240,1)'
 * @param  {string} hexCode
 * @param  {number} opacity
 * @return {string}
 */
export function convertHexToRGBA(hexCode, opacity = 1) {
    let hex = hexCode.replace('#', '');

    if (hex.length === 3) {
        hex = `${hex[0]}${hex[0]}${hex[1]}${hex[1]}${hex[2]}${hex[2]}`;
    }

    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);

    if (opacity > 1 && opacity <= 100) {
        opacity = opacity / 100;
    }

    return `rgba(${r},${g},${b},${opacity})`;
}
