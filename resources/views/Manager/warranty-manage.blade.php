<!DOCTYPE html>
<html>
<head>
    <title>Manage Warranties - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    @include('manager.inc.navbar')
    <div class="container mt-5">
        <h3 class="mb-4">Warranty Claims Management</h3>
        
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <h4 class="mt-5 text-primary"><i class="fa fa-gears"></i> Product Warranties</h4>
        <div class="table-responsive shadow-sm bg-white p-3 rounded mb-5">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $prodCount = count($productClaims);
                    @endphp
                    @if ($prodCount > 0)
                        @for ($i = 0; $i < $prodCount; $i = $i + 1) 
                            @php
                                $c = $productClaims[$i];
                            @endphp
                            <tr class="align-middle">
                                <td>#{{ $c->id }}</td>
                                <td>{{ $c->fname }} {{ $c->lname }}</td>
                                <td>{{ $c->product_name }}</td>
                                <td><span class="badge bg-secondary">{{ $c->claim_type }}</span></td>
                                <td><small>{{ $c->reason }}</small></td>
                                <td>
                                    @php
                                        $badge = "bg-info text-dark";
                                        if ($c->status == 'Approved') $badge = "bg-success";
                                        if ($c->status == 'Rejected') $badge = "bg-danger";
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $c->status }}</span>
                                </td>
                                <td>
                                    @if ($c->status == 'Pending')
                                        <a href="{{ route('manager.warranties.status', ['id' => $c->id, 'status' => 'Approved']) }}" class="btn btn-sm btn-success">Approve</a>
                                        <a href="{{ route('manager.warranties.status', ['id' => $c->id, 'status' => 'Rejected']) }}" class="btn btn-sm btn-danger">Reject</a>
                                    @else
                                        <span class="text-muted small">Processed</span>
                                    @endif
                                </td>
                            </tr>
                        @endfor
                    @else
                        <tr><td colspan="7" class="text-center py-3">No product claims found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>

        <h4 class="mt-5 text-info"><i class="fa fa-car"></i> Car Warranties</h4>
        <div class="table-responsive shadow-sm bg-white p-3 rounded mb-5">
            <table class="table table-hover">
                <thead class="table-info">
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Car Model</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $carCount = count($carClaims);
                    @endphp
                    @if ($carCount > 0)
                        @for ($j = 0; $j < $carCount; $j = $j + 1) 
                            @php
                                $cc = $carClaims[$j];
                            @endphp
                            <tr class="align-middle">
                                <td>#{{ $cc->id }}</td>
                                <td>{{ $cc->fname }} {{ $cc->lname }}</td>
                                <td>{{ $cc->brand }} {{ $cc->car_name }}</td>
                                <td><span class="badge bg-secondary">{{ $cc->claim_type }}</span></td>
                                <td><small>{{ $cc->reason }}</small></td>
                                <td>
                                    @php
                                        $badgeC = "bg-info text-dark";
                                        if ($cc->status == 'Approved') $badgeC = "bg-success";
                                        if ($cc->status == 'Rejected') $badgeC = "bg-danger";
                                    @endphp
                                    <span class="badge {{ $badgeC }}">{{ $cc->status }}</span>
                                </td>
                                <td>
                                    @if ($cc->status == 'Pending')
                                        <a href="{{ route('manager.warranties.status', ['id' => $cc->id, 'status' => 'Approved']) }}" class="btn btn-sm btn-success">Approve</a>
                                        <a href="{{ route('manager.warranties.status', ['id' => $cc->id, 'status' => 'Rejected']) }}" class="btn btn-sm btn-danger">Reject</a>
                                    @else
                                        <span class="text-muted small">Processed</span>
                                    @endif
                                </td>
                            </tr>
                        @endfor
                    @else
                        <tr><td colspan="7" class="text-center py-3">No car claims found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>