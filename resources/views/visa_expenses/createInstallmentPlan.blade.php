<form action="{{ route('VisaExpense.createInstallmentPlan') }}" method="POST" id="installmentPlanForm">
    @csrf
    <input type="hidden" name="rider_id" value="{{ $account->id }}">

    <div class="modal-body">
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Rider Information</h6>
                        <p><strong>Name:</strong> {{ $account->name }}</p>

                        @php
                        $currentMonth = \Carbon\Carbon::now()->format('Y-m');
                        $existingCurrentMonthPlan = \App\Models\visa_installment_plan::where('rider_id', $account->id)
                        ->where('billing_month', $currentMonth)
                        ->exists();
                        @endphp

                        @if($existingCurrentMonthPlan)
                        <div class="alert alert-warning mt-2">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> An installment plan already exists for this rider in {{ \Carbon\Carbon::now()->format('F Y') }}.
                            Please select a different starting month to avoid conflicts.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- Reference Number -->
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="reference_number" class="form-label">Reference Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="Reference Number" required>
                    <small class="form-text text-muted">Reference number for this installment plan</small>
                </div>
            </div>
            <!-- Step 1: Select Number of Installments -->
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="number_of_installments" class="form-label">Number of Installments <span class="text-danger">*</span></label>
                    <select class="form-control" id="number_of_installments" name="number_of_installments" required>
                        <option value="">Select installments</option>
                        <option value="1">1 Installment</option>
                        <option value="2">2 Installments</option>
                        <option value="3">3 Installments</option>
                        <option value="4">4 Installments</option>
                        <option value="5">5 Installments</option>
                        <option value="6">6 Installments</option>
                        <option value="7">7 Installments</option>
                        <option value="8">8 Installments</option>
                        <option value="9">9 Installments</option>
                        <option value="10">10 Installments</option>
                        <option value="11">11 Installments</option>
                        <option value="12">12 Installments</option>
                    </select>
                    <small class="form-text text-muted">Select the number of installments (e.g., 3, 6, 12)</small>
                </div>
            </div>

            <!-- Step 2: Enter Total Amount -->
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="total_amount" class="form-label">Total Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="1" class="form-control" id="total_amount" name="total_amount" required>
                    <small class="form-text text-muted">Enter total amount (e.g., 12,000)</small>
                </div>
            </div>

            <!-- Step 3: Starting Billing Month -->
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="billing_month" class="form-label">Starting Billing Month <span class="text-danger">*</span></label>
                    <input type="month" class="form-control" id="billing_month" name="billing_month" value="{{ date('Y-m') }}" required>
                    <small class="form-text text-muted">Select the first billing month</small>
                </div>
            </div>



            <!-- Step 4: Dynamic Installment Input Fields -->
            <div class="col-md-12" id="installment-inputs-section" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fa fa-edit me-2"></i>Individual Installment Amounts
                            <small class="text-muted ms-2">(Editable - System will auto-adjust remaining installments)</small>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row" id="installment-inputs">
                            <!-- Dynamic installment input fields will be generated here -->
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <strong>Total Amount:</strong> <span id="total-display">0.00</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <strong>Remaining Balance:</strong> <span id="remaining-balance">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 5: Installment Preview -->
            <div class="col-md-12" id="installment-preview-section" style="display: none;">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Installment Preview</h6>
                        <div id="installment-preview">
                            <!-- Preview will be generated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="action-btn">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
            <i class="fa fa-plus me-2"></i>Create Installment Plan
        </button>
    </div>
</form>

