<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CustomerController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'signup'])->name('signup.post');


Route::post('/contact', [HomeController::class, 'sendContact'])->name('contact.send');



Route::middleware(['auth'])->group(function () {
    Route::post('/customer/account-delete', [CustomerController::class, 'deleteAccount'])->name('customer.account.delete');
    Route::post('/customer/add-to-cart', [CustomerController::class, 'addToCart'])->name('customer.cart.add');
    Route::post('/customer/auction/bid', [CustomerController::class, 'placeBid'])->name('customer.auction.bid');
    Route::post('/customer/auction-purchase', [CustomerController::class, 'auctionPurchase'])->name('customer.auction.purchase');
    Route::post('/customer/builder-action', [CustomerController::class, 'builderAction'])->name('customer.builder.action');
    Route::post('/customer/car-purchase', [CustomerController::class, 'carPurchase'])->name('customer.car.purchase');
    Route::post('/customer/change-password', [CustomerController::class, 'changePassword'])->name('customer.password.update');
    Route::post('/customer/checkout', [CustomerController::class, 'checkout'])->name('customer.checkout');
    Route::get('/customer/delete-build/{id}', [CustomerController::class, 'deleteBuild'])->name('customer.build.delete');
    Route::post('/customer/edit-review', [CustomerController::class, 'editReview'])->name('customer.review.update');
    Route::post('/customer/place-order', [CustomerController::class, 'placeOrder'])->name('customer.order.place');
    Route::match(['get', 'post'], '/customer/post-review', [CustomerController::class, 'postReview'])->name('customer.review.post');
    Route::post('/customer/profile-update', [CustomerController::class, 'updateProfile'])->name('customer.profile.update');
    Route::get('/customer/cart/reduce/{cartId}', [CustomerController::class, 'reduceQuantity'])->name('customer.cart.reduce');
    Route::get('/customer/cart/remove/{cartId}', [CustomerController::class, 'removeFromCart'])->name('customer.cart.remove');
    Route::post('/customer/repair-add', [CustomerController::class, 'storeRepairRequest'])->name('customer.repair.store');
    Route::get('/customer/repair-cancel/{id}', [CustomerController::class, 'cancelRepairRequest'])->name('customer.repair.cancel');
    Route::get('/customer/repair-decline-parts/{id}', [CustomerController::class, 'declineRepairParts'])->name('customer.repair.decline.parts');
    Route::get('/customer/repair-transaction/{id}', [CustomerController::class, 'repairTransaction'])->name('customer.repair.transaction');
    Route::get('/customer/my-reviews/delete/{id}', [CustomerController::class, 'deleteMyReview'])->name('customer.review.delete.my');
    Route::post('/customer/send-message', [CustomerController::class, 'sendMessage'])->name('customer.message.send');
    Route::post('/customer/submit-warranty', [CustomerController::class, 'submitWarrantyClaim'])->name('customer.warranty.submit');
    Route::get('/customer/wishlist-handle/{id}', [CustomerController::class, 'wishlistHandle'])->name('customer.wishlist.handle');


    Route::get('/employee/auction-delete/{id}', [EmployeeController::class, 'terminateAuction'])->name('employee.auction.delete');
    Route::get('/employee/auction-end/{id}', [EmployeeController::class, 'endAuction'])->name('employee.auction.end');
    Route::post('/employee/car-add', [EmployeeController::class, 'storeCar'])->name('employee.car.store');
    Route::get('/employee/car-delete/{id}', [EmployeeController::class, 'deleteCar'])->name('employee.car.delete');
    Route::post('/employee/car-edit/{id}', [EmployeeController::class, 'updateCar'])->name('employee.car.update');
    Route::post('/employee/car-review-reply', [EmployeeController::class, 'replyToReview'])->name('employee.car.review.reply');
    Route::post('/employee/message-delete', [EmployeeController::class, 'deleteMessage'])->name('employee.message.delete');
    Route::post('/employee/inventory-action', [EmployeeController::class, 'handleInventoryAction'])->name('employee.inventory.action');
    Route::get('/employee/product-delete/{id}', [EmployeeController::class, 'deleteProduct'])->name('employee.product.delete');
    Route::post('/employee/product-edit/{id}', [EmployeeController::class, 'updateProduct'])->name('employee.product.update');
    Route::post('/employee/profile-edit', [EmployeeController::class, 'updateProfile'])->name('employee.profile.update');
    Route::post('/employee/repair-update', [EmployeeController::class, 'handleRepairAction'])->name('employee.repair.action');
    Route::post('/employee/reply-review', [EmployeeController::class, 'replyToProductReview'])->name('employee.review.reply');
    Route::get('/employee/warranty-status/{id}', [EmployeeController::class, 'updateWarrantyStatus'])->name('employee.warranty.status');



    Route::post('/manager/auction-add', [ManagerController::class, 'storeAuction'])->name('manager.auction.store');
    Route::get('/manager/auction-confirm-payment/{id}', [ManagerController::class, 'confirmAuctionPayment'])->name('manager.auction.confirm.payment');
    Route::get('/manager/auction-delete/{id}', [ManagerController::class, 'deleteAuction'])->name('manager.auction.delete');
    Route::get('/manager/auction-end/{id}', [ManagerController::class, 'endAuction'])->name('manager.auction.end');
    Route::get('/manager/auction-reject-payment/{id}', [ManagerController::class, 'rejectAuctionPayment'])->name('manager.auction.reject.payment');
    
    //one file
    Route::post('/manager/builder-settings', [ManagerController::class, 'handleBuilderSettingsPost'])->name('manager.builder.settings.post');
    Route::get('/manager/category-toggle/{id}', [ManagerController::class, 'toggleMandatoryCategory'])->name('manager.category.toggle');
    Route::get('/manager/category-delete/{id}', [ManagerController::class, 'deleteCategory'])->name('manager.category.delete');
    Route::get('/manager/tag-delete/{id}', [ManagerController::class, 'deleteTag'])->name('manager.tag.delete');
    //file end

    Route::post('/manager/car-add', [ManagerController::class, 'storeCar'])->name('manager.car.store');
    Route::get('/manager/car-delete/{id}', [ManagerController::class, 'deleteCar'])->name('manager.car.delete');
    Route::post('/manager/car-edit/{id}', [ManagerController::class, 'updateCar'])->name('manager.car.update');
    Route::post('/manager/car-review-reply', [ManagerController::class, 'replyToCarReview'])->name('manager.car.review.reply');
    Route::post('/manager/category-add', [ManagerController::class, 'storeCategory'])->name('manager.category.store');

    //one file
    Route::post('/manager/employee-register', [ManagerController::class, 'registerEmployee'])->name('manager.employee.register');
    Route::post('/manager/employee-salary-update', [ManagerController::class, 'updateEmployeeSalary'])->name('manager.employee.salary.update');
    Route::post('/manager/employee-info-update', [ManagerController::class, 'updateEmployeeInfo'])->name('manager.employee.info.update');
    Route::get('/manager/employee-delete/{id}', [ManagerController::class, 'deleteEmployee'])->name('manager.employee.delete');
    //file end

    Route::post('/manager/message-delete', [ManagerController::class, 'deleteMessage'])->name('manager.message.delete');

    //onefile
    Route::post('/manager/product-restock', [ManagerController::class, 'restockProduct'])->name('manager.product.restock');
    Route::post('/manager/product-add', [ManagerController::class, 'storeProduct'])->name('manager.product.store');
    //file end

    Route::get('/manager/product-delete/{id}', [ManagerController::class, 'deleteProduct'])->name('manager.product.delete');
    Route::post('/manager/product-edit/{id}', [ManagerController::class, 'updateProduct'])->name('manager.product.update');
    Route::post('/manager/profile-edit', [ManagerController::class, 'updateProfile'])->name('manager.profile.update');
    Route::post('/manager/repair-update', [ManagerController::class, 'updateRepair'])->name('manager.repair.update');
    Route::post('/manager/reply-review', [ManagerController::class, 'replyToProductReview'])->name('manager.product.review.reply');
    Route::post('/manager/settings-edit', [ManagerController::class, 'updateSettings'])->name('manager.settings.update');
    Route::get('/manager/warranty-status/{id}', [ManagerController::class, 'updateWarrantyStatus'])->name('manager.warranty.status');

});



    





Route::get('/', [HomeController::class, 'index'])->name('home');

