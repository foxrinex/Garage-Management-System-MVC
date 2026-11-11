<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager - Car Showroom</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        .stats-card { border: none; border-radius: 12px; transition: 0.3s; min-height: 100px; }
        .car-card { transition: 0.3s; border: none; border-radius: 15px; overflow: hidden; position: relative; }
        .car-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; position: sticky; top: 20px; }
        .car-img-top { height: 180px; width: 100%; object-fit: cover; cursor: pointer; }
        .spec-badge { background: #f8f9fa; color: #6c757d; font-size: 0.72rem; padding: 4px 8px; border-radius: 5px; border: 1px solid #eee; }
        
        .page-header-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .page-header-icon {
            background-color: #343a40;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .page-header-title {
            font-weight: 800;
            font-size: 1.8rem;
            color: #212529;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card-header-text {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
            font-weight: 600;
        }

        .btn-add-large {
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
        }

        .wishlist-star {
            color: #ffc107;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .qty-display-large {
            background: #fdfdfd;
            border-top: 1px dashed #ddd;
            padding: 10px;
            text-align: center;
        }
        .qty-number {
            font-size: 1.4rem;
            font-weight: 800;
            display: block;
        }
        .qty-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
        }
        a { text-decoration: none; color: inherit; }
        .car-link:hover h5 { color: #0d6efd; }

        .auction-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.7rem;
            font-weight: bold;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container-fluid mt-4 px-4">

        <div class="page-header-container">
            <div class="page-header-icon">
                <i class="fa fa-car"></i>
            </div>
            <h2 class="page-header-title">Vehicle Inventory & Showroom</h2>
        </div>
        
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-car fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Total Fleet</div>
                            <h4 class="mb-0 fw-bold">{{ $carCount }} Units</h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-clock-o fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Pending Arrivals</div>
                            <h4 class="mb-0 fw-bold">{{ $count_upcoming }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stats-card bg-success text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-money fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Inventory Value</div>
                            <h4 class="mb-0 fw-bold">${{ number_format($total_inventory_value / 1000, 1) }}k</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stats-card bg-danger text-white shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <div class="card-header-text">Inventory Alert</div>
                            <h4 class="mb-0 fw-bold">{{ $count_out_of_stock }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="filter-section shadow-sm mb-4">
                    <form action="{{ route('manager.showroom') }}" method="GET">
                        <h6 class="fw-bold mb-3">Search Vehicle</h6>
                        <div class="input-group input-group-sm mb-4">
                            <input type="text" name="search" class="form-control" placeholder="Brand or Model..." value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                        </div>

                        <h6 class="fw-bold mb-3">Filter by Availability</h6>
                        <div class="mb-4">
                           <select name="status_filter" class="form-select form-select-sm">
                                <option value="">All Availability</option>
                                <option value="In Stock" {{ request('status_filter') == 'In Stock' ? 'selected' : '' }}>In Stock Only</option>
                                <option value="Upcoming" {{ request('status_filter') == 'Upcoming' ? 'selected' : '' }}>Upcoming Only</option>
                                <option value="Out of Stock" {{ request('status_filter') == 'Out of Stock' ? 'selected' : '' }}>Out of Stock Only</option>
                           </select>
                        </div>

                        <h6 class="fw-bold mb-3">Specifications</h6>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Category</label>
                            <select name="cat_id" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ request('cat_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->category_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Price Range ($)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="min_price" class="form-control" placeholder="Min" min="0" value="{{ request('min_price') }}">
                                <input type="number" name="max_price" class="form-control" placeholder="Max" min="0" value="{{ request('max_price') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Year Range (1900 - 2026)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="min_year" class="form-control" placeholder="From" min="1900" max="2026" value="{{ request('min_year') }}">
                                <input type="number" name="max_year" class="form-control" placeholder="To" min="1900" max="2026" value="{{ request('max_year') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Max Mileage (km)</label>
                            <input type="number" name="max_mileage" class="form-control form-control-sm" placeholder="Up to..." min="0" value="{{ request('max_mileage') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Transmission</label>
                            <select name="trans" class="form-select form-select-sm">
                                <option value="">All Transmissions</option>
                                @foreach ($transmissions as $t)
                                    <option value="{{ $t }}" {{ request('trans') == $t ? 'selected' : '' }}>
                                        {{ $t }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-sm btn-dark">Apply Filters</button>
                            <a href="{{ route('manager.showroom') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0">Available Vehicles</h3>
                    <div>
                        <a href="{{ route('manager.car.add') }}" class="btn btn-success btn-add-large">
                            <i class="fa fa-plus-circle me-2"></i> Add New Vehicle
                        </a>
                    </div>
                </div>

                <div class="row">
                    @if ($cars->count() > 0)
                        @foreach ($cars as $car)
                            @php
                                $qtyTextColor = "text-dark";
                                $qtyLabel = "Stock Quantity";

                                if ($car->status == 'Upcoming') {
                                    $qtyTextColor = "text-warning";
                                    $qtyLabel = "Upcoming Order";
                                } else if ($car->quantity <= 0 || $car->status == 'Sold Out') {
                                    $qtyTextColor = "text-danger";
                                    $qtyLabel = "Out of Stock";
                                }
                            @endphp
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card car-card shadow-sm h-100">
                                    @if ($car->in_auction > 0)
                                        <div class="auction-badge"><i class="fa fa-gavel"></i> IN AUCTION</div>
                                    @endif

                                    <!-- Clickable Image to View Car -->
                                    <a href="{{ route('manager.car.view', $car->id) }}">
                                        <img src="{{ asset('uploads/' . $car->image) }}" 
                                             class="car-img-top"
                                             onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
                                    </a>
                                    
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-primary fw-bold text-uppercase">{{ $car->brand }}</small>
                                            <h6 class="text-success fw-bold m-0">${{ number_format($car->price) }}</h6>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <!-- Clickable Title to View Car -->
                                            <a href="{{ route('manager.car.view', $car->id) }}" class="car-link">
                                                <h5 class="card-title fw-bold m-0">{{ $car->model }}</h5>
                                            </a>
                                            <span class="wishlist-star">
                                                <i class="fa fa-star"></i> {{ $car->wishlist_count }}
                                            </span>
                                        </div>
                                        
                                        <div class="d-flex flex-wrap gap-1 mt-3">
                                            <span class="spec-badge"><i class="fa fa-calendar"></i> {{ $car->year }}</span>
                                            <span class="spec-badge"><i class="fa fa-cog"></i> {{ $car->transmission }}</span>
                                            <span class="spec-badge"><i class="fa fa-road"></i> {{ number_format($car->mileage) }} km</span>
                                        </div>
                                    </div>

                                    <div class="qty-display-large">
                                        <span class="qty-label">{{ $qtyLabel }}</span>
                                        <span class="qty-number {{ $qtyTextColor }}">
                                            {{ $car->status == 'Upcoming' ? '---' : $car->quantity }}
                                        </span>
                                    </div>

                                    <div class="card-footer bg-transparent border-0 pb-3">
                                        <div class="d-flex flex-column gap-2">
                                            <div class="d-flex gap-2">
                                                @if ($car->in_auction > 0)
                                                    <button class="btn btn-sm btn-secondary flex-grow-1" disabled title="Locked: Car is in Auction">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-secondary" disabled title="Locked: Car is in Auction">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                @else
                                                    <!-- Working Edit Button -->
                                                    <a href="{{ route('manager.car.edit', $car->id) }}" class="btn btn-sm btn-outline-primary flex-grow-1">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                    <!-- Working Delete Button -->
                                                    <button onclick="deleteCar('{{ route('manager.car.delete', $car->id) }}')" class="btn btn-sm btn-outline-danger">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                            <!-- Manage Reviews Button -->
                                            <a href="{{ route('manager.inventory.reviews', $car->id) }}" class="btn btn-sm btn-light border w-100">
                                                <i class="fa fa-comments"></i> Manage Reviews
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-12 text-center py-5">
                            <i class="fa fa-search fa-3x text-muted"></i>
                            <h5 class="mt-3 text-muted">No vehicles match your search.</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteCar(deleteUrl) {
            if (confirm('Are you sure you want to delete this vehicle?')) {
                window.location.href = deleteUrl;
            }
        }
    </script>
</body>
</html>