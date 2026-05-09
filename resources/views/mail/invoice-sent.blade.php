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

@if($publicInvoiceUrl)
<x-mail::button :url="$publicInvoiceUrl">
View Invoice Online
</x-mail::button>
@endif

Thanks,<br>
{{ $companyName }}
</x-mail::message>
