<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Order - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5">
        <h3 class="mb-4">Order Review</h3>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4 border-0" style="border-radius: 20px;">
                    <div class="card-header bg-white py-3 border-0"><strong>Items</strong></div>
                    <div class="card-body">
                        @php
                            $total = 0;
                        @endphp
                        @foreach ($items as $item)
                            @php
                                $subtotal = ($item->product->price ?? 0) * $item->quantity;
                                $total += $subtotal;
                            @endphp
                            <div class="d-flex justify-content-between mb-3 align-items-center">
                                <div>
                                    <h6 class="fw-bold mb-0">{{ $item->product->product_name ?? 'Product' }}</h6>
                                    <small class="text-muted">Quantity: {{ $item->quantity }}</small>
                                </div>
                                <span class="fw-bold">${{ number_format($subtotal, 2) }}</span>
                            </div>
                        @endforeach
                        <hr>
                        <div class="d-flex justify-content-between fw-bold h5">
                            <span>Total</span>
                            <span class="text-success">${{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-primary border-0" style="border-radius: 20px;">
                    <div class="card-header bg-primary text-white py-3" style="border-radius: 20px 20px 0 0;"><strong>Shipping Details</strong></div>
                    <div class="card-body p-4">
                        <p class="mb-1 small text-muted">Customer Name:</p>
                        <p class="fw-bold">{{ $user ? $user->fname . " " . $user->lname : 'Valued Customer' }}</p>
                        
                        <p class="mb-1 small text-muted">Contact Email:</p>
                        <p class="fw-bold mb-4">{{ $user ? $user->email_address : 'N/A' }}</p>
                        
                        <form action="{{ route('customer.checkout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 py-3 rounded-pill fw-bold shadow-sm">
                                Confirm & Purchase <i class="fa fa-check ms-1"></i>
                            </button>
                            <a href="{{ route('customer.cart') }}" class="btn btn-link w-100 text-muted mt-2 text-decoration-none">
                                <i class="fa fa-edit me-1"></i> Edit Cart
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>