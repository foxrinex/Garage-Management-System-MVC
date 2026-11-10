<?php
namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\AuctionSale;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Product;
use Illuminate\Support\Facades\File;
use App\Models\Setting;
use App\Models\Review;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use App\Models\Message;
use App\Models\Cart;   
use App\Models\Manager;
use App\Models\CarRepair;
use App\Models\RepairPartsSelected;
use App\Models\WarrantyClaim;



class ManagerController extends Controller
{
    public function storeAuction(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if user is logged in and is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        $startPrice = $request->input('start_price');
        $reservePrice = $request->input('reserve_price');
        $minIncrement = $request->input('min_increment');
        $endTime = $request->input('end_time');

        // 2. Validation
        if ($reservePrice < $startPrice) {
            return redirect()->route('auction.add.form')->with('error', 'Reserve price cannot be lower than the starting bid');
        }

        if (empty($minIncrement) || $minIncrement <= 0) {
            return redirect()->route('auction.add.form')->with('error', 'Minimum increment must be greater than 0');
        }

        // 3. Time Validation
        $currentTime = Carbon::now();
        $selectedTime = Carbon::parse($endTime);

        if ($selectedTime <= $currentTime) {
            return redirect()->route('auction.add.form')->with('error', 'Auction end time must be in the future');
        }

        try {
            return DB::transaction(function () use ($request, $startPrice, $reservePrice, $minIncrement, $selectedTime) {
                $carId = 0;

                // 4. Handle Vehicle Logic (Unified Flow)
                if ($request->filled('brand')) {
                    // Manual entry: New Registration
                    if (!$request->hasFile('car_image') || $request->file('car_image')->getError() !== 0) {
                        return redirect()->route('auction.add.form')->with('error', 'Vehicle image is required for new registration');
                    }

                    $file = $request->file('car_image');
                    $imgExLc = strtolower($file->getClientOriginalExtension());
                    $allowedExs = ["jpg", "jpeg", "png"];

                    if (!in_array($imgExLc, $allowedExs)) {
                        return redirect()->route('auction.add.form')->with('error', 'Invalid image format');
                    }

                    $newImgName = "AUC-" . uniqid() . "." . $imgExLc;
                    $file->move(public_path('uploads'), $newImgName);

                    $regW = $request->has('regular_warranty') ? 1 : 0;
                    $repW = $request->has('replacement_warranty') ? 1 : 0;

                    // Step 1: Add to inventory with 0 quantity (since it goes straight to auction)
                    $car = Car::create([
                        'brand' => $request->input('brand'),
                        'model' => $request->input('model'),
                        'year' => $request->input('year'),
                        'category_id' => $request->input('category_id'),
                        'price' => $reservePrice,
                        'quantity' => 0,
                        'status' => 'In Auction',
                        'transmission' => $request->input('transmission'),
                        'mileage' => $request->input('mileage'),
                        'description' => $request->input('description'),
                        'regular_warranty' => $regW,
                        'replacement_warranty' => $repW,
                        'image' => $newImgName,
                        'in_auction' => 1,
                    ]);

                    $carId = $car->id;

                } elseif ($request->filled('car_id')) {
                    // Handle selection from existing Inventory with row locking
                    $carId = $request->input('car_id');
                    $car = Car::where('id', $carId)->lockForUpdate()->first();

                    if ($car && $car->quantity > 0) {
                        $newQty = $car->quantity - 1;
                        $newStatus = ($newQty <= 0) ? 'Out of Stock' : 'In Stock';

                        $car->update([
                            'quantity' => $newQty,
                            'status' => $newStatus,
                            'in_auction' => 1,
                        ]);
                    } else {
                        return redirect()->route('auction.add.form')->with('error', 'Car is out of stock');
                    }
                } else {
                    return redirect()->route('auction.add.form')->with('error', 'Please provide car details or select from inventory');
                }

                // 5. Create the Auction
                if ($carId > 0) {
                    $auction = Auction::create([
                        'car_id' => $carId,
                        'start_price' => $startPrice,
                        'current_bid' => $startPrice,
                        'reserve_price' => $reservePrice,
                        'min_increment' => $minIncrement,
                        'end_time' => $selectedTime,
                        'status' => 'Active',
                    ]);

                    if ($auction) {
                        return redirect()->route('auction.dashboard')->with('success', 'Auction successfully launched!');
                    } else {
                        return redirect()->route('auction.add.form')->with('error', 'Failed to initialize auction record');
                    }
                }

                return redirect()->route('auction.add.form')->with('error', 'Invalid car reference');
            });

        } catch (\Exception $e) {
            return redirect()->route('auction.add.form')->with('error', 'An error occurred while launching the auction.');
        }
    }

