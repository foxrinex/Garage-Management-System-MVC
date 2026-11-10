<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Transaction - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header bg-white py-3">
                        <h4 class="fw-bold mb-0 text-center">
                            <i class="fa {{ $isAuction ? 'fa-gavel' : 'fa-credit-card' }} me-2"></i>
                            {{ $headingText }}
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-5">
                                <img src="{{ asset('uploads/' . $car->image) }}" class="img-fluid rounded shadow-sm" onerror="this.src='https://via.placeholder.com/400x250?text=Vehicle+Image'">
                            </div>
                            <div class="col-md-7">
                                <small class="text-muted text-uppercase">{{ $car->brand }}</small>
                                <h3 class="fw-bold">{{ $car->model }}</h3>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Category:</span>
                                    <span class="fw-bold">{{ $car->category->category_name ?? 'N/A' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Year:</span>
                                    <span class="fw-bold">{{ $car->year }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-4">
                                    <span>Transmission:</span>
                                    <span class="fw-bold">{{ $car->transmission }}</span>
                                </div>
                                
                                <div class="bg-light p-3 rounded d-flex justify-content-between align-items-center">
                                    <span class="h5 mb-0">Total Amount:</span>
                                    <span class="h3 fw-bold text-success mb-0">${{ number_format($finalPrice) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 p-4">
                        <form action="{{ $purchaseAction }}" method="POST">
                            @csrf
                            <input type="hidden" name="car_id" value="{{ $car->id }}">
                            <input type="hidden" name="price" value="{{ $finalPrice }}">
                            @if ($isAuction)
                                <input type="hidden" name="auction_id" value="{{ $auctionId }}">
                            @endif
                            
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <a href="{{ $isAuction ? route('customer.auction.view', $auctionId) : route('customer.showroom') }}" class="btn btn-outline-secondary w-100 py-2">Cancel</a>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                        Confirm & Pay <i class="fa fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>