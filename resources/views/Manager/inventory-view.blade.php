<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager - Parts Inventory</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .table-container { background: #fff; border-radius: 15px; overflow: hidden; }
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .tag-badge { font-size: 0.7rem; font-weight: 500; letter-spacing: 0.5px; border: 1px solid rgba(0,0,0,0.05); }
        .hover-row:hover { background-color: #f8f9fa !important; }
        .search-bar { border-radius: 20px 0 0 20px; border-right: none; }
        .search-btn { border-radius: 0 20px 20px 0; }
        .stat-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: #f0f2f5; color: #555; }
    </style>
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container mt-5 px-4" style="max-width: 1400px;">
        
        <div class="row align-items-center mb-4">
            <div class="col-md-4">
                <h3 class="fw-bold mb-1"><i class="fa fa-cubes text-primary me-2"></i>Parts Inventory</h3>
                <p class="text-muted small">Total Items Tracked: {{ $products->count() }}</p>
            </div>
            <div class="col-md-5">
                <form action="{{ route('manager.inventory.view') }}" method="GET" class="d-flex shadow-sm rounded-pill">
                    <input type="text" name="search" class="form-control search-bar px-4" placeholder="Search by name, category, or tags..." value="{{ $search }}">
                    <button type="submit" class="btn btn-primary search-btn px-4">
                        <i class="fa fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-3 text-end">
                <a href="{{ route('manager.inventory') }}" class="btn btn-dark rounded-pill px-4 shadow-sm">
                    <i class="fa fa-plus me-2"></i>Add New Part
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="table-responsive shadow-sm table-container border p-2">
            <table class="table table-borderless align-middle mb-0">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="ps-3" style="font-size: 0.85rem; text-transform: uppercase;">Image</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Product Details</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Category</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">HP</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Compatibility Tags</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Price</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Stock Status</th>
                        <th style="font-size: 0.85rem; text-transform: uppercase;">Engagement</th>
                        <th class="text-end pe-3" style="font-size: 0.85rem; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $p)
                        @php
                            $displayCat = optional($p->category)->category_name ?? "Uncategorized";

                            // Stock Badge Logic
                            $badgeClass = "bg-success";
                            $statusText = $p->quantity . " in stock";

                            if ($p->status == "Upcoming") {
                                $badgeClass = "bg-primary";
                                $statusText = "Upcoming";
                            } else {
                                if ($p->quantity <= 0) {
                                    $badgeClass = "bg-danger";
                                    $statusText = "Out of Stock";
                                } else if ($p->quantity <= 5) {
                                    $badgeClass = "bg-warning text-dark";
                                }
                            }
                        @endphp
                        <tr class="hover-row border-bottom">
                            <td class="ps-3">
                                @if(!empty($p->image))
                                    <img src="{{ asset('uploads/' . $p->image) }}" class="product-img border" onerror="this.src='https://via.placeholder.com/100'">
                                @else
                                    <div class="product-img bg-secondary d-flex align-items-center justify-content-center text-white small">N/A</div>
                                @endif
                            </td>

                            <td>
                                <div class="fw-bold text-dark">{{ $p->product_name }}</div>
                                <div class="text-muted small">SKU: #{{ $p->id }}</div>
                            </td>

                            <td>
                                <span class="text-secondary small fw-bold"><i class="fa fa-folder-open-o me-1"></i>{{ $displayCat }}</span>
                            </td>

                            <td>
                                <span class="badge bg-dark rounded-pill">{{ !empty($p->hp) ? $p->hp . " HP" : "0 HP" }}</span>
                            </td>

                            <td>
                                @php
                                    $tags = $p->tags;
                                @endphp
                                @if (empty($tags))
                                    <span class="badge rounded-pill bg-light text-dark border"><i class="fa fa-globe text-muted me-1"></i> Universal</span>
                                @else
                                    @foreach (explode(',', $tags) as $tagName)
                                        <span class="badge rounded-pill bg-info text-dark me-1 tag-badge">{{ trim($tagName) }}</span>
                                    @endforeach
                                @endif
                            </td>

                            <td>
                                <span class="fw-bold text-dark">${{ number_format($p->price, 2) }}</span>
                            </td>

                            <td>
                                <span class="badge {{ $badgeClass }} rounded-pill px-3 py-2" style="font-size: 0.75rem;">
                                    {{ $statusText }}
                                </span>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon me-2">
                                        <i class="fa {{ ($p->status == 'Upcoming') ? 'fa-heart text-danger' : 'fa-heart-o' }}"></i>
                                    </div>
                                    <span class="fw-bold {{ ($p->status == 'Upcoming') ? 'text-primary' : 'text-muted' }}">
                                        {{ $p->wishlist_count }}
                                    </span>
                                </div>
                            </td>

                            <td class="text-end pe-3">
                                <div class="btn-group shadow-sm">
                                    <a href="#" class="btn btn-white btn-sm border" title="Reviews">
                                        <i class="fa fa-comments text-info"></i>
                                    </a>
                                    <a href="#" class="btn btn-white btn-sm border" title="Edit">
                                        <i class="fa fa-pencil text-warning"></i>
                                    </a>
                                    <a href="#" class="btn btn-white btn-sm border" title="Delete" onclick="return confirm('Delete this part?')">
                                        <i class="fa fa-trash text-danger"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fa fa-search-minus fa-3x text-light mb-3"></i>
                                <p class="text-muted">No parts found matching your criteria.</p>
                                <a href="{{ route('manager.inventory.view') }}" class="btn btn-link text-decoration-none">Clear Search</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>