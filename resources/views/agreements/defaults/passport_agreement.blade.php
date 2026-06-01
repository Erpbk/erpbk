<h2 style="text-align:center;">PASSPORT CUSTODY &amp; HANDOVER AGREEMENT</h2>
<p><strong>Date:</strong> {agreement_date}</p>
<p>This Passport Agreement is made between <strong>{company_name}</strong> and <strong>{rider_name}</strong> (Rider ID: <strong>{rider_code}</strong>).</p>

<h3>Rider Details</h3>
<ul>
  <li><strong>Passport Number:</strong> {rider_passport_number}</li>
  <li><strong>Nationality:</strong> {rider_nationality}</li>
  <li><strong>Emirates ID:</strong> {rider_cnic}</li>
  <li><strong>Contact:</strong>@if(!empty($rider_phone)) {rider_phone} @endif @if(!empty($rider_email)) {rider_email} @endif</li>
</ul>

<h3>Agreement</h3>
<p>The {company_name} acknowledges that the passport may be held by the {rider_name} solely for legitimate employment and visa processing purposes, and will be returned upon request in accordance with company policy and applicable law.</p>
<p>The {rider_name} confirms the passport details above are accurate as of {current_date}.</p>