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
    Route::post('/customer/profile/edit', [CustomerController::class, 'updateProfile'])->name('customer.profile.update');
    Route::get('/customer/cart/reduce/{cartId}', [CustomerController::class, 'reduceQuantity'])->name('customer.cart.reduce');
    Route::get('/customer/cart/remove/{cartId}', [CustomerController::class, 'removeFromCart'])->name('customer.cart.remove');
    Route::post('/customer/repair-add', [CustomerController::class, 'storeRepairRequest'])->name('customer.repair.store');
    Route::get('/customer/repair-cancel/{id}', [CustomerController::class, 'cancelRepairRequest'])->name('customer.repair.cancel');
    Route::get('/customer/repair-decline-parts/{id}', [CustomerController::class, 'declineRepairParts'])->name('customer.repair.decline.parts');
    Route::get('/customer/my-reviews/delete/{id}', [CustomerController::class, 'deleteMyReview'])->name('customer.review.delete.my');
    Route::post('/customer/send-message', [CustomerController::class, 'sendMessage'])->name('customer.support.send');
    Route::post('/customer/submit-warranty', [CustomerController::class, 'submitWarrantyClaim'])->name('customer.warranty.submit');
    Route::get('/customer/wishlist-handle/{id}', [CustomerController::class, 'wishlistHandle'])->name('customer.wishlist.handle');
    Route::get('/customer/auction-history', [CustomerController::class, 'auctionHistory'])->name('customer.auction.history');
    Route::get('/customer/auction-lobby', [CustomerController::class, 'auctionLobby'])->name('customer.auction.lobby');
    Route::get('/customer/auction-view/{id}', [CustomerController::class, 'auctionView'])->name('customer.auction.view');
    Route::get('/customer/builder-select-part', [CustomerController::class, 'builderSelectPart'])->name('customer.builder.select');
    Route::get('/customer/car-builder', [CustomerController::class, 'carBuilder'])->name('customer.builder');
    Route::get('/customer/cart', [CustomerController::class, 'viewCart'])->name('customer.cart');
    Route::get('/customer/car-transaction/{id}', [CustomerController::class, 'carTransaction'])->name('customer.car.transaction');
    Route::get('/customer/car-view/{id}', [CustomerController::class, 'carView'])->name('customer.car.view');
    Route::get('/customer/checkout-review', [CustomerController::class, 'checkoutReview'])->name('customer.checkout.review');
    Route::get('/customer/claim-warranty', [CustomerController::class, 'claimWarrantyForm'])->name('customer.warranty.claim.form');
    Route::get('/customer/contact', [CustomerController::class, 'contactSupport'])->name('customer.contact');
    Route::get('/customer/store', [CustomerController::class, 'store'])->name('customer.store');
    Route::get('/customer/history', [CustomerController::class, 'history'])->name('customer.history');
    Route::get('/customer/dashboard', [CustomerController::class, 'dashboard'])->name('customer.dashboard');
    Route::get('/customer/my-reviews', [CustomerController::class, 'myReviews'])->name('customer.my.reviews');
    Route::get('/customer/order-item/{id}', [CustomerController::class, 'orderItem'])->name('customer.order.item');
    Route::get('/customer/product/{id}', [CustomerController::class, 'viewProduct'])->name('customer.product.view');
    Route::post('/customer/product/{id}/check-stock', [CustomerController::class, 'checkProductStock'])->name('customer.product.check.stock');
    Route::get('/customer/profile', [CustomerController::class, 'profile'])->name('customer.profile');
    Route::get('/customer/profile/edit', [CustomerController::class, 'editProfile'])->name('customer.profile.edit');
    Route::get('/customer/repair-add', [CustomerController::class, 'createRepairForm'])->name('customer.repair.add');
    Route::get('/customer/repair-dashboard', [CustomerController::class, 'repairDashboard'])->name('customer.repair.dashboard');
    Route::get('/customer/repair-transaction/{id}', [CustomerController::class, 'showRepairTransaction'])->name('customer.repair.transaction');
    Route::get('/customer/repair-transaction-process/{id}', [CustomerController::class, 'repairTransaction'])->name('customer.repair.transaction.process');
    Route::get('/customer/repair-view/{id}', [CustomerController::class, 'repairView'])->name('customer.repair.view');
    Route::get('/customer/saved-builds', [CustomerController::class, 'savedBuilds'])->name('customer.saved.builds');
    Route::get('/customer/select-order', [CustomerController::class, 'selectOrder'])->name('customer.warranty.select.order');
    Route::get('/customer/settings', [CustomerController::class, 'settings'])->name('customer.settings');
    Route::get('/customer/showroom', [CustomerController::class, 'showroom'])->name('customer.showroom');
    Route::get('/customer/build-details/{id}', [CustomerController::class, 'viewBuildDetails'])->name('customer.build.details');
    Route::get('/customer/wishlist', [CustomerController::class, 'wishlist'])->name('customer.wishlist');
    Route::get('/customer/orders', [CustomerController::class, 'orders'])->name('customer.orders');
    Route::get('/customer/reviews', [CustomerController::class, 'reviews'])->name('customer.reviews');
    Route::get('/customer/history', [CustomerController::class, 'warrantyManage'])->name('customer.history');
    Route::get('/customer/warranty-claims', [CustomerController::class, 'warrantyManage'])->name('customer.warranty.manage');
    Route::get('/customer/builder', [CustomerController::class, 'builder'])->name('customer.show.builder');
    
    






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

