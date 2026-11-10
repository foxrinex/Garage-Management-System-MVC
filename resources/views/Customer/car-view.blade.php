<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $car->brand . " " . $car->model }} | Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .car-main-img { border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; height: auto; object-fit: cover; }
        .spec-card { background: #fff; border: 1px solid #eee; border-radius: 12px; transition: 0.3s; }
        .spec-card:hover { transform: translateY(-5px); border-color: #0d6efd; }
        .price-tag { font-size: 2.5rem; font-weight: 800; color: #198754; }
        .review-card { border: none; border-radius: 15px; background: #fff; }
        .sticky-sidebar { position: sticky; top: 20px; }
        .badge-premium { padding: 8px 16px; border-radius: 50px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .company-reply { background: #f0f7ff; border-left: 4px solid #0d6efd; padding: 15px; border-radius: 0 10px 10px 0; }
    </style>
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5 mb-5">
        
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4">
                <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-5">
            <div class="col-lg-7">
                <img src="{{ asset('uploads/' . $car->image) }}" class="car-main-img mb-4">
                
                <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 15px;">
                    <h4 class="fw-bold mb-3">Description</h4>
                    <p class="text-secondary" style="line-height: 1.8; font-size: 1.1rem;">
                        {!! nl2br(e($car->description)) !!}
                    </p>
                </div>

                <div class="row g-3 mb-5">
                    <div class="col-md-4">
                        <div class="spec-card p-3 text-center">
                            <i class="fa fa-cog text-primary mb-2 fa-2x"></i>
                            <small class="text-muted d-block">Transmission</small>
                            <span class="fw-bold">{{ $car->transmission }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="spec-card p-3 text-center">
                            <i class="fa fa-dashboard text-primary mb-2 fa-2x"></i>
                            <small class="text-muted d-block">Mileage</small>
                            <span class="fw-bold">{{ number_format($car->mileage) }} km</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="spec-card p-3 text-center">
                            <i class="fa fa-cubes text-primary mb-2 fa-2x"></i>
                            <small class="text-muted d-block">Inventory</small>
                            <span class="fw-bold">{{ $car->quantity }} Units</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">Customer Reviews</h4>
                    <span class="badge bg-white text-dark border p-2">{{ count($reviews) }} Feedback</span>
                </div>

                @if ($hasPurchased)
                    <div class="card p-4 mb-4 shadow-sm border-0" style="border-radius: 15px; background: #f8f9fa;">
                        <h5 class="fw-bold mb-3">{{ $myReview ? 'Manage Your Review' : 'Share Your Experience' }}</h5>
                        <form action="{{ route('customer.review.post') }}" method="POST">
                            @csrf
                            <input type="hidden" name="car_id" value="{{ $carId }}">
                            @if ($myReview)
                                <input type="hidden" name="review_id" value="{{ $myReview->id }}">
                                <input type="hidden" name="action" value="update">
                            @else
                                <input type="hidden" name="action" value="add">
                            @endif

                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <select name="rating" class="form-select border-0 shadow-sm">
                                        @for ($i = 5; $i >= 1; $i--)
                                            @php
                                                $selected = ($myReview && $i == $myReview->rating) ? 'selected' : '';
                                            @endphp
                                            <option value="{{ $i }}" {{ $selected }}>
                                                {{ $i }} Stars
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <textarea name="comment" class="form-control border-0 shadow-sm" rows="3" placeholder="How is the car performing?" required>{{ $myReview ? $myReview->comment : '' }}</textarea>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-dark px-4">{{ $myReview ? 'Update' : 'Submit' }}</button>
                                @if ($myReview)
                                    <a href="{{ route('customer.review.delete', ['review_id' => $myReview->id, 'car_id' => $carId]) }}" 
                                       class="btn btn-outline-danger" onclick="return confirm('Delete permanently?')">Delete</a>
                                @endif
                            </div>
                        </form>
                    </div>
                @endif

                @foreach ($reviews as $r)
                    <div class="card p-4 mb-3 review-card shadow-sm {{ $r->user_id == $userId ? 'border-start border-primary border-5' : '' }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-0">{{ $r->customer ? $r->customer->fname . " " . $r->customer->lname : 'User' }} 
                                    @if ($r->user_id == $userId) <span class="badge bg-primary ms-1">You</span> @endif
                                </h6>
                                <small class="text-muted">{{ date("F j, Y", strtotime($r->date_posted)) }}</small>
                            </div>
                            <div class="text-warning">
                                @for ($j = 0; $j < $r->rating; $j++) <i class="fa fa-star"></i> @endfor
                            </div>
                        </div>
                        <p class="mt-3 text-secondary">{{ $r->comment }}</p>
                        
                        @if (!empty($r->company_reply))
                            <div class="company-reply mt-3">
                                <small class="fw-bold text-primary text-uppercase">Rev Nation Response</small>
                                <p class="mb-0 mt-1 small text-dark">{{ $r->company_reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="col-lg-5">
                <div class="sticky-sidebar">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
                        <span class="badge bg-primary-soft text-primary badge-premium mb-2" style="background: #e7f1ff; width: fit-content;">
                            {{ $car->category->category_name ?? 'Car' }}
                        </span>
                        <h1 class="fw-bold display-6">{{ $car->brand }}</h1>
                        <h3 class="text-muted mb-4">{{ $car->model . " (" . $car->year . ")" }}</h3>
                        
                        <div class="price-tag mb-4">${{ number_format($car->price) }}</div>

                        <hr class="mb-4">

                        <div class="mb-4">
                            <h6 class="fw-bold"><i class="fa fa-shield text-success me-2"></i> Warranty Included</h6>
                            <ul class="list-unstyled mt-2">
                                @if ($car->regular_warranty == 1)
                                    <li class="mb-2"><i class="fa fa-check text-success me-2"></i> Standard Service Warranty</li>
                                @endif
                                @if ($car->replacement_warranty == 1)
                                    <li><i class="fa fa-check text-success me-2"></i> Full Parts Replacement Coverage</li>
                                @endif
                            </ul>
                        </div>

                        <div class="d-grid gap-3">
                            @if ($car->quantity > 0)
                                <a href="{{ route('customer.car.transaction', $carId) }}" class="btn btn-success btn-lg py-3 fw-bold shadow">
                                    PROCEED TO PURCHASE
                                </a>
                            @else
                                <button class="btn btn-secondary btn-lg py-3 fw-bold" disabled>OUT OF STOCK</button>
                            @endif

                            @php
                                $wishAction = $isWishlisted ? "remove" : "add";
                                $wishBtnClass = $isWishlisted ? "btn-danger" : "btn-outline-danger";
                                $wishIcon = $isWishlisted ? "fa-heart" : "fa-heart-o";
                            @endphp
                            <a href="{{ route('customer.wishlist.handle', ['id' => $carId, 'action' => $wishAction, 'type' => 'car', 'source' => 'car-view']) }}" 
                               class="btn {{ $wishBtnClass }} btn-lg py-3 fw-bold">
                                <i class="fa {{ $wishIcon }} me-2"></i> 
                                {{ $isWishlisted ? 'REMOVE FROM WISHLIST' : 'ADD TO WISHLIST' }}
                            </a>
                        </div>

                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <i class="fa fa-info-circle me-1"></i> Secure checkout powered by Rev Nation
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>