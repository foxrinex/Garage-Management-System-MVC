<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager View - {{ $car->brand }} | Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .manager-img { border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); width: 100%; height: auto; }
        .spec-badge { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 10px; padding: 15px; }
        .control-panel { position: sticky; top: 20px; border-radius: 20px; border: none; }
        .review-card { border-radius: 15px; transition: 0.2s; }
        .reply-box { border-left: 4px solid #6c757d; background-color: #f1f3f5; }
        .active-reply { border-left-color: #0d6efd; background-color: #e7f1ff; }
    </style>
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container mt-5 mb-5">
        
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4">
                <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="mb-4">
                    <img src="{{ asset('uploads/' . $car->image) }}" class="manager-img" onerror="this.src='https://via.placeholder.com/600x400?text=No+Image'">
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h4 class="fw-bold mb-3">Specifications</h4>
                    <div class="row g-3">
                        <div class="col-md-4 text-center">
                            <div class="spec-badge">
                                <small class="text-muted d-block text-uppercase">Year</small>
                                <span class="fw-bold">{{ $car->year }}</span>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="spec-badge">
                                <small class="text-muted d-block text-uppercase">Transmission</small>
                                <span class="fw-bold">{{ $car->transmission }}</span>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="spec-badge">
                                <small class="text-muted d-block text-uppercase">Mileage</small>
                                <span class="fw-bold">{{ number_format($car->mileage) }} km</span>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold mt-4 mb-2">Description</h5>
                    <p class="text-secondary">{!! nl2br(e($car->description)) !!}</p>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">Review Management</h4>
                    <span class="badge bg-dark rounded-pill">{{ $reviews->count() }} Reviews</span>
                </div>

                @if ($reviews->count() == 0)
                    <div class="alert bg-white border shadow-sm text-center py-5">
                        <i class="fa fa-comments-o fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No customers have reviewed this vehicle yet.</p>
                    </div>
                @endif

                @foreach ($reviews as $r)
                    @php
                        $isEditing = (isset($editId) && $editId == $r->id);
                    @endphp
                    <div class="card p-4 mb-3 review-card border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-0">{{ optional($r->customer)->fname . " " . optional($r->customer)->lname }}</h6>
                                <small class="text-muted">Posted: {{ date("M d, Y", strtotime($r->date_posted)) }}</small>
                            </div>
                            <div class="text-warning">
                                @for ($j = 0; $j < $r->rating; $j++) ★ @endfor
                            </div>
                        </div>
                        <p class="mt-3 text-dark">{{ $r->comment }}</p>
                        
                        <div class="mt-3 p-3 rounded reply-box {{ !empty($r->company_reply) ? 'active-reply' : '' }}">
                            @if (!empty($r->company_reply) && !$isEditing)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="fw-bold text-primary">COMPANY RESPONSE</small>
                                    <div>
                                        <a href="{{ route('manager.car.view', ['id' => $car->id, 'edit_id' => $r->id]) }}" 
                                           class="btn btn-sm btn-link text-decoration-none p-0 me-2">Edit</a>
                                        <a href="{{ route('manager.car.review.reply.delete', ['id' => $r->id, 'car_id' => $car->id]) }}" 
                                           class="btn btn-sm btn-link text-danger text-decoration-none p-0"
                                           onclick="return confirm('Remove this reply?')">Delete</a>
                                    </div>
                                </div>
                                <p class="mb-0 small fst-italic text-secondary">{{ $r->company_reply }}</p>

                            @else
                                <form action="{{ route('manager.car.review.reply') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="review_id" value="{{ $r->id }}">
                                    <input type="hidden" name="car_id" value="{{ $car->id }}">
                                    <label class="small fw-bold mb-2 text-uppercase">{{ $isEditing ? 'Update Reply' : 'New Reply' }}</label>
                                    <div class="input-group">
                                        <input type="text" name="reply_text" class="form-control" 
                                               placeholder="Type your official response..." 
                                               value="{{ $isEditing ? $r->company_reply : '' }}" required>
                                        <button class="btn btn-primary" type="submit">
                                            {{ $isEditing ? 'Update' : 'Post Reply' }}
                                        </button>
                                        @if ($isEditing)
                                            <a href="{{ route('manager.car.view', $car->id) }}" class="btn btn-secondary">Cancel</a>
                                        @endif
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="col-lg-5">
                <div class="control-panel card shadow-sm p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-secondary mb-2">{{ optional($car->category)->category_name }}</span>
                            <h2 class="fw-bold mb-0">{{ $car->brand }}</h2>
                            <p class="text-muted">{{ $car->model }}</p>
                        </div>
                        <div class="text-end">
                            <h3 class="text-success fw-bold">${{ number_format($car->price) }}</h3>
                            <span class="badge {{ ($car->quantity > 0) ? 'bg-success' : 'bg-danger' }}">
                                {{ $car->quantity }} In Stock
                            </span>
                        </div>
                    </div>

                    <hr>

                    <div class="d-grid gap-3 mt-4">
                        @if ($isLockedByAuction)
                            <div class="alert alert-danger mb-0 border-0 shadow-sm">
                                <h6 class="fw-bold mb-1"><i class="fa fa-lock"></i> Listing Locked</h6>
                                <p class="small mb-0">This car is currently tied to an <strong>Active or Unpaid Auction</strong>. Edit features are disabled.</p>
                            </div>
                            <button class="btn btn-secondary btn-lg py-3 disabled">
                                <i class="fa fa-edit me-2"></i> Edit Car Details
                            </button>
                        @else
                            <a href="{{ route('manager.car.edit', $car->id) }}" class="btn btn-warning btn-lg py-3 fw-bold shadow-sm">
                                <i class="fa fa-edit me-2"></i> Edit Car Details
                            </a>
                        @endif

                        <a href="#" class="btn btn-outline-dark btn-lg py-3 fw-bold">
                            <i class="fa fa-arrow-left me-2"></i> Back to Showroom
                        </a>
                    </div>

                    <div class="mt-5">
                        <h6 class="fw-bold text-muted text-uppercase small">Inventory Health</h6>
                        <div class="progress mt-2" style="height: 10px; border-radius: 50px;">
                            @php
                                $stockPercent = ($car->quantity > 10) ? 100 : $car->quantity * 10;
                                $barColor = ($car->quantity < 3) ? 'bg-danger' : 'bg-success';
                            @endphp
                            <div class="progress-bar {{ $barColor }}" style="width: {{ $stockPercent }}%"></div>
                        </div>
                        <small class="text-muted d-block mt-1">Stock status: {{ $car->quantity }} units remaining</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>