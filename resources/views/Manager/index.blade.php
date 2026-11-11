<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager - Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="icon" href="{{ asset('logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        .stat-card-link { text-decoration: none; display: block; transition: transform 0.2s ease-in-out; }
        .stat-card-link:hover { transform: translateY(-5px); }
        .profile-icon-placeholder { 
            width: 120px; 
            height: 120px; 
            background-color: #e9ecef; 
            color: #adb5bd; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 4rem; 
            border: 5px solid #fff; 
        }
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
    @include('manager.inc.navbar')

    <div class="container mt-5">
        
        <div class="p-5 mb-4 bg-white rounded-3 shadow-sm d-flex align-items-center">
            <div class="rounded-circle shadow-sm me-4 profile-icon-placeholder">
                <i class="fa fa-user"></i>
            </div>
            <div>
                <h1 class="display-6 fw-bold text-dark mb-1">
                    <i class="fa fa-dashboard text-primary"></i> Manager Dashboard
                </h1>
                <p class="text-muted mb-0">Welcome back, {{ optional($manager)->fname }}. Monitor your inventory, auctions, and repairs.</p>
                <a href="{{ route('manager.profile') }}" class="btn btn-sm btn-outline-primary mt-2 rounded-pill">Manage Profile</a>
            </div>
        </div>

        <div class="row mb-4 text-center">
            <div class="col-md-3 mb-3">
                <a href="{{ route('manager.inventory.view') }}" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-primary text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-cubes icon-bg"></i>
                        <h5 class="text-uppercase small mb-1" style="letter-spacing: 1px;">Inventory</h5>
                        <h2 class="mb-0">Manage Stock</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="{{ route('manager.showroom') }}" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-info text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-car icon-bg"></i>
                        <h5 class="text-uppercase small mb-1" style="letter-spacing: 1px;">Showroom</h5>
                        <h2 class="mb-0">Manage Cars</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="{{ route('manager.auction.dashboard') }}" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-dark text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-gavel icon-bg text-warning"></i>
                        <h5 class="text-uppercase small mb-1 text-warning" style="letter-spacing: 1px;">Auctions</h5>
                        <h2 class="mb-0 text-white">Live Control</h2>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="{{ route('manager.sales-history') }}" class="stat-card-link">
                    <div class="card shadow-sm border-0 bg-success text-white p-3 h-100 d-flex flex-column justify-content-center">
                        <i class="fa fa-money icon-bg"></i>
                        <h5 class="text-uppercase small mb-1" style="letter-spacing: 1px;">Revenue</h5>
                        <h2 class="mb-0">View Sales</h2>
                    </div>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa fa-shopping-cart text-muted me-2"></i> Recent Orders</h5>
                        <a href="{{ route('manager.sales-history') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentOrders as $order)
                                        @php
                                            $displayName = "";
                                            $imgPath = "";
                                            $isDeleted = false;

                                            if (!empty($order->car_id) && optional($order->car)->brand) {
                                                $displayName = optional($order->car)->brand . " " . optional($order->car)->model;
                                                $imgPath = asset('uploads/' . optional($order->car)->image);
                                            } elseif (!empty($order->product_id) && optional($order->product)->product_name) {
                                                $displayName = optional($order->product)->product_name;
                                                $imgPath = asset('uploads/' . optional($order->product)->image);
                                            } else {
                                                $displayName = "Deleted Item";
                                                $isDeleted = true;
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                @if (!$isDeleted)
                                                    <img src="{{ $imgPath }}" width="45" height="45" class="rounded me-2 border" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/100'">
                                                @else
                                                    <div class="deleted-item-icon me-2">
                                                        <i class="fa fa-archive"></i>
                                                    </div>
                                                @endif
                                                <span class="fw-semibold text-truncate d-inline-block" style="max-width: 150px;">{{ $displayName }}</span>
                                            </td>
                                            <td class="small">{{ optional($order->customer)->fname }}</td>
                                            <td class="small text-muted">{{ $order->date_ordered }}</td>
                                            <td class="fw-bold text-success">${{ number_format($order->total_price, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No recent orders found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa fa-wrench text-muted me-2"></i> Recent Repairs</h5>
                        <a href="{{ route('manager.repair-house') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentRepairs as $rep)
                                        <tr>
                                            <td>
                                                <img src="{{ asset('uploads/' . $rep->car_image) }}" width="45" height="45" class="rounded me-2 border" style="object-fit: cover;" onerror="this.src='https://via.placeholder.com/100'">
                                                <span class="fw-semibold">{{ $rep->brand . " " . $rep->model }}</span>
                                            </td>
                                            <td class="small">{{ optional($rep->customer)->fname }}</td>
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
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No recent repairs found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fa fa-bolt text-warning me-2"></i> Management Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="{{ route('manager.inventory') }}" class="btn btn-primary p-3 text-start">
                                <i class="fa fa-plus-circle me-2"></i> Add New Parts
                            </a>
                            <a href="{{ route('manager.messages') }}" class="btn btn-danger text-white p-3 text-start">
                                <i class="fa fa-envelope me-2"></i> Inbox ({{ $unreadCount }})
                            </a>
                            <a href="{{ route('manager.repair-house') }}" class="btn btn-success text-white p-3 text-start">
                                <i class="fa fa-university me-2"></i> Access Repair House
                            </a>
                            <a href="{{ route('manager.warranties') }}" class="btn btn-secondary text-white p-3 text-start">
                                <i class="fa fa-shield me-2"></i> Review Warranties
                            </a>
                            <a href="{{ route('manager.employees.index') }}" class="btn btn-outline-dark p-3 text-start">
                                <i class="fa fa-users me-2"></i> Employee Directory
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 bg-dark text-white">
                    <div class="card-body text-center p-4">
                        <i class="fa fa-cog fa-2x mb-2 text-warning"></i>
                        <h5>System Settings</h5>
                        <p class="small opacity-75">Configure garage settings.</p>
                        <a href="{{ route('manager.settings') }}" class="btn btn-sm btn-warning">Edit Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>