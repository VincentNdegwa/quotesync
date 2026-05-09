<x-mail::message>
@if ($logoUrl)
![{{ $companyName }}]({{ $logoUrl }})
@else
# {{ $companyName }}
@endif

# {{ $subjectLine }}

{!! nl2br(e($messageBody)) !!}

<x-mail::button :url="$viewUrl">
View Quote
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
