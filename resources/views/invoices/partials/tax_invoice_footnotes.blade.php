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
    $pdfBlue = ($brand['primary_color'] ?? null) ?: '#004aad';
@endphp
@if($noteCards->isNotEmpty())
    @if(!empty($isPdf))
        @php
            $cols = max(1, min(3, $noteCards->count()));
            $width = (int) floor(100 / $cols);
        @endphp
        <table class="pdf-footnotes" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:separate; border-spacing:8px 0; margin-top:10px;">
            <tr>
                @foreach($noteCards as $card)
                <td width="{{ $width }}%" valign="top" style="width:{{ $width }}%; vertical-align:top; background:#f8fafc; border:1px solid #e2e8f0; border-left:4px solid {{ $pdfBlue }}; padding:10px 12px;">
                    <div style="font-size:9px; font-weight:700; letter-spacing:0.7px; text-transform:uppercase; color:{{ $pdfBlue }}; margin:0 0 6px;">{{ $card['title'] }}</div>
                    <div style="font-size:10px; color:#334155; line-height:1.5;">{!! nl2br(e($card['body'])) !!}</div>
                </td>
                @endforeach
            </tr>
        </table>
    @else
        <div class="footnotes {{ $noteGridClass }}">
            @foreach($noteCards as $card)
            <div class="note-card">
                <h4>{{ $card['title'] }}</h4>
                <div class="body">{!! nl2br(e($card['body'])) !!}</div>
            </div>
            @endforeach
        </div>
    @endif
@endif
