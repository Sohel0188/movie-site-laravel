@extends('layouts.app')

@section('content')
<section class="mt-10 text-center py-20 text-muted">
    <p class="text-lg">{{ $message }}</p>
    <a href="{{ $link }}" class="text-accent text-sm mt-2 inline-block">{{ $linkText }}</a>
</section>
@endsection
