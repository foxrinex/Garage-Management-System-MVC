<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Order</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white text-center">
                        <h4 class="mb-0">Confirm Your Order</h4>
                    </div>
                    <div class="card-body text-center">
                        <img src="{{ asset('uploads/' . $product->image) }}" 
                             width="200" 
                             class="rounded mb-3"
                             onerror="this.src='https://via.placeholder.com/200?text=Product+Image'">
                        
                        <h3>{{ $product->product_name }}</h3>
                        <p class="text-muted">{{ $product->description }}</p>
                        <h2 class="text-primary">${{ number_format($product->price, 2) }}</h2>
                        
                        <hr>
                        
                        <form action="{{ route('customer.place.order') }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="price" value="{{ $product->price }}">
                            
                            <div class="mb-3 text-start">
                                <label class="form-label">Quantity</label>
                                <input type="number" 
                                       name="order_qty" 
                                       class="form-control" 
                                       value="1" 
                                       min="1" 
                                       max="{{ $product->quantity }}">
                                <small class="text-muted">Available stock: {{ $product->quantity }}</small>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100">
                                Confirm Purchase
                            </button>
                        </form>

                        <a href="{{ route('customer.store') }}" class="btn btn-link mt-2">Back to Store</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>