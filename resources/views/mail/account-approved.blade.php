<x-mail::message>
# Account Approved

Hello {{ $userName }},

Your account has been approved. You can now sign in and access your account.

<x-mail::button :url="$loginUrl">
Sign In
</x-mail::button>

If you have not verified your email address yet, complete verification after signing in.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
