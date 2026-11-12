<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bid History - {{ $auction->brand }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .history-container { 
            background: #fff; 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        }
        .badge-winner { background-color: #198754; color: white; }
    </style>
</head>
<body>
    @include('manager.inc.navbar')

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold m-0 text-dark">
                            <i class="fa fa-history text-success me-2"></i> Bid History
                        </h2>
                        <p class="text-muted">
                            {{ $auction->year }} {{ $auction->brand }} {{ $auction->model }} 
                            <span class="ms-2 badge bg-secondary">ID: #{{ $auction->id }}</span>
                        </p>
                    </div>
                    <a href="{{ route('manager.auction.dashboard') }}" class="btn btn-outline-primary rounded-pill">
                        <i class="fa fa-arrow-left"></i> Back to Auction Dashboard
                    </a>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="history-container mb-4">
                            <h6 class="text-uppercase fw-bold text-muted mb-3" style="font-size: 0.8rem;">Auction Summary</h6>
                            
                            <div class="mb-3">
                                <label class="d-block text-muted small">Reserve Price</label>
                                <span class="fw-bold">${{ number_format($auction->reserve_price) }}</span>
                            </div>

                            <div class="mb-3">
                                <label class="d-block text-muted small">Min. Increment</label>
                                <span class="fw-bold">${{ number_format($auction->min_increment) }}</span>
                            </div>

                            <div class="mb-0">
                                <label class="d-block text-muted small">Total Bids</label>
                                <span class="fw-bold text-success" style="font-size: 1.5rem;">{{ $totalBids }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="history-container">
                            <h5 class="fw-bold mb-4">All Bids</h5>

                            @if ($totalBids > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Bidder</th>
                                                <th>Amount</th>
                                                <th>Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $bidsCount = count($bids);
                                            @endphp
                                            @for ($i = 0; $i < $bidsCount; $i = $i + 1) 
                                                @php
                                                    $b = $bids[$i];
                                                    $isHighest = ($i == 0);
                                                @endphp
                                                <tr class="{{ $isHighest ? 'table-success' : '' }}">
                                                    <td>
                                                        <div class="fw-bold text-dark">
                                                            <i class="fa fa-user-circle-o me-1 text-muted"></i>
                                                            {{ $b->username }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($b->bid_amount) }}</span>
                                                    </td>
                                                    <td class="text-muted small">
                                                        {{ date('M d, Y | h:i A', strtotime($b->bid_time)) }}
                                                    </td>
                                                    <td>
                                                        @if ($isHighest)
                                                            <span class="badge badge-winner">Highest Bid</span>
                                                        @else
                                                            <span class="badge bg-light text-muted border">Outbid</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endfor
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <p class="text-muted">No bids have been placed yet for this auction.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>