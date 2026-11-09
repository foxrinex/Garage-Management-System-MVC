<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $setting->garage_name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="icon" href="{{ asset('logo.png') }}">
</head>
<body class="body-home">
    <div class="black-fill"><br /> <br />
        <div class="container">
        <nav class="navbar navbar-expand-lg bg-light" id="homeNav">
          <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="{{ asset('logo.png') }}" width="40">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
              <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                  <a class="nav-link active" aria-current="page" href="#">Home</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="#about">About</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="#contact">Contact</a>
                </li>
              </ul>
              
              <ul class="navbar-nav me-right mb-2 mb-lg-0">
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('login') }}">Login</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('signup') }}">Signup</a>
                </li>
              </ul>
          </div>
            </div>
        </nav>

        <section class="welcome-text d-flex justify-content-center align-items-center flex-column">
            <img src="{{ asset('logo.png') }}">
            <h4>Welcome to {{ $setting->garage_name }}</h4>
            <p>{{ $setting->slogan }}</p>
        </section>

        <section id="about" class="d-flex justify-content-center align-items-center flex-column">
            <div class="card mb-3 card-1">
              <div class="row g-0">
                <div class="col-md-4">
                  <img src="{{ asset('logo.png') }}" class="img-fluid rounded-start">
                </div>
                <div class="col-md-8">
                  <div class="card-body">
                    <h5 class="card-title">About Us</h5>
                    <p class="card-text">{{ $setting->about }}</p>
                    <p class="card-text"><small class="text-muted">{{ $setting->garage_name }}</small></p>
                  </div>
                </div>
              </div>
            </div>
        </section>

        <section id="contact" class="d-flex justify-content-center align-items-center flex-column">
            <form method="post" action="{{ route('contact.send') }}">
                @csrf
                <h3>Contact Us</h3>

                @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger" role="alert">
                {{ session('error') }}
                </div>
                @endif
                @if (session('success'))
                <div class="alert alert-success" role="alert">
                {{ session('success') }}
                </div>
                @endif
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Email address</label>
                <input type="email" class="form-control" id="exampleInputEmail1" name="email" aria-describedby="emailHelp" value="{{ old('email') }}">
                <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" rows="4">{{ old('message') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </section>

        <div class="text-center text-light">
            Copyright &copy; {{ $setting->current_year }} {{ $setting->garage_name }}. All rights reserved.
        </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>    
</body>
</html>