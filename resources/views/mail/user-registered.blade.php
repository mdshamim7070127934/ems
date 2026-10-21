<x-mail::message>
# {{ __('Welcome to :app', ['app' => config('app.name')]) }}

{{ __('Hello :name,', ['name' => $userName]) }}

{{ __('An account has been created for you. Sign in with your email address to get started.') }}

<x-mail::button :url="$loginUrl">
{{ __('Sign in') }}
</x-mail::button>

{{ __('If you did not expect this email, you can ignore it.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
