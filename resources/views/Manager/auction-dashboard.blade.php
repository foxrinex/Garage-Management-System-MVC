<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager - Auction Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .stats-card { border: none; border-radius: 12px; transition: 0.3s; min-height: 100px; }
        .auction-card { transition: 0.3s; border: none; border-radius: 15px; overflow: hidden; position: relative; }
        .auction-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; position: sticky; top: 20px; }
        .car-img-top { height: 180px; width: 100%; object-fit: cover; }
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 0.72rem; padding: 4px 8px; border-radius: 5px; border: 1px solid #eee; }
        
        .page-header-container { display: flex; align-items: center; margin-bottom: 25px; padding: 10px 0; border-bottom: 1px solid #dee2e6; }
        .page-header-icon { background-color: #0d6efd; color: white; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 1.5rem; }
        .page-header-title { font-weight: 800; font-size: 1.8rem; color: #212529; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
        
        .bid-display { background: #fdfdfd; border-top: 1px dashed #ddd; padding: 10px; text-align: center; }
        .bid-number { font-size: 1.4rem; font-weight: 800; display: block; }
        .bid-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; color: #888; }
        .card-header-text { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; font-weight: 600; }
        .clickable-card-link { text-decoration: none; color: inherit; display: block; }
        .clickable-card-link:hover { color: #0d6efd; }
    </style>
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container-fluid mt-4 px-4">
        
        <div class="page-header-container">
            <div class="page-header-icon bg-primary">
                <i class="fa fa-gavel"></i>
            </div>
            <h2 class="page-header-title">Auction Management</h2>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-gavel fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Live Auctions</div>
                            <h4 class="mb-0 fw-bold">{{ $totalActive }} Units</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <a href="{{ route('manager.auction.sales') }}" style="text-decoration: none;">
                    <div class="card stats-card bg-success text-white shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <i class="fa fa-money fa-2x me-3"></i>
                            <div>
                                <div class="card-header-text">Total Earnings</div>
                                <h4 class="mb-0 fw-bold">${{ number_format($totalEarnings) }}</h4>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="#" style="text-decoration: none;">
                    <div class="card stats-card bg-warning text-dark shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <i class="fa fa-clock-o fa-2x me-3"></i>
                            <div>
                                <div class="card-header-text">Pending Payments</div>
                                <h4 class="mb-0 fw-bold">{{ $unpaidAuctions }}</h4>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-dark text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-history fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Total Auctions</div>
                            <h4 class="mb-0 fw-bold">{{ $auctionCount }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="filter-section shadow-sm mb-4">
                    <form action="{{ route('manager.auction.dashboard') }}" method="GET">
                        
                        <h6 class="fw-bold mb-3"><i class="fa fa-search"></i> Quick Search</h6>
                        <div class="mb-4">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Model or Brand..." value="{{ request('search') }}">
                        </div>

                        <h6 class="fw-bold mb-2">Category & Status</h6>
                        <div class="mb-2">
                            <select name="category" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->category_name }}" {{ request('category') == $cat->category_name ? 'selected' : '' }}>
                                        {{ $cat->category_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                           <select name="status_filter" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="Active" {{ request('status_filter') == 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Closed" {{ request('status_filter') == 'Closed' ? 'selected' : '' }}>Closed</option>
                                <option value="Sold" {{ request('status_filter') == 'Sold' ? 'selected' : '' }}>Sold</option>
                                <option value="Failed" {{ request('status_filter') == 'Failed' ? 'selected' : '' }}>Failed</option>
                           </select>
                        </div>

                        <h6 class="fw-bold mb-2">Technical Specs</h6>
                        <div class="mb-2">
                            <select name="transmission" class="form-select form-select-sm">
                                <option value="">Transmission</option>
                                <option value="Automatic" {{ request('transmission') == 'Automatic' ? 'selected' : '' }}>Automatic</option>
                                <option value="Manual" {{ request('transmission') == 'Manual' ? 'selected' : '' }}>Manual</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <input type="number" name="max_mileage" class="form-control form-control-sm" placeholder="Max Mileage (km)" value="{{ request('max_mileage') }}">
                        </div>

                        <h6 class="fw-bold mb-2">Bid Price Range</h6>
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min $" value="{{ request('min_price') }}">
                            </div>
                            <div class="col-6">
                                <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max $" value="{{ request('max_price') }}">
                            </div>
                        </div>

                        <h6 class="fw-bold mb-2">Manufacturing Year</h6>
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <input type="number" name="min_year" class="form-control form-control-sm" placeholder="From" value="{{ request('min_year') }}">
                            </div>
                            <div class="col-6">
                                <input type="number" name="max_year" class="form-control form-control-sm" placeholder="To" value="{{ request('max_year') }}">
                            </div>
                        </div>

                        <h6 class="fw-bold mb-2">Auction End Date</h6>
                        <div class="mb-2">
                            <label class="small text-muted">From:</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                        </div>
                        <div class="mb-4">
                            <label class="small text-muted">To:</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-sm btn-dark">Apply Filters</button>
                            <a href="{{ route('manager.auction.dashboard') }}" class="btn btn-sm btn-outline-secondary">Reset All</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0">Auction List</h3>
                    <div class="d-flex gap-2">
                        <a href="{{ route('manager.auction.sales') }}" class="btn btn-outline-dark" style="font-weight:700; border-radius:10px; padding:10px 20px;">
                            <i class="fa fa-line-chart me-2"></i> AUCTION SALES
                        </a>
                        <a href="{{ route('manager.auction.create') }}" class="btn btn-success" style="font-weight:700; border-radius:10px; padding:10px 20px;">
                            <i class="fa fa-plus-circle me-2"></i> START NEW AUCTION
                        </a>
                    </div>
                </div>

                <div class="row">
                    @if ($auctionCount > 0)
                        @foreach ($auctions as $a)
                            @php
                                $isCarDeleted = empty($a->car);
                                $statusColor = "text-primary";
                                $displayStatus = $a->status;

                                if ($isCarDeleted) {
                                    $statusColor = "text-danger";
                                    $displayStatus = "CAR DELETED";
                                } else {
                                    if ($a->status == 'Closed') { $statusColor = "text-secondary"; }
                                    if ($a->status == 'Failed') { $statusColor = "text-danger"; }
                                    if ($a->status == 'Sold') { 
                                        $statusColor = "text-success"; 
                                        $displayStatus = "Sold / Closed";
                                    }
                                }
                            @endphp
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card auction-card shadow-sm h-100">
                                    @if ($isCarDeleted)
                                        <div class="car-img-top bg-secondary d-flex align-items-center justify-content-center text-white">
                                            <i class="fa fa-trash fa-3x"></i>
                                        </div>
                                    @else
                                        <a href="#">
                                            <img src="{{ asset('uploads/' . $a->car->image) }}" class="car-img-top" onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
                                        </a>
                                    @endif
                                    
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-primary fw-bold text-uppercase">
                                                {{ $isCarDeleted ? 'DELETED' : $a->car->brand }}
                                            </small>
                                            <span class="badge bg-light text-dark border">
                                                {{ $isCarDeleted ? 'None' : ($a->car->category->category_name ?? 'N/A') }}
                                            </span>
                                        </div>

                                        @if ($isCarDeleted)
                                            <h5 class="card-title fw-bold mb-2 text-muted">Stock Record Removed</h5>
                                        @else
                                            <a href="#" class="clickable-card-link">
                                                <h5 class="card-title fw-bold mb-2">{{ $a->car->year . " " . $a->car->model }}</h5>
                                            </a>
                                        @endif
                                        
                                        <div class="d-flex flex-wrap gap-1 mb-3">
                                            @if (!$isCarDeleted)
                                                <span class="spec-badge"><i class="fa fa-cog"></i> {{ $a->car->transmission }}</span>
                                                <span class="spec-badge"><i class="fa fa-dashboard"></i> {{ number_format($a->car->mileage) }} km</span>
                                            @endif
                                            <span class="spec-badge text-danger"><i class="fa fa-clock-o"></i> Ends: {{ date('M d, H:i', strtotime($a->end_time)) }}</span>
                                        </div>

                                        <div class="p-2 rounded border bg-light text-center">
                                            <small class="text-muted d-block card-header-text">Current Status</small>
                                            <span class="fw-bold {{ $statusColor }}">{{ $displayStatus }}</span>
                                        </div>
                                        
                                        @if ($isCarDeleted)
                                            <div class="text-center mt-2 small text-danger fw-bold">the car has been removed from the showroom</div>
                                        @endif
                                    </div>

                                    <div class="bid-display">
                                        <span class="bid-label">Current High Bid</span>
                                        <span class="bid-number text-success">
                                            ${{ number_format($a->current_bid) }}
                                        </span>
                                    </div>

                                    <div class="card-footer bg-transparent border-0 pb-3">
                                        <div class="d-flex flex-column gap-2">
                                            <a href="#" class="btn btn-sm btn-outline-primary w-100">
                                                <i class="fa fa-list"></i> View Bid History
                                            </a>
                                            @if ($a->status == 'Active')
                                                <button onclick="endAuction({{ $a->id }})" class="btn btn-sm btn-success">
                                                    <i class="fa fa-check-circle"></i> End & Settle Winner
                                                </button>
                                                <button onclick="stopAuction({{ $a->id }})" class="btn btn-sm btn-danger">
                                                    <i class="fa fa-stop-circle"></i> Terminate Auction
                                                </button>
                                            @else
                                                <a href="{{ route('manager.auction.sales') }}" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fa fa-file-text-o"></i> View Sale Details
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-12 text-center py-5">
                            <i class="fa fa-gavel fa-3x text-muted"></i>
                            <h5 class="mt-3 text-muted">No auctions found matching filters.</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function stopAuction(id) {
            window.location.href = "/manager/auction-delete/" + id;
        }

        function endAuction(id) {
            window.location.href = "/manager/auction-end/" + id;
        }
    </script>
</body>
</html>