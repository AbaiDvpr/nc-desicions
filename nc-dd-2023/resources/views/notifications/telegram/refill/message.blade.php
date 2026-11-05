@php $refill = $refill->getRefillData(); @endphp
<b>Запрос на пополнение через Kaspi.</b>

Данные пользователя:
    <b>Имя:</b> {{ $refill->getUserName() }}
    <b>Номер кабинета:</b> {{ $refill->getCabinetId() }}

Данные формы запроса:
    <b>ФИО:</b> {{ $refill->getFullName() }}
    <b>Телефон:</b> {{ $refill->getPhone() }}
@if ($refill->getComment())
    <b>Комментарий:</b> {{ $refill->getComment() }}
@endif
