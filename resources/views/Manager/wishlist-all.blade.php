<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager - Demand Analysis</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container mt-5">
        <div class="mb-4">
            <a href="#" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h3 class="mb-0 text-dark"><i class="fa fa-line-chart text-primary me-2"></i> Wishlist Analysis</h3>
                <p class="text-muted small mb-0">Sorted by Interest Count, then by Unit Price</p>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Rank</th>
                                <th>Preview</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Total Wishlists</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                $count = count($all_wishlisted);
                            @endphp
                            
                            @if ($count > 0)
                                @for ($i = 0; $i < $count; $i = $i + 1) 
                                    @php
                                        $item = (array) $all_wishlisted[$i];
                                        $img_src = asset('uploads/' . $item['image']);
                                    @endphp
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        
                                        <td>
                                            @if (!empty($item['image']))
                                                <img src="{{ $img_src }}" width="60" height="60" class="rounded border" style="object-fit: cover;">
                                            @endif
                                        </td>
                                        
                                        <td class="fw-bold">{{ $item['name'] }}</td>
                                        
                                        <td>
                                            <span class="badge {{ ($item['category'] == 'Car') ? 'bg-info' : 'bg-secondary' }}">
                                                {{ $item['category'] }}
                                            </span>
                                        </td>
                                        
                                        <td class="text-dark fw-bold">${{ number_format($item['price'], 2) }}</td>
                                        
                                        <td>
                                            <span class="badge bg-primary rounded-pill fs-6 px-3">
                                                <i class="fa fa-heart me-1"></i> {{ $item['wish_count'] }} 
                                            </span>
                                        </td>
                                    </tr>
                                @endfor
                            @else 
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        No wishlisted items found.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>