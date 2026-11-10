<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .wishlist-img {
            height: 220px;
            width: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .wishlist-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: #fff;
        }
        .wishlist-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
        }
        .wishlist-card:hover .wishlist-img {
            transform: scale(1.05);
        }
        .card-title {
            font-size: 1.15rem;
            min-height: 2.4em;
            margin-bottom: 0.5rem;
        }
        .price-tag {
            font-size: 1.25rem;
            color: #0d6efd;
            font-weight: 700;
        }
        .btn-action {
            border-radius: 8px;
            padding: 8px 12px;
            font-weight: 500;
        }
        .page-header {
            background: #fff;
            padding: 2rem 0;
            margin-bottom: 3rem;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
    </style>
</head>
<body>
    @include('customer.inc.navbar')

    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-1 fw-bold"><i class="fa fa-heart text-danger me-2"></i>My Wishlist</h2>
                    <p class="text-muted mb-0">Items you've saved for later</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="btn-group shadow-sm">
                        <a href="{{ route('customer.dashboard') }}" class="btn btn-white border">
                            <i class="fa fa-dashboard me-1"></i> Dashboard
                        </a>
                        <a href="{{ route('customer.store') }}" class="btn btn-white border">
                            <i class="fa fa-shopping-basket me-1"></i> Store
                        </a>
                        <a href="{{ route('customer.showroom') }}" class="btn btn-primary">
                            <i class="fa fa-car me-1"></i> Showroom
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
                <i class="fa fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            @php $count = count($wishlistItems); @endphp

            @if ($count > 0)
                @for ($i = 0; $i < $count; $i = $i + 1)
                    @php
                        $item = $wishlistItems[$i];
                        
                        $displayName = "";
                        $displayImg = "";
                        $displayPrice = 0;
                        $viewLink = "";
                        $removeId = 0;
                        $removeType = "";
                        $isDeleted = false;

                        // Logic to check if it's a Spare Part
                        if (!empty($item->p_id)) {
                            $displayName = $item->product_name;
                            $displayImg = $item->p_img;
                            $displayPrice = $item->p_price;
                            $viewLink = route('customer.product.view', $item->p_id);
                            $removeId = $item->p_id;
                            $removeType = "product";
                        } 
                        // Logic to check if it's a Car
                        else if (!empty($item->c_id)) {
                            $displayName = $item->car_brand . " " . $item->car_model;
                            $displayImg = $item->c_img;
                            $displayPrice = $item->c_price;
                            $viewLink = route('customer.car.view', $item->c_id);
                            $removeId = $item->c_id;
                            $removeType = "car";
                        } 
                        // If not found in either table, it was deleted
                        else {
                            $isDeleted = true;
                            $removeId = (!empty($item->product_id)) ? $item->product_id : $item->car_id;
                            $removeType = (!empty($item->product_id)) ? "product" : "car";
                        }
                    @endphp

                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card h-100 wishlist-card shadow-sm">
                            
                            <div class="position-relative overflow-hidden">
                                @if ($isDeleted)
                                    <div class="wishlist-img d-flex flex-column align-items-center justify-content-center text-muted bg-light">
                                        <i class="fa fa-chain-broken fa-3x mb-2"></i>
                                        <small>Unavailable</small>
                                    </div>
                                @else
                                    <a href="{{ $viewLink }}">
                                        <img src="{{ asset('uploads/' . $displayImg) }}" 
                                             class="wishlist-img" 
                                             alt="{{ $displayName }}"
                                             onerror="this.src='https://via.placeholder.com/400x250?text=No+Image'">
                                    </a>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-dark opacity-75 fw-normal">
                                            {{ ($removeType == 'car') ? 'Vehicle' : 'Part' }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="card-body d-flex flex-column p-4">
                                <h5 class="card-title">
                                    @if ($isDeleted)
                                        <span class="text-danger fw-bold">Item Removed</span>
                                    @else
                                        <a href="{{ $viewLink }}" class="text-decoration-none text-dark fw-bold">
                                            {{ $displayName }}
                                        </a>
                                    @endif
                                </h5>
                                
                                @if (!$isDeleted)
                                    <p class="price-tag mb-3">${{ number_format($displayPrice, 2) }}</p>
                                @else
                                    <p class="text-muted small mb-3">This product is no longer in our inventory.</p>
                                @endif
                                
                                <div class="d-grid gap-2 mt-auto">
                                    @if (!$isDeleted)
                                        <a href="{{ $viewLink }}" class="btn btn-dark btn-action">
                                            <i class="fa fa-shopping-cart me-1"></i> View Item
                                        </a>
                                    @endif

                                    <a href="{{ route('customer.wishlist.handle', ['id' => $removeId, 'action' => 'remove', 'type' => $removeType, 'source' => 'wishlist']) }}" 
                                       class="btn btn-outline-danger btn-action border-0">
                                        <i class="fa fa-trash-o me-1"></i> Remove
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            @else
                <div class="col-12 text-center py-5">
                    <div class="bg-white p-5 rounded-4 shadow-sm">
                        <i class="fa fa-heart-o fa-5x text-light mb-4"></i>
                        <h4 class="text-muted">Your wishlist is lonely</h4>
                        <p class="text-muted mb-4">Explore our store and showroom to find items you love.</p>
                        <a href="{{ route('customer.store') }}" class="btn btn-primary px-4 py-2">Start Shopping</a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="mb-5"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>