    public function confirmAuctionPayment(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if user is logged in and is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($id) {
                // --- 1. Get Auction details and related Car using row locking ---
                $auction = Auction::where('id', $id)->lockForUpdate()->first();

                if (!$auction) {
                    return redirect()->route('auction.pending.payment')->with('error', 'Data not found.');
                }

                $car = Car::where('id', $auction->car_id)->lockForUpdate()->first();

                if (!$car) {
                    return redirect()->route('auction.pending.payment')->with('error', 'Associated car not found.');
                }

                $carId = $auction->car_id;
                $qty = $car->quantity;
                $price = $auction->current_bid;
                $custId = $auction->highest_bidder_id;

                // --- 2. Mark Auction as Paid and Closed ---
                $auction->update([
                    'winner_paid' => 1,
                    'status' => 'Closed',
                ]);

                // --- 3. Finalize Car Status ---
                $newStatus = ($qty <= 0) ? 'Out of Stock' : 'In Stock';
                $car->update([
                    'status' => $newStatus,
                ]);

                // --- 4. Record the Revenue in auction_sales table ---
                $sale = AuctionSale::create([
                    'auction_id' => $id,
                    'car_id' => $carId,
                    'customer_id' => $custId,
                    'sale_price' => $price,
                ]);

                if ($sale) {
                    return redirect()->route('auction.pending.payment')->with('success', 'Payment confirmed and recorded in Auction Sales.');
                } else {
                    return redirect()->route('auction.pending.payment')->with('error', 'Failed to record sale data.');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('auction.pending.payment')->with('error', 'An error occurred while confirming the auction payment.');
        }
    }

    public function deleteAuction(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if user is logged in and is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($id) {
                // 1. Fetch auction details using row-level locking
                $auction = Auction::where('id', $id)->lockForUpdate()->first();

                if (!$auction) {
                    return redirect()->route('auction.dashboard')->with('error', 'Not found');
                }

                $carId = $auction->car_id;

                // 2. Restore the car to the showroom (increment quantity, set status to In Stock, in_auction to 0)
                $car = Car::where('id', $carId)->lockForUpdate()->first();

                if ($car) {
                    $car->update([
                        'quantity' => $car->quantity + 1,
                        'status' => 'In Stock',
                        'in_auction' => 0,
                    ]);
                }

                // 3. Delete the Auction record
                $deleted = $auction->delete();

                // 4. Redirect immediately
                if ($deleted) {
                    return redirect()->route('auction.dashboard')->with('success', 'Auction removed');
                } else {
                    return redirect()->route('auction.dashboard')->with('error', 'Delete failed');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('auction.dashboard')->with('error', 'An error occurred while deleting the auction.');
        }
    }

    public function endAuction(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if user is logged in and is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($id) {
                // 1. Fetch auction details using row-level locking
                $auction = Auction::where('id', $id)->lockForUpdate()->first();

                if (!$auction) {
                    return redirect()->route('auction.dashboard')->with('error', 'Auction not found.');
                }

                $carId = $auction->car_id;
                $winnerId = $auction->highest_bidder_id;

                // 2. Scenario Check: Did anyone bid?
                if (is_null($winnerId)) {
                    // Return the vehicle to showroom stock using row-level locking
                    $car = Car::where('id', $carId)->lockForUpdate()->first();

                    if ($car) {
                        $car->update([
                            'quantity' => $car->quantity + 1,
                            'status' => 'In Stock',
                            'in_auction' => 0,
                        ]);
                    }

                    // Update auction status to Failed
                    $auction->update([
                        'status' => 'Failed',
                    ]);

                    return redirect()->route('auction.dashboard')->with('info', 'Auction failed. No bidders found.');
                } else {
                    // SCENARIO: Winner exists
                    // Set status to Closed. winner_paid remains 0 until they pay.
                    $updated = $auction->update([
                        'status' => 'Closed',
                    ]);

                    if ($updated) {
                        return redirect()->route('auction.dashboard')->with('success', 'Auction settled. Winner is waiting for payment.');
                    } else {
                        return redirect()->route('auction.dashboard')->with('error', 'Failed to settle auction.');
                    }
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('auction.dashboard')->with('error', 'An error occurred while ending the auction.');
        }
    }

    public function rejectAuctionPayment(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if user is logged in and is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($id) {
                // --- 1. Find the auction and related car using row locking ---
                $auction = Auction::where('id', $id)->lockForUpdate()->first();

                if (!$auction) {
                    return redirect()->route('auction.pending.payment')->with('error', 'Auction record not found.');
                }

                $car = Car::where('id', $auction->car_id)->lockForUpdate()->first();

                if (!$car) {
                    return redirect()->route('auction.pending.payment')->with('error', 'Associated car not found.');
                }

                // --- 2. Update auction status to 'failed' (or 'Failed' matching your convention) ---
                $auction->update([
                    'status' => 'failed',
                ]);

                // --- 3. Increase Quantity and Update Status ---
                $updatedCar = $car->update([
                    'quantity' => $car->quantity + 1,
                    'status' => 'In Stock',
                    'in_auction' => 0,
                ]);

                if ($updatedCar) {
                    return redirect()->route('auction.pending.payment')->with('success', 'Payment rejected. Quantity increased and car is In Stock.');
                } else {
                    return redirect()->route('auction.pending.payment')->with('error', 'Failed to update car inventory.');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('auction.pending.payment')->with('error', 'An error occurred while rejecting the auction payment.');
        }
    }

    public function handleBuilderSettingsPost(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        $type = $request->input('type');

        // 1. Add New Category
        if ($type === 'add_category') {
            $name = $request->input('category_name');
            $isMandatory = $request->has('is_mandatory') ? 1 : 0;

            // Check for duplicate category name
            $existingCategory = Category::where('category_name', $name)->where('type', 'Part')->first();
            if ($existingCategory) {
                return redirect()->route('inventory.index')->with('error', 'Category name already exists!');
            }

            if (empty($name)) {
                return redirect()->route('inventory.index')->with('error', 'Category name is required.');
            }

            if ($request->hasFile('category_img')) {
                $file = $request->file('category_img');
                if ($file->isValid()) {
                    $imgExLc = strtolower($file->getClientOriginalExtension());
                    $newImgName = "CAT-" . uniqid('', true) . '.' . $imgExLc;
                    $file->move(public_path('uploads'), $newImgName);

                    Category::create([
                        'category_name' => $name,
                        'type' => 'Part',
                        'is_mandatory' => $isMandatory,
                        'category_img' => $newImgName,
                    ]);

                    return redirect()->route('inventory.index')->with('success', 'Category created!');
                } else {
                    return redirect()->route('inventory.index')->with('error', 'Failed to move uploaded file.');
                }
            } else {
                return redirect()->route('inventory.index')->with('error', 'Default category image is required.');
            }
        }

        // 2. Edit/Rename Category Name
        elseif ($type === 'edit_category') {
            $cId = $request->input('category_id');
            $newName = $request->input('new_name');

            if (empty($newName)) {
                return redirect()->route('inventory.index')->with('error', 'New name cannot be empty.');
            }

            $duplicate = Category::where('category_name', $newName)->where('id', '!=', $cId)->where('type', 'Part')->first();
            if ($duplicate) {
                return redirect()->route('inventory.index')->with('error', 'This category name is already in use.');
            }

            $category = Category::find($cId);
            if ($category) {
                $category->update(['category_name' => $newName]);
                return redirect()->route('inventory.index')->with('success', 'Category updated!');
            }

            return redirect()->route('inventory.index');
        }

        // 2.5 Edit Category Image
        elseif ($type === 'edit_category_img') {
            $cId = $request->input('category_id');

            if ($request->hasFile('category_img')) {
                $file = $request->file('category_img');
                if ($file->isValid()) {
                    $imgExLc = strtolower($file->getClientOriginalExtension());
                    $newImgName = "CAT-" . uniqid('', true) . '.' . $imgExLc;
                    $file->move(public_path('uploads'), $newImgName);

                    $category = Category::find($cId);
                    if ($category) {
                        // Delete old image if exists and not default
                        $oldPath = public_path('uploads/' . $category->category_img);
                        if ($category->category_img && File::exists($oldPath) && strpos($category->category_img, 'CAT-') !== false) {
                            File::delete($oldPath);
                        }

                        $category->update(['category_img' => $newImgName]);
                        return redirect()->route('inventory.index')->with('success', 'Category image updated!');
                    }
                }
            }

            return redirect()->route('inventory.index')->with('error', 'Please select a valid image.');
        }

        // 3. Add Compatibility Tag
        elseif ($type === 'add_tag') {
            $tagName = $request->input('tag_name');

            if (empty($tagName)) {
                return redirect()->route('inventory.index')->with('error', 'Tag name is required.');
            }

            try {
                Tag::create(['tag_name' => $tagName]);
                return redirect()->route('inventory.index')->with('success', 'Tag added!');
            } catch (\Exception $e) {
                return redirect()->route('inventory.index')->with('error', 'Tag already exists or error occurred.');
            }
        }

        // 4. Edit Existing Tag
        elseif ($type === 'edit_tag') {
            $tagId = $request->input('tag_id');
            $newTagName = $request->input('new_tag_name');

            if (empty($newTagName)) {
                return redirect()->route('inventory.index')->with('error', 'Tag name cannot be empty.');
            }

            try {
                $tag = Tag::find($tagId);
                if ($tag) {
                    $tag->update(['tag_name' => $newTagName]);
                    return redirect()->route('inventory.index')->with('success', 'Tag updated!');
                }
            } catch (\Exception $e) {
                return redirect()->route('inventory.index')->with('error', 'Tag name already exists or error occurred.');
            }
        }

        return redirect()->route('inventory.index');
    }

    public function toggleMandatoryCategory(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        $current = $request->input('current');
        $newStatus = ($current == 1) ? 0 : 1;

        $category = Category::find($id);
        if ($category) {
            $category->update(['is_mandatory' => $newStatus]);
        }

        return redirect()->route('inventory.index')->with('success', 'Category requirement updated!');
    }

    public function deleteCategory($id)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($id) {
                Product::where('category_id', $id)->update(['category_id' => 0]);
                Category::where('id', $id)->delete();

                return redirect()->route('inventory.index')->with('success', 'Category deleted!');
            });
        } catch (\Exception $e) {
            return redirect()->route('inventory.index')->with('error', 'Failed to delete category.');
        }
    }

    public function deleteTag($id)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        Tag::where('id', $id)->delete();

        return redirect()->route('inventory.index')->with('success', 'Tag removed!');
    }

