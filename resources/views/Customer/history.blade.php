<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Order History - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .order-card { border: none; border-radius: 12px; transition: transform 0.2s; }
        .table thead { background-color: #212529; color: #fff; }
        .img-thumbnail-custom { object-fit: cover; width: 70px; height: 50px; border-radius: 8px; }
        .status-badge { font-weight: 500; padding: 0.5em 1em; border-radius: 20px; text-transform: uppercase; font-size: 0.75rem; }
        .price-text { color: #2ecc71; font-weight: 700; }
        .item-title { font-size: 1rem; margin-bottom: 0; }
    </style>
</head>
<body>
    @include('customer.inc.navbar')

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Order History</h2>
                <p class="text-muted">Manage your purchases and warranty claims</p>
            </div>
            <i class="fa fa-history fa-3x text-light"></i>
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
        
        <div class="card order-card shadow-sm mb-5">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-dark">
                            <tr>
                                <th class="ps-4 py-3">ID</th>
                                <th>Item</th>
                                <th>Details</th>
                                <th>Qty</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th class="pe-4">Warranty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (count($orders) > 0)
                                @foreach ($orders as $o)
                                    @php
                                        $displayName = "Item Deleted";
                                        $displayImg = "deleted-part.png"; 
                                        $viewLink = "#";
                                        $category = "Unknown";
                                        $isDeleted = true;

                                        if ($o->product) {
                                            $displayName = $o->product->product_name;
                                            if (!empty($o->product->image)) {
                                                $displayImg = $o->product->image;
                                            }
                                            $viewLink = route('customer.product.view', $o->product_id);
                                            $category = "Parts & Acc.";
                                            $isDeleted = false;
                                        } elseif ($o->car) {
                                            $displayName = $o->car->brand . " " . $o->car->model;
                                            if (!empty($o->car->image)) {
                                                $displayImg = $o->car->image;
                                            }
                                            $viewLink = route('customer.car.view', $o->car_id);
                                            $category = "Vehicle";
                                            $isDeleted = false;
                                        }

                                        $displayDate = "N/A";
                                        if (!empty($o->date_ordered)) {
                                            $displayDate = date("M d, Y", strtotime($o->date_ordered));
                                        } elseif (!empty($o->date)) {
                                            $displayDate = date("M d, Y", strtotime($o->date));
                                        }

                                        $claimStatus = $o->warrantyClaim->status ?? null;
                                    @endphp
                                    <tr class="align-middle border-bottom">
                                        <td class="ps-4 text-muted fw-bold">#{{ $o->id }}</td>
                                        <td>
                                            <img src="{{ asset('uploads/' . $displayImg) }}" class="img-thumbnail-custom shadow-sm border" onerror="this.src='https://via.placeholder.com/70x50?text=Image'">
                                        </td>
                                        <td>
                                            <p class="item-title">
                                                @if (!$isDeleted)
                                                    <a href="{{ $viewLink }}" class="text-decoration-none text-dark fw-bold">
                                                        {{ $displayName }}
                                                    </a>
                                                @else
                                                    <span class="text-muted fw-bold">{{ $displayName }}</span>
                                                @endif
                                            </p>
                                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.65rem;">
                                                {{ $category }}
                                            </span>
                                        </td>
                                        <td><span class="badge rounded-pill bg-light text-dark border px-3">x{{ $o->quantity }}</span></td>
                                        <td class="price-text">${{ number_format($o->total_price, 2) }}</td>
                                        <td class="text-muted" style="font-size: 0.9rem;">{{ $displayDate }}</td>
                                        <td class="pe-4">
                                            @if (empty($claimStatus))
                                                @if (!$isDeleted)
                                                    @php
                                                        $claimUrl = route('customer.warranty.claim.form') . "?order_id=" . $o->id;
                                                        if (!empty($o->product_id)) {
                                                            $claimUrl .= "&product_id=" . $o->product_id;
                                                        } else {
                                                            $claimUrl .= "&car_id=" . $o->car_id;
                                                        }
                                                    @endphp
                                                    <a href="{{ $claimUrl }}" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                                        <i class="fa fa-shield"></i> Claim
                                                    </a>
                                                @else
                                                    <span class="text-muted small italic">Unavailable</span>
                                                @endif
                                            @else
                                                @php
                                                    $badgeClass = "bg-secondary";
                                                    if ($claimStatus == 'Pending') $badgeClass = "bg-info text-dark";
                                                    if ($claimStatus == 'Approved') $badgeClass = "bg-success";
                                                    if ($claimStatus == 'Rejected') $badgeClass = "bg-danger";
                                                @endphp
                                                <span class="status-badge {{ $badgeClass }} shadow-sm">
                                                    {{ $claimStatus }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fa fa-shopping-basket fa-3x text-light mb-3 d-block"></i>
                                        <p class="text-muted">You haven't placed any orders yet.</p>
                                        <a href="{{ route('customer.store') }}" class="btn btn-primary btn-sm rounded-pill">Start Shopping</a>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-4 d-flex gap-3 mb-5 justify-content-center">
            <a href="{{ route('customer.dashboard') }}" class="btn btn-light border rounded-pill px-4"><i class="fa fa-home"></i> Dashboard</a>
            <a href="{{ route('customer.showroom') }}" class="btn btn-dark rounded-pill px-4"><i class="fa fa-car"></i> View Cars</a>
            <a href="{{ route('customer.store') }}" class="btn btn-outline-dark rounded-pill px-4"><i class="fa fa-shopping-bag"></i> Parts Store</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>