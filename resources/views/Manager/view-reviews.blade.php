<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Reviews - {{ $product->product_name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container mt-5">
        <div class="mb-4">
            <a href="{{ route('manager.inventory.view') }}" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Back to Inventory
            </a>
            <h3 class="mt-3">Reviews for: <span class="text-primary">{{ $product->product_name }}</span></h3>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-12">
                @php
                    $reviewsCount = $reviews->count();
                @endphp

                @if ($reviewsCount > 0)
                    @for ($i = 0; $i < $reviewsCount; $i = $i + 1) 
                        @php
                            $r = $reviews[$i];
                        @endphp
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <h5>{{ $r->fname }} {{ $r->lname }}</h5>
                                    <span class="text-warning">
                                        @php
                                            for ($j = 0; $j < $r->rating; $j = $j + 1) { 
                                                echo "★"; 
                                            } 
                                        @endphp
                                    </span>
                                </div>
                                <p class="text-muted small">{{ $r->date_posted }}</p>
                                <p class="border-start ps-3 italic">"{{ $r->comment }}"</p>

                                <hr>

                                @if (!empty($r->company_reply))
                                    <div id="reply-display-{{ $r->id }}" class="bg-light p-3 border-start border-primary border-4">
                                        <strong>Company Reply:</strong>
                                        <p class="mb-2">{{ $r->company_reply }}</p>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="toggleEditReply({{ $r->id }})">
                                                <i class="fa fa-pencil"></i> Edit Reply
                                            </button>
                                            <a href="{{ route('manager.product.review.reply.delete', $r->id) }}" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this company response?')">
                                                <i class="fa fa-trash"></i> Delete Response
                                            </a>
                                        </div>
                                    </div>

                                    <div id="reply-edit-{{ $r->id }}" style="display: none;" class="bg-white p-3 border rounded shadow-sm">
                                        <form action="{{ route('manager.inventory.reviews.reply') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="review_id" value="{{ $r->id }}">
                                            <input type="hidden" name="product_id" value="{{ $id }}">
                                            <input type="hidden" name="is_edit" value="1">
                                            
                                            <div class="mb-2">
                                                <label class="form-label small fw-bold">Update Response</label>
                                                <textarea name="reply" class="form-control" rows="2">{{ $r->company_reply }}</textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-sm">Update</button>
                                            <button type="button" class="btn btn-light btn-sm" onclick="toggleEditReply({{ $r->id }})">Cancel</button>
                                        </form>
                                    </div>

                                @else
                                    <form action="{{ route('manager.inventory.reviews.reply') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="review_id" value="{{ $r->id }}">
                                        <input type="hidden" name="product_id" value="{{ $id }}">
                                        
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold">Post a Reply</label>
                                            <textarea name="reply" class="form-control" rows="2" placeholder="Write response..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">Submit Reply</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endfor
                @else
                    <div class='alert alert-info'>No reviews found for this product yet.</div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function toggleEditReply(id) {
            var displayDiv = document.getElementById('reply-display-' + id);
            var editDiv = document.getElementById('reply-edit-' + id);
            
            if (editDiv.style.display === "none") {
                editDiv.style.display = "block";
                displayDiv.style.display = "none";
            } else {
                editDiv.style.display = "none";
                displayDiv.style.display = "block";
            }
        }
    </script>
</body>
</html>