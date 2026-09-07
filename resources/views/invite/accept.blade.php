<x-layouts::public title="You're invited" heading="You're invited">
    <p>
        This invite is for <strong class="font-medium">{{ $invite->email }}</strong>.
    </p>
    <form method="POST" action="{{ route('invites.accept', $token) }}">
        @csrf

        <label for="username">Username (permanent)</label>
        <input type="text" name="username" id="username" autocomplete="username" value="{{ old('username') }}" />
        @error('username')
            <p>{{ $message }}</p>
        @enderror

        <label for="name">Display name</label>
        <input type="text" name="name" id="name" autocomplete="name"
            value="{{ old('name', $invite->suggested_name) }}" />

        <button type="submit">Choose username</button>
    </form>
</x-layouts::public>
