<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select {{ $category->category_name }} | Garage GMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .filter-card { border: none; border-radius: 10px; }
        .product-table-card { border: none; border-radius: 10px; overflow: hidden; }
        .card-header-custom { background: #212529 !important; color: white !important; }
        .img-container { width: 60px; height: 60px; overflow: hidden; border-radius: 8px; border: 1px solid #eee; }
        .img-container img { width: 100%; height: 100%; object-fit: cover; }
        .disabled-row { opacity: 0.6; background-color: #f8f9fa; }
        .tag-badge { font-size: 0.7rem; font-weight: 500; }
        .btn-select { font-weight: bold; padding: 5px 20px; }
        .hp-badge { font-size: 0.75rem; background: #e9ecef; color: #0d6efd; padding: 2px 8px; border-radius: 4px; font-weight: bold; border: 1px solid #0d6efd; margin-top: 4px; display: inline-block; }
        .hp-badge-neg { font-size: 0.75rem; background: #fff5f5; color: #dc3545; padding: 2px 8px; border-radius: 4px; font-weight: bold; border: 1px solid #dc3545; margin-top: 4px; display: inline-block; }
    </style>
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5 mb-5">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card p-4 shadow-sm filter-card">
                    <h6 class="fw-bold mb-3"><i class="fa fa-filter me-2"></i>Filter Parts</h6>
                    <form action="{{ route('customer.builder.select') }}" method="GET">
                        <input type="hidden" name="cat_id" value="{{ $catId }}">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Search Name</label>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="e.g. Turbo" value="{{ htmlspecialchars($search) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Filter by Tag</label>
                            <select name="tag_filter" class="form-select form-select-sm">
                                <option value="">All Tags</option>
                                @foreach ($dbTags as $tag)
                                    <option value="{{ $tag->tag_name }}" {{ $tagFilter == $tag->tag_name ? 'selected' : '' }}>
                                        {{ $tag->tag_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Availability</label>
                            <select name="filter" class="form-select form-select-sm">
                                <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>All Items</option>
                                <option value="available" {{ $filter == 'available' ? 'selected' : '' }}>In Stock</option>
                                <option value="upcoming" {{ $filter == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                                <option value="out_of_stock" {{ $filter == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Sort By</label>
                            <select name="sort" class="form-select form-select-sm">
                                <option value="name_asc" {{ $sort == 'name_asc' ? 'selected' : '' }}>Name (A-Z)</option>
                                <option value="name_desc" {{ $sort == 'name_desc' ? 'selected' : '' }}>Name (Z-A)</option>
                                <option value="price_low" {{ $sort == 'price_low' ? 'selected' : '' }}>Price (Low to High)</option>
                                <option value="price_high" {{ $sort == 'price_high' ? 'selected' : '' }}>Price (High to Low)</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">Apply Filters</button>
                            <a href="{{ route('customer.builder.select', ['cat_id' => $catId]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                            <hr>
                            <a href="{{ route('customer.builder') }}" class="btn btn-dark btn-sm"><i class="fa fa-arrow-left me-2"></i>Back to Builder</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card shadow-sm product-table-card">
                    <div class="card-header card-header-custom py-3">
                        <h5 class="mb-0 fw-bold">Available {{ $category->category_name }}</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4" style="width: 40%;">Product Details</th>
                                        <th style="width: 25%;">Tags</th>
                                        <th style="width: 15%;">Price</th>
                                        <th class="text-end pe-4" style="width: 20%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($products->count() > 0)
                                        @foreach ($products as $p)
                                            @php
                                                $isAvailable = ($p->status == 'In Stock' && $p->quantity > 0);
                                                $isUpcoming = ($p->status == 'Upcoming');
                                                $pHp = isset($p->hp) ? (int)$p->hp : 0;
                                            @endphp
                                            <tr class="{{ !$isAvailable ? 'disabled-row' : '' }}">
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="img-container me-3 shadow-sm">
                                                            <img src="{{ asset('uploads/' . $p->image) }}" onerror="this.src='{{ asset('uploads/default.png') }}'">
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-dark">{{ $p->product_name }}</div>
                                                            <small class="text-muted">{{ $p->status }} · Qty: {{ $p->quantity }}</small>
                                                            <br>
                                                            @if ($pHp > 0)
                                                                <span class="hp-badge">+{{ $pHp }} HP</span>
                                                            @elseif ($pHp < 0)
                                                                <span class="hp-badge-neg">{{ $pHp }} HP</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @php
                                                        $rawTags = trim($p->tags);
                                                        if ($rawTags == "" || strtolower($rawTags) == "null") {
                                                            echo "<span class='badge bg-success text-white tag-badge me-1'>Universal</span>";
                                                        } else {
                                                            $pTags = explode(',', $rawTags);
                                                            foreach ($pTags as $tName) {
                                                                $tName = trim($tName);
                                                                if ($tName != "" && strtolower($tName) !== "null") {
                                                                    $badgeClass = (strtolower($tName) == 'universal') ? 'bg-success text-white' : 'bg-info text-white';
                                                                    echo "<span class='badge {$badgeClass} tag-badge me-1'>{$tName}</span>";
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                </td>
                                                <td class="fw-bold text-primary">${{ number_format($p->price, 2) }}</td>
                                                <td class="text-end pe-4">
                                                    @if ($isAvailable)
                                                        <form action="{{ route('customer.builder.action') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="cat_id" value="{{ $catId }}">
                                                            <input type="hidden" name="p_id" value="{{ $p->id }}">
                                                            <input type="hidden" name="action" value="select_part">
                                                            <button type="submit" class="btn btn-sm btn-primary btn-select">Select</button>
                                                        </form>
                                                    @elseif ($isUpcoming)
                                                        <button class="btn btn-sm btn-warning btn-select" disabled>Upcoming</button>
                                                    @else
                                                        <button class="btn btn-sm btn-secondary btn-select" disabled>Sold Out</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="4" class="text-center py-5">
                                                <i class="fa fa-search fa-2x text-muted mb-3"></i>
                                                <p class="text-muted">No compatible products found for this category.</p>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>