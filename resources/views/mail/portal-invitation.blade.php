<x-mail::message>
@if ($isMagicLink)
# Your Portal Login Link

Click the button below to access your client portal. This link will expire in 24 hours.

<x-mail::button :url="route('portal.magic-link', ['token' => $magicLink->token])">
Access Portal
</x-mail::button>
@else
# Portal Invitation

You have been invited to access the client portal for {{ $invitation->client->company_name }}.

Click the button below to set up your account and view your quotes.

<x-mail::button :url="route('portal.register', ['token' => $invitation->token])">
Accept Invitation
</x-mail::button>

This invitation will expire in 7 days.
@endif

<x-slot:subcopy>
You are receiving this email because you were invited to access a client portal.
</x-slot:subcopy>
</x-mail::message>
