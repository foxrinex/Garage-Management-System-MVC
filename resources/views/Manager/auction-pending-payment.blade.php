<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Overview - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .payment-card { transition: 0.3s; border: none; border-radius: 15px; overflow: hidden; background: #fff; }
        .payment-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; }
        .car-img-aside { width: 100%; height: 100%; min-height: 220px; object-fit: cover; }
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 0.75rem; padding: 4px 8px; border-radius: 5px; border: 1px solid #eee; }
        .price-tag { font-size: 1.5rem; font-weight: 800; color: #198754; }
        .stats-banner { background: #fff; border-radius: 15px; border-left: 5px solid #0d6efd; }
        .section-label { font-size: 0.7rem; font-weight: 700; color: #adb5bd; text-transform: uppercase; letter-spacing: 1px; }
        
        .main-content { max-width: 1300px; margin: 0 auto; }
    </style>
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container mt-4 px-3">
        <div class="main-content">
            
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <i class="fa fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <div class="row mb-4 align-items-end">
                <div class="col-lg-8 col-md-7">
                    <h2 class="fw-bold"><i class="fa fa-university me-2 text-primary"></i>Financial Overview</h2>
                    <p class="text-muted mb-0">Manage pending auction payments and vehicle verification for Rev Nation.</p>
                </div>
                <div class="col-lg-4 col-md-5 mt-3 mt-md-0">
                    <div class="stats-banner p-3 shadow-sm d-flex justify-content-between align-items-center">
                        <div>
                            <span class="section-label">Total Outstanding</span>
                            <h3 class="fw-bold mb-0 text-primary">${{ number_format($totalRevenue) }}</h3>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-warning text-dark rounded-pill px-3">{{ $pendingCount }} Items</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-4 mb-4">
                    <div class="filter-section shadow-sm">
                        <form action="{{ route('manager.auction.pending') }}" method="GET">
                            
                            <h6 class="fw-bold mb-3"><i class="fa fa-filter me-2"></i>Filters</h6>
                            
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Bidder Name</label>
                                <input type="text" name="search_name" class="form-control form-control-sm" placeholder="Search customer..." value="{{ $searchName }}">
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Brand & Model</label>
                                <div class="mb-2">
                                    <input type="text" name="brand" class="form-control form-control-sm mb-1" placeholder="Brand" value="{{ $brand }}">
                                    <input type="text" name="model" class="form-control form-control-sm" placeholder="Model" value="{{ $model }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Category</label>
                                <select name="cat_id" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ ($catId == $cat->id) ? 'selected' : '' }}>
                                            {{ $cat->category_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Transmission</label>
                                <select name="transmission" class="form-select form-select-sm">
                                    <option value="">Any</option>
                                    @foreach ($transmissions as $tx)
                                        <option value="{{ $tx }}" {{ ($transmission == $tx) ? 'selected' : '' }}>
                                            {{ $tx }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Winning Bid ($)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="min_price" class="form-control" placeholder="Min" value="{{ $minPrice }}">
                                    <input type="number" name="max_price" class="form-control" placeholder="Max" value="{{ $maxPrice }}">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="small text-muted fw-bold">Year (1900 - {{ $currentSystemYear }})</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="min_year" class="form-control" placeholder="From" value="{{ $minYear }}">
                                    <input type="number" name="max_year" class="form-control" placeholder="To" value="{{ $maxYear }}">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-dark btn-sm">Apply Filters</button>
                                <a href="{{ route('manager.auction.pending') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-9 col-md-8">
                    @if ($pendingCount > 0)
                        @foreach ($pending as $p)
                            <div class="card payment-card shadow-sm mb-4">
                                <div class="row g-0">
                                    <div class="col-xl-4 col-lg-5">
                                        <img src="{{ asset('uploads/' . $p->car->image) }}" class="car-img-aside" onerror="this.src='https://via.placeholder.com/400x250?text=Vehicle'">
                                    </div>
                                    <div class="col-xl-5 col-lg-7 p-4">
                                        <div class="mb-3">
                                            <span class="section-label text-success">Winning Bidder</span>
                                            <h5 class="fw-bold mb-1">{{ optional($p->highestBidder)->fname . " " . optional($p->highestBidder)->lname }}</h5>
                                            <div class="text-muted small">
                                                <i class="fa fa-envelope me-2"></i>{{ optional($p->highestBidder)->email_address }}<br>
                                                <i class="fa fa-phone me-2"></i>{{ optional($p->highestBidder)->phone_number }}
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            <span class="spec-badge"><i class="fa fa-car me-1"></i> {{ optional($p->car)->brand . " " . optional($p->car)->model }}</span>
                                            <span class="spec-badge"><i class="fa fa-calendar me-1"></i> {{ optional($p->car)->year }}</span>
                                            <span class="spec-badge"><i class="fa fa-road me-1"></i> {{ number_format(optional($p->car)->mileage) }} km</span>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-12 p-4 bg-light d-flex flex-column justify-content-center border-start">
                                        <div class="text-center mb-3">
                                            <span class="section-label">Final Bid Amount</span>
                                            <div class="price-tag">${{ number_format($p->current_bid) }}</div>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button onclick="confirmPaid({{ $p->id }})" class="btn btn-success fw-bold">
                                                <i class="fa fa-check-circle me-1"></i> MARK PAID
                                            </button>

                                            <button onclick="rejectPayment({{ $p->id }})" class="btn btn-danger fw-bold">
                                                <i class="fa fa-times-circle me-1"></i> REJECT BID
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="card border-0 shadow-sm p-5 text-center">
                            <i class="fa fa-search-minus fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No pending payments found</h4>
                            <p>Try adjusting your filters or check back later.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmPaid(id) {
            window.location.href = "/manager/auction-confirm-payment/" + id;
        }

        function rejectPayment(id) {
            window.location.href = "/manager/auction-reject-payment/" + id;
        }
    </script>
</body>
</html>