@extends('layouts.app')

@section('content')
<div class="min-h-[50vh] flex flex-col items-center justify-center text-muted">
    <p>{{ $error ?? 'Person not found' }}</p>
    <button type="button" onclick="history.back()" class="text-accent text-sm mt-4">← Go back</button>
</div>
@endsection
