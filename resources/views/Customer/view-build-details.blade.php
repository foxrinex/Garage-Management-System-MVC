<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Build Details - {{ $buildInfo->build_name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .tag-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 50px; background: #e9ecef; margin-right: 4px; color: #495057; border: 1px solid #dee2e6; }
        .tag-universal { background: #d1e7dd; color: #0f5132; }
        .out-of-stock-row { background-color: #fff5f5; }
        .btn-wishlisted { background-color: #ffc107 !important; color: #000 !important; border-color: #ffc107 !important; }
    </style>
</head>

<body class="bg-light">

    @include('customer.inc.navbar')

    <div class="container mt-5">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="mb-4 d-flex justify-content-between">
            <a href="{{ route('customer.saved.builds') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fa fa-arrow-left"></i> Back to My Builds
            </a>
            
            <form action="{{ route('customer.builder.action') }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="wishlist_build">
                <input type="hidden" name="build_id" value="{{ $id }}">
                <button type="submit" class="btn {{ $allPartsWishlisted ? 'btn-warning' : 'btn-outline-warning' }} btn-sm shadow-sm">
                    <i class="fa fa-heart"></i> {{ $allPartsWishlisted ? 'Entire Build Wishlisted' : 'Wishlist Entire Build' }}
                </button>
            </form>
        </div>

        <div class="card shadow border-0">
            <div class="card-header bg-dark text-white p-3">
                <h3 class="mb-0">
                    <i class="fa fa-car text-warning"></i> 
                    {{ $buildInfo->build_name }}
                </h3>
                <small class="text-muted">Saved on: {{ $buildInfo->created_at }}</small>
            </div>

            <div class="card-body">
                <h5 class="fw-bold mb-3">Selected Components</h5>
                <hr>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Image</th>
                                <th>Category & Tags</th>
                                <th>Part Name</th>
                                <th>HP</th>
                                <th>Status</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                            @php
                            $grandTotal = 0;
                            $totalHp = 0;
                            @endphp

                            @for ($i = 0; $i < $totalItems; $i = $i + 1)
                                @php
                                $item = $items[$i];
                                $grandTotal = $grandTotal + $item->price;
                                $totalHp = $totalHp + (int)$item->hp;
                                $imgPath = asset('uploads/' . $item->image);
                                $inStock = ($item->quantity > 0 && $item->status == 'In Stock');

                                $isInWishlist = in_array($item->product_id, $wishlistData);
                                @endphp

                                <tr class="{{ !$inStock ? 'out-of-stock-row' : '' }}">
                                    <td style="width: 100px;">
                                        <img src="{{ $imgPath }}" class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;" onerror="this.src='{{ asset('uploads/default-cat.png') }}'">
                                    </td>
                                    <td>
                                        <div class="text-uppercase small fw-bold text-muted mb-1">
                                            {{ !empty($item->category_name) ? $item->category_name : "Component" }}
                                        </div>
                                        <div>
                                            @if (!empty($item->tags))
                                                @php $pTags = explode(',', $item->tags); @endphp
                                                @for ($t = 0; $t < count($pTags); $t = $t + 1)
                                                    @php
                                                    $tName = trim($pTags[$t]);
                                                    $isU = (strtolower($tName) == 'universal');
                                                    @endphp
                                                    <span class="tag-badge {{ $isU ? 'tag-universal' : '' }}">{{ $tName }}</span>
                                                @endfor
                                            @else
                                                <span class="tag-badge tag-universal">universal</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $item->product_name }}</td>
                                    <td><span class="badge bg-dark">{{ (int)$item->hp }} HP</span></td>
                                    <td>
                                        @if ($inStock)
                                            <span class="badge bg-success">In Stock</span>
                                        @else
                                            <span class="badge bg-danger">Unavailable</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">${{ number_format($item->price, 2) }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('customer.builder.action') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="p_id" value="{{ $item->product_id }}">
                                            <input type="hidden" name="build_id" value="{{ $id }}">
                                            <input type="hidden" name="action" value="wishlist_item">
                                            
                                            <button type="submit" 
                                                    class="btn btn-sm {{ $isInWishlist ? 'btn-wishlisted' : 'btn-outline-warning' }}"
                                                    title="{{ $isInWishlist ? 'In Wishlist' : 'Add to Wishlist' }}">
                                                <i class="fa {{ $isInWishlist ? 'fa-heart' : 'fa-heart-o' }}"></i>
                                            </button>
                                        </form>

                                        <form action="{{ route('customer.builder.action') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="p_id" value="{{ $item->product_id }}">
                                            <input type="hidden" name="build_id" value="{{ $id }}">
                                            <input type="hidden" name="action" value="cart_item">
                                            <button type="submit" class="btn btn-sm btn-outline-success" {{ !$inStock ? 'disabled' : '' }}><i class="fa fa-shopping-cart"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endfor

                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <td colspan="3" class="text-end fw-bold">Total Power:</td>
                                <td colspan="1" class="fw-bold text-info">{{ $totalHp }} HP</td>
                                <td colspan="1" class="text-end fw-bold">Total Build Cost:</td>
                                <td class="text-end fw-bold text-warning">
                                    ${{ number_format($grandTotal, 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if (!$isComplete)
                    <div class="alert alert-danger mt-3">
                        <i class="fa fa-times-circle"></i> <strong>Invalid Build:</strong> Mandatory parts are missing. A component might have been removed from the shop.
                    </div>
                @endif

                @if ($isComplete && !$isCompatible)
                    <div class="alert alert-danger mt-3">
                        <i class="fa fa-warning"></i> <strong>Compatibility Error:</strong> Some parts in this build no longer share a common tag.
                    </div>
                @endif

                @if (!$allInStock)
                    <div class="alert alert-warning mt-2">
                        <i class="fa fa-exclamation-triangle"></i> <strong>Stock Warning:</strong> One or more items in this build are currently out of stock.
                    </div>
                @endif

                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        <i class="fa fa-info-circle"></i> Status: 
                        {!! $isComplete ? '<span class="text-success">Complete</span>' : '<span class="text-danger">Incomplete</span>' !!} | 
                        {!! $isCompatible ? '<span class="text-success">Compatible</span>' : '<span class="text-danger">Incompatible</span>' !!}
                    </div>

                    <form action="{{ route('customer.builder.action') }}" method="POST">
                        @csrf
                        <input type="hidden" name="build_id" value="{{ $id }}">
                        <input type="hidden" name="action" value="add_build_to_cart">
                        <button type="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm" 
                                {{ (!$isCompatible || !$allInStock || !$isComplete) ? 'disabled' : '' }}>
                            <i class="fa fa-shopping-cart"></i> Add Full Build to Cart
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>