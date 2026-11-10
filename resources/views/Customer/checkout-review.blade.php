<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Order</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5">
        <h3 class="mb-4">Order Review</h3>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><strong>Items</strong></div>
                    <div class="card-body">
                        @php
                            $total = 0;
                        @endphp
                        @foreach ($items as $item)
                            @php
                                $subtotal = ($item->product->price ?? 0) * $item->quantity;
                                $total += $subtotal;
                            @endphp
                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ $item->product->product_name ?? 'Product' }} (x{{ $item->quantity }})</span>
                                <span>${{ number_format($subtotal, 2) }}</span>
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
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white"><strong>Shipping Details</strong></div>
                    <div class="card-body">
                        <p class="mb-1 small text-muted">Customer Name:</p>
                        <p class="fw-bold">{{ $user ? $user->fname . " " . $user->lname : 'Valued Customer' }}</p>
                        
                        <p class="mb-1 small text-muted">Contact Email:</p>
                        <p class="fw-bold mb-4">{{ $user ? $user->email_address : 'N/A' }}</p>
                        
                        <form action="{{ route('customer.place.order') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 py-2">
                                Confirm & Purchase <i class="fa fa-check"></i>
                            </button>
                            <a href="{{ route('customer.cart') }}" class="btn btn-link w-100 text-muted mt-2 text-decoration-none">
                                <i class="fa fa-edit"></i> Edit Cart
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>