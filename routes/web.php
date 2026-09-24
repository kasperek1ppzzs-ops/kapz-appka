<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\TravelPlanController;
use App\Http\Controllers\TravelOrderController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\BookReportController;
use App\Http\Controllers\ActivityReportController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\Admin\AdminPeriodController;
use App\Http\Controllers\Admin\AdminManagementController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Attendance Routes
    Route::get('/attendance/kapz', [AttendanceController::class, 'showKapzAttendance'])->name('attendance.kapz');
    Route::post('/attendance/kapz/save', [AttendanceController::class, 'saveKapzAttendance'])->name('attendance.kapz.save');
    Route::post('/attendance/kapz/quick-fill', [AttendanceController::class, 'quickFillKapzMonth'])->name('attendance.kapz.quick_fill');
    Route::get('/attendance/kapz/pdf', [AttendanceController::class, 'downloadKapzPdf'])->name('attendance.kapz.pdf');

    Route::get('/attendance/apz', [AttendanceController::class, 'showApzAttendance'])->name('attendance.apz');
    Route::post('/attendance/apz/save', [AttendanceController::class, 'saveApzAttendance'])->name('attendance.apz.save');
    Route::post('/attendance/apz/quick-fill', [AttendanceController::class, 'quickFillApzMonth'])->name('attendance.apz.quick_fill');
    Route::post('/attendance/apz/quick-fill-all', [AttendanceController::class, 'quickFillAllApzMonth'])->name('attendance.apz.quick_fill_all');
    Route::get('/attendance/apz/pdf', [AttendanceController::class, 'downloadApzPdf'])->name('attendance.apz.pdf');
    Route::get('/attendance/apz/all-pdf', [AttendanceController::class, 'downloadAllApzPdf'])->name('attendance.apz.all_pdf');

    // Travel Planning & Workflow Routes
    Route::get('/travel', [TravelPlanController::class, 'index'])->name('travel.index');
    Route::post('/travel/add-item', [TravelPlanController::class, 'addItem'])->name('travel.add_item');
    Route::post('/travel/items/{item}/update', [TravelPlanController::class, 'updateItem'])->name('travel.update_item');
    Route::delete('/travel/items/{item}', [TravelPlanController::class, 'deleteItem'])->name('travel.delete_item');
    Route::post('/travel/submit', [TravelPlanController::class, 'submit'])->name('travel.submit');
    Route::post('/travel/approve', [TravelPlanController::class, 'approve'])->name('travel.approve');
    Route::post('/travel/return', [TravelPlanController::class, 'returnPlan'])->name('travel.return');
    Route::get('/travel/pdf/{plan}', [TravelPlanController::class, 'downloadPdf'])->name('travel.pdf');
    Route::get('/travel/calculate-distance', [TravelPlanController::class, 'calculateDistance'])->name('travel.calculate_distance');

    // Travel Orders & Settlements
    Route::get('/travel/orders', [TravelOrderController::class, 'index'])->name('travel.orders');
    Route::get('/travel/orders/{order}', [TravelOrderController::class, 'show'])->name('travel.orders.show');
    Route::get('/travel/orders/{order}/pdf', [TravelOrderController::class, 'downloadPdf'])->name('travel.orders.pdf');

    // Calendar & Tasks Module
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/tasks', [CalendarController::class, 'storeTask'])->name('calendar.tasks.store');
    Route::post('/calendar/tasks/{task}/status', [CalendarController::class, 'updateStatus'])->name('calendar.tasks.update_status');
    Route::delete('/calendar/tasks/{task}', [CalendarController::class, 'deleteTask'])->name('calendar.tasks.delete');

    // Statements (Prehlásenia)
    Route::get('/statements', [StatementController::class, 'index'])->name('statements.index');
    Route::post('/statements/{declaration}/save', [StatementController::class, 'save'])->name('statements.save');
    Route::post('/statements/{declaration}/quick-fill-no', [StatementController::class, 'quickFillAllNo'])->name('statements.quick_fill_no');
    Route::get('/statements/{declaration}/pdf', [StatementController::class, 'downloadPdf'])->name('statements.pdf');

    // KNIHY (Kniha príchodov a odchodov)
    Route::get('/knihy', [BookReportController::class, 'index'])->name('knihy.index');
    Route::post('/knihy/{book}/save', [BookReportController::class, 'save'])->name('knihy.save');
    Route::post('/knihy/{book}/sync', [BookReportController::class, 'sync'])->name('knihy.sync');
    Route::get('/knihy/all-pdf', [BookReportController::class, 'downloadAllPdf'])->name('knihy.all_pdf');
    Route::get('/knihy/{book}/pdf', [BookReportController::class, 'downloadPdf'])->name('knihy.pdf');

    // Activity Reports (Správa o činnosti)
    Route::get('/activity-reports', [ActivityReportController::class, 'index'])->name('activity_reports.index');
    Route::get('/activity-reports/{report}/pdf', [ActivityReportController::class, 'downloadPdf'])->name('activity_reports.pdf');

    // Reports & CSV Export
    Route::get('/reports', [ReportingController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-csv', [ReportingController::class, 'exportCsv'])->name('reports.export_csv');

    // Contacts Directory (Zoznam kontaktov)
    Route::get('/contacts', [\App\Http\Controllers\ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/pdf', [\App\Http\Controllers\ContactController::class, 'downloadPdf'])->name('contacts.pdf');
    Route::get('/contacts/export-csv', [\App\Http\Controllers\ContactController::class, 'exportCsv'])->name('contacts.export_csv');

    // Admin Routes
    Route::post('/admin/period/{period}/toggle-lock', [AdminPeriodController::class, 'togglePeriodLock'])->name('admin.period.toggle_lock');
    Route::get('/admin/kapz', [AdminManagementController::class, 'listKapz'])->name('admin.kapz.index');
    Route::get('/admin/apz', [AdminManagementController::class, 'listApz'])->name('admin.apz.index');
    Route::get('/admin/assignments', [AdminManagementController::class, 'listAssignments'])->name('admin.assignments.index');
    Route::post('/admin/assignments', [AdminManagementController::class, 'storeAssignment'])->name('admin.assignments.store');
    Route::get('/admin/audit-logs', [AdminManagementController::class, 'listAuditLogs'])->name('admin.audit_logs');
});
