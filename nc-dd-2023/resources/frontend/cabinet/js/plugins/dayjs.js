import dayjs, { UnitTypeLong, UnitTypeShort } from 'dayjs';
import utc from 'dayjs/plugin/utc';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import store from '@/store/store';

import 'dayjs/locale/ru';
import 'dayjs/locale/kk';

dayjs.locale('ru');
dayjs.locale('en');
dayjs.locale('kk');
dayjs.extend(utc);
dayjs.extend(customParseFormat);

/**
 * Возвращаем TimeStamp от времени
 *
 * @param {Date | string} date - Дата
 * @returns {number}
 */
export const getUnixDate = date => (date ? dayjs(date).unix() : date);

/**
 * Дата со временем
 *
 * @example 2020-01-23T10:46:43Z => 23.01.20 г., 16:46
 * @param {Date | string} date - Дата
 * @param {string} [format] - Формат времени
 * @returns {string}
 */
export const getFormatDate = (date, format = 'DD.MM.YY г., HH:mm') => {
    if (!date) return date;

    const locale      = store.getters['lang/locale'] ?? 'ru';
    const formattedDate = dayjs(date).locale(locale).format(format);

    return formattedDate === 'Invalid Date' ? date : formattedDate;
};

/**
 * Получаем разницу времени от сегодняшнего дня вида => 15д 23ч 5м 1с
 *
 * @param {Object|string|Date} date - dayjs объект, который будет сравниваться сегодняшнем днем
 * @param {boolean} [isAfter] - считаем до указанного времени
 * @param {Object} [options] - опций показа
 * @param {boolean} [options.hideSeconds] - скрываем секунды
 * @param {boolean} [options.hideMinutes] - скрываем минуты
 * @param {boolean} [options.forceUTC] - Время переводим в UTC без изменения исходного времени
 * @returns {string}
 */
export const getTodayDiffDateFormat = (date, isAfter = false, options = {}) => {
    const dateJs = dayjs.isDayjs(date) ? date : dayjs(date);

    dateJs.utc(options.forceUTC);

    const today = dayjs().utc(options.forceUTC);
    const milliSeconds = isAfter ? dateJs.diff(today, 'millisecond') : today.diff(dateJs, 'millisecond');

    return formatSecondToBeatify(milliSeconds, options);
};

/**
 * Отдает сколько дней осталось от сегодняшнего дня до указанного времени
 *
 * @param {Date | string| Object} date - Дата сравнения (будущее указываем)
 * @param {UnitTypeLong| UnitTypeShort} [timeUnit] - по какому виду времени считать разницу, (по дням, по часам и т.д.)
 * @param {boolean} [forceUTC] - Время переводим в UTC без изменения исходного времени
 * @returns {number | string}
 */
export const getLeftTime = (date, timeUnit = 'd', forceUTC = false) => {
    if (!date) return date;

    const dateJs = dayjs.isDayjs(date) ? date : dayjs(date);

    dateJs.utc(forceUTC);

    const today = dayjs().utc(forceUTC);

    return dateJs.diff(today, timeUnit);
};
