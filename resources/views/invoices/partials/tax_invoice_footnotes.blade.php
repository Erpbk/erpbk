{{--
  Shared footnotes for screen + PDF (same markup).
  Expected: $noteCards (collection of ['title'=>,'body'=>])
--}}
@php
    $noteCards = collect($noteCards ?? []);
@endphp
@if($noteCards->isNotEmpty())
@php
    $cols = max(1, min(3, $noteCards->count()));
    $width = (int) floor(100 / $cols);
@endphp
<table class="inv-footnotes" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        @foreach($noteCards as $card)
        <td class="inv-footnote-cell" width="{{ $width }}%" valign="top">
            <div class="inv-note-box">
                <div class="inv-note-title">{{ $card['title'] }}</div>
                <div class="inv-note-body">{!! nl2br(e($card['body'])) !!}</div>
            </div>
        </td>
        @endforeach
    </tr>
</table>
@endif
