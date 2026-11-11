<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Auction Sales Report - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .stats-card { background: #fff; border-radius: 12px; border-left: 5px solid #198754; }
        .table-card { background: #fff; border-radius: 15px; overflow: hidden; }
        .car-thumb { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; }
        .deleted-thumb { width: 60px; height: 40px; background: #e9ecef; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; color: #6c757d; }
    </style>
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container mt-4">
        
        <div class="row mb-4 align-items-center">
            <div class="col-md-7">
                <h2 class="fw-bold"><i class="fa fa-bar-chart me-2 text-success"></i>Auction Sales</h2>
                <p class="text-muted">History of all finalized auction transactions.</p>
            </div>
            <div class="col-md-5">
                <div class="stats-card p-3 shadow-sm d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">Total Revenue Generated</span>
                        <h3 class="fw-bold mb-0 text-success">${{ number_format($totalRevenue, 2) }}</h3>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success rounded-pill px-3">{{ $salesCount }} Sold</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Sale Date</th>
                        <th>Vehicle Details</th>
                        <th>Buyer Information</th>
                        <th class="text-end pe-4">Final Price</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($salesCount > 0)
                        @foreach ($sales as $sale)
                            @php
                                $isDeleted = empty(optional($sale->car)->model);
                            @endphp
                            <tr class="align-middle">
                                <td class="ps-4">
                                    <div class="fw-bold">{{ date("F j, Y", strtotime($sale->sale_date)) }}</div>
                                    <small class="text-muted">{{ date("g:i A", strtotime($sale->sale_date)) }}</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if ($isDeleted)
                                            <div class="deleted-thumb me-3"><i class="fa fa-trash-o"></i></div>
                                            <div>
                                                <div class="fw-bold text-danger">Vehicle Information Missing</div>
                                                <small class="text-muted">the car has been removed from the showroom | ID: #{{ $sale->car_id }}</small>
                                            </div>
                                        @else
                                            <img src="{{ asset('uploads/' . optional($sale->car)->image) }}" class="car-thumb me-3" onerror="this.src='https://via.placeholder.com/300x180?text=No+Image'">
                                            <div>
                                                <div class="fw-bold">{{ optional($sale->car)->brand . " " . optional($sale->car)->model }}</div>
                                                <small class="text-muted">Year: {{ optional($sale->car)->year }} | ID: #{{ $sale->car_id }}</small>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ optional($sale->customer)->fname . " " . optional($sale->customer)->lname }}</div>
                                    <small class="text-muted">{{ optional($sale->customer)->email_address }}</small>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="fs-5 fw-bold text-success">${{ number_format($sale->sale_price, 2) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fa fa-folder-open-o fa-3x mb-3"></i>
                                <p>No auction sales have been recorded yet.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>