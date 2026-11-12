<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $auction->car->brand }} {{ $auction->car->model }} | Auction Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .auction-card { border-radius: 20px; overflow: hidden; border: none; background: #fff; }
        .timer-box { background: #111; color: #fff; padding: 20px; border-radius: 15px; text-align: center; border-bottom: 5px solid #0d6efd; }
        .bid-history-scroll { max-height: 300px; overflow-y: auto; }
        .current-bid-display { font-size: 2.5rem; font-weight: 800; color: #0d6efd; }
        .buy-now-display { font-size: 1.8rem; font-weight: 700; color: #198754; }
        .spec-item { background: #f8f9fa; padding: 15px; border-radius: 10px; border: 1px solid #eee; height: 100%; }
        .showroom-link { text-decoration: none; color: #198754; font-weight: bold; transition: 0.3s; }
        .showroom-link:hover { color: #146c43; text-decoration: underline; }
    </style>
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5 mb-5">
        
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Dynamic Status Banner --}}
        @if ($auction->status == 'Active')
            @if ($isHighestBidder)
                <div class="alert alert-success d-flex align-items-center mb-4 shadow-sm" style="border-radius: 15px;">
                    <i class="fa fa-trophy fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-0 fw-bold">You are currently the highest bidder!</h6>
                        <small>Keep an eye on the timer. If no one outbids you, the car is yours.</small>
                    </div>
                </div>
            @endif
        @else
            @if ($auction->highest_bidder_id == session('user_id'))
                <div class="alert alert-success d-flex align-items-center mb-4 shadow-sm" style="border-radius: 15px;">
                    <i class="fa fa-trophy fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-0 fw-bold">Congratulations! You won this auction!</h6>
                        <small>The auction has ended and you secured the winning bid. Contact the Garage to arrange payment and pickup.</small>
                    </div>
                </div>
            @else
                <div class="alert alert-secondary d-flex align-items-center mb-4 shadow-sm" style="border-radius: 15px;">
                    <i class="fa fa-info-circle fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-0 fw-bold">Auction Closed</h6>
                        <small>Better luck next time! This auction has ended and another bidder won.</small>
                    </div>
                </div>
            @endif
        @endif

        <div class="row">
            <div class="col-md-7">
                <div class="card auction-card shadow-sm mb-4">
                    <img src="{{ asset('uploads/' . $auction->car->image) }}" class="img-fluid" style="width: 100%; height: 450px; object-fit: cover;">
                    
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary px-3 py-2">{{ $auction->car->category->category_name ?? 'Car' }}</span>
                            
                            <a href="{{ route('customer.car.view', $auction->car_id) }}" class="showroom-link">
                                <i class="fa fa-university me-1"></i> View in Showroom 
                                <span class="badge bg-success ms-1">{{ $auction->car->quantity }} In Stock</span>
                            </a>
                        </div>

                        <h1 class="fw-bold mb-1">{{ $auction->car->brand }} {{ $auction->car->model }}</h1>
                        <p class="text-muted fs-5">Year: {{ $auction->car->year }}</p>
                        
                        <hr class="my-4">
                        
                        <h5 class="fw-bold mb-3">Vehicle Description</h5>
                        <p class="text-secondary" style="line-height: 1.7;">
                            {!! nl2br(e($auction->car->description)) !!}
                        </p>

                        <h5 class="fw-bold mt-5 mb-3">Technical Specifications</h5>
                        <div class="row g-3">
                            <div class="col-6 col-md-4">
                                <div class="spec-item text-center">
                                    <small class="text-muted text-uppercase d-block mb-1">Transmission</small>
                                    <span class="fw-bold">{{ $auction->car->transmission }}</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="spec-item text-center">
                                    <small class="text-muted text-uppercase d-block mb-1">Mileage</small>
                                    <span class="fw-bold">{{ number_format($auction->car->mileage) }} km</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="spec-item text-center">
                                    <small class="text-muted text-uppercase d-block mb-1">Stock ID</small>
                                    <span class="fw-bold">#CAR-{{ $auction->car_id }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                @if ($auction->status == 'Active')
                    <div class="timer-box mb-4 shadow">
                        <h6 class="text-uppercase small mb-2" style="letter-spacing: 1px;">Time Remaining</h6>
                        <h2 id="countdown" class="fw-bold mb-0">--:--:--</h2>
                    </div>
                @else
                    <div class="alert alert-secondary text-center fw-bold py-3 mb-4 shadow-sm" style="border-radius: 15px;">
                        <i class="fa fa-clock-o me-2"></i> AUCTION ENDED
                    </div>
                @endif

                <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 20px;">
                    <p class="text-muted mb-1">{{ $auction->status == 'Active' ? 'Current Highest Bid' : 'Winning Bid' }}</p>
                    <div class="current-bid-display mb-4">${{ number_format($auction->current_bid) }}</div>

                    @if ($auction->status == 'Active')
                        <form action="{{ route('customer.auction.bid') }}" method="POST">
                            @csrf
                            <input type="hidden" name="auction_id" value="{{ $auction->id }}">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Enter Your Bid</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white">$</span>
                                    <input type="number" name="bid_amount" class="form-control" 
                                           min="{{ $minNextBid }}" 
                                           value="{{ $minNextBid }}" 
                                           required 
                                           {{ $isHighestBidder ? 'disabled' : '' }}>
                                </div>
                                <div class="form-text mt-2 text-primary">
                                    Minimum Bid: <strong>${{ number_format($minNextBid) }}</strong>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" 
                                        class="btn btn-primary btn-lg fw-bold py-3 shadow-sm {{ $isHighestBidder ? 'disabled opacity-50' : '' }}"
                                        {{ $isHighestBidder ? 'disabled' : '' }}>
                                    {{ $isHighestBidder ? 'YOU ARE LEADING' : 'PLACE BID' }}
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="text-muted mb-1">Don't want to wait? Buy it instantly!</p>
                            <div class="buy-now-display mb-3">${{ number_format($auction->reserve_price) }}</div>
                            
                            <div class="d-grid">
                                <a href="{{ route('customer.car.transaction', ['id' => $auction->car_id, 'auction_id' => $auction->id]) }}" 
                                   class="btn btn-success btn-lg fw-bold py-3 shadow-sm w-100 text-center">
                                    <i class="fa fa-bolt me-2"></i> BUY IT NOW
                                </a>
                            </div>
                        </div>

                    @else
                        <div class="alert alert-secondary text-center fw-bold py-3 mb-0">
                            AUCTION {{ strtoupper($auction->status) }}
                        </div>
                    @endif
                </div>

                <div class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
                    <h6 class="fw-bold mb-4"><i class="fa fa-history me-2 text-primary"></i>Bid History</h6>
                    <div class="bid-history-scroll">
                        <table class="table table-hover align-middle">
                            <tbody>
                                @foreach ($bidHistory as $index => $b)
                                    <tr class="{{ $index == 0 ? 'table-primary fw-bold' : '' }}">
                                        <td>{{ $b->customer ? $b->customer->fname : 'Bidder' }}</td>
                                        <td>${{ number_format($b->bid_amount) }}</td>
                                        <td class="text-muted small text-end">
                                            {{ date("H:i", strtotime($b->bid_time)) }}
                                        </td>
                                    </tr>
                                @endforeach

                                @if ($bidHistory->count() == 0)
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted small">
                                            No bids yet. Be the first!
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    @if ($auction->status == 'Active')
    <script>
        var endTime = new Date("{{ $auction->end_time }}").getTime();

        var x = setInterval(function() {
            var now = new Date().getTime();
            var distance = endTime - now;

            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            var countdownEl = document.getElementById("countdown");
            if (countdownEl) {
                countdownEl.innerHTML = days + "d " + hours + "h " + minutes + "m " + seconds + "s ";
            }

            if (distance < 0) {
                clearInterval(x);
                if (countdownEl) {
                    countdownEl.innerHTML = "AUCTION CLOSED";
                }
            }
        }, 1000);
    </script>
    @endif
</body>
</html>