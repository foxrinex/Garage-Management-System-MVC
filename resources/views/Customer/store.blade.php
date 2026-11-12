<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer - Spare Parts Store</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .product-link { color: inherit; text-decoration: none; transition: color 0.2s; }
        .product-link:hover { color: #0d6efd; }
        .btn-wishlist-add { border: 1px solid #ff4757; color: #ff4757; }
        .btn-wishlist-add:hover { background-color: #ff4757; color: white; }
        .btn-wishlist-remove { background-color: #ff4757; color: white; border: 1px solid #ff4757; }
        .btn-wishlist-remove:hover { background-color: #e84118; color: white; }
        .tag-badge { font-size: 0.65rem; border-radius: 10px; padding: 2px 8px; display: inline-block; margin-right: 3px; margin-bottom: 2px; }
    </style>
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5 mb-5">
        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <h3><i class="fa fa-gears"></i> Spare Parts Store</h3>
                <p class="text-muted">Find the best performance parts for your vehicle</p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">
                <i class="fa fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card p-3 shadow-sm border-0 mb-4">
            <form action="{{ route('customer.store') }}" method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="small text-muted">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" {{ $catFilter == $c->id ? 'selected' : '' }}>
                                {{ $c->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small text-muted">Class</label>
                    <select name="tag_filter" class="form-select">
                        <option value="">Any Class</option>
                        @foreach ($allAvailableTags as $t)
                            <option value="{{ $t->tag_name }}" {{ $tagFilter == $t->tag_name ? 'selected' : '' }}>
                                {{ $t->tag_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small text-muted">Availability</label>
                    <select name="status" class="form-select">
                        <option value="All" {{ $statusFilter == 'All' ? 'selected' : '' }}>All Items</option>
                        <option value="In Stock" {{ $statusFilter == 'In Stock' ? 'selected' : '' }}>Available</option>
                        <option value="Upcoming" {{ $statusFilter == 'Upcoming' ? 'selected' : '' }}>Upcoming</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="small text-muted">Search Keywords</label>
                    <input type="text" name="search" class="form-control" placeholder="Search name, description or class..." value="{{ $search }}">
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="{{ route('customer.store') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="row">
            @if (count($products) > 0)
                @foreach ($products as $p)
                    @php
                        $isFav = in_array($p->id, $wishlistIds);
                        $currentInCart = $cartQuantities[$p->id] ?? 0;
                    @endphp
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <a href="{{ route('customer.product.view', $p->id) }}">
                                <img src="{{ asset('uploads/' . $p->image) }}" 
                                     class="card-img-top" 
                                     style="height: 180px; object-fit: cover;"
                                     onerror="this.src='https://via.placeholder.com/300x180?text=Product+Image'">
                            </a>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="badge bg-info text-dark">
                                        {{ $p->category->category_name ?? 'Part' }}
                                    </span>
                                    
                                    @if ($p->status == 'Upcoming')
                                        <span class="badge bg-primary">Upcoming</span>
                                    @elseif ($p->status == 'Out of Stock' || $p->quantity <= 0)
                                        <span class="badge bg-danger">Sold Out</span>
                                    @else
                                        <span class="badge bg-success">In Stock</span>
                                    @endif
                                </div>

                                <h5 class="card-title mb-1">
                                    <a href="{{ route('customer.product.view', $p->id) }}" class="product-link">
                                        {{ $p->product_name }}
                                    </a>
                                </h5>

                                <div class="mb-2">
                                    @if (!empty($p->tags))
                                        @php
                                            $pTags = explode(",", $p->tags);
                                        @endphp
                                        @foreach ($pTags as $tagText)
                                            @php $tagText = trim($tagText); @endphp
                                            @if (!empty($tagText))
                                                <span class="tag-badge bg-secondary text-white">{{ $tagText }}</span>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                                
                                <div class="mt-auto">
                                    <h5 class="text-primary mb-1">${{ number_format($p->price, 2) }}</h5>
                                    
                                    <a href="{{ route('customer.product.view', $p->id) }}" class="text-decoration-none small mb-3 d-block text-muted">
                                        <i class="fa fa-star text-warning"></i> View Reviews
                                    </a>

                                    @if ($p->status == 'In Stock' && $p->quantity > 0)
                                        @if ($currentInCart >= $p->quantity)
                                            <div class="alert alert-danger p-2 small mb-2 text-center">
                                                <i class="fa fa-warning"></i> Stock Limit in Cart
                                            </div>
                                        @else
                                            <form action="{{ route('customer.cart.add') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $p->id }}">
                                                <input type="hidden" name="source" value="store">
                                                <div class="input-group input-group-sm mb-2">
                                                    <span class="input-group-text">Qty</span>
                                                    <input type="number" name="qty" class="form-control" value="1" min="1" max="{{ $p->quantity - $currentInCart }}">
                                                </div>
                                                <div class="d-grid gap-2">
                                                    <button type="submit" class="btn btn-dark">
                                                        <i class="fa fa-shopping-cart"></i> Add to Cart
                                                    </button>
                                                </div>
                                            </form>
                                        @endif

                                        <div class="d-grid gap-2 mt-2">
                                            @if ($isFav)
                                                <a href="{{ route('customer.wishlist.handle', ['id' => $p->id, 'action' => 'remove', 'type' => 'product', 'source' => 'store']) }}" class="btn btn-sm btn-wishlist-remove">
                                                    <i class="fa fa-heart"></i> Remove
                                                </a>
                                            @else
                                                <a href="{{ route('customer.wishlist.handle', ['id' => $p->id, 'action' => 'add', 'type' => 'product', 'source' => 'store']) }}" class="btn btn-sm btn-wishlist-add">
                                                    <i class="fa fa-heart-o"></i> Wishlist
                                                </a>
                                            @endif
                                        </div>

                                    @else
                                        <button class="btn btn-secondary w-100 mb-2" disabled>
                                            {{ $p->status == 'Upcoming' ? 'Upcoming' : 'Out of Stock' }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="col-12 text-center py-5">
                    <div class="alert alert-info">No parts found.</div>
                </div>
            @endif
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>