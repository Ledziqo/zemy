<link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ substr(hash_file('sha256', public_path('assets/app.css')), 0, 12) }}">
@if($alpine ?? false)
    <script defer src="{{ asset('assets/alpine.min.js') }}?v={{ substr(hash_file('sha256', public_path('assets/alpine.min.js')), 0, 12) }}"></script>
@endif
