<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .out-of-stock-bg { background-color: #fce4e4; }
        .qty-controls { display: flex; align-items: center; gap: 10px; }
        .stock-error { color: #dc3545; font-size: 0.85rem; margin-top: 5px; border: 1px solid #dc3545; padding: 5px; border-radius: 4px; background-color: #fff; }
    </style>
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5">
        <h3 class="mb-4"><i class="fa fa-shopping-cart text-primary"></i> Shopping Cart</h3>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="table-responsive shadow-sm bg-white p-3">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if (count($cartItems) > 0)
                        @foreach ($cartItems as $item)
                            <tr class="{{ (!$item['is_available'] || $item['insufficient_stock']) ? 'out-of-stock-bg' : '' }}">
                                <td>
                                    @if (!empty($item['image']))
                                        <img src="{{ asset('uploads/' . $item['image']) }}" width="50" class="rounded shadow-sm">
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $item['product_name'] }}</strong>
                                    
                                    @if ($item['insufficient_stock'] && $item['is_available'])
                                        <div class="stock-error">
                                            <i class="fa fa-exclamation-triangle"></i> 
                                            Error: Only {{ $item['stock_qty'] }} items left in stock.
                                        </div>
                                    @endif
                                </td>
                                <td>${{ number_format($item['price'], 2) }}</td>
                                <td>
                                    <div class="qty-controls">
                                        <span class="fw-bold mx-1">{{ $item['quantity'] }}</span>

                                        @if ($item['quantity'] > 1)
                                            <a href="{{ route('customer.cart.reduce', $item['id']) }}" 
                                               class="btn btn-sm btn-outline-danger"
                                               title="Reduce Quantity">
                                                <i class="fa fa-minus"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                                <i class="fa fa-minus"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="fw-bold">${{ number_format($item['subtotal'], 2) }}</td>
                                <td>
                                    @if ($item['is_available'] && !$item['insufficient_stock'])
                                        <span class="badge bg-success">Available</span>
                                    @elseif ($item['insufficient_stock'] && $item['is_available'])
                                        <span class="badge bg-warning text-dark">Stock Low</span>
                                    @else
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('customer.cart.remove', $item['id']) }}" 
                                       class="btn btn-danger btn-sm"
                                       title="Remove Item">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <p class="text-muted mb-0">Your cart is empty.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="d-flex gap-2">
                    <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-primary">
                        <i class="fa fa-home"></i> Home
                    </a>
                    <a href="{{ route('customer.store') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-shopping-bag"></i> Store
                    </a>
                </div>

                @if ($grandTotal > 0)
                <div class="text-end">
                    @if (!$canPlaceOrder)
                        <div class="text-danger mb-2 small">
                            <i class="fa fa-warning"></i> Some items have stock issues.
                        </div>
                    @endif

                    <h4 class="mb-3">Total: <span class="text-success">${{ number_format($grandTotal, 2) }}</span></h4>
                    
                    <a href="{{ route('customer.checkout.review') }}" 
                        class="btn btn-success btn-lg px-5 {{ !$canPlaceOrder ? 'disabled' : '' }}">
                        Proceed to Checkout <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>