@extends('layout')
@section('title', 'Email verification code')
@section('content')<section class="formcard">
    <div class="eyebrow"><i></i> Two-step sign-in</div>
    <h1>Check your email</h1>
    <p>Enter the six-digit SecureShop sign-in code we sent to your verified email address. The code expires after 60 seconds.</p>
    <form method="post" action="/email-code" class="stack">@csrf
        <label>Email sign-in code
            <input name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                autocomplete="one-time-code" required autofocus>
        </label>
        <button class="action hot">Verify and sign in</button>
    </form>
    <p class="formfoot">If the code expires, sign in again to request a new one.</p>
</section>@endsection