<script>
    $(document).ready(function() {
        let installmentAmounts = [];
        let totalAmount = 0;
        let numberOfInstallments = 0;

        // Generate dynamic installment input fields
        function generateInstallmentInputs() {
            const totalAmountValue = parseFloat($('#total_amount').val());
            const numberOfInstallmentsValue = parseInt($('#number_of_installments').val());
            const billingMonth = $('#billing_month').val();

            if (!totalAmountValue || !numberOfInstallmentsValue || !billingMonth) {
                $('#installment-inputs-section').hide();
                $('#installment-preview-section').hide();
                $('#submitBtn').prop('disabled', true);
                return;
            }

            totalAmount = totalAmountValue;
            numberOfInstallments = numberOfInstallmentsValue;

            // Calculate equal distribution initially
            const equalAmount = totalAmount / numberOfInstallments;
            installmentAmounts = new Array(numberOfInstallments).fill(equalAmount);

            // Generate input fields
            let html = '';
            for (let i = 0; i < numberOfInstallments; i++) {
                const currentDate = new Date(billingMonth + '-01');
                currentDate.setMonth(currentDate.getMonth() + i);
                const formattedMonth = currentDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long'
                });

                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="form-group">
                            <label for="installment_${i}" class="form-label">Installment ${i + 1} - ${formattedMonth}</label>
                            <div class="input-group">
                                <span class="input-group-text">AED</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       class="form-control installment-input" 
                                       id="installment_${i}" 
                                       data-index="${i}"
                                       value="${equalAmount.toFixed(2)}"
                                       placeholder="0.00">
                            </div>
                            <small class="form-text text-muted">Billing: ${formattedMonth}</small>
                        </div>
                    </div>
                `;
            }

            $('#installment-inputs').html(html);
            $('#installment-inputs-section').show();

            // Update totals
            updateTotals();
            generatePreview();
        }

        // Auto-adjust remaining installments when one is changed
        function adjustRemainingInstallments(changedIndex, newAmount) {
            if (numberOfInstallments <= 1) return;

            // Update the changed installment amount
            installmentAmounts[changedIndex] = parseFloat(newAmount) || 0;

            // Step 1: Calculate remaining balance
            // Remaining balance = total_amount - sum(edited installments)
            const remainingBalance = totalAmount - installmentAmounts[changedIndex];

            // Step 2: Get remaining installments (excluding the edited one)
            const remainingInstallments = [];
            for (let i = 0; i < numberOfInstallments; i++) {
                if (i !== changedIndex) {
                    remainingInstallments.push(i);
                }
            }

            if (remainingInstallments.length === 0) return;

            // Step 3: Distribute balance equally among remaining installments
            const amountPerInstallment = remainingBalance / remainingInstallments.length;

            // Step 4: Apply rounding - round all but the last installment
            const roundedAmount = Math.floor(amountPerInstallment * 100) / 100; // Round down to 2 decimal places
            const lastInstallmentIndex = remainingInstallments[remainingInstallments.length - 1];

            // Set all remaining installments to rounded amount
            remainingInstallments.forEach((index, i) => {
                if (i === remainingInstallments.length - 1) {
                    // Last installment gets the remaining balance to handle rounding
                    const usedAmount = installmentAmounts[changedIndex] + (roundedAmount * (remainingInstallments.length - 1));
                    const lastAmount = totalAmount - usedAmount;
                    installmentAmounts[index] = lastAmount;
                    $(`#installment_${index}`).val(lastAmount.toFixed(2));
                } else {
                    installmentAmounts[index] = roundedAmount;
                    $(`#installment_${index}`).val(roundedAmount.toFixed(2));
                }
            });

            updateTotals();
            generatePreview();
        }

        // Update total and remaining balance displays
        function updateTotals() {
            const currentTotal = installmentAmounts.reduce((sum, amount) => sum + amount, 0);
            const remainingBalance = totalAmount - currentTotal;

            $('#total-display').text(currentTotal.toFixed(2));
            $('#remaining-balance').text(remainingBalance.toFixed(2));

            // Color coding for balance
            const balanceElement = $('#remaining-balance');
            if (Math.abs(remainingBalance) < 0.01) {
                balanceElement.removeClass('text-danger text-warning').addClass('text-success');
            } else if (remainingBalance > 0) {
                balanceElement.removeClass('text-success text-danger').addClass('text-warning');
            } else {
                balanceElement.removeClass('text-success text-warning').addClass('text-danger');
            }
        }

        // Generate preview table
        function generatePreview() {
            const billingMonth = $('#billing_month').val();

            if (!billingMonth || installmentAmounts.length === 0) {
                $('#installment-preview-section').hide();
                return;
            }

            let html = '<div class="table-responsive">';
            html += '<table class="table table-sm table-striped">';
            html += '<thead><tr><th>#</th><th>Billing Month</th><th>Amount</th><th>Status</th></tr></thead>';
            html += '<tbody>';

            for (let i = 0; i < numberOfInstallments; i++) {
                const currentDate = new Date(billingMonth + '-01');
                currentDate.setMonth(currentDate.getMonth() + i);

                const formattedMonth = currentDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long'
                });

                html += `<tr>
                    <td>${i + 1}</td>
                    <td>${formattedMonth}</td>
                    <td>AED ${installmentAmounts[i].toFixed(2)}</td>
                    <td><span class="badge bg-warning">Pending</span></td>
                </tr>`;
            }

            const currentTotal = installmentAmounts.reduce((sum, amount) => sum + amount, 0);
            html += '</tbody>';
            html += '<tfoot><tr><td colspan="2"><strong>Total</strong></td><td><strong>AED ' + currentTotal.toFixed(2) + '</strong></td><td></td></tr></tfoot>';
            html += '</table></div>';

            $('#installment-preview').html(html);
            $('#installment-preview-section').show();

            // Enable submit button if totals match
            const remainingBalance = totalAmount - currentTotal;
            if (Math.abs(remainingBalance) < 0.01) {
                $('#submitBtn').prop('disabled', false);
            } else {
                $('#submitBtn').prop('disabled', true);
            }
        }

        // Event handlers
        $('#number_of_installments, #total_amount, #billing_month').on('change', function() {
            generateInstallmentInputs();
        });

        // Handle installment input changes
        $(document).on('input', '.installment-input', function() {
            const index = parseInt($(this).data('index'));
            const newAmount = parseFloat($(this).val()) || 0;

            // Validate amount
            if (newAmount < 0) {
                $(this).val('0.00');
                return;
            }

            adjustRemainingInstallments(index, newAmount);
        });

        // Form validation
        $('#installmentPlanForm').on('submit', function(e) {
            const totalAmountValue = parseFloat($('#total_amount').val());
            const numberOfInstallmentsValue = parseInt($('#number_of_installments').val());
            const billingMonth = $('#billing_month').val();

            if (!totalAmountValue || !numberOfInstallmentsValue || !billingMonth) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }

            if (totalAmountValue <= 0) {
                e.preventDefault();
                alert('Total amount must be greater than zero.');
                return false;
            }

            if (numberOfInstallmentsValue <= 0 || numberOfInstallmentsValue > 12) {
                e.preventDefault();
                alert('Number of installments must be between 1 and 12.');
                return false;
            }

            // Check if there's already a plan for the current month
            const currentMonth = new Date().toISOString().slice(0, 7); // YYYY-MM format
            if (billingMonth === currentMonth) {
                @if($existingCurrentMonthPlan)
                e.preventDefault();
                alert('An installment plan already exists for this rider in ' + new Date().toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long'
                }) + '. Please select a different starting month.');
                return false;
                @endif
            }

            // Validate that all installments sum to total amount
            const currentTotal = installmentAmounts.reduce((sum, amount) => sum + amount, 0);
            const remainingBalance = totalAmountValue - currentTotal;

            if (Math.abs(remainingBalance) > 0.01) {
                e.preventDefault();
                alert('The sum of all installments must equal the total amount. Please adjust the installment amounts.');
                return false;
            }

            // Add hidden inputs for individual installment amounts
            installmentAmounts.forEach((amount, index) => {
                $('<input>').attr({
                    type: 'hidden',
                    name: `installment_amounts[${index}]`,
                    value: amount.toFixed(2)
                }).appendTo('#installmentPlanForm');
            });

            // Disable submit button to prevent double submission
            $('#submitBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i>Creating...');
        });
    });
</script>