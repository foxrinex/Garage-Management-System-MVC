<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Claim Warranty - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-warning text-dark fw-bold">
                        <i class="fa fa-shield"></i> Warranty Claim Form
                    </div>
                    <div class="card-body">
                        <p>Claiming for: <strong>{{ $itemName }}</strong></p>
                        <hr>

                        <form action="{{ route('customer.warranty.submit') }}" method="POST">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $orderId }}">
                            
                            <div class="mb-3">
                                <label class="form-label">Warranty Type</label>
                                <select name="claim_type" class="form-select" required>
                                    <option value="" selected disabled>-- Choose Type --</option>
                                    
                                    @if ($hasRegular == 1)
                                        <option value="Regular">Regular (Repair)</option>
                                    @endif

                                    @if ($hasReplacement == 1)
                                        <option value="Replacement">Replacement</option>
                                    @endif

                                    @if ($hasRegular == 0 && $hasReplacement == 0)
                                        <option value="" disabled>No warranty available for this item</option>
                                    @endif
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reason for Claim</label>
                                <textarea name="reason" class="form-control" rows="5" placeholder="Describe the issue..." required></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                @if ($hasRegular == 1 || $hasReplacement == 1)
                                    <button type="submit" class="btn btn-dark">Submit Claim</button>
                                @else
                                    <button type="button" class="btn btn-secondary" disabled>Claim Unavailable</button>
                                @endif
                                <a href="{{ route('customer.history') }}" class="btn btn-light border">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>