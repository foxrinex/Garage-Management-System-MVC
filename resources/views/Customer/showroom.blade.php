<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Showroom - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        .car-card { transition: 0.3s; border: none; border-radius: 15px; overflow: hidden; }
        .car-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; position: sticky; top: 20px; }
        .car-img-top { height: 200px; width: 100%; object-fit: cover; }
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 0.75rem; padding: 4px 8px; border-radius: 5px; border: 1px solid #eee; }
        .price-tag { font-size: 1.2rem; font-weight: 800; color: #198754; }
        
        .btn-wishlist-add { border: 1px solid #ff4757; color: #ff4757; }
        .btn-wishlist-add:hover { background-color: #ff4757; color: white; }
        .btn-wishlist-remove { background-color: #ff4757; color: white; border: 1px solid #ff4757; }
        
        a { text-decoration: none; color: inherit; }
    </style>
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container-fluid mt-4 px-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold"><i class="fa fa-car me-2"></i>Premium Car Showroom</h2>
                <p class="text-muted">Browse our exclusive collection of vehicles.</p>
                
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="filter-section shadow-sm mb-4">
                    <form id="filterForm" action="{{ route('customer.showroom') }}" method="GET">
                        
                        <h6 class="fw-bold mb-3">Search</h6>
                        <div class="input-group mb-4">
                            <input type="text" name="search" class="form-control" placeholder="Brand or Model..." value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                        </div>

                        <h6 class="fw-bold mb-3">Availability</h6>
                        <select name="status_filter" class="form-select mb-4">
                            <option value="">All Vehicles</option>
                            <option value="In Stock" {{ request('status_filter') == 'In Stock' ? 'selected' : '' }}>Available Now</option>
                            <option value="Upcoming" {{ request('status_filter') == 'Upcoming' ? 'selected' : '' }}>Upcoming</option>
                            <option value="Out of Stock" {{ request('status_filter') == 'Out of Stock' ? 'selected' : '' }}>Sold Out</option>
                        </select>

                        <h6 class="fw-bold mb-3">Specifications</h6>
                        
                        <div class="mb-3">
                            <label class="small text-muted">Category</label>
                            <select name="cat_id" class="form-select">
                                <option value="">All Types</option>
                                @for ($i = 0; $i < count($categories); $i = $i + 1)
                                    @php $cat = $categories[$i]; @endphp
                                    <option value="{{ $cat->id }}" {{ request('cat_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->category_name }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Price Range ($0 - $1B)</label>
                            <div class="input-group">
                                <input type="number" id="min_price" name="min_price" class="form-control" placeholder="Min" min="0" max="1000000000" value="{{ request('min_price') }}">
                                <input type="number" id="max_price" name="max_price" class="form-control" placeholder="Max" min="0" max="1000000000" value="{{ request('max_price') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Year Range (1900 - {{ $currentSystemYear }})</label>
                            <div class="input-group">
                                <input type="number" id="min_year" name="min_year" class="form-control" placeholder="From" min="1900" max="{{ $currentSystemYear }}" value="{{ request('min_year') }}">
                                <input type="number" id="max_year" name="max_year" class="form-control" placeholder="To" min="1900" max="{{ $currentSystemYear }}" value="{{ request('max_year') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted">Max Mileage (0 - 1B km)</label>
                            <input type="number" name="max_mileage" class="form-control" placeholder="Up to..." min="0" max="1000000000" value="{{ request('max_mileage') }}">
                        </div>

                        <div class="mb-4">
                            <label class="small text-muted">Transmission</label>
                            <select name="trans" class="form-select">
                                <option value="">Any</option>
                                @for ($i = 0; $i < count($transmissions); $i = $i + 1)
                                    @php $t = $transmissions[$i]; @endphp
                                    <option value="{{ $t }}" {{ request('trans') == $t ? 'selected' : '' }}>
                                        {{ $t }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-dark">Apply Filters</button>
                            <a href="{{ route('customer.showroom') }}" class="btn btn-outline-secondary">Clear All</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="row">
                    @if (count($cars) > 0)
                        @for ($i = 0; $i < count($cars); $i = $i + 1)
                            @php
                                $car = $cars[$i];
                                $hasPurchased = in_array($car->id, $purchasedCars);
                                $isWished = in_array($car->id, $wishlistIds);
                            @endphp

                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card car-card shadow-sm h-100">
                                    <a href="{{ route('customer.car.view', $car->id) }}">
                                        <img src="{{ asset('uploads/' . $car->image) }}" class="car-img-top" onerror="this.src='https://via.placeholder.com/400x250?text=Vehicle+Image'">
                                    </a>
                                    
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <small class="text-primary text-uppercase fw-bold">{{ $car->brand }}</small>
                                                <h5 class="fw-bold mb-0">{{ $car->model }}</h5>
                                            </div>
                                            <div class="price-tag">${{ number_format($car->price) }}</div>
                                        </div>
                                        
                                        <div class="d-flex flex-wrap gap-1 mb-3">
                                            <span class="spec-badge"><i class="fa fa-calendar"></i> {{ $car->year }}</span>
                                            <span class="spec-badge"><i class="fa fa-cog"></i> {{ $car->transmission }}</span>
                                            <span class="spec-badge"><i class="fa fa-road"></i> {{ number_format($car->mileage) }} km</span>
                                        </div>

                                        @if ($car->status == 'Upcoming')
                                            <div class="alert alert-primary py-1 px-2 mb-0 text-center small">Coming Soon</div>
                                        @elseif ($car->quantity <= 0)
                                            <div class="alert alert-danger py-1 px-2 mb-0 text-center small">Sold Out</div>
                                        @endif
                                    </div>

                                    <div class="card-footer bg-white border-0 pb-3">
                                        <div class="d-grid gap-2">
                                            
                                            @if ($hasPurchased)
                                                <a href="{{ route('customer.warranty.select.order', ['car_id' => $car->id]) }}" class="btn btn-warning btn-sm fw-bold">
                                                    <i class="fa fa-shield"></i> Claim Warranty
                                                </a>
                                            @endif

                                            @if ($car->status == 'In Stock' && $car->quantity > 0)
                                                <form action="{{ route('customer.car.transaction', $car->id) }}" method="GET" class="d-grid">
                                                    <button type="submit" class="btn btn-success btn-sm fw-bold">
                                                        <i class="fa fa-shopping-cart"></i> Purchase Now
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <a href="{{ route('customer.car.view', $car->id) }}" class="btn btn-outline-dark btn-sm">
                                                <i class="fa fa-eye"></i> View Details & Reviews
                                            </a>

                                            @if ($isWished)
                                                <a href="{{ route('customer.wishlist.handle', ['id' => $car->id, 'action' => 'remove', 'type' => 'car', 'source' => 'showroom']) }}" class="btn btn-sm btn-wishlist-remove">
                                                    <i class="fa fa-heart"></i> Remove from Wishlist
                                                </a>
                                            @else
                                                <a href="{{ route('customer.wishlist.handle', ['id' => $car->id, 'action' => 'add', 'type' => 'car', 'source' => 'showroom']) }}" class="btn btn-sm btn-wishlist-add">
                                                    <i class="fa fa-heart-o"></i> Add to Wishlist
                                                </a>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @else
                        <div class="col-12 text-center py-5">
                            <i class="fa fa-car fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No vehicles found matching your criteria.</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent min values from exceeding max values on input and form submission
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            const minPrice = parseFloat(document.getElementById('min_price').value);
            const maxPrice = parseFloat(document.getElementById('max_price').value);
            if (!isNaN(minPrice) && !isNaN(maxPrice) && minPrice > maxPrice) {
                alert('Minimum price cannot be greater than maximum price.');
                e.preventDefault();
                return;
            }

            const minYear = parseInt(document.getElementById('min_year').value);
            const maxYear = parseInt(document.getElementById('max_year').value);
            if (!isNaN(minYear) && !isNaN(maxYear) && minYear > maxYear) {
                alert('Minimum year cannot be greater than maximum year.');
                e.preventDefault();
                return;
            }
        });

        // Dynamic constraint attributes syncing
        document.getElementById('min_price').addEventListener('input', function() {
            document.getElementById('max_price').min = this.value;
        });
        document.getElementById('max_price').addEventListener('input', function() {
            document.getElementById('min_price').max = this.value;
        });

        document.getElementById('min_year').addEventListener('input', function() {
            document.getElementById('max_year').min = this.value;
        });
        document.getElementById('max_year').addEventListener('input', function() {
            document.getElementById('min_year').max = this.value;
        });
    </script>
</body>
</html>