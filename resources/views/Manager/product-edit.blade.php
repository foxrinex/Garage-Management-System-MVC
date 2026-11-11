<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product | Manager Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container mt-5 mb-5" style="max-width: 800px;">
        <form action="{{ route('manager.product.update', $product->id) }}" 
              method="POST" 
              enctype="multipart/form-data" 
              class="shadow p-4 bg-white rounded border-top border-primary border-4">
            @csrf
            
            <h4 class="text-center fw-bold">Edit Product Information</h4>
            <hr>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <input type="hidden" name="id" value="{{ $product->id }}">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Product Name</label>
                        <input type="text" name="product_name" class="form-control" value="{{ old('product_name', $product->product_name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="0">-- Uncategorized --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $cat->id == $product->category_id ? 'selected' : '' }}>
                                    {{ $cat->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Price ($)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $product->price) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Horsepower (hp)</label>
                        <input type="number" 
                            name="hp" 
                            class="form-control" 
                            value="{{ old('hp', $product->hp) }}" 
                            min="0" 
                            step="1" 
                            onkeypress="return event.charCode >= 48 && event.charCode <= 57" 
                            placeholder="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Description</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Compatibility Tags</label>
                        <div class="border p-2 rounded bg-light" style="max-height: 150px; overflow-y: auto;">
                            @foreach ($allTags as $i => $tag)
                                @php
                                    $tName = $tag->tag_name;
                                    $isChecked = in_array($tName, array_map('trim', $currentTagsArray)) ? 'checked' : '';
                                @endphp
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="tags[]" 
                                           value="{{ $tName }}" 
                                           id="tag_{{ $i }}"
                                           {{ $isChecked }}>
                                    <label class="form-check-label small" for="tag_{{ $i }}">
                                        {{ $tName }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block fw-bold small">Warranty Types</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="regular_warranty" value="1" id="regW" 
                                   {{ $product->regular_warranty == 1 ? 'checked' : '' }}>
                            <label class="form-check-label small" for="regW">Regular</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="replacement_warranty" value="1" id="repW" 
                                   {{ $product->replacement_warranty == 1 ? 'checked' : '' }}>
                            <label class="form-check-label small" for="repW">Replacement</label>
                        </div>
                    </div>

                    <div class="mb-3 border p-3 rounded bg-light">
                        <label class="form-label fw-bold small">Product Image</label>
                        <input type="file" name="product_image" id="product_image_input" class="form-control form-control-sm mb-2">
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="remove_image" id="removeImg" value="1" onchange="toggleImageInput(this)">
                            <label class="form-check-label text-danger small" for="removeImg">
                                Remove current image
                            </label>
                        </div>

                        <div class="text-center">
                            @if (!empty($product->image))
                                <img src="{{ asset('uploads/' . $product->image) }}" width="100" class="img-thumbnail" onerror="this.src='https://via.placeholder.com/100'">
                            @else
                                <span class="badge bg-secondary">No Product Image</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-md-6 mb-2 mb-md-0">
                    <a href="{{ route('manager.inventory.view') }}" class="btn btn-secondary w-100">Cancel</a>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary w-100">Update Product</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function toggleImageInput(checkbox) {
            var fileInput = document.getElementById('product_image_input');
            if (checkbox.checked) {
                fileInput.value = ""; 
                fileInput.disabled = true;
                fileInput.style.opacity = "0.5";
            } else {
                fileInput.disabled = false;
                fileInput.style.opacity = "1";
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>