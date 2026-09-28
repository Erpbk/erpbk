{{--
  Expected: $noteCards (Illuminate\Support\Collection of ['title'=>,'body'=>]), $noteGridClass
--}}
@php
    $noteCards = collect($noteCards ?? []);
    $noteGridClass = $noteGridClass ?? match ($noteCards->count()) {
        1 => 'one',
        3 => 'three',
        default => '',
    };
@endphp
@if($noteCards->isNotEmpty())
<div class="footnotes {{ $noteGridClass }}">
    @foreach($noteCards as $card)
    <div class="note-card">
        <h4>{{ $card['title'] }}</h4>
        <div class="body">{!! nl2br(e($card['body'])) !!}</div>
    </div>
    @endforeach
</div>
@endif
