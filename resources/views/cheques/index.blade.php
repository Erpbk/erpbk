@extends('layouts.app')
@section('title','Cheques')
@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<style>
    .table-responsive {
        max-height: calc(100vh - 210px);
    }
</style>
@endpush
@section('content')
        @include('flash::message')
        <div class="clearfix"></div>
        @can('cheques_view')
        @include('cheques._cheque_top_slider')
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
                @can('cheques_create')
                    <button class="btn btn-primary btn-sm show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Cheque" data-action="{{ route('cheques.create') }}">Add New</button>
                @endcan
            </div>
            <div class="card-body table-responsive py-0" id="table-data">
                @include('cheques.table')
            </div>
        </div>
        @endcan
        @cannot('cheques_view')
            <div class="text-center mt-5">
                <h3>You do not have permission to view Cheques.</h3> 
            </div>
        @endcannot
@endsection

@push('page-scripts')
<script>
    function filterByChequeTopOption(optionId) {
        const url = new URL(window.location);
        url.searchParams.delete('cheque_top_option_id');
        url.searchParams.delete('cheque_top_status');
        url.searchParams.delete('cheque_top_status[]');
        url.searchParams.set('cheque_top_option_id', optionId);
        url.searchParams.append('cheque_top_status[]', 'cleared');
        url.searchParams.append('cheque_top_status[]', 'pending');
        window.location.href = url.toString();
    }

    function filterByChequeTopOptionStatus(optionId, status) {
        const url = new URL(window.location);
        const currentOptionId = url.searchParams.get('cheque_top_option_id');
        const currentStatuses = url.searchParams.getAll('cheque_top_status[]');

        if (currentOptionId === String(optionId) && currentStatuses.includes(status)) {
            const newStatuses = currentStatuses.filter(s => s !== status);
            url.searchParams.delete('cheque_top_status[]');
            newStatuses.forEach(s => url.searchParams.append('cheque_top_status[]', s));
            if (newStatuses.length === 0) {
                url.searchParams.delete('cheque_top_option_id');
            }
        } else {
            url.searchParams.set('cheque_top_option_id', optionId);
            url.searchParams.delete('cheque_top_status[]');
            url.searchParams.set('cheque_top_status[]', status);
        }
        window.location.href = url.toString();
    }

    function initFleetSupervisorSlider() {
        const sliderTrack = document.getElementById('sliderTrack');
        if (!sliderTrack || sliderTrack.dataset.tickerInit === '1') return;

        const cards = Array.from(sliderTrack.querySelectorAll('.fleet-supervisor-card'));
        if (!cards.length) return;

        const container = sliderTrack.closest('.fleet-supervisor-slider-container');
        if (container) container.classList.add('ticker-mode');

        sliderTrack.dataset.tickerInit = '1';
        if (cards.length < 2) return;

        let isAnimating = false;
        const computedTrackStyle = window.getComputedStyle(sliderTrack);
        const gap = parseFloat(computedTrackStyle.columnGap || computedTrackStyle.gap || '16') || 16;

        function slideNextCard() {
            if (isAnimating) return;
            const firstCard = sliderTrack.querySelector('.fleet-supervisor-card');
            if (!firstCard) return;
            isAnimating = true;
            const shiftAmount = firstCard.offsetWidth + gap;
            sliderTrack.style.transition = 'transform 520ms ease';
            sliderTrack.style.transform = 'translateX(-' + shiftAmount + 'px)';
            window.setTimeout(function() {
                sliderTrack.style.transition = 'none';
                sliderTrack.style.transform = 'translateX(0)';
                sliderTrack.appendChild(firstCard);
                void sliderTrack.offsetWidth;
                isAnimating = false;
            }, 540);
        }

        const intervalId = window.setInterval(slideNextCard, 2600);
        sliderTrack.dataset.tickerIntervalId = String(intervalId);
    }

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initFleetSupervisorSlider, 150);
    });
    window.addEventListener('load', function() {
        setTimeout(initFleetSupervisorSlider, 150);
    });
</script>
@endpush
