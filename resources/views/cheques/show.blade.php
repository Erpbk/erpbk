<div class="container-fluid px-0">

    <!-- Cheque Information Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
            <h4 class="fw-bold mb-0">{{ $cheque->cheque_number }}</h4>
            <div class="text-muted small">{{ $cheque->reference ?? 'No reference' }}</div>
        </div>
        <div class="text-end">
            <div class="fw-bold text-success fs-4 mb-1">AED {{ number_format($cheque->amount, 2) }}</div>
            @php
                $badgeClasses = [
                    'Issued' => 'bg-primary',
                    'Cleared' => 'bg-success',
                    'Returned' => 'bg-danger',
                    'Stop Payment' => 'bg-warning',
                    'Lost' => 'bg-secondary',
                ];
            @endphp
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $badgeClasses[$cheque->status] ?? 'bg-secondary' }}">{{ $cheque->status }}</span>
                <span class="badge border {{ $cheque->type == 'payable' ? 'border-danger' : 'border-success' }} text-black">
                    {{ ucfirst($cheque->type) }}
                </span>
            </div>
        </div>
    </div>
    <!-- Issued By -->
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
        <div class="text-muted small">Issued By:</div>
        <div class="fw-medium">{{ $cheque->issued_by ?? '-' }}</div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column - Primary Information -->
        <div class="col-lg-8">
            <!-- Parties Information -->
            <div class="mb-4">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-users text-primary me-2"></i>
                    <h6 class="mb-0 fw-semibold">Parties Information</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-light-subtle">
                            <div class="text-muted small mb-1">Receiver</div>
                            <div class="fw-medium">{{ $cheque->payee->name ?? $cheque->payee_name ?? '-' }}</div>
                            @if($cheque->payee_account)
                                <div class="text-muted small mt-1">{{ $cheque->payee_account }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-light-subtle">
                            <div class="text-muted small mb-1">Sender</div>
                            <div class="fw-medium">{{ $cheque->payer->name ?? $cheque->payer_name ?? '-' }}</div>
                            @if($cheque->payer_account)
                                <div class="text-muted small mt-1">{{ $cheque->payer_account }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Information Grid -->
            <div class="mb-4">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    <h6 class="mb-0 fw-semibold">Key Information</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-2">
                            <div class="text-muted small mb-1">Issue Date</div>
                            <div class="fw-medium">{{ $cheque->issue_date ? \App\Helpers\Common::DateFormat($cheque->issue_date) : '------' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2">
                            <div class="text-muted small mb-1">Cheque Date</div>
                            <div class="fw-medium">{{ $cheque->cheque_date ? \App\Helpers\Common::DateFormat($cheque->cheque_date) : '------' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2">
                            <div class="text-muted small mb-1">Billing Month</div>
                            <div class="fw-medium">{{ $cheque->billing_month ? \App\Helpers\Common::MonthFormat($cheque->billing_month) : '------' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            @if($cheque->description)
            <div class="mb-4">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-align-left text-primary me-2"></i>
                    <h6 class="mb-0 fw-semibold">Description</h6>
                </div>
                <div class="border rounded p-3 bg-light-subtle">
                    <div class="small">{{ $cheque->description }}</div>
                </div>
            </div>
            @endif

        </div>

        <!-- Right Column - Status & Actions -->
        <div class="col-lg-4 border-start ps-4">
            <!-- Status Timeline -->
            <div class="mb-4">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-history text-primary me-2"></i>
                    <h6 class="mb-0 fw-semibold">Status History</h6>
                </div>
                <div class="timeline-compact">
                    @php
                        $dateItems = [
                            [
                                'icon' => 'fas fa-paper-plane',
                                'color' => 'primary',
                                'date' => $cheque->issue_date,
                                'title' => 'Issued',
                                'show' => true
                            ],
                            [
                                'icon' => 'fas fa-check-circle',
                                'color' => 'success',
                                'date' => $cheque->cleared_date,
                                'title' => 'Cleared',
                                'show' => $cheque->cleared_date
                            ],
                            [
                                'icon' => 'fas fa-ban',
                                'color' => 'warning',
                                'date' => $cheque->stop_payment_date,
                                'title' => 'Stop Payment',
                                'show' => $cheque->stop_payment_date,
                                'reason' => $cheque->stop_payment_reason ?? null
                            ],
                            [
                                'icon' => 'fas fa-times-circle',
                                'color' => 'danger',
                                'date' => $cheque->returned_date,
                                'title' => 'Returned',
                                'show' => $cheque->returned_date,
                                'reason' => $cheque->return_reason
                            ]
                        ];
                    @endphp
                    
                    @foreach($dateItems as $item)
                        @if($item['show'])
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-3">
                                <i class="{{ $item['icon'] }} text-{{ $item['color'] }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-medium">{{ $item['title'] }}</span>
                                    @if($item['date'])
                                        <span class="text-muted small">{{ \App\Helpers\Common::DateFormat($item['date']) }}</span>
                                    @endif
                                </div>
                                @if(!empty($item['reason']))
                                    <div class="text-danger small mt-1">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        {{ $item['reason'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Document Links -->
            <div class="mb-4">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-paperclip text-primary me-2"></i>
                    <h6 class="mb-0 fw-semibold">Document Links</h6>
                </div>
                <div class="d-grid gap-2">
                    @if($cheque->voucher_id)
                    <a href="javascript:void(0);" 
                       data-action="{{ route('vouchers.show', $cheque->voucher_id) }}" 
                       class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-between show-modal" 
                       data-title="Cheque Voucher" 
                       data-size="xl">
                        <span class="d-flex align-items-center">
                            <i class="fas fa-file-invoice me-2"></i>
                            <span>View Voucher</span>
                        </span>
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    @endif
                    
                    @if($cheque->attachment)
                    <a href="{{ url('storage/vouchers/' . $cheque->attachment) }}" 
                       class="btn btn-outline-success btn-sm d-flex align-items-center justify-content-between" 
                       target="_blank">
                        <span class="d-flex align-items-center">
                            <i class="fas fa-paperclip me-2"></i>
                            <span>View Attachment</span>
                        </span>
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    @endif
                </div>
            </div>

            <!-- Additional Info -->
            <div class="border rounded p-3 bg-light-subtle">
                <div class="text-muted small mb-2">Additional Information</div>
                <div class="small">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Created:</span>
                        <span>{{ $cheque->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Created By:</span>
                        <span>{{ $cheque->Created_by->name ?? '' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Last Updated:</span>
                        <span>{{ $cheque->updated_at->format('d M Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Updated By:</span>
                        <span>{{ $cheque->Updated_by->name ?? '' }}</span>
                    </div>
                    @if($cheque->deleted_at)
                    <div class="d-flex justify-content-between text-danger">
                        <span>Deleted:</span>
                        <span>{{ $cheque->deleted_at->format('d M Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Minimal custom styling */
    .border-start {
        border-left: 1px solid #dee2e6 !important;
    }
    
    .bg-light-subtle {
        background-color: #f8f9fa !important;
    }
    
    .timeline-compact .d-flex {
        padding: 0.25rem 0;
    }
    
    .form-select-sm, .form-control-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8125rem;
    }
    
    .badge {
        padding: 0.4em 0.8em;
        font-weight: 500;
    }
    
    @media (max-width: 992px) {
        .border-start {
            border-left: none !important;
            padding-left: 0 !important;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #dee2e6 !important;
        }
    }
    
    @media (max-width: 768px) {
        .fs-4 {
            font-size: 1.5rem !important;
        }
        
        .border.rounded.p-3 {
            padding: 1rem !important;
        }
    }
</style>