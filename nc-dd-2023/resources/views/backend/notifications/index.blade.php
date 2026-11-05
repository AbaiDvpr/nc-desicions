@extends('backend.layouts.base')

@section('pageTitle') Уведомления @endsection

@section('body')
    @forelse($notifications as $notification)
        <div class="alert alert-{{ $notification->data['alert'] }}" role="alert">
            <div class="d-flex justify-content-between mb-2">
                <span><b>{{ $notification->data['message'] }}</b></span>
                <span>{{ $notification->created_at }}</span>
            </div>

            <div class="row">
                @if($notification->data['context'])
                    <div class="col-12">
                        <pre>{{ serialize($notification->data['context']) }}</pre>
                    </div>
                @endif

                <div class="col-12">
                    <a href="{{ route('backend.notifications.mark-as-read', ['notify_id' => $notification->id]) }}" class="float-right mark-as-read" data-id="{{ $notification->id }}">
                        Пометить как прочитанное
                    </a>
                </div>
            </div>
        </div>
    @empty
        На данный момент нет уведомлений
    @endforelse

    <x-paginate :paginate="$notifications"/>
@endsection


