<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Support | Garage Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" href="{{ asset('logo.png') }}">
    
    <style>
        body { background-color: #f4f7f6; }
        .contact-card { border: none; border-radius: 15px; overflow: hidden; }
        .contact-header { background: #212529; color: white; padding: 2rem; }
        .form-control:focus { border-color: #ffc107; box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25); }
        .read-only-box { background-color: #e9ecef; cursor: not-allowed; font-weight: 500; }
        .btn-send { background-color: #ffc107; border: none; color: #000; font-weight: bold; transition: all 0.3s ease; }
        .btn-send:hover { background-color: #e0a800; transform: translateY(-2px); }
    </style>
</head>
<body>
    @include('customer.inc.navbar')

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="contact-card shadow-lg bg-white">
                    <div class="row g-0">
                        <div class="col-md-4 contact-header d-flex flex-column justify-content-center text-center">
                            <i class="fa fa-headphones fa-4x mb-3 text-warning"></i>
                            <h3>Support</h3>
                            <p class="small opacity-75">We usually respond within 24 hours.</p>
                        </div>

                        <div class="col-md-8 p-4 p-lg-5">
                            <h2 class="fw-bold mb-4">Send us a message</h2>
                            
                            @if (session('success'))
                                <div class="alert alert-success d-flex align-items-center mb-4">
                                    <i class="fa fa-check-circle me-2"></i>
                                    <div>{{ session('success') }}</div>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger d-flex align-items-center mb-4">
                                    <i class="fa fa-times-circle me-2"></i>
                                    <div>{{ session('error') }}</div>
                                </div>
                            @endif

                            <form action="{{ route('customer.support.send') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">FROM</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fa fa-user text-muted"></i></span>
                                            <input type="text" class="form-control border-start-0 read-only-box" value="{{ $fullName }}" readonly>
                                            <input type="hidden" name="full_name" value="{{ $fullName }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">REPLY EMAIL</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fa fa-envelope text-muted"></i></span>
                                            <input type="text" class="form-control border-start-0 read-only-box" value="{{ $email }}" readonly>
                                            <input type="hidden" name="email_address" value="{{ $email }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold">MESSAGE</label>
                                    <textarea name="message_text" class="form-control" rows="5" placeholder="Tell us how we can help you..." required></textarea>
                                </div>

                                <div class="d-flex align-items-center justify-content-between">
                                    <a href="{{ route('customer.support.send') }}" class="text-decoration-none text-muted"><i class="fa fa-chevron-left small"></i> Back</a>
                                    <button type="submit" class="btn btn-send px-4 py-2 shadow-sm">
                                        Send Message <i class="fa fa-paper-plane ms-2"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>