/**
 * Клик за пределами элемента
 *
 * @param element
 * @param callback
 */
export function onClickOutside(element, callback) {
    document.addEventListener('click', event => {
        if (!element.contains(event.target)) callback();
    });
};
