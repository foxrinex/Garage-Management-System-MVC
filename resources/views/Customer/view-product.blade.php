<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $product->product_name }} - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .product-card-wrap { border-radius: 20px; overflow: hidden; }
        .product-img-main { border-radius: 15px; width: 100%; object-fit: cover; }
        .tag-badge { font-size: 0.75rem; border-radius: 50px; padding: 5px 15px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .price-text { font-size: 2rem; font-weight: 800; color: #0d6efd; }
        .purchase-zone { position: sticky; top: 20px; }
        .review-item { transition: 0.3s; border-radius: 15px; }
        .review-item:hover { background-color: #f8f9fa !important; }
        .company-reply { border-left: 4px solid #0d6efd; background: #f0f7ff; border-radius: 0 10px 10px 0; }
        .qty-input { border-radius: 10px 0 0 10px !important; max-width: 80px; text-align: center; }
        .warranty-box { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; }
    </style>
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success border-0 shadow-sm rounded-pill px-4">{{ session('success') }}</div>
                @endif
                
                @if (session('error'))
                    <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4">{{ session('error') }}</div>
                @endif

                @if (session('info'))
                    <div class="alert alert-info border-0 shadow-sm rounded-pill px-4">{{ session('info') }}</div>
                @endif
            </div>
        </div>

        <div class="row g-5">
            <div class="col-md-6">
                <div class="p-2 bg-white product-card-wrap shadow-sm">
                    <img src="{{ asset('uploads/' . $product->image) }}" class="product-img-main shadow-sm" onerror="this.src='https://via.placeholder.com/500'">
                </div>
                
                <div class="mt-4 p-4 bg-white shadow-sm" style="border-radius: 20px;">
                    <h5 class="fw-bold mb-3"><i class="fa fa-info-circle me-2 text-primary"></i>Description</h5>
                    <p class="text-secondary" style="line-height: 1.8;">{!! nl2br(e($product->description)) !!}</p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="purchase-zone">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 25px;">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item"><a href="{{ route('customer.store') }}" class="text-decoration-none">Store</a></li>
                                <li class="breadcrumb-item active">{{ !empty($product->category->category_name) ? $product->category->category_name : "General" }}</li>
                            </ol>
                        </nav>
                        
                        <h1 class="fw-bold mb-2">{{ $product->product_name }}</h1>
                        
                        <div class="mb-3">
                            @php
                                $p_tags = explode(", ", $product->tags);
                            @endphp
                            @foreach ($p_tags as $tag_text)
                                @if (!empty($tag_text))
                                    <span class="tag-badge bg-light text-dark border me-1">{{ $tag_text }}</span>
                                @endif
                            @endforeach
                            @if (empty($product->tags))
                                <span class="tag-badge bg-light text-muted border">Universal Fit</span>
                            @endif
                        </div>

                        <div class="price-text mb-3">${{ number_format($product->price, 2) }}</div>

                        <!-- Available Warranties Section -->
                        @php
                            $warrantyType = strtolower($product->warranty_type ?? $product->warranty ?? '');
                            $hasRegular = ($product->regular_warranty ?? false) || str_contains($warrantyType, 'regular') || $warrantyType === 'both';
                            $hasReplacement = ($product->replacement_warranty ?? false) || str_contains($warrantyType, 'replacement') || $warrantyType === 'both';
                        @endphp

                        <div class="warranty-box p-3 mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa fa-shield text-warning fa-lg me-2"></i>
                                <span class="fw-bold text-dark">Warranty Coverage</span>
                            </div>

                            @if ($hasRegular || $hasReplacement)
                                <div class="d-flex flex-wrap gap-2">
                                    @if ($hasRegular)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                                            <i class="fa fa-wrench me-1"></i> Regular Warranty (Repair & Service)
                                        </span>
                                    @endif

                                    @if ($hasReplacement)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                                            <i class="fa fa-refresh me-1"></i> Replacement Warranty (1-to-1 Exchange)
                                        </span>
                                    @endif
                                </div>
                                @if (!empty($product->warranty_duration))
                                    <small class="text-muted d-block mt-2">
                                        Coverage Period: <strong>{{ $product->warranty_duration }}</strong>
                                    </small>
                                @endif
                            @else
                                <small class="text-muted">
                                    <i class="fa fa-info-circle me-1"></i> Standard store return policy applies. No extended warranty listed for this item.
                                </small>
                            @endif
                        </div>
                        <!-- End Available Warranties Section -->

                        <div class="actions">
                            @if (session('role') == 'Customer')
                                @php
                                    $wish_action = $is_wishlisted ? 'remove' : 'add';
                                    $wish_icon = $is_wishlisted ? 'fa-heart' : 'fa-heart-o';
                                    $wish_btn_class = $is_wishlisted ? 'btn-danger' : 'btn-outline-danger';
                                    $wish_text = $is_wishlisted ? 'Wishlisted' : 'Add to Wishlist';
                                @endphp

                                @if ($product->status == 'Upcoming')
                                    <div class="alert alert-primary border-0 rounded-4">
                                        <strong>Coming Soon!</strong> This item isn't available for purchase yet.
                                    </div>
                                    <a href="{{ route('customer.wishlist.handle', ['id' => $product->id, 'action' => $wish_action, 'source' => 'view']) }}" 
                                       class="btn {{ $wish_btn_class }} btn-lg w-100 py-3 rounded-4 shadow-sm fw-bold">
                                       <i class="fa {{ $wish_icon }} me-2"></i> {{ $wish_text }}
                                    </a>

                                @elseif ($product->status == 'In Stock' && $product->quantity > 0)
                                    <form action="{{ route('customer.cart.add') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $product->id }}">
                                        <input type="hidden" name="source" value="view">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <div class="input-group input-group-lg" style="width: 160px;">
                                                <span class="input-group-text bg-white border-end-0 qty-input"><i class="fa fa-cubes"></i></span>
                                                <input type="number" name="qty" class="form-control border-start-0 text-center fw-bold" value="1" min="1" max="{{ $product->quantity }}" style="border-radius: 0 10px 10px 0;">
                                            </div>
                                            <small class="text-success fw-bold">{{ $product->quantity }} available</small>
                                        </div>

                                        <div class="row g-2">
                                            <div class="col-8">
                                                <button type="submit" class="btn btn-dark btn-lg w-100 py-3 rounded-4 fw-bold shadow">
                                                    <i class="fa fa-shopping-cart me-2"></i> Add to Cart
                                                </button>
                                            </div>
                                            <div class="col-4">
                                                <a href="{{ route('customer.wishlist.handle', ['id' => $product->id, 'action' => $wish_action, 'source' => 'view']) }}" 
                                                class="btn {{ $wish_btn_class }} btn-lg w-100 py-3 rounded-4 shadow-sm">
                                                <i class="fa {{ $wish_icon }}"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </form>

                                    @if ($canReview)
                                        <div class="mt-4 pt-4 border-top">
                                            <a href="{{ route('customer.warranty.select.order', ['product_id' => $product->id]) }}" class="btn btn-warning w-100 py-2 rounded-pill fw-bold">
                                                <i class="fa fa-shield me-2"></i> Claim Warranty
                                            </a>
                                        </div>
                                    @endif

                                @else
                                    <div class="alert alert-danger border-0 rounded-4">
                                        <strong>Sold Out!</strong> We are currently out of stock.
                                    </div>
                                    <a href="{{ route('customer.wishlist.handle', ['id' => $product->id, 'action' => $wish_action, 'source' => 'view']) }}" 
                                       class="btn {{ $wish_btn_class }} btn-lg w-100 py-3 rounded-4 shadow-sm">
                                       <i class="fa {{ $wish_icon }} me-2"></i> Save for Later
                                    </a>
                                @endif

                            @else
                                <div class="bg-light p-4 rounded-4 text-center border">
                                    <p class="mb-3 text-muted">Want to buy this item?</p>
                                    <a href="{{ route('login') }}" class="btn btn-primary px-4 rounded-pill">Login to Shop</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5 pt-5 pb-5">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0">Customer Feedback</h3>
                    <span class="badge bg-dark rounded-pill">{{ count($reviews) }} Reviews</span>
                </div>

                @if ($canReview)
                    <div class="card border-0 shadow-sm p-4 mb-5" style="border-radius: 20px;">
                        @if ($myExistingReview)
                            <h5 class="text-primary fw-bold mb-3">Update Your Review</h5>
                            <form action="{{ route('customer.review.post') }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="review_id" value="{{ $myExistingReview->id }}">
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                
                                <div class="mb-3">
                                    <label class="small fw-bold text-uppercase text-muted">Your Rating</label>
                                    <select name="rating" class="form-select w-25 rounded-3">
                                        @for ($k = 5; $k >= 1; $k--)
                                            <option value="{{ $k }}" @if($myExistingReview->rating == $k) selected @endif>{{ $k }} Stars</option>
                                        @endfor
                                    </select>
                                </div>
                                <textarea name="comment" class="form-control mb-3 rounded-4 p-3" rows="3" required>{{ $myExistingReview->comment }}</textarea>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Update Review</button>
                                    <a href="{{ route('customer.review.post', ['action' => 'delete', 'review_id' => $myExistingReview->id, 'product_id' => $product->id]) }}" 
                                       class="btn btn-outline-danger px-4 rounded-pill">Delete</a>
                                </div>
                            </form>
                        @else
                            <h5 class="fw-bold mb-3">Rate this Product</h5>
                            <form action="{{ route('customer.review.post') }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <div class="mb-3 w-25">
                                    <select name="rating" class="form-select rounded-3">
                                        <option value="5">5 Stars</option>
                                        <option value="4">4 Stars</option>
                                        <option value="3">3 Stars</option>
                                        <option value="2">2 Stars</option>
                                        <option value="1">1 Star</option>
                                    </select>
                                </div>
                                <textarea name="comment" class="form-control mb-3 rounded-4 p-3" rows="3" placeholder="Share your experience with other car enthusiasts..." required></textarea>
                                <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-bold">Post Review</button>
                            </form>
                        @endif
                    </div>
                @endif

                <div class="bg-white p-4 shadow-sm" style="border-radius: 20px;">
                    @if (count($reviews) > 0)
                        @foreach ($reviews as $r)
                            @php
                                $isMyReview = (session('user_id') && $r->user_id == session('user_id'));
                            @endphp
                            <div class="review-item p-4 mb-2 {{ $isMyReview ? 'bg-light border border-primary-subtle' : '' }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            {{ strtoupper(substr($r->customer->fname ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong class="d-block text-dark">{{ ($r->customer->fname ?? 'User') . " " . ($r->customer->lname ?? '') }} @if($isMyReview)<span class="badge bg-primary ms-1">You</span>@endif</strong>
                                            <small class="text-muted">{{ date("M d, Y", strtotime($r->date_posted)) }}</small>
                                        </div>
                                    </div>
                                    <div class="text-warning">
                                        @for ($j = 0; $j < $r->rating; $j++) <i class="fa fa-star"></i> @endfor
                                    </div>
                                </div>
                                <p class="mb-0 text-secondary ps-5">{{ $r->comment }}</p>

                                @if (!empty($r->company_reply))
                                    <div class="company-reply ms-5 p-3 mt-3 shadow-sm">
                                        <small class="fw-bold text-primary d-block mb-1"><i class="fa fa-reply me-1"></i> Rev Nation Response:</small>
                                        <p class="mb-0 small fst-italic text-dark">{{ $r->company_reply }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class='text-center py-4'>
                            <i class='fa fa-comments-o fa-3x text-light mb-2 d-block'></i>
                            @if ($canReview)
                                <p class='text-muted'>No reviews yet. Be the first to share your thoughts!</p>
                            @else
                                <p class='text-muted'>No reviews yet. Buy the product and be the first to share your thoughts!</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>