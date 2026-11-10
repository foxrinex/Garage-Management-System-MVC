<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction History - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .auc-card { border: none; border-radius: 12px; }
        .table thead { background-color: #212529; color: #fff; }
        .img-auc { object-fit: cover; width: 80px; height: 55px; border-radius: 8px; }
        .status-pill { font-size: 0.75rem; padding: 0.4em 1em; border-radius: 20px; font-weight: 600; text-transform: uppercase; }
        .price-tag { color: #2ecc71; font-weight: 700; }
    </style>
</head>
<body>
    @include('customer.inc.navbar')

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Auction History</h2>
                <p class="text-muted">Track your bids, wins, and active competitions</p>
            </div>
            <i class="fa fa-gavel fa-3x text-light"></i>
        </div>

        @if ($myAuctions->count() > 0)
            <div class="card auc-card shadow-sm overflow-hidden mb-5">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3">Vehicle</th>
                                <th>End Date</th>
                                <th>My Highest Bid</th>
                                <th>Current/Final</th>
                                <th class="pe-4">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($myAuctions as $row)
                                @php
                                    $aid = $row['id'];
                                    $myMax = $row['my_max'];
                                    $statusText = "Pending";
                                    $bgClass = "bg-secondary text-white";

                                    if ($row['status'] == 'Active') {
                                        $statusText = "In Progress";
                                        $bgClass = "bg-primary text-white";
                                    } else if ($row['status'] == 'Failed') {
                                        $statusText = "Cancelled";
                                        $bgClass = "bg-danger text-white";
                                    } else if ($row['status'] == 'Closed') {
                                        if ($row['highest_bidder_id'] == session('user_id')) {
                                            $statusText = "WINNER";
                                            $bgClass = "bg-success text-white";
                                        } else {
                                            $statusText = "Outbid";
                                            $bgClass = "bg-dark text-white";
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('uploads/' . $row['image']) }}" class="img-auc border shadow-sm me-3">
                                            <div>
                                                <a href="{{ route('customer.auction.details', $aid) }}" class="text-decoration-none text-dark fw-bold">
                                                    {{ $row['brand'] }} {{ $row['model'] }}
                                                </a>
                                                <div class="text-muted small">{{ $row['year'] }} Model</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fa fa-clock-o"></i> {{ date("M d, Y H:i", strtotime($row['end_time'])) }}
                                        </small>
                                    </td>
                                    <td><strong class="text-dark">${{ number_format($myMax) }}</strong></td>
                                    <td class="price-tag">${{ number_format($row['current_bid']) }}</td>
                                    <td class="pe-4">
                                        <span class="status-pill {{ $bgClass }} shadow-sm">
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="text-center bg-white p-5 shadow-sm rounded-3">
                <i class="fa fa-gavel fa-4x text-light mb-3"></i>
                <h5 class="fw-bold">No auction activity</h5>
                <p class="text-muted">You haven't placed any bids on vehicles yet.</p>
                <a href="{{ route('customer.showroom') }}" class="btn btn-dark rounded-pill px-4">Visit Showroom</a>
            </div>
        @endif

        <div class="mb-5 text-center">
             <a href="{{ route('customer.dashboard') }}" class="btn btn-light border rounded-pill px-4">Back to Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>