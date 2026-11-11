<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager - Garage Settings</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    @include('manager.inc.navbar')

    <div class="container mt-5" style="max-width: 700px;">
        <form action="{{ route('manager.settings.update') }}" method="POST" class="shadow p-3 mt-5 form-w bg-white rounded">
            @csrf
            
            <h3>Edit Garage Information</h3><hr>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Garage Name</label>
                <input type="text" name="garage_name" class="form-control" value="{{ old('garage_name', optional($setting)->garage_name) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Slogan</label>
                <input type="text" name="slogan" class="form-control" value="{{ old('slogan', optional($setting)->slogan) }}">
            </div>

            <div class="mb-3">
                <label class="form-label">About Us</label>
                <textarea name="about" class="form-control" rows="4">{{ old('about', optional($setting)->about) }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Year</label>
                <input type="number" name="current_year" class="form-control" value="{{ old('current_year', optional($setting)->current_year) }}" required>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>