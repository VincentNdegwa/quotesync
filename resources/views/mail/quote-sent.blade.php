<x-mail::message>
@if ($logoUrl)
![{{ $companyName }}]({{ $logoUrl }})
@else
# {{ $companyName }}
@endif

# Your quote is ready

@if ($messageBody !== '')
{!! nl2br(e($messageBody)) !!}
@endif

@if ($coverMessage)
<x-mail::panel>
{!! nl2br(e($coverMessage)) !!}
</x-mail::panel>
@endif

<x-mail::table>
| Quote | Total |
| :--- | :--- |
| {{ $quoteNumber }} - {{ $quoteTitle }} | {{ $quoteTotal }} |
</x-mail::table>

@if (!empty($lineItems))
<x-mail::table>
| Item | Qty | Total |
| :--- | :--- | :--- |
@foreach ($lineItems as $lineItem)
| {{ $lineItem['name'] }} | {{ $lineItem['quantity'] }} | {{ $lineItem['total'] }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="$viewUrl">
View & Accept Quote
</x-mail::button>

@if ($validUntil)
Valid until **{{ $validUntil }}**
@endif

<x-slot:subcopy>
You are receiving this email because a quote was shared with you.
@if ($unsubscribeUrl)
<br>
[Unsubscribe / opt-out]({{ $unsubscribeUrl }})
@endif
</x-slot:subcopy>
</x-mail::message>
