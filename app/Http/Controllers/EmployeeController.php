<?php

namespace App\Http\Controllers; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Auction;
use App\Models\Car;
use Illuminate\Support\Facades\File;
use App\Models\Setting;
use App\Models\Review;
use App\Models\Message;
use App\Models\Product;
use App\Models\Category;
use App\Models\Cart;
use App\Models\User;
use App\Models\Employee; 
use Illuminate\Support\Facades\Hash;
use App\Models\CarRepair;
use App\Models\RepairPartsSelected;
use App\Models\WarrantyClaim;
use App\Models\Order;

class EmployeeController extends Controller
{
    public function terminateAuction(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // Check for Manager OR Employee role matching legacy behavior
        if (!$userId || ($role !== 'Manager' && $role !== 'Employee')) {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($id) {
                // 1. Fetch auction details locking the row
                $auction = Auction::where('id', $id)->lockForUpdate()->first();

                if (!$auction) {
                    return redirect()->route('auction.dashboard')->with('error', 'Auction not found');
                }

                $carId = $auction->car_id;

                // 2. Restore the car to the showroom (quantity + 1, status = 'In Stock', in_auction = 0)
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

                // 4. Redirect immediately with appropriate status message
                if ($deleted) {
                    return redirect()->route('auction.dashboard')->with('success', 'Auction terminated and car restored');
                } else {
                    return redirect()->route('auction.dashboard')->with('error', 'Termination failed');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('auction.dashboard')->with('error', 'Termination failed');
        }
    }

    public function endAuction(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // Check if user is logged in and is either a Manager or an Employee
        if (!$userId || ($role !== 'Manager' && $role !== 'Employee')) {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($id) {
                // 1. Fetch auction details locking the row
                $auction = Auction::where('id', $id)->lockForUpdate()->first();

                if (!$auction) {
                    return redirect()->route('auction.dashboard')->with('error', 'Auction not found.');
                }

                $carId = $auction->car_id;
                $winnerId = $auction->highest_bidder_id;

                // 2. Scenario Check: Did anyone bid?
                if (is_null($winnerId)) {
                    // No bids: Return the vehicle to showroom stock with locking
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
                    // Scenario: Winner exists
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
            return redirect()->route('auction.dashboard')->with('error', 'Failed to settle auction due to an error.');
        }
    }

    public function storeCar(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // Check if user is logged in and is either a Manager or an Employee
        if (!$userId || ($role !== 'Manager' && $role !== 'Employee')) {
            return redirect()->route('login');
        }

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
            'car_image.mimes' => 'Incorrect file type. Use JPG, JPEG, or PNG.',
        ]);

        // Image Upload Handling
        $file = $request->file('car_image');

        if ($file && $file->isValid()) {
            $imgExLc = strtolower($file->getClientOriginalExtension());
            $allowedExs = ["jpg", "jpeg", "png"];

            if (!in_array($imgExLc, $allowedExs)) {
                return redirect()->route('car.add.form')->with('error', 'Incorrect file type. Use JPG, JPEG, or PNG.');
            }

            $newImgName = "CAR-" . uniqid() . "." . $imgExLc;
            $file->move(public_path('uploads'), $newImgName);

            // Database Insert via Eloquent Model
            $car = Car::create([
                'brand' => $request->input('brand'),
                'model' => $request->input('model'),
                'year' => $request->input('year'),
                'category_id' => $request->input('category_id'),
                'price' => $request->input('price'),
                'quantity' => $request->input('quantity', 1),
                'status' => $request->input('status', 'In Stock'),
                'transmission' => $request->input('transmission'),
                'mileage' => $request->input('mileage'),
                'description' => $request->input('description'),
                'regular_warranty' => $request->has('regular_warranty') ? 1 : 0,
                'replacement_warranty' => $request->has('replacement_warranty') ? 1 : 0,
                'image' => $newImgName,
            ]);

            if ($car) {
                return redirect()->route('car.add.form')->with('success', 'New vehicle added successfully!');
            } else {
                return redirect()->route('car.add.form')->with('error', 'An error occurred during database saving.');
            }
        } else {
            return redirect()->route('car.add.form')->with('error', 'Image is required.');
        }
    }

    public function deleteCar(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // Check if user is logged in and is either a Manager or an Employee
        if (!$userId || ($role !== 'Manager' && $role !== 'Employee')) {
            return redirect()->route('login');
        }

        // 1. Find the car record
        $car = Car::find($id);

        if ($car) {
            $imageName = $car->image;
            $path = public_path('uploads/' . $imageName);

            // 2. Delete the physical image file from the folder if it exists
            if ($imageName && File::exists($path)) {
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
        } else {
            return redirect()->route('showroom.manage');
        }
    }

    public function updateCar(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // Check if user is logged in and is either a Manager or an Employee
        if (!$userId || ($role !== 'Manager' && $role !== 'Employee')) {
            return redirect()->route('login');
        }

        $car = Car::find($id);

        if (!$car) {
            return redirect()->route('showroom.manage');
        }

        /* --- 1. AUCTION CHECK --- */
        $inAuction = Auction::where('car_id', $id)->where('status', 'Active')->first();

        if ($inAuction) {
            return redirect()->route('car.edit.form', ['id' => $id])->with('error', 'This car is currently in an active auction and cannot be edited.');
        }

        /* --- 2. FETCH CURRENT YEAR --- */
        $setting = Setting::first();
        $currYear = $setting ? $setting->current_year : date('Y');

        /* --- 3. VALIDATION --- */
        $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . $currYear,
            'price' => 'required|numeric|between:1,999999999',
            'mileage' => 'required|numeric|between:1,999999999',
            'category_id' => 'required|integer',
            'transmission' => 'required|string',
            'quantity' => 'required|integer',
            'status' => 'required|string',
            'description' => 'nullable|string',
            'car_image' => 'nullable|image|mimes:jpg,jpeg,png',
        ], [
            'year.min' => "Year must be between 1900 and $currYear",
            'year.max' => "Year must be between 1900 and $currYear",
            'price.between' => 'Price must be between 1 and 999,999,999',
            'mileage.between' => 'Mileage must be between 1 and 999,999,999',
            'car_image.image' => 'Invalid image format',
            'car_image.mimes' => 'Invalid image format',
        ]);

        /* --- 4. WARRANTY CHECKBOXES --- */
        $regW = $request->has('regular_warranty') ? 1 : 0;
        $repW = $request->has('replacement_warranty') ? 1 : 0;

        $imageName = $car->image;

        /* --- 5. IMAGE HANDLING --- */
        if ($request->hasFile('car_image')) {
            $file = $request->file('car_image');

            if ($file->isValid()) {
                $imgExLc = strtolower($file->getClientOriginalExtension());
                $allowedExs = ["jpg", "jpeg", "png"];

                if (!in_array($imgExLc, $allowedExs)) {
                    return redirect()->route('car.edit.form', ['id' => $id])->with('error', 'Invalid image format');
                }

                $newImgName = "CAR-" . uniqid() . "." . $imgExLc;
                $oldImagePath = public_path('uploads/' . $car->image);

                // Delete old physical image file
                if ($car->image && File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }

                $file->move(public_path('uploads'), $newImgName);
                $imageName = $newImgName;
            }
        }

        /* --- 6. UPDATE RECORD --- */
        $updated = $car->update([
            'brand' => $request->input('brand'),
            'model' => $request->input('model'),
            'year' => $request->input('year'),
            'category_id' => $request->input('category_id'),
            'price' => $request->input('price'),
            'quantity' => $request->input('quantity'),
            'status' => $request->input('status'),
            'transmission' => $request->input('transmission'),
            'mileage' => $request->input('mileage'),
            'description' => $request->input('description'),
            'regular_warranty' => $regW,
            'replacement_warranty' => $repW,
            'image' => $imageName,
        ]);

        /* --- 7. FINAL REDIRECT --- */
        if ($updated) {
            return redirect()->route('showroom.manage')->with('success', 'Vehicle updated successfully');
        } else {
            return redirect()->route('car.edit.form', ['id' => $id])->with('error', 'Unknown error occurred');
        }
    }

    public function replyToReview(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // Check if user is logged in and is either a Manager or an Employee
        if (!$userId || ($role !== 'Manager' && $role !== 'Employee')) {
            return redirect()->route('login');
        }

        $request->validate([
            'review_id' => 'required|integer',
            'car_id' => 'required|integer',
            'reply_text' => 'required|string',
        ], [
            'reply_text.required' => 'Reply cannot be empty',
        ]);

        $reviewId = $request->input('review_id');
        $replyText = $request->input('reply_text');
        $carId = $request->input('car_id');

        // Find and update the review with the company reply
        $review = Review::find($reviewId);

        if ($review) {
            $updated = $review->update([
                'company_reply' => $replyText,
            ]);

            if ($updated) {
                return redirect()->route('car.view', ['id' => $carId])->with('success', 'Reply posted successfully!');
            } else {
                return redirect()->route('car.view', ['id' => $carId])->with('error', 'Database error occurred');
            }
        } else {
            return redirect()->route('car.view', ['id' => $carId])->with('error', 'Review not found');
        }
    }

    public function deleteMessage(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // Ensure the user is logged in as an Employee (matching the script's check)
        if (!$userId || $role !== 'Employee') {
            return redirect()->route('login');
        }

        $messageId = $request->input('message_id');

        if (!$messageId) {
            return redirect()->route('messages.index');
        }

        // Find and delete the message using Eloquent (assuming primary key is 'message_id' or 'id')
        $message = Message::where('message_id', $messageId)->first();

        if ($message) {
            $deleted = $message->delete();

            if ($deleted) {
                return redirect()->route('messages.index')->with('success', 'Message deleted successfully!');
            } else {
                return redirect()->route('messages.index')->with('error', 'An error occurred while deleting.');
            }
        } else {
            return redirect()->route('messages.index')->with('error', 'Message not found.');
        }
    }

    public function handleInventoryAction(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        if (!$userId || ($role !== 'Manager' && $role !== 'Employee')) {
            return redirect()->route('login');
        }

        $action = $request->input('action');

        // --- ACTION 1: STOCK ADJUSTMENT ---
        if ($action === 'restock') {
            $productId = $request->input('product_id');
            $qty = $request->input('quantity');
            $type = $request->input('type');

            if (empty($productId) || empty($qty)) {
                return redirect()->route('inventory.index')->with('error', 'Select product and quantity');
            }

            try {
                return DB::transaction(function () use ($productId, $qty, $type) {
                    $product = Product::where('id', $productId)->lockForUpdate()->first();

                    if (!$product) {
                        return redirect()->route('inventory.index')->with('error', 'Product not found');
                    }

                    if ($type === 'subtract') {
                        if ($qty > $product->quantity) {
                            return redirect()->route('inventory.index')->with('error', "Cannot remove $qty items. Only {$product->quantity} available.");
                        }

                        $newQuantity = $product->quantity - $qty;
                        $status = ($newQuantity <= 0) ? 'Out of Stock' : $product->status;

                        $product->update([
                            'quantity' => $newQuantity,
                            'status' => $status,
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
                return redirect()->route('inventory.index')->with('error', 'Stock adjustment failed.');
            }
        }

        // --- ACTION 2: ADD NEW PRODUCT ---
        elseif ($action === 'add_new') {
            $pName = $request->input('p_name');
            $price = $request->input('price');
            $quantity = $request->input('quantity');
            $hp = $request->input('hp');
            $categoryId = $request->input('category_id');
            $description = $request->input('description');

            if ($price < 0) {
                return redirect()->route('inventory.index')->with('error', 'Price cannot be negative');
            }

            if ($quantity < 0) {
                return redirect()->route('inventory.index')->with('error', 'Quantity cannot be negative');
            }

            if (empty($pName) || empty($price) || empty($categoryId)) {
                return redirect()->route('inventory.index')->with('error', 'Name, Price, and Category are required');
            }

            // Handle Tags using a full for loop complying with coding preferences
            $tagString = "";
            $tagList = $request->input('tags', []);

            if (is_array($tagList)) {
                for ($i = 0; $i < count($tagList); $i = $i + 1) {
                    $tagString = $tagString . $tagList[$i];

                    if ($i < count($tagList) - 1) {
                        $tagString = $tagString . ", ";
                    }
                }
            }

            $regWarranty = $request->has('regular_warranty') ? 1 : 0;
            $repWarranty = $request->has('replacement_warranty') ? 1 : 0;

            $finalImgName = "";

            if ($request->hasFile('product_image')) {
                $file = $request->file('product_image');

                if ($file->isValid()) {
                    $imgExLc = strtolower($file->getClientOriginalExtension());
                    $newImgName = "PRODUCT-" . uniqid('', true) . '.' . $imgExLc;
                    $file->move(public_path('uploads'), $newImgName);
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
                'hp' => $hp,
                'image' => $finalImgName,
                'category_id' => $categoryId,
                'status' => 'In Stock',
                'regular_warranty' => $regWarranty,
                'replacement_warranty' => $repWarranty,
                'tags' => $tagString,
            ]);

            return redirect()->route('inventory.index')->with('success', 'Product added successfully!');
        }

        return redirect()->route('inventory.index');
    }

    public function deleteProduct(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // Allow both Manager and Employee roles
        if (!$userId || ($role !== 'Manager' && $role !== 'Employee')) {
            return redirect()->route('login');
        }

        try {
            return DB::transaction(function () use ($id) {
                // 1. Find the product
                $product = Product::where('id', $id)->lockForUpdate()->first();

                if (!$product) {
                    return redirect()->route('inventory.view')->with('error', 'Product not found.');
                }

                $imageName = $product->image;
                $filePath = public_path('uploads/' . $imageName);

                // 2. Delete the physical image file from the 'uploads' folder if it exists
                if ($imageName && File::exists($filePath)) {
                    File::delete($filePath);
                }

                // 3. Remove this product from all customer carts
                Cart::where('product_id', $id)->delete();

                // 4. Delete the product record from the database table
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

        // Check if user is logged in and is either a Manager or an Employee
        if (!$userId || ($role !== 'Manager' && $role !== 'Employee')) {
            return redirect()->route('login');
        }

        $product = Product::find($id);

        if (!$product) {
            return redirect()->route('inventory.view');
        }

        // Validation
        $request->validate([
            'product_name' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'price' => 'required|numeric',
            'product_image' => 'nullable|image|mimes:jpg,jpeg,png',
        ]);

        $pName = $request->input('product_name');
        $categoryId = $request->input('category_id');
        $price = $request->input('price');
        $description = $request->input('description');
        $hp = $request->input('hp');
        $removeImg = $request->has('remove_image') ? 1 : 0;

        // Warranty Checkboxes
        $regW = $request->has('regular_warranty') ? 1 : 0;
        $repW = $request->has('replacement_warranty') ? 1 : 0;

        // --- TAGS LOGIC (Using your preferred full for loop style with spacing) ---
        $tagsStr = "";
        $tagsArray = $request->input('tags', []);

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

        $finalImage = null;

        // --- IMAGE UPDATE LOGIC ---
        if ($removeImg == 1) {
            // Scenario A: Removal of image -> Revert to category image or default
            $category = Category::find($categoryId);
            if ($category && !empty($category->category_img)) {
                $finalImage = $category->category_img;
            } else {
                $finalImage = "default-cat.png";
            }
        } else if ($request->hasFile('product_image')) {
            // Scenario B: Uploading a NEW image
            $file = $request->file('product_image');

            if ($file->isValid()) {
                $imgExLc = strtolower($file->getClientOriginalExtension());
                $allowedExs = ["jpg", "jpeg", "png"];

                if (in_array($imgExLc, $allowedExs)) {
                    $newImgName = uniqid("IMG-", true) . '.' . $imgExLc;
                    $file->move(public_path('uploads'), $newImgName);
                    $finalImage = $newImgName;
                }
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
            // Delete old physical image if replacing/removing and it's not a default/category icon
            $oldImagePath = public_path('uploads/' . $product->image);
            if ($product->image && File::exists($oldImagePath) && strpos($product->image, 'IMG-') !== false) {
                File::delete($oldImagePath);
            }
            $updateData['image'] = $finalImage;
        }

        $updated = $product->update($updateData);

        if ($updated) {
            return redirect()->route('inventory.view')->with('success', 'Product updated successfully');
        } else {
            return redirect()->route('product.edit.form', ['id' => $id])->with('error', 'Unknown error occurred');
        }
    }

    public function updateProfile(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // Ensure user is logged in as an Employee
        if (!$userId || $role !== 'Employee') {
            return redirect()->route('login');
        }

        $request->validate([
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email_address' => 'required|email|max:255',
            'old_password' => 'required|string',
        ], [
            'fname.required' => 'Required fields missing',
            'lname.required' => 'Required fields missing',
            'email_address.required' => 'Required fields missing',
            'old_password.required' => 'Required fields missing',
        ]);

        $fname = $request->input('fname');
        $lname = $request->input('lname');
        $email = $request->input('email_address');
        $phone = $request->input('phone_number');
        $oldPass = $request->input('old_password');
        $newPass = $request->input('new_password');
        $cPass = $request->input('confirm_password');

        try {
            return DB::transaction(function () use ($userId, $fname, $lname, $email, $phone, $oldPass, $newPass, $cPass) {
                // 1. Fetch current user record locking the row
                $user = User::where('uid', $userId)->lockForUpdate()->first();

                if (!$user || !Hash::check($oldPass, $user->password)) {
                    return redirect()->route('profile.edit')->with('error', 'Current password is incorrect');
                }

                // 2. Update Profile Info in the employee table using user_id / uid
                $employeeProfile = Employee::where('user_id', $userId)->first();
                
                $resEmployee = false;
                if ($employeeProfile) {
                    $resEmployee = $employeeProfile->update([
                        'fname' => $fname,
                        'lname' => $lname,
                        'email_address' => $email,
                        'phone_number' => $phone,
                    ]);
                }

                // 3. Update Password in 'users' table if a new one was provided
                if (!empty($newPass)) {
                    if ($newPass !== $cPass) {
                        return redirect()->route('profile.edit')->with('error', 'New passwords do not match');
                    }

                    $user->update([
                        'password' => Hash::make($newPass),
                    ]);
                }

                if ($resEmployee) {
                    return redirect()->route('profile.edit')->with('success', 'Profile updated successfully!');
                } else {
                    return redirect()->route('profile.edit')->with('error', 'An error occurred');
                }
            });

        } catch (\Exception $e) {
            return redirect()->route('profile.edit')->with('error', 'An error occurred while updating profile.');
        }
    }

    public function handleRepairAction(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // Access check: Allow both Employee and Manager roles
        if (!$userId || ($role !== 'Employee' && $role !== 'Manager')) {
            return redirect()->route('login');
        }

        if (!$request->has('repair_id') || !$request->has('action')) {
            return redirect()->route('repair.house');
        }

        $repairId = $request->input('repair_id');
        $action = $request->input('action');

        try {
            return DB::transaction(function () use ($request, $repairId, $action) {
                
                // Fetch repair record with locking
                $repair = CarRepair::where('repair_id', $repairId)->lockForUpdate()->first();

                if (!$repair) {
                    return redirect()->route('repair.house')->with('error', 'Repair request not found.');
                }

                // --- ACTION: ACCEPT ---
                if ($action === 'accept') {
                    $repair->update(['status' => 'Inspecting']);
                    return redirect()->route('repair.edit', ['id' => $repairId])->with('success', 'Request Accepted');
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

                        // Remove old selections first
                        RepairPartsSelected::where('repair_id', $repairId)->delete();

                        $partsCount = count($selectedParts);
                        for ($i = 0; $i < $partsCount; $i = $i + 1) {
                            $productId = $selectedParts[$i];

                            if (!empty($productId)) {
                                $product = Product::with('category')->where('id', $productId)->lockForUpdate()->first();

                                if ($product) {
                                    // Deduct from stock
                                    $product->update([
                                        'quantity' => $product->quantity - 1,
                                    ]);

                                    // Log the snapshot
                                    RepairPartsSelected::create([
                                        'repair_id' => $repairId,
                                        'product_id' => $productId,
                                        'product_name' => $product->product_name,
                                        'product_image' => $product->image,
                                        'category_name' => $product->category ? $product->category->category_name : '',
                                        'price_at_time' => $product->price,
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

                    return redirect()->route('repair.edit', ['id' => $repairId])->with('success', 'Invoice Sent');
                }

                // --- ACTION: SET READY ---
                elseif ($action === 'set_ready') {
                    $repairNotes = $request->input('repair_notes');

                    $repair->update([
                        'status' => 'Ready',
                        'repair_notes' => $repairNotes,
                    ]);

                    return redirect()->route('repair.edit', ['id' => $repairId])->with('success', 'Job Marked Ready');
                }

                // --- ACTION: FINALIZE ---
                elseif ($action === 'finalize') {
                    $repair->update(['status' => 'Completed']);
                    return redirect()->route('repair.edit', ['id' => $repairId])->with('success', 'Job Completed');
                }

                // --- ACTION: CANCEL/REJECT MID-PROCESS ---
                elseif ($action === 'cancel_repair') {
                    $currentStatus = $repair->status;
                    $allowedToCancel = ['Pending', 'Inspecting', 'Awaiting Payment'];
                    $isAllowed = false;

                    $allowedCount = count($allowedToCancel);
                    for ($k = 0; $k < $allowedCount; $k = $k + 1) {
                        if ($currentStatus === $allowedToCancel[$k]) {
                            $isAllowed = true;
                        }
                    }

                    if ($isAllowed) {
                        $partsToReturn = RepairPartsSelected::where('repair_id', $repairId)->get();

                        $partsReturnCount = count($partsToReturn);
                        for ($i = 0; $i < $partsReturnCount; $i = $i + 1) {
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
                        return redirect()->route('repair.edit', ['id' => $repairId])->with('error', 'Cannot reject job in current status');
                    }
                }

                return redirect()->route('repair.house');
            });

        } catch (\Exception $e) {
            return redirect()->route('repair.house')->with('error', 'An error occurred while processing the repair action.');
        }
    }

    public function replyToProductReview(Request $request)
    {
        $userId = session('user_id');
        $role = session('role');

        // Updated role check: Both Manager and Employee can post/edit replies
        if (!$userId || ($role !== 'Manager' && $role !== 'Employee')) {
            return redirect()->route('login');
        }

        if (!$request->has('reply') || !$request->has('review_id') || !$request->has('product_id')) {
            return redirect()->route('inventory.view');
        }

        $reply = $request->input('reply');
        $revId = $request->input('review_id');
        $pId = $request->input('product_id');

        // Determine the message based on if it's an edit
        $msg = "Reply posted successfully!";
        if ($request->has('is_edit')) {
            $msg = "Reply updated successfully!";
        }

        if (empty($reply)) {
            return redirect()->route('view.reviews', ['id' => $pId])->with('error', 'Reply cannot be empty');
        }

        $review = Review::find($revId);

        if ($review) {
            $updated = $review->update([
                'company_reply' => $reply,
            ]);

            if ($updated) {
                return redirect()->route('view.reviews', ['id' => $pId])->with('success', $msg);
            } else {
                return redirect()->route('view.reviews', ['id' => $pId])->with('error', 'An error occurred while saving');
            }
        } else {
            return redirect()->route('view.reviews', ['id' => $pId])->with('error', 'Review not found');
        }
    }

    public function updateWarrantyStatus(Request $request, $id)
    {
        $userId = session('user_id');
        $role = session('role');

        // Ensure the user is logged in as an Employee (matching legacy session check)
        if (!$userId || $role !== 'Employee') {
            return redirect()->route('login');
        }

        if (!$request->has('status')) {
            return redirect()->route('warranty.manage');
        }

        $status = $request->input('status');

        // Find and update the warranty claim record
        $claim = WarrantyClaim::find($id);

        if ($claim) {
            $updated = $claim->update([
                'status' => $status,
            ]);

            if ($updated) {
                return redirect()->route('warranty.manage')->with('success', "Claim updated to $status");
            } else {
                return redirect()->route('warranty.manage')->with('error', 'Error occurred');
            }
        } else {
            return redirect()->route('warranty.manage');
        }
    }
}