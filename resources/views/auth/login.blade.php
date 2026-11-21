@extends('layout')
@section('title', 'Welcome back')
@section('content')<section class="formcard">
        <div class="eyebrow"><i></i> SecureShop account</div>
        <h1>Welcome back</h1>
        <p>Sign in with your password, then enter the six-digit code we email you.</p>
        <form class="stack" method="post" action="/login">@csrf
            <label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="username"
                    required></label>
            <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
            <button class="action hot">Continue</button>
        </form>
        <p class="formfoot">New to SecureShop? <a href="/register">Create an account</a></p>
</section>@endsection
