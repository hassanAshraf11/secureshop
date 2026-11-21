@extends('layout')
@section('title', 'Create an account')
@section('content')<section class="formcard">
        <div class="eyebrow"><i></i> SecureShop account</div>
        <h1>Create an account</h1>
        <p>Start with 1,000 EGP in demo store credit.</p>
        <form class="stack" method="post" action="/register">@csrf
            <label>Full name<input name="name" value="{{ old('name') }}" maxlength="80" autocomplete="name"
                    required></label>
            <label>Email address<input name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                    required></label>
            <label>Password<input name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password"
                    required><small>Use 12 or more characters.</small></label>
            <label>Confirm password<input name="password_confirmation" type="password" autocomplete="new-password"
                    required></label>
            <button class="action hot">Create account</button>
        </form>
        <p class="formfoot">Already registered? <a href="/login">Sign in</a></p>
</section>@endsection
