<x-mail::message>
# {{ $subjectLine }}

{{ $messageBody }}

---

## Invoice Details

**Invoice #:** {{ $invoiceNumber }}
**Title:** {{ $invoiceTitle }}
**Total:** {{ $invoiceTotal }}
**Due Date:** {{ $dueDate ?? 'N/A' }}

@if($notes)
**Notes:** {{ $notes }}
@endif

---

## Line Items

| Description | Qty | Total |
|-------------|-----|-------|
@foreach($lineItems as $item)
| {{ $item['name'] }} | {{ $item['quantity'] }} | {{ $item['total'] }} |
@endforeach

---

<x-mail::button :url="url('/invoices')">
View Invoices
</x-mail::button>

Thanks,<br>
{{ $companyName }}
</x-mail::message>