    public function storeCar(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if user is logged in and is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        // 2. Validation
        $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'price' => 'required|numeric|between:1,999999999',
            'mileage' => 'required|numeric|between:1,999999999',
            'car_image' => 'required|image|mimes:jpg,jpeg,png',
        ], [
            'brand.required' => 'Brand, Model, and Price are required',
            'model.required' => 'Brand, Model, and Price are required',
            'price.required' => 'Brand, Model, and Price are required',
            'price.between' => 'Price must be between 1 and 999,999,999',
            'mileage.between' => 'Mileage must be between 1 and 999,999,999',
            'car_image.required' => 'Image is required.',
            'car_image.image' => 'Incorrect file type. Use JPG, JPEG, or PNG.',
        ]);

        $brand = $request->input('brand');
        $model = $request->input('model');
        $year = $request->input('year');
        $categoryId = $request->input('category_id');
        $transmission = $request->input('transmission');
        $mileage = $request->input('mileage');
        $price = $request->input('price');
        $quantity = $request->input('quantity');
        $status = $request->input('status');
        $description = $request->input('description');

        // Checkbox logic for warranty
        $regW = $request->has('regular_warranty') ? 1 : 0;
        $repW = $request->has('replacement_warranty') ? 1 : 0;

        // 3. Image Processing
        if ($request->hasFile('car_image')) {
            $file = $request->file('car_image');

            if ($file->isValid()) {
                $imgExLc = strtolower($file->getClientOriginalExtension());
                $allowedExs = ["jpg", "jpeg", "png"];

                if (in_array($imgExLc, $allowedExs)) {
                    $newImgName = "CAR-" . uniqid('', true) . "." . $imgExLc;
                    
                    // Move file to public/uploads folder
                    if ($file->move(public_path('uploads'), $newImgName)) {
                        
                        // 4. Database Insert using Eloquent
                        $car = Car::create([
                            'brand' => $brand,
                            'model' => $model,
                            'year' => $year,
                            'category_id' => $categoryId,
                            'price' => $price,
                            'quantity' => $quantity,
                            'status' => $status,
                            'transmission' => $transmission,
                            'mileage' => $mileage,
                            'description' => $description,
                            'regular_warranty' => $regW,
                            'replacement_warranty' => $repW,
                            'image' => $newImgName,
                        ]);

                        if ($car) {
                            return redirect()->route('car.add.form')->with('success', 'New vehicle added successfully!');
                        } else {
                            return redirect()->route('car.add.form')->with('error', 'An error occurred during database saving.');
                        }
                    } else {
                        return redirect()->route('car.add.form')->with('error', 'Failed to move file to root/uploads folder. Check permissions.');
                    }
                } else {
                    return redirect()->route('car.add.form')->with('error', 'Incorrect file type. Use JPG, JPEG, or PNG.');
                }
            } else {
                return redirect()->route('car.add.form')->with('error', 'Image upload error.');
            }
        } else {
            return redirect()->route('car.add.form')->with('error', 'Image is required.');
        }
    }

    public function deleteCar(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if user is logged in and is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($id) {
                // 1. Find the car record using row-level locking
                $car = Car::where('id', $id)->lockForUpdate()->first();

                if (!$car) {
                    return redirect()->route('showroom.manage');
                }

                $imageName = $car->image;
                $path = public_path('uploads/' . $imageName);

                // 2. Delete the physical file from the folder if it exists
                if ($imageName && File::exists($path) && strpos($imageName, 'CAR-') !== false) {
                    File::delete($path);
                }

                // 3. Delete from Database
                $deleted = $car->delete();

                if ($deleted) {
                    $sm = "Vehicle and associated image deleted successfully!";
                    return redirect()->route('showroom.manage')->with('success', $sm);
                } else {
                    $em = "Error occurred while deleting from database.";
                    return redirect()->route('showroom.manage')->with('error', $em);
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('showroom.manage')->with('error', 'An error occurred while deleting the vehicle.');
        }
    }

    public function updateCar(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if user is logged in and is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($request, $id) {
                // --- 1. AUCTION CHECK (Security/Logic Gate) with locking ---
                $activeAuction = Auction::where('car_id', $id)->where('status', 'Active')->lockForUpdate()->first();

                if ($activeAuction) {
                    return redirect()->route('car.edit.form', ['id' => $id])->with('error', 'This car is currently in an active auction and cannot be edited.');
                }

                $car = Car::where('id', $id)->lockForUpdate()->first();

                if (!$car) {
                    return redirect()->route('showroom.manage');
                }

                $model = $request->input('model');
                $brand = $request->input('brand');
                $categoryId = $request->input('category_id');
                $transmission = $request->input('transmission');
                $price = $request->input('price');
                $year = $request->input('year');
                $quantity = $request->input('quantity');
                $mileage = $request->input('mileage');
                $status = $request->input('status');
                $description = $request->input('description');
                $oldImage = $request->input('old_image');

                // --- 2. FETCH CURRENT YEAR FOR VALIDATION ---
                $setting = Setting::first();
                $currYear = $setting ? $setting->current_year : date('Y');

                // --- 3. VALIDATION LOGIC ---
                $maxVal = 1000000000;

                if ($year < 1900 || $year > $currYear) {
                    return redirect()->route('car.edit.form', ['id' => $id])->with('error', "Year must be between 1900 and $currYear");
                }

                if ($price <= 0 || $price >= $maxVal) {
                    return redirect()->route('car.edit.form', ['id' => $id])->with('error', 'Price must be between 1 and 999,999,999');
                }

                if ($mileage <= 0 || $mileage >= $maxVal) {
                    return redirect()->route('car.edit.form', ['id' => $id])->with('error', 'Mileage must be between 1 and 999,999,999');
                }

                // --- 4. HANDLE WARRANTY CHECKBOXES ---
                $regW = $request->has('regular_warranty') ? 1 : 0;
                $repW = $request->has('replacement_warranty') ? 1 : 0;

                $updateData = [
                    'brand' => $brand,
                    'model' => $model,
                    'year' => $year,
                    'category_id' => $categoryId,
                    'price' => $price,
                    'quantity' => $quantity,
                    'status' => $status,
                    'transmission' => $transmission,
                    'mileage' => $mileage,
                    'description' => $description,
                    'regular_warranty' => $regW,
                    'replacement_warranty' => $repW,
                ];

                // --- 5. IMAGE HANDLING LOGIC ---
                if ($request->hasFile('car_image') && $request->file('car_image')->isValid()) {
                    $file = $request->file('car_image');
                    $imgExLc = strtolower($file->getClientOriginalExtension());
                    $allowedExs = ["jpg", "jpeg", "png"];

                    if (in_array($imgExLc, $allowedExs)) {
                        $newImgName = "CAR-" . uniqid('', true) . "." . $imgExLc;
                        
                        // Delete old image file if it exists
                        $oldImagePath = public_path('uploads/' . $oldImage);
                        if ($oldImage && File::exists($oldImagePath) && strpos($oldImage, 'CAR-') !== false) {
                            File::delete($oldImagePath);
                        }

                        $file->move(public_path('uploads'), $newImgName);
                        $updateData['image'] = $newImgName;
                    } else {
                        return redirect()->route('car.edit.form', ['id' => $id])->with('error', 'Invalid image format');
                    }
                }

                // --- 6. DATABASE UPDATE ---
                $updated = $car->update($updateData);

                if ($updated) {
                    return redirect()->route('showroom.manage')->with('success', 'Vehicle updated successfully');
                } else {
                    return redirect()->route('car.edit.form', ['id' => $id])->with('error', 'Unknown error occurred');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('car.edit.form', ['id' => $id])->with('error', 'An error occurred while updating the vehicle.');
        }
    }

    public function replyToCarReview(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if the user is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        if (!$request->has('review_id') || !$request->has('reply_text') || !$request->has('car_id')) {
            return redirect()->route('showroom.manage');
        }

        $reviewId = $request->input('review_id');
        $replyText = $request->input('reply_text');
        $carId = $request->input('car_id');

        // Validation: Ensure reply isn't empty
        if (empty($replyText)) {
            return redirect()->route('car.view', ['id' => $carId])->with('error', 'Reply cannot be empty');
        }

        // 2. Find and update the review with the company reply
        $review = Review::find($reviewId);

        if ($review) {
            $updated = $review->update([
                'company_reply' => $replyText,
            ]);

            if ($updated) {
                // SUCCESS: Redirect back to the manager's car view with a success message
                return redirect()->route('car.view', ['id' => $carId])->with('success', 'Reply posted successfully!');
            } else {
                // FAILURE: Redirect back with error
                return redirect()->route('car.view', ['id' => $carId])->with('error', 'Database error occurred');
            }
        } else {
            return redirect()->route('car.view', ['id' => $carId])->with('error', 'Review not found');
        }
    }

    public function storeCategory(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if the user is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        if (!$request->has('category_name')) {
            return redirect()->route('inventory.index');
        }

        $name = $request->input('category_name');
        $type = $request->input('type');

        // Determine back_to redirect route or fallback to inventory index
        $backRoute = 'inventory.index';
        if ($request->filled('back_to')) {
            // You can map your legacy relative paths or named routes as needed
            $backRoute = $request->input('back_to');
        }

        if (empty($name)) {
            return redirect()->route($backRoute)->with('error', 'Category name is required');
        }

        // Check if the category name already exists
        $existingCategory = Category::where('category_name', $name)->first();

        if ($existingCategory) {
            return redirect()->route($backRoute)->with('error', "The category '$name' already exists!");
        }

        // Proceed with the insertion
        $category = Category::create([
            'category_name' => $name,
            'type' => $type,
        ]);

        if ($category) {
            return redirect()->route($backRoute)->with('success', 'New category created!');
        } else {
            return redirect()->route($backRoute)->with('error', 'Unknown error occurred');
        }
    }

    public function registerEmployee(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        $uname = strtolower($request->input('username'));
        $fname = $request->input('fname');
        $lname = $request->input('lname');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $sal = $request->input('salary');

        // Constraint: Salary cannot be below 0
        if ($sal < 0) {
            return redirect()->route('employees.index')->with('error', 'Salary cannot be a negative value');
        }

        try {
            return DB::transaction(function () use ($uname, $fname, $lname, $email, $phone, $sal) {
                // Check if username already exists with row locking
                $existingUser = User::where('username', $uname)->lockForUpdate()->first();

                if ($existingUser) {
                    return redirect()->route('employees.index')->with('error', 'Username already exists');
                }

                // Set the starting password as the literal string 'default'
                $pass = Hash::make('default');

                // Insert into users table
                $user = User::create([
                    'username' => $uname,
                    'password' => $pass,
                    'user_type' => 'employee',
                ]);

                // Insert into employees table
                Employee::create([
                    'user_id' => $user->uid, // Adjust to $user->id if your PK is 'id'
                    'fname' => $fname,
                    'lname' => $lname,
                    'email_address' => $email,
                    'phone_number' => $phone,
                    'salary' => $sal,
                ]);

                return redirect()->route('employees.index')->with('success', 'Registered successfully');
            });
        } catch (\Exception $e) {
            return redirect()->route('employees.index')->with('error', 'An error occurred during registration.');
        }
    }

    public function updateEmployeeSalary(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        $newSal = $request->input('new_salary');
        $empId = $request->input('emp_id');

        if ($newSal < 0) {
            return redirect()->route('employees.index')->with('error', 'Update failed: Salary cannot be below 0');
        }

        $employee = Employee::where('employee_id', $empId)->first();
        if ($employee) {
            $employee->update(['salary' => $newSal]);
            return redirect()->route('employees.index')->with('success', 'Salary updated');
        }

        return redirect()->route('employees.index')->with('error', 'Employee not found');
    }

    public function updateEmployeeInfo(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        $fname = $request->input('fname');
        $lname = $request->input('lname');
        $email = $request->input('email');
        $empId = $request->input('emp_id');

        $employee = Employee::where('employee_id', $empId)->first();
        if ($employee) {
            $employee->update([
                'fname' => $fname,
                'lname' => $lname,
                'email_address' => $email,
            ]);
            return redirect()->route('employees.index')->with('success', 'Info updated');
        }

        return redirect()->route('employees.index')->with('error', 'Employee not found');
    }

    public function deleteEmployee($deleteId)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($deleteId) {
                // Delete user record (cascades or deletes associated employee record based on DB setup)
                $user = User::where('uid', $deleteId)->lockForUpdate()->first();
                if ($user) {
                    $user->delete();
                    return redirect()->route('employees.index')->with('success', 'Employee deleted');
                }

                return redirect()->route('employees.index')->with('error', 'Employee user not found');
            });
        } catch (\Exception $e) {
            return redirect()->route('employees.index')->with('error', 'An error occurred while deleting the employee.');
        }
    }

    public function deleteMessage(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if the user is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        if (!$request->has('message_id')) {
            return redirect()->route('messages.index');
        }

        $messageId = $request->input('message_id');

        try {
            return DB::transaction(function () use ($messageId) {
                // Find and delete the message record with row locking
                $message = Message::where('message_id', $messageId)->lockForUpdate()->first();

                if ($message) {
                    $deleted = $message->delete();

                    if ($deleted) {
                        return redirect()->route('messages.index')->with('success', 'Message deleted successfully!');
                    }
                }

                return redirect()->route('messages.index')->with('error', 'An error occurred while deleting.');
            });

        } catch (\Exception $e) {
            return redirect()->route('messages.index')->with('error', 'An error occurred while deleting the message.');
        }
    }

    public function restockProduct(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        $pId = $request->input('product_id');
        $qty = $request->input('quantity');
        $type = $request->input('type');

        if (empty($pId) || empty($qty)) {
            return redirect()->route('inventory.index')->with('error', 'Select product and quantity');
        }

        try {
            return DB::transaction(function () use ($pId, $qty, $type) {
                $product = Product::where('id', $pId)->lockForUpdate()->first();

                if (!$product) {
                    return redirect()->route('inventory.index')->with('error', 'Product not found');
                }

                if ($type === 'subtract') {
                    $currentStock = $product->quantity;

                    if ($qty > $currentStock) {
                        return redirect()->route('inventory.index')->with('error', "Cannot remove $qty items. Only $currentStock available.");
                    }

                    $newQty = $currentStock - $qty;
                    $newStatus = ($newQty <= 0) ? 'Out of Stock' : $product->status;

                    $product->update([
                        'quantity' => $newQty,
                        'status' => $newStatus,
                    ]);

                    $msg = 'Stock reduced successfully!';
                } else {
                    $product->update([
                        'quantity' => $product->quantity + $qty,
                        'status' => 'In Stock',
                    ]);

                    $msg = 'Stock added and status updated to In Stock!';
                }

                return redirect()->route('inventory.index')->with('success', $msg);
            });
        } catch (\Exception $e) {
            return redirect()->route('inventory.index')->with('error', 'An error occurred during stock adjustment.');
        }
    }

    public function storeProduct(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        $pName = $request->input('p_name');
        $price = $request->input('price');
        $quantity = $request->input('quantity');
        $categoryId = $request->input('category_id');
        $description = $request->input('description');
        $hp = $request->input('hp');
        $status = 'In Stock';

        if (empty($pName) || empty($price) || empty($categoryId)) {
            return redirect()->route('inventory.index')->with('error', 'Name, Price, and Category are required');
        }

        // Handle Tags using a full for loop matching your standard
        $tagString = "";
        $tags = $request->input('tags');

        if (is_array($tags)) {
            for ($i = 0; $i < count($tags); $i = $i + 1) {
                $tagString = $tagString . $tags[$i];

                if ($i < count($tags) - 1) {
                    $tagString = $tagString . ", ";
                }
            }
        }

        $regWarranty = $request->has('regular_warranty') ? 1 : 0;
        $repWarranty = $request->has('replacement_warranty') ? 1 : 0;

        $finalImgName = "";

        if ($request->hasFile('product_image') && $request->file('product_image')->isValid()) {
            $file = $request->file('product_image');
            $imgExLc = strtolower($file->getClientOriginalExtension());
            $newImgName = "PRODUCT-" . uniqid('', true) . '.' . $imgExLc;

            if ($file->move(public_path('uploads'), $newImgName)) {
                $finalImgName = $newImgName;
            } else {
                return redirect()->route('inventory.index')->with('error', 'Failed to move uploaded file');
            }
        } else {
            $category = Category::find($categoryId);

            if ($category && !empty($category->category_img)) {
                $finalImgName = $category->category_img;
            } else {
                $finalImgName = "default-cat.png";
            }
        }

        Product::create([
            'product_name' => $pName,
            'description' => $description,
            'price' => $price,
            'quantity' => $quantity,
            'image' => $finalImgName,
            'category_id' => $categoryId,
            'status' => $status,
            'regular_warranty' => $regWarranty,
            'replacement_warranty' => $repWarranty,
            'tags' => $tagString,
            'hp' => $hp,
        ]);

        return redirect()->route('inventory.index')->with('success', 'Product added successfully!');
    }

    public function deleteProduct(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if user is logged in and is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($id) {
                // --- 1. Find the product using row-level locking ---
                $product = Product::where('id', $id)->lockForUpdate()->first();

                if (!$product) {
                    return redirect()->route('inventory.view')->with('error', 'Product record not found.');
                }

                $imageName = $product->image;
                $filePath = public_path('uploads/' . $imageName);

                // --- 2. Delete the physical image file from the 'uploads' folder ---
                if ($imageName && File::exists($filePath) && strpos($imageName, 'PRODUCT-') !== false) {
                    File::delete($filePath);
                }

                // --- 3. Remove this product from all customer carts ---
                Cart::where('product_id', $id)->delete();

                // --- 4. Delete the product record from the database table ---
                $deleted = $product->delete();

                if ($deleted) {
                    return redirect()->route('inventory.view')->with('success', 'Product deleted successfully');
                } else {
                    return redirect()->route('inventory.view')->with('error', 'Could not delete from database');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('inventory.view')->with('error', 'An error occurred while deleting the product.');
        }
    }

    public function updateProduct(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if the user is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        if (!$request->has('product_name') || !$request->has('category_id') || !$request->has('price')) {
            return redirect()->route('inventory.view');
        }

        try {
            return DB::transaction(function () use ($request, $id) {
                $product = Product::where('id', $id)->lockForUpdate()->first();

                if (!$product) {
                    return redirect()->route('inventory.view')->with('error', 'Product not found');
                }

                $pName = $request->input('product_name');
                $categoryId = $request->input('category_id');
                $price = $request->input('price');
                $description = $request->input('description');
                $hp = $request->input('hp');
                $removeImg = $request->has('remove_image') ? 1 : 0;

                // Handle Warranty Checkboxes (1 or 0)
                $regW = $request->has('regular_warranty') ? 1 : 0;
                $repW = $request->has('replacement_warranty') ? 1 : 0;

                // --- TAGS LOGIC ---
                $tagsStr = "";
                $tagsArray = $request->input('tags');
                if (is_array($tagsArray)) {
                    $tagCount = count($tagsArray);
                    for ($i = 0; $i < $tagCount; $i = $i + 1) {
                        if ($i == 0) {
                            $tagsStr = $tagsArray[$i];
                        } else {
                            $tagsStr = $tagsStr . "," . $tagsArray[$i];
                        }
                    }
                }

                // Default: Keep current image
                $finalImage = null;

                // --- IMAGE UPDATE LOGIC ---
                // Scenario A: Manager wants to REMOVE the image (Revert to category icon)
                if ($removeImg == 1) {
                    $category = Category::find($categoryId);
                    if ($category && !empty($category->category_img)) {
                        $finalImage = $category->category_img;
                    } else {
                        $finalImage = "default-cat.png";
                    }
                } 
                // Scenario B: Manager uploaded a NEW image
                else if ($request->hasFile('product_image') && $request->file('product_image')->isValid()) {
                    $file = $request->file('product_image');
                    $imgName = $file->getClientOriginalName();
                    $imgEx = pathinfo($imgName, PATHINFO_EXTENSION);
                    $imgExLc = strtolower($imgEx);
                    $allowedExs = ["jpg", "jpeg", "png"];

                    if (in_array($imgExLc, $allowedExs)) {
                        $newImgName = uniqid("IMG-", true) . '.' . $imgExLc;
                        $file->move(public_path('uploads'), $newImgName);
                        $finalImage = $newImgName;
                    }
                }

                // --- DATABASE UPDATE ---
                $updateData = [
                    'product_name' => $pName,
                    'category_id' => $categoryId,
                    'price' => $price,
                    'description' => $description,
                    'regular_warranty' => $regW,
                    'replacement_warranty' => $repW,
                    'tags' => $tagsStr,
                    'hp' => $hp,
                ];

                if ($finalImage !== null) {
                    $updateData['image'] = $finalImage;
                }

                $updated = $product->update($updateData);

                if ($updated) {
                    return redirect()->route('inventory.view')->with('success', 'Product updated successfully');
                } else {
                    return redirect()->route('product.edit.form', ['id' => $id])->with('error', 'Unknown error occurred');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('product.edit.form', ['id' => $id])->with('error', 'An error occurred while updating the product.');
        }
    }

    public function updateProfile(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if the user is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        if (!$request->has('fname') || !$request->has('lname') || !$request->has('email_address') || !$request->has('old_password')) {
            return redirect()->route('profile.edit.form');
        }

        $fname = $request->input('fname');
        $lname = $request->input('lname');
        $email = $request->input('email_address');
        $phone = $request->input('phone_number');
        $oldPass = $request->input('old_password');
        $newPass = $request->input('new_password');
        $cPass = $request->input('confirm_password');

        if (empty($fname) || empty($lname) || empty($email) || empty($oldPass)) {
            return redirect()->route('profile.edit.form')->with('error', 'Required fields missing');
        }

        try {
            return DB::transaction(function () use ($userId, $fname, $lname, $email, $phone, $oldPass, $newPass, $cPass) {
                // 1. Fetch current user from 'users' table using row locking
                $user = User::where('uid', $userId)->lockForUpdate()->first();

                if (!$user) {
                    return redirect()->route('login');
                }

                // Verify the old password
                if (!Hash::check($oldPass, $user->password)) {
                    return redirect()->route('profile.edit.form')->with('error', 'Current password is incorrect');
                }

                // 2. Update Profile Info in 'manager' table
                $manager = Manager::where('user_id', $userId)->lockForUpdate()->first();

                if ($manager) {
                    $resManager = $manager->update([
                        'fname' => $fname,
                        'lname' => $lname,
                        'email_address' => $email,
                        'phone_number' => $phone,
                    ]);

                    // 3. Update Password in 'users' table if a new one was provided
                    if (!empty($newPass)) {
                        if ($newPass !== $cPass) {
                            return redirect()->route('profile.edit.form')->with('error', 'New passwords do not match');
                        }

                        $hashedPass = Hash::make($newPass);
                        $user->update([
                            'password' => $hashedPass,
                        ]);
                    }

                    if ($resManager) {
                        return redirect()->route('profile.edit.form')->with('success', 'Profile updated successfully!');
                    } else {
                        return redirect()->route('profile.edit.form')->with('error', 'An error occurred');
                    }
                } else {
                    return redirect()->route('profile.edit.form')->with('error', 'Manager profile not found');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('profile.edit.form')->with('error', 'An error occurred while updating the profile.');
        }
    }

    public function updateRepair(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if the user is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        if (!$request->has('repair_id') || !$request->has('action')) {
            return redirect()->route('repair.house');
        }

        $repairId = $request->input('repair_id');
        $action = $request->input('action');

        try {
            return DB::transaction(function () use ($request, $repairId, $action) {
                // Fetch repair record with row-level locking
                $repair = CarRepair::where('repair_id', $repairId)->lockForUpdate()->first();

                if (!$repair) {
                    return redirect()->route('repair.house')->with('error', 'Repair request not found.');
                }

                // --- ACTION: ACCEPT ---
                if ($action === 'accept') {
                    $repair->update(['status' => 'Inspecting']);
                    return redirect()->route('repair.edit.form', ['id' => $repairId])->with('success', 'Request Accepted');
                }

                // --- ACTION: REJECT (INITIAL REQUEST) ---
                elseif ($action === 'reject') {
                    $repair->update(['status' => 'Rejected']);
                    return redirect()->route('repair.house')->with('success', 'Request Rejected');
                }

                // --- ACTION: SEND INVOICE ---
                elseif ($action === 'request_payment') {
                    $repairNotes = $request->input('repair_notes');
                    $laborFee = $request->input('labor_fee');
                    $partsTotal = $request->input('parts_total');
                    $status = 'Awaiting Payment';

                    if ($request->has('parts')) {
                        $selectedParts = $request->input('parts');

                        // Remove old selections first and restore stock if necessary, or simply clear
                        RepairPartsSelected::where('repair_id', $repairId)->delete();

                        for ($i = 0; $i < count($selectedParts); $i = $i + 1) {
                            $productId = $selectedParts[$i];

                            if (!empty($productId)) {
                                $product = Product::with('category')->where('id', $productId)->lockForUpdate()->first();

                                if ($product) {
                                    $pName = $product->product_name;
                                    $pImg = $product->image;
                                    $pCat = $product->category ? $product->category->category_name : '';
                                    $pPrice = $product->price;

                                    // Deduct from stock with row locking
                                    $product->update([
                                        'quantity' => $product->quantity - 1,
                                    ]);

                                    // Log the snapshot
                                    RepairPartsSelected::create([
                                        'repair_id' => $repairId,
                                        'product_id' => $productId,
                                        'product_name' => $pName,
                                        'product_image' => $pImg,
                                        'category_name' => $pCat,
                                        'price_at_time' => $pPrice,
                                    ]);
                                }
                            }
                        }
                    }

                    $repair->update([
                        'status' => $status,
                        'repair_notes' => $repairNotes,
                        'labor_fee' => $laborFee,
                        'parts_total' => $partsTotal,
                    ]);

                    return redirect()->route('repair.edit.form', ['id' => $repairId])->with('success', 'Invoice Sent');
                }

                // --- ACTION: SET READY ---
                elseif ($action === 'set_ready') {
                    $repairNotes = $request->input('repair_notes');
                    $repair->update([
                        'status' => 'Ready',
                        'repair_notes' => $repairNotes,
                    ]);

                    return redirect()->route('repair.edit.form', ['id' => $repairId])->with('success', 'Job Marked Ready');
                }

                // --- ACTION: FINALIZE ---
                elseif ($action === 'finalize') {
                    $repair->update(['status' => 'Completed']);
                    return redirect()->route('repair.edit.form', ['id' => $repairId])->with('success', 'Job Completed');
                }

                // --- ACTION: CANCEL/REJECT MID-PROCESS ---
                elseif ($action === 'cancel_repair') {
                    $currentStatus = $repair->status;
                    $allowedToCancel = ['Pending', 'Inspecting', 'Awaiting Payment'];
                    $isAllowed = false;

                    for ($k = 0; $k < count($allowedToCancel); $k = $k + 1) {
                        if ($currentStatus === $allowedToCancel[$k]) {
                            $isAllowed = true;
                        }
                    }

                    if ($isAllowed) {
                        $partsToReturn = RepairPartsSelected::where('repair_id', $repairId)->get();

                        for ($i = 0; $i < count($partsToReturn); $i = $i + 1) {
                            $pId = $partsToReturn[$i]->product_id;
                            $product = Product::where('id', $pId)->lockForUpdate()->first();

                            if ($product) {
                                $product->update([
                                    'quantity' => $product->quantity + 1,
                                ]);
                            }
                        }

                        RepairPartsSelected::where('repair_id', $repairId)->delete();
                        $repair->update(['status' => 'Rejected']);

                        return redirect()->route('repair.house')->with('success', 'Repair has been rejected');
                    } else {
                        return redirect()->route('repair.edit.form', ['id' => $repairId])->with('error', 'Cannot reject job in current status');
                    }
                }

                return redirect()->route('repair.house');
            });

        } catch (\Exception $e) {
            return redirect()->route('repair.house')->with('error', 'An error occurred while updating the repair.');
        }
    }

    public function replyToProductReview(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if the user is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        if (!$request->has('reply') || !$request->has('review_id') || !$request->has('product_id')) {
            return redirect()->route('inventory.view');
        }

        $reply = $request->input('reply');
        $revId = $request->input('review_id');
        $pId = $request->input('product_id');

        // Determine the message based on if it's an edit
        $msg = $request->has('is_edit') ? 'Reply updated successfully!' : 'Reply posted successfully!';

        if (empty($reply)) {
            return redirect()->route('product.reviews.view', ['id' => $pId])->with('error', 'Reply cannot be empty');
        }

        // 2. Find and update the review with the company reply
        $review = Review::find($revId);

        if ($review) {
            $updated = $review->update([
                'company_reply' => $reply,
            ]);

            if ($updated) {
                return redirect()->route('product.reviews.view', ['id' => $pId])->with('success', $msg);
            } else {
                return redirect()->route('product.reviews.view', ['id' => $pId])->with('error', 'An error occurred while saving');
            }
        } else {
            return redirect()->route('product.reviews.view', ['id' => $pId])->with('error', 'Review not found');
        }
    }

    public function updateSettings(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if the user is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('logout');
        }

        if (!$request->has('garage_name') || !$request->has('slogan') || !$request->has('about') || !$request->has('current_year')) {
            return redirect()->route('settings.index')->with('error', 'An error occurred');
        }

        $garageName = $request->input('garage_name');
        $slogan = $request->input('slogan');
        $about = $request->input('about');
        $currentYear = $request->input('current_year');

        // Validation - Checking if fields are empty
        if (empty($garageName)) {
            return redirect()->route('settings.index')->with('error', 'Garage name is required');
        } else if (empty($slogan)) {
            return redirect()->route('settings.index')->with('error', 'Slogan is required');
        } else if (empty($about)) {
            return redirect()->route('settings.index')->with('error', 'About description is required');
        } else if (empty($currentYear)) {
            return redirect()->route('settings.index')->with('error', 'Current year is required');
        }

        try {
            return DB::transaction(function () use ($garageName, $slogan, $about, $currentYear) {
                // Find the settings record (assuming ID 1 for main garage configuration) with row locking
                $setting = Setting::where('id', 1)->lockForUpdate()->first();

                if ($setting) {
                    $setting->update([
                        'garage_name' => $garageName,
                        'slogan' => $slogan,
                        'about' => $about,
                        'current_year' => $currentYear,
                    ]);

                    return redirect()->route('settings.index')->with('success', 'Settings updated successfully');
                } else {
                    return redirect()->route('settings.index')->with('error', 'Settings record not found');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('settings.index')->with('error', 'An error occurred while updating settings');
        }
    }

    public function updateWarrantyStatus(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // 1. Check if the user is a Manager
        if (!$userId || $role !== 'Manager') {
            return redirect()->route('login');
        }

        if (!$request->has('status')) {
            return redirect()->route('warranty.manage');
        }

        $status = $request->input('status');

        try {
            return DB::transaction(function () use ($id, $status) {
                // Find the warranty claim record with row locking
                $claim = WarrantyClaim::where('id', $id)->lockForUpdate()->first();

                if (!$claim) {
                    return redirect()->route('warranty.manage')->with('error', 'Warranty claim not found');
                }

                $updated = $claim->update([
                    'status' => $status,
                ]);

                if ($updated) {
                    return redirect()->route('warranty.manage')->with('success', "Claim updated to $status");
                } else {
                    return redirect()->route('warranty.manage')->with('error', 'Error occurred');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('warranty.manage')->with('error', 'An error occurred while updating the warranty claim.');
        }
    }
}