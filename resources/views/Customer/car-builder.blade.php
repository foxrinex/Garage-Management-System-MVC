<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Builder | Garage GMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .builder-header { background: #fff; padding: 20px; border-bottom: 1px solid #ddd; margin-bottom: 30px; }
        .section-title { background: #343a40; padding: 10px 15px; font-weight: bold; color: #fff; border-radius: 5px 5px 0 0; margin-top: 20px; }
        .category-row { background: #fff; border-bottom: 1px solid #eee; padding: 15px; display: flex; align-items: center; }
        .category-row.unavailable { background: #fff5f5; }
        .icon-box { width: 70px; height: 70px; background: #f8f9fa; border: 1px solid #eee; border-radius: 8px; margin-right: 20px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .icon-box img { width: 100%; height: 100%; object-fit: contain; padding: 5px; }
        .part-info { flex-grow: 1; }
        .mandatory-badge { font-size: 0.7rem; background: #dc3545; color: #fff; padding: 2px 6px; border-radius: 4px; margin-left: 10px; }
        .status-badge { font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; font-weight: bold; }
        .individual-actions .btn { padding: 2px 8px; }
        .tag-container { margin: 5px 0; }
        .tag-badge { 
            font-size: 0.65rem;
            padding: 2px 8px; border-radius: 50px; margin-right: 5px; 
            background: #f1f3f5; color: #495057; border: 1px solid #dee2e6; 
            text-transform: uppercase; font-weight: 600;
            display: inline-block;
        }
        .tag-universal { background: #d1e7dd; color: #0f5132; border-color: #badbcc; }
        .hp-badge { font-size: 0.75rem; background: #e9ecef; color: #0d6efd; padding: 2px 8px; border-radius: 4px; font-weight: bold; border: 1px solid #0d6efd; margin-left: 10px; }
        .hp-badge-neg { 
            font-size: 0.75rem; 
            background: #fff5f5; 
            color: #dc3545; 
            padding: 2px 8px; 
            border-radius: 4px; 
            font-weight: bold; 
            border: 1px solid #dc3545; 
            margin-left: 10px; 
        }
    </style>
</head>
<body class="bg-light" onload="checkSaveButton()">
    @include('customer.inc.navbar')

    <div class="builder-header shadow-sm">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 fw-bold">Build Your Custom Vehicle</h4>
                <small class="text-muted">Part selection logic synced with Showroom standards</small>
            </div>
            <div class="text-end d-flex align-items-center">
                <a href="{{ route('customer.saved.builds') }}" class="btn btn-outline-dark me-3">
                    <i class="fa fa-folder-open"></i> My Saved Builds
                </a>
                <div>
                    <div class="fw-bold text-primary fs-4" id="main-total">$0.00</div>
                    <span class="badge bg-dark">{{ count($dbSelections) }} items selected</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-md-9">
                <div class="section-title">Core Components</div>
                <div class="card shadow-sm border-0 mb-4">
                    @foreach ($mandatoryCats as $cat)
                        @php
                            $pId = $dbSelections[$cat->id] ?? null;
                            $part = $pId ? \App\Models\Product::find($pId) : null;

                            $isAvailable = true;
                            $isDeleted = false;
                            $pStatus = 'In Stock';
                            $isPartWishlisted = false;

                            if ($pId && !$part) {
                                $isDeleted = true;
                                $isAvailable = false;
                                $hasUnavailableParts = true;
                            }

                            if ($part) {
                                $pQty = $part->quantity ?? 0;
                                if ($pQty <= 0 || $part->status == 'Out of Stock' || $part->status == 'Upcoming') {
                                    $isAvailable = false;
                                    $hasUnavailableParts = true;
                                    $pStatus = $part->status == 'In Stock' ? 'Out of Stock' : $part->status;
                                } else {
                                    $pStatus = 'In Stock';
                                }

                                $qtyInCart = $currentCart[$pId] ?? 0;
                                if (($qtyInCart + 1) > $pQty) {
                                    $buildStockExceeded = true;
                                }

                                $isPartWishlisted = Wishlist::where('user_id', $userId)->where('product_id', $pId)->exists();
                            }
                        @endphp
                        <div class="category-row {{ (!$isAvailable && ($part || $isDeleted)) ? 'unavailable' : '' }}">
                            <div class="icon-box">
                                @php
                                    if ($isDeleted) {
                                        $imgSrc = "deleted-part.png";
                                    } else if ($part && $part->image) {
                                        $imgSrc = $part->image;
                                    } else {
                                        $imgSrc = $cat->category_img;
                                    }
                                @endphp
                                <img src="{{ asset('uploads/' . $imgSrc) }}" onerror="this.src='{{ asset('uploads/default-cat.png') }}'">
                            </div>

                            <div class="part-info">
                                <h6 class="mb-0 fw-bold">{{ $cat->category_name }} <span class="mandatory-badge">Required</span></h6>
                                @if ($isDeleted)
                                    <div class="text-danger fw-bold mb-1">Item has been deleted</div>
                                    <span class="badge bg-danger status-badge">Unavailable</span>
                                @elseif ($part)
                                    <div class="text-primary fw-bold mb-1">{{ $part->product_name ?? 'Unknown Product' }}</div>
                                    
                                    <div class="tag-container">
                                        @php
                                            if (!empty($part->tags)) {
                                                $pTags = explode(',', $part->tags);
                                                foreach ($pTags as $tName) {
                                                    $tName = trim($tName);
                                                    $isUni = (strtolower($tName) == 'universal');
                                                    echo '<span class="tag-badge '.($isUni ? 'tag-universal' : '').'"> <i class="fa fa-tag"></i> '.$tName.'</span>';
                                                }
                                            } else {
                                                echo '<span class="tag-badge tag-universal"> <i class="fa fa-tag"></i> Universal</span>';
                                            }
                                        @endphp
                                    </div>

                                    @if ($isAvailable)
                                        @php
                                            $price = $part->price ?? 0;
                                            $totalPrice += $price;
                                            $pHp = isset($part->hp) ? (int)$part->hp : 0;
                                            $totalHp += $pHp;
                                        @endphp
                                        <div class="d-flex align-items-center">
                                            <span class="fw-bold">${{ number_format($price, 2) }}</span>
                                            @if ($pHp > 0)
                                                <span class="hp-badge">+{{ $pHp }} HP</span>
                                            @elseif ($pHp < 0)
                                                <span class="hp-badge-neg">{{ $pHp }} HP</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-danger status-badge">{{ $pStatus }}</span>
                                    @endif
                                    
                                    <div class="individual-actions mt-1">
                                        <form action="{{ route('customer.builder.action') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="p_id" value="{{ $pId }}">
                                            <input type="hidden" name="action" value="wishlist_item">
                                            <button type="submit" class="btn btn-sm {{ $isPartWishlisted ? 'btn-warning' : 'btn-outline-warning' }}">
                                                <i class="fa fa-heart"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('customer.builder.action') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="p_id" value="{{ $pId }}">
                                            <input type="hidden" name="action" value="cart_item">
                                            @php
                                                $qtyInCart = $currentCart[$pId] ?? 0;
                                                $canAddToCart = ($isAvailable && ($qtyInCart < $part->quantity));
                                            @endphp
                                            <button type="submit" class="btn btn-sm btn-outline-success" {{ !$canAddToCart ? 'disabled title=Max stock reached' : '' }}>
                                                <i class="fa fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <small class='text-muted'>Not Selected</small>
                                @endif
                            </div>

                            <div class="action d-flex align-items-center">
                                @if ($part || $isDeleted)
                                    <form action="{{ route('customer.builder.action') }}" method="POST" class="me-2">
                                        @csrf
                                        <input type="hidden" name="cat_id" value="{{ $cat->id }}">
                                        <input type="hidden" name="action" value="remove_part">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                @endif
                                <a href="{{ route('customer.builder.select', ['cat_id' => $cat->id]) }}" class="btn btn-primary btn-sm px-4 fw-bold">
                                    {{ ($part || $isDeleted) ? 'Change' : 'Choose' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="section-title">Upgrades & Aesthetics</div>
                <div class="card shadow-sm border-0 mb-5">
                    @foreach ($optionalCats as $cat)
                        @php
                            $pId = $dbSelections[$cat->id] ?? null;
                            $part = $pId ? \App\Models\Product::find($pId) : null;

                            $isAvailable = true;
                            $isDeleted = false;
                            $pStatus = 'In Stock';
                            $isPartWishlisted = false;

                            if ($pId && !$part) {
                                $isDeleted = true;
                                $isAvailable = false;
                                $hasUnavailableParts = true;
                            }

                            if ($part) {
                                $pQty = $part->quantity ?? 0;
                                if ($pQty <= 0 || $part->status == 'Out of Stock' || $part->status == 'Upcoming') {
                                    $isAvailable = false;
                                    $hasUnavailableParts = true;
                                    $pStatus = $part->status == 'In Stock' ? 'Out of Stock' : $part->status;
                                } else {
                                    $pStatus = 'In Stock';
                                }

                                $qtyInCart = $currentCart[$pId] ?? 0;
                                if (($qtyInCart + 1) > $pQty) {
                                    $buildStockExceeded = true;
                                }

                                $isPartWishlisted = Wishlist::where('user_id', $userId)->where('product_id', $pId)->exists();
                            }
                        @endphp
                        <div class="category-row {{ (!$isAvailable && ($part || $isDeleted)) ? 'unavailable' : '' }}">
                            <div class="icon-box">
                                @php
                                    if ($isDeleted) {
                                        $imgSrc = "deleted-part.png";
                                    } else if ($part && $part->image) {
                                        $imgSrc = $part->image;
                                    } else {
                                        $imgSrc = $cat->category_img;
                                    }
                                @endphp
                                <img src="{{ asset('uploads/' . $imgSrc) }}" onerror="this.src='{{ asset('uploads/default-cat.png') }}'">
                            </div>

                            <div class="part-info">
                                <h6 class="mb-0 fw-bold">{{ $cat->category_name }}</h6>
                                @if ($isDeleted)
                                    <div class="text-danger fw-bold mb-1">Item has been deleted</div>
                                    <span class="badge bg-danger status-badge">Removed by Admin</span>
                                @elseif ($part)
                                    <div class="text-primary fw-bold mb-1">{{ $part->product_name ?? 'Unknown Product' }}</div>
                                    
                                    <div class="tag-container">
                                        @php
                                            if (!empty($part->tags)) {
                                                $pTags = explode(',', $part->tags);
                                                foreach ($pTags as $tName) {
                                                    $tName = trim($tName);
                                                    $isUni = (strtolower($tName) == 'universal');
                                                    echo '<span class="tag-badge '.($isUni ? 'tag-universal' : '').'"> <i class="fa fa-tag"></i> '.$tName.'</span>';
                                                }
                                            } else {
                                                echo '<span class="tag-badge tag-universal"> <i class="fa fa-tag"></i> Universal</span>';
                                            }
                                        @endphp
                                    </div>

                                    @if ($isAvailable)
                                        @php
                                            $price = $part->price ?? 0;
                                            $totalPrice += $price;
                                            $pHp = isset($part->hp) ? (int)$part->hp : 0;
                                            $totalHp += $pHp;
                                        @endphp
                                        <div class="d-flex align-items-center">
                                            <span class="fw-bold">${{ number_format($price, 2) }}</span>
                                            @if ($pHp > 0)
                                                <span class="hp-badge">+{{ $pHp }} HP</span>
                                            @elseif ($pHp < 0)
                                                <span class="hp-badge-neg">{{ $pHp }} HP</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-danger status-badge">{{ $pStatus }}</span>
                                    @endif

                                    <div class="individual-actions mt-1">
                                        <form action="{{ route('customer.builder.action') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="p_id" value="{{ $pId }}">
                                            <input type="hidden" name="action" value="wishlist_item">
                                            <button type="submit" class="btn btn-sm {{ $isPartWishlisted ? 'btn-warning' : 'btn-outline-warning' }}">
                                                <i class="fa fa-heart"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('customer.builder.action') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="p_id" value="{{ $pId }}">
                                            <input type="hidden" name="action" value="cart_item">
                                            @php
                                                $qtyInCart = $currentCart[$pId] ?? 0;
                                                $canAddToCart = ($isAvailable && ($qtyInCart < $part->quantity));
                                            @endphp
                                            <button type="submit" class="btn btn-sm btn-outline-success" {{ !$canAddToCart ? 'disabled title=Max stock reached' : '' }}>
                                                <i class="fa fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <small class='text-muted'>Optional Selection</small>
                                @endif
                            </div>

                            <div class="action d-flex align-items-center">
                                @if ($part || $isDeleted)
                                    <form action="{{ route('customer.builder.action') }}" method="POST" class="me-2">
                                        @csrf
                                        <input type="hidden" name="cat_id" value="{{ $cat->id }}">
                                        <input type="hidden" name="action" value="remove_part">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                @endif
                                <a href="{{ route('customer.builder.select', ['cat_id' => $cat->id]) }}" class="btn btn-outline-primary btn-sm px-4 fw-bold">
                                    {{ ($part || $isDeleted) ? 'Change' : 'Choose' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                    <div class="card-body text-center">
                        <h6 class="fw-bold">Build Status</h6>
                        <hr>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Estimated Total Performance</small>
                            <h3 class="fw-bold text-dark mb-0">{{ $totalHp }} <small style="font-size: 0.9rem;">HP</small></h3>
                        </div>
                        <hr>

                        @php
                            $missing = 0;
                            foreach ($mandatoryCats as $cat) {
                                if (!isset($dbSelections[$cat->id])) {
                                    $missing++;
                                }
                            }
                        @endphp

                        @if ($missing > 0)
                            <p class="small text-danger"><i class="fa fa-times-circle"></i> Missing {{ $missing }} core parts</p>
                            <button class="btn btn-secondary w-100 fw-bold mb-2" disabled>Add Build to Cart</button>
                        @elseif ($hasUnavailableParts)
                            <p class="small text-danger"><i class="fa fa-exclamation-triangle"></i> Some parts are unavailable</p>
                            <button class="btn btn-secondary w-100 fw-bold mb-2" disabled>Add Build to Cart</button>
                        @elseif (!$isCompatible)
                            <div class="alert alert-warning p-2 small text-start">
                                <i class="fa fa-warning"></i> <strong>Compatibility Issue:</strong> 
                                <p class="mb-0 mt-1" style="font-size: 0.75rem;">
                                    {{ $contradictionError != "" ? $contradictionError : "Selected parts do not share a common tag. Ensure all parts belong to the same tag group or are marked 'universal'." }}
                                </p>
                            </div>
                            <button class="btn btn-secondary w-100 fw-bold mb-2" disabled>Add Build to Cart</button>
                        @elseif ($buildStockExceeded)
                            <p class="small text-danger"><i class="fa fa-exclamation-circle"></i> Some parts exceed current stock levels in your cart</p>
                            <button class="btn btn-secondary w-100 fw-bold mb-2" disabled title="Check individual parts for stock limits">Add Build to Cart</button>
                        @else
                            <p class="small text-success"><i class="fa fa-check-circle"></i> Build Complete & Compatible!</p>
                            <form action="{{ route('customer.builder.action') }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="add_build_to_cart">
                                <button type="submit" class="btn btn-success w-100 fw-bold mb-2">Add Build to Cart</button>
                            </form>
                        @endif

                        <form action="{{ route('customer.builder.action') }}" method="POST" class="mb-2">
                            @csrf
                            <input type="hidden" name="action" value="save_build">
                            <div class="mb-2 text-start">
                                <label class="small fw-bold">Build Name:</label>
                                <input type="text" name="build_name" id="build_name" class="form-control form-control-sm" placeholder="e.g. My Fast Sedan" oninput="checkSaveButton()" required>
                            </div>
                            <button type="submit" id="save-build-btn" class="btn btn-primary w-100 fw-bold" {{ !$isCompatible ? 'disabled' : '' }}>
                                <i class="fa fa-save"></i> Save Build
                            </button>
                        </form>

                        <form action="{{ route('customer.builder.action') }}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="wishlist_build">
                            <button type="submit" class="btn {{ $isBuildWishlisted ? 'btn-warning' : 'btn-outline-warning' }} w-100 fw-bold" {{ count($dbSelections) == 0 ? 'disabled' : '' }}>
                                <i class="fa fa-heart"></i> Wishlist Build
                            </button>
                        </form>
                        
                        <form action="{{ route('customer.builder.action') }}" method="POST" class="mt-2">
                            @csrf
                            <input type="hidden" name="action" value="clear_all">
                            <button type="submit" class="btn btn-link btn-sm text-danger">Clear Progress</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('main-total').innerText = "${{ number_format($totalPrice, 2) }}";

        function checkSaveButton() {
            var nameInput = document.getElementById('build_name');
            var saveBtn = document.getElementById('save-build-btn');
            var selectionCount = {{ count($dbSelections) }};
            var isCompatible = {{ $isCompatible ? 'true' : 'false' }};

            if (nameInput && saveBtn) {
                if (nameInput.value.trim() !== "" && selectionCount > 0 && isCompatible === true) {
                    saveBtn.disabled = false;
                } else {
                    saveBtn.disabled = true;
                }
            }
        }
    </script>
</body>
</html>