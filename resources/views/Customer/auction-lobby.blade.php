<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Auction Lobby - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        .car-card { transition: 0.3s; border: none; border-radius: 15px; overflow: hidden; }
        .car-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; position: sticky; top: 20px; }
        .car-img-top { height: 200px; width: 100%; object-fit: cover; }
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 0.75rem; padding: 4px 8px; border-radius: 5px; border: 1px solid #eee; }
        .bid-tag { font-size: 1.2rem; font-weight: 800; color: #0d6efd; }
        .timer-badge { font-size: 0.85rem; font-weight: bold; background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 20px; }
        a { text-decoration: none; color: inherit; }
    </style>
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container-fluid mt-4 px-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold"><i class="fa fa-gavel me-2"></i>Live Auction Lobby</h2>
                    <p class="text-muted">Find and bid on premium vehicles.</p>
                </div>
                <a href="{{ route('customer.auction.history') }}" class="btn btn-dark rounded-pill px-4 shadow-sm">
                    <i class="fa fa-history me-2"></i>My Bids & History
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="filter-section shadow-sm mb-4">
                    <form action="{{ route('customer.auction.lobby') }}" method="GET">
                        
                        <h6 class="fw-bold mb-3">Search</h6>
                        <div class="input-group mb-4">
                            <input type="text" name="search" class="form-control" placeholder="Brand or Model..." value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                        </div>

                        <h6 class="fw-bold mb-3">Specifications</h6>

                        <div class="mb-3">
                            <label class="small text-muted">Category</label>
                            <select name="cat_id" class="form-select">
                                <option value="">All Types</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ request('cat_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->category_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Current Bid Range</label>
                            <div class="input-group">
                                <input type="number" name="min_price" class="form-control" placeholder="Min" value="{{ request('min_price') }}">
                                <input type="number" name="max_price" class="form-control" placeholder="Max" value="{{ request('max_price') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Year Range</label>
                            <div class="input-group">
                                <input type="number" name="min_year" class="form-control" placeholder="From" min="1900" max="{{ $currentSystemYear }}" value="{{ request('min_year') }}">
                                <input type="number" name="max_year" class="form-control" placeholder="To" min="1900" max="{{ $currentSystemYear }}" value="{{ request('max_year') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Max Mileage (km)</label>
                            <input type="number" name="max_mileage" class="form-control" placeholder="Up to..." value="{{ request('max_mileage') }}">
                        </div>

                        <div class="mb-4">
                            <label class="small text-muted">Transmission</label>
                            <select name="trans" class="form-select">
                                <option value="">Any</option>
                                @foreach ($transmissions as $trans)
                                    <option value="{{ $trans }}" {{ request('trans') == $trans ? 'selected' : '' }}>
                                        {{ $trans }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-dark">Apply Filters</button>
                            <a href="{{ route('customer.auction.lobby') }}" class="btn btn-outline-secondary text-center">Clear All</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="row">
                    @if ($auctions->count() > 0)
                        @foreach ($auctions as $auc)
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card car-card shadow-sm h-100">
                                    <a href="{{ route('customer.auction.view', $auc->id) }}">
                                        <img src="{{ asset('uploads/' . $auc->car->image) }}" class="car-img-top" onerror="this.src='https://via.placeholder.com/400x250?text=Vehicle+Image'">
                                    </a>
                                    
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <span class="timer-badge">
                                                <i class="fa fa-clock-o"></i> Ends: {{ date("M d, H:i", strtotime($auc->end_time)) }}
                                            </span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <small class="text-primary text-uppercase fw-bold">{{ $auc->car->brand }}</small>
                                                <h5 class="fw-bold mb-0">{{ $auc->car->model }}</h5>
                                            </div>
                                            <div class="bid-tag">${{ number_format($auc->current_bid) }}</div>
                                        </div>
                                        
                                        <div class="d-flex flex-wrap gap-1 mb-3">
                                            <span class="spec-badge">{{ $auc->car->year }}</span>
                                            <span class="spec-badge">{{ $auc->car->transmission }}</span>
                                            <span class="spec-badge">{{ number_format($auc->car->mileage) }} km</span>
                                        </div>
                                    </div>

                                    <div class="card-footer bg-white border-0 pb-3">
                                        <div class="d-grid gap-2">
                                            <a href="{{ route('customer.auction.view', $auc->id) }}" class="btn btn-primary btn-sm fw-bold">
                                                <i class="fa fa-gavel"></i> Bid Now
                                            </a>
                                            <a href="{{ route('customer.car.view', $auc->car_id) }}" class="btn btn-outline-dark btn-sm">
                                                <i class="fa fa-eye"></i> Car Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-12 text-center py-5">
                            <i class="fa fa-gavel fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No active auctions match your filters.</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>