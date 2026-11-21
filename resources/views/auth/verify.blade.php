@extends('layout')
@section('title', 'Confirm your email')
@section('content')<section class="formcard">
        <div class="eyebrow"><i></i> SecureShop account</div>
        <h1>Confirm your email</h1>
        <p>Confirm ownership of this inbox. A sign-in code will be emailed to you each time you sign in.</p>
        <form method="post" action="/verify/{{ $id }}" class="stack">@csrf
            <label>Verification key<input name="token" data-verification-key autocomplete="off" required minlength="64"
                    maxlength="64"><small>Your email link fills this in. You can also paste the key from the
                    message.</small></label>
            <button class="action hot">Confirm email</button>
        </form>
</section>@endsection
