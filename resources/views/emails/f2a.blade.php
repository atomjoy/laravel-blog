@php
$password = (string) $password ?? '000000';
@endphp

<x-proton::email title="{{ __('email.f2a.subject') }}" locale="{{ app()->getlocale() }}">
	<x-slot:style>
		<style>
			.proton-table tr td {
				padding: 0px 40px;
			}

			.apilogin-logo {
				margin: 0px auto;
				margin-bottom: 20px;
				max-height: 100px;
				max-width: 50%;
			}

			ul {
				display: inline-block;
				padding: 10px 15px;
				margin: 5px 5px;
				border-radius: 6px;
				border: 1px dashed #024ef0aa;
				background: #fff;
				color: #024ef0;
				font-weight: 900;
				font-size: 21px;
			}
		</style>
	</x-slot:style>

	<x-proton::row>
		<x-proton::margin />

		<center>
			<img src="{{ config('default.f2a_image_url', 'https://raw.githubusercontent.com/atomjoy/proton/main/public/proton-default.png') }}"
				 alt="Image">
		</center>

		<h2>@lang('email.f2a.welcome') {{ $user?->name ?? '' }}!</h2>
		<p>@lang('email.f2a.message')</p>
	</x-proton::row>

	<x-proton::row>
		<x-proton::code>
			<ul>{{ $password[0] ?? 0 }}</ul>
			<ul>{{ $password[1] ?? 0 }}</ul>
			<ul>{{ $password[2] ?? 0 }}</ul>
			<ul>{{ $password[3] ?? 0 }}</ul>
			<ul>{{ $password[4] ?? 0 }}</ul>
			<ul>{{ $password[5] ?? 0 }}</ul>
		</x-proton::code>
	</x-proton::row>

	<x-proton::row>
		<h3>@lang('email.regards')</h3>
		<strong>{{ $user?->name ?? '' }}</strong>
		<p>@lang('email.regards_text')</p>
	</x-proton::row>

	<x-proton::row>
		<x-proton::divider />

		<center>
			<span class="proton-rights"> © @lang('email.rights') </span>
		</center>

		<br />

		<x-proton::flex>
			<x-proton::social name="twitter" />
			<x-proton::social name="facebook" />
			<x-proton::social name="instagram" />
			<x-proton::social name="tiktok" />
		</x-proton::flex>

		<x-proton::margin />
	</x-proton::row>
</x-proton::email>