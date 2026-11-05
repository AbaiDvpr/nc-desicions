@php
    $letters = str_split('NC-DECISION');
    $lettersDelay = 0;
@endphp

<div class="preloader">
    @foreach($letters as $letter )
        <div class="preloader__letter" style="animation-delay: {{ $lettersDelay }}s">
            {{ $letter }}
        </div>

        @php $lettersDelay += 0.1; @endphp
    @endforeach
</div>
