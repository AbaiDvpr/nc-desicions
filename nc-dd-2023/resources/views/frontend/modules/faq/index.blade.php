@extends('frontend.inner')

@section('content')

    @if ($faq->count())
        <div class="section-title">
            <h2>Вопрос ответ</h2>
            <p>Magnam dolores commodi suscipit. Necessitatibus eius consequatur ex aliquid fuga eum quidem. Sit sint consectetur velit. Quisquam quos quisquam cupiditate. Et nemo qui impedit suscipit alias ea. Quia fugiat sit in iste officiis commodi quidem hic quas.</p>
        </div>

        <div class="accordion-list">
            <ul>
                @foreach($faq as $fa)
                    <li data-aos="fade-up" data-aos-delay="100">
                        <a data-bs-toggle="collapse" class="collapsed" data-bs-target="#accordion-list-{{ $fa->id }}" aria-expanded="false">
                            <span>{{ $loop->iteration >= 10 ? $loop->iteration : '0' . $loop->iteration }}</span>
                            {{ strip_tags($fa->question) }}
                            <i class="bx bx-chevron-down icon-show"></i>
                            <i class="bx bx-chevron-up icon-close"></i>
                        </a>
                        <div id="accordion-list-{{ $fa->id }}" class="collapse" data-bs-parent=".accordion-list">
                            <p>{!! $fa->reply !!}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            {{ $faq->links('vendor.pagination.frontend-bootstrap') }}
        </div>
    @endif

@endsection
