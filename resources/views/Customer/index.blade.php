<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer - Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="icon" href="{{ asset('logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        .stat-card-link { text-decoration: none; display: block; transition: transform 0.2s ease-in-out; }
        .stat-card-link:hover { transform: translateY(-5px); }
        .profile-img { width: 120px; height: 120px; object-fit: cover; border: 5px solid #fff; }
        .icon-bg { font-size: 2rem; opacity: 0.3; position: absolute; right: 15px; top: 15px; }
        .card { position: relative; overflow: hidden; }
        .deleted-item-icon {
            width: 45px;
            height: 45px;
            background-color: #6c757d;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5">
        
        <div class="p-5 mb-4 bg-white rounded-3 shadow-sm d-flex align-items-center">
            <img src="{{ asset('img/Customer-' . ($customer->gender ?? 'Male') . '.png') }}" 
                 class="rounded-circle shadow-sm me-4 profile-img" alt="Profile"
                 onerror="this.src='{{ asset('img/Customer-Male.png') }}'">
            <div>
                <h1 class="display-6 fw-bold text-dark mb-1">
                    <i class="fa fa-user-circle-o text-primary"></i> Hello, {{ $customer->fname ?? 'Customer' }}!
                </h1>
                <p class="text-muted mb-0">Manage your garage, builds, and bids from one place.</p>
                <a href="{{ route('customer.profile') }}" class="btn btn-sm btn-outline-primary mt-2 rounded-pill">Edit Profile</a>
            </div>
        </div>

        <div class="row mb-4 text-center">
            <div class="col-md-3 mb-3">
                <a href="{{ route('customer.cart') }}" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-success text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-shopping-cart icon-bg"></i>
                        <h5 class="text-uppercase small mb-1" style="letter-spacing: 1px;">Shopping Cart</h5>
                        <h2 class="mb-0">{{ $cartCount }} Items</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="{{ route('customer.wishlist') }}" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-danger text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-heart icon-bg"></i>
                        <h5 class="text-uppercase small mb-1" style="letter-spacing: 1px;">Saved Items</h5>
                        <h2 class="mb-0">{{ $wishCount }} Wishlisted</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="{{ route('customer.auction.lobby') }}" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-dark text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-gavel icon-bg text-warning"></i>
                        <h5 class="text-uppercase small mb-1 text-warning" style="letter-spacing: 1px;">Make a Bid</h5>
                        <h2 class="mb-0 text-white">Auctions</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="{{ route('customer.builder') }}" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-warning p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-wrench icon-bg text-dark"></i>
                        <h5 class="text-uppercase small mb-1 text-dark" style="letter-spacing: 1px;">Custom Build</h5>
                        <h2 class="mb-0 text-dark">Build a Car</h2>
                    </div>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa fa-history text-muted me-2"></i> Recent Orders</h5>
                        <a href="{{ route('customer.history') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($recentOrders->count() > 0)
                                        @foreach ($recentOrders as $order)
                                            @php
                                                $displayName = "";
                                                $imgPath = "";
                                                $isDeleted = false;

                                                if ($order->car_id && $order->car) {
                                                    $displayName = $order->car->brand . " " . $order->car->model;
                                                    $imgPath = asset('uploads/' . $order->car->image);
                                                } elseif ($order->product_id && $order->product) {
                                                    $displayName = $order->product->product_name;
                                                    $imgPath = asset('uploads/' . $order->product->image);
                                                } else {
                                                    $displayName = "Deleted Item";
                                                    $isDeleted = true;
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if (!$isDeleted)
                                                        <img src="{{ $imgPath }}" width="45" height="45" class="rounded me-2 border" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/45'">
                                                    @else
                                                        <div class="deleted-item-icon me-2">
                                                            <i class="fa fa-archive"></i>
                                                        </div>
                                                    @endif
                                                    <span class="fw-semibold">{{ $displayName }}</span>
                                                </td>
                                                <td class="small text-muted">{{ $order->date_ordered }}</td>
                                                <td><span class="badge bg-light text-dark border"><i class="fa fa-check-circle text-success"></i> Completed</span></td>
                                                <td class="fw-bold text-success">${{ number_format($order->total_price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No recent purchases found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa fa-wrench text-muted me-2"></i> Recent Repairs</h5>
                        <a href="{{ route('customer.repair.dashboard') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Repair ID</th>
                                        <th>Status</th>
                                        <th>Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($recentRepairs->count() > 0)
                                        @foreach ($recentRepairs as $rep)
                                            <tr>
                                                <td>
                                                    <img src="{{ asset('uploads/' . $rep->car_image) }}" width="45" height="45" class="rounded me-2 border" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/100'">
                                                    <span class="fw-semibold">{{ $rep->brand . " " . $rep->model }}</span>
                                                </td>
                                                <td class="small text-muted">#{{ $rep->repair_id }}</td>
                                                <td>
                                                    @php
                                                        $statusClass = "bg-light text-dark";
                                                        if ($rep->status == "Completed") { $statusClass = "bg-success text-white"; }
                                                        if ($rep->status == "Repairing") { $statusClass = "bg-primary text-white"; }
                                                        if ($rep->status == "Pending") { $statusClass = "bg-warning text-dark"; }
                                                        if ($rep->status == "Rejected") { $statusClass = "bg-danger text-white"; }
                                                    @endphp
                                                    <span class="badge {{ $statusClass }} border">
                                                        {{ $rep->status }}
                                                    </span>
                                                </td>
                                                <td class="fw-bold text-dark">
                                                    ${{ number_format($rep->labor_fee + $rep->parts_total, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No recent repairs found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fa fa-bolt text-warning me-2"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="{{ route('customer.store') }}" class="btn btn-primary p-3 text-start">
                                <i class="fa fa-shopping-bag me-2"></i> Browse Parts Store
                            </a>
                            <a href="{{ route('customer.showroom') }}" class="btn btn-info text-white p-3 text-start">
                                <i class="fa fa-car me-2"></i> Visit Showroom
                            </a>
                            <a href="{{ route('customer.repair.dashboard') }}" class="btn btn-success text-white p-3 text-start">
                                <i class="fa fa-wrench me-2"></i> Repair Your Car
                            </a>
                            <a href="{{ route('customer.history') }}" class="btn btn-outline-dark p-3 text-start">
                                <i class="fa fa-shield me-2"></i> File Warranty Claim
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 bg-dark text-white">
                    <div class="card-body text-center p-4">
                        <i class="fa fa-envelope-o fa-2x mb-2 text-warning"></i>
                        <h5>Need Help?</h5>
                        <p class="small opacity-75">Have questions about an order or a custom build?</p>
                        <a href="{{ route('customer.contact') }}" class="btn btn-sm btn-warning">Message Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>