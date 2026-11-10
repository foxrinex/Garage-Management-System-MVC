<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .review-card { border: none; border-radius: 12px; }
        .table thead { background-color: #212529; color: #fff; }
        .star-rating { color: #f1c40f; letter-spacing: 2px; }
        .reply-box { background-color: #f8f9fa; border-left: 3px solid #2ecc71; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    @include('customer.inc.navbar')

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">My Feedback</h2>
                <p class="text-muted">History of your ratings and company responses</p>
            </div>
            <span class="badge bg-dark rounded-pill px-3 py-2">{{ count($reviews) }} Reviews</span>
        </div>

        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm rounded-3">
                <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-0 shadow-sm rounded-3">
                <i class="fa fa-times-circle me-2"></i> {{ session('error') }}
            </div>
        @endif

        @if (count($reviews) > 0)
            <div class="card review-card shadow-sm overflow-hidden mb-5">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3">Item Details</th>
                                <th>Rating</th>
                                <th style="width: 30%;">Comment</th>
                                <th>Company Reply</th>
                                <th class="pe-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reviews as $r)
                                @php
                                    $itemName = "Unknown Item";
                                    $viewLink = "#";
                                    
                                    if ($r->product) {
                                        $itemName = $r->product->product_name;
                                        $viewLink = route('customer.product.view', $r->product_id);
                                    } elseif ($r->car) {
                                        $itemName = $r->car->brand . " " . $r->car->model;
                                        $viewLink = route('customer.car.view', $r->car_id);
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <a href="{{ $viewLink }}" class="text-decoration-none fw-bold text-dark d-block">
                                            {{ $itemName }}
                                        </a>
                                        <small class="text-muted">{{ date("M d, Y", strtotime($r->date_posted)) }}</small>
                                    </td>
                                    <td>
                                        <div class="star-rating">
                                            @for ($j = 0; $j < $r->rating; $j++)
                                                ★
                                            @endfor
                                        </div>
                                    </td>
                                    <td>
                                        <p class="mb-0 small text-secondary italic">"{{ $r->comment }}"</p>
                                    </td>
                                    <td>
                                        @if (!empty($r->company_reply))
                                            <div class="reply-box">
                                                <small class="d-block fw-bold text-success mb-1"><i class="fa fa-reply"></i> Rev Nation:</small>
                                                <small class="text-dark">{{ $r->company_reply }}</small>
                                            </div>
                                        @else
                                            <span class="badge bg-light text-muted border fw-normal">Pending Reply</span>
                                        @endif
                                    </td>
                                    <td class="pe-4 text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="{{ $viewLink }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                Edit
                                            </a>
                                            <a href="{{ route('customer.review.delete.my', $r->id) }}" 
                                               class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                               onclick="return confirm('Are you sure you want to delete this review?');">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="text-center bg-white p-5 shadow-sm rounded-3">
                <i class="fa fa-pencil-square-o fa-4x text-light mb-3"></i>
                <h5 class="fw-bold">No feedback yet</h5>
                <p class="text-muted">Share your experience with our products or vehicles!</p>
                <a href="{{ route('customer.dashboard') }}" class="btn btn-dark rounded-pill px-4">Start Shopping</a>
            </div>
        @endif
        
        <div class="mb-5 text-center">
             <a href="{{ route('customer.dashboard') }}" class="btn btn-light border rounded-pill px-4"><i class="fa fa-arrow-left me-2"></i>Back to Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>