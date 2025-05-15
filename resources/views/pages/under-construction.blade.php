@extends('layouts.app')

@section('page')
<div class="d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="text-center">
        <h1 class="display-4 text-warning">🚧 Under Construction 🚧</h1>
        <p class="lead mt-3 text-dark">
            We're working hard to bring this page to life. Please check back soon!
        </p>
        <a href="{{ url()->previous() }}" class="btn btn-gradient mt-4">
            <i class="fa-solid fa-arrow-left mr-2"></i> Go Back
        </a>
    </div>
</div>
@endsection
