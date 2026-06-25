<?php

/** @var \App\Models\RiderInvoices $riderInvoice */

use App\Services\RiderInvoice\RiderInvoiceViewDataBuilder;

extract(app(RiderInvoiceViewDataBuilder::class)->build($riderInvoice));
