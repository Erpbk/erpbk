# Graph Report - app\Http\Controllers  (2026-07-31)

## Corpus Check
- 130 files · ~169,346 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2240 nodes · 5365 edges · 104 communities (65 shown, 39 thin omitted)
- Extraction: 95% EXTRACTED · 5% INFERRED · 0% AMBIGUOUS · INFERRED: 252 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `15636192`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- AgreementSettingsController
- RolePermissionController
- SalikController
- BikeMaintenanceController
- RiderSettingsController
- EmployeeSettingsController
- UserController
- SimsController
- RiderInventoryController
- SupplierController
- App\Traits\GlobalPagination
- BikesController
- BikeSettingsController
- RiderInvoicesController
- EmployeeController
- Illuminate\Http\Request
- Controller
- FixedAssetController
- BikeRegistrationController
- LeasingCompaniesController
- LoansController
- ChequesSettingsController
- RiderActivitiesController
- ModuleSettingsController
- AccountsController
- CustomersController
- AdminPermissionsController
- LegalCaseController
- ExpenseController
- LicenseexpenseController
- VisaexpenseController
- AdminGlobalAccountsController
- ManagesVisaInstallments.php
- BikeRentCompaniesController
- VatController
- .vouchers
- App\Http\Controllers\Concerns\SavesModuleMenuIcons
- BanksController
- CompanyRegistrationController
- Employee
- RidersController
- FuelCompaniesController
- RecruitersController
- RtaFinesController
- AttendanceController
- .findAccessibleRider
- VatSettingsController
- AdminUsersController
- TrashController
- BikeRegistrationStatusController
- AdminCompaniesController
- App\Traits\HasTrashFunctionality
- Cheques
- DepartmentsController
- DropdownsController
- FilesController
- VendorsController
- ModuleTopBarSettingsController
- EmployeeInvoicesController
- DeleteRequestsController
- FuelDataController
- LicenseStatusController
- BikeHistoryController
- GaragesController
- RiderAttendanceController
- LeasingCompanyBillingInvoicesController
- Receipt
- .index
- .settings
- LegalCaseStatusController
- VisaStatusController
- RiderEmailsController
- FuelCardController
- Riders
- App\DataTables\LedgerDataTable
- Illuminate\Http\JsonResponse
- ReportController
- RiderInvoiceTemplateSettingsController
- ActivityLogController
- AdminBlogsController
- AdminTestimonialsController
- ChequesSettingsController.php
- CustomerInvoiceItemController
- InventoryAdjustmentController
- CustomerInvoicesController
- AccountsReportController
- ItemCategoriesController
- riderhiringController
- SimSettingsController
- .riderInvoiceAccountChildren
- VisaRenewalCategoryController
- ChequeTopCategory
- AdminPolicyController
- EmployeeController.php
- FileController
- DashboardSettingsController
- UserEmailSettingsController
- SettingsPanelController
- App\DataTables\RiderAttendanceDataTable
- App\Repositories\RidersRepository

## God Nodes (most connected - your core abstractions)
1. `Controller` - 104 edges
2. `RidersController` - 83 edges
3. `AppBaseController` - 76 edges
4. `SalikController` - 65 edges
5. `ChequesSettingsController` - 59 edges
6. `EmployeeSettingsController` - 58 edges
7. `RiderSettingsController` - 58 edges
8. `EmployeeController` - 52 edges
9. `BikeSettingsController` - 48 edges
10. `ModuleSettingsController` - 47 edges

## Surprising Connections (you probably didn't know these)
- `AccountFieldSettingsController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AccountFieldSettingsController.php → app/Http/Controllers/Controller.php
- `AccountsController` --inherits--> `AppBaseController`  [EXTRACTED]
  app/Http/Controllers/AccountsController.php → app/Http/Controllers/AppBaseController.php
- `AccountsReportController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AccountsReportController.php → app/Http/Controllers/Controller.php
- `ActivityLogController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/ActivityLogController.php → app/Http/Controllers/Controller.php
- `AdminBlogsController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Admin/AdminBlogsController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (104 total, 39 thin omitted)

### Community 0 - "AgreementSettingsController"
Cohesion: 0.07
Nodes (14): AgreementGenerationController, AgreementTemplate, AgreementSettingsController, AgreementCategory, AgreementTemplate, App\Models\AgreementCategory, App\Models\AgreementTemplate, App\Services\Agreements\AgreementModuleService (+6 more)

### Community 1 - "RolePermissionController"
Cohesion: 0.06
Nodes (14): AdminErpPermissionsController, App\DataTables\PermissionsDataTable, App\DataTables\RolesDataTable, App\Repositories\PermissionsRepository, App\Repositories\RolesRepository, Illuminate\Support\Collection, Permission, PermissionsController (+6 more)

### Community 2 - "SalikController"
Cohesion: 0.07
Nodes (7): App\Http\Requests\StoreSalikTopUpRequest, App\Models\salik, App\Repositories\SalikRepository, App\Services\SalikTopUpService, Bikes, salik, SalikController

### Community 3 - "BikeMaintenanceController"
Cohesion: 0.06
Nodes (15): App\Http\Requests\CreateItemsRequest, App\Http\Requests\UpdateItemsRequest, App\Models\BikeMaintenance, App\Models\Bikes, App\Models\Garages, App\Models\InventoryPurchase, App\Repositories\ItemsRepository, BikeMaintenance (+7 more)

### Community 4 - "RiderSettingsController"
Cohesion: 0.06
Nodes (5): App\Models\RiderCategory, App\Models\RiderTopCategory, RiderCategory, RiderSettingsController, RiderTopCategory

### Community 5 - "EmployeeSettingsController"
Cohesion: 0.07
Nodes (3): EmployeeCategory, EmployeeSettingsController, EmployeeTopCategory

### Community 6 - "UserController"
Cohesion: 0.08
Nodes (13): App\DataTables\UserDataTable, App\Http\Requests\CreateUserRequest, App\Http\Requests\StoreEmailAccountRequest, App\Http\Requests\UpdateEmailAccountRequest, App\Http\Requests\UpdateUserRequest, App\Models\Branch, App\Models\EmailAccount, App\Repositories\UserRepository (+5 more)

### Community 7 - "SimsController"
Cohesion: 0.06
Nodes (9): App\Http\Requests\UpdateSimsRequest, App\Models\SimHistory, App\Repositories\SimInvoicesRepository, App\Repositories\SimsRepository, SimHistory, SimHistoryController, SimInvoicesController, Sims (+1 more)

### Community 8 - "RiderInventoryController"
Cohesion: 0.09
Nodes (5): App\Models\RiderInventoryAssignment, RiderInventoryAssignment, RiderInventoryController, RiderInventoryItemController, RiderInventoryReportController

### Community 9 - "SupplierController"
Cohesion: 0.06
Nodes (11): App\Http\Requests\CreateGarageItemRequest, App\Http\Requests\CreateSupplierInvoicesRequest, App\Http\Requests\UpdateGarageItemRequest, App\Http\Requests\UpdateSupplierInvoicesRequest, App\Repositories\SupplierInvoicesRepository, App\Repositories\SuppliersRepository, App\Services\GarageItemService, GarageItemsController (+3 more)

### Community 10 - "App\Traits\GlobalPagination"
Cohesion: 0.11
Nodes (15): App\Http\Controllers\Concerns\AppliesModuleTopBarFilters, App\Http\Controllers\Concerns\ManagesVisaInstallments, App\Http\Requests\CreateAccountsRequest, App\Http\Requests\UpdateAccountsRequest, App\Models\Items, App\Models\RiderInvoices, App\Models\Riders, App\Models\Sims (+7 more)

### Community 11 - "BikesController"
Cohesion: 0.09
Nodes (6): App\Http\Requests\CreateBikesRequest, App\Http\Requests\UpdateBikesRequest, App\Repositories\BikesRepository, BikesController, Bikes, Riders

### Community 12 - "BikeSettingsController"
Cohesion: 0.09
Nodes (3): App\Models\BikeCategory, BikeCategory, BikeSettingsController

### Community 13 - "RiderInvoicesController"
Cohesion: 0.08
Nodes (7): App\Http\Requests\CreateRiderInvoicesRequest, App\Http\Requests\UpdateRiderInvoicesRequest, App\Repositories\PaymentsRepository, App\Repositories\RiderInvoicesRepository, PaymentController, RiderInvoices, RiderInvoicesController

### Community 14 - "EmployeeController"
Cohesion: 0.12
Nodes (3): App\Models\Employee, EmployeeController, Unique

### Community 15 - "Illuminate\Http\Request"
Cohesion: 0.07
Nodes (6): saveModuleDisplayLabel(), saveModuleMenuIcons(), ErpSettingsController, FuelCardHistoryController, Illuminate\Http\Request, VoucherSettingsController

### Community 16 - "Controller"
Cohesion: 0.07
Nodes (13): AdminAuthBrandingController, AdminLoginController, LoginBasic, RegisterBasic, BikesImportController, CompanyEmailChangeController, Controller, Illuminate\Foundation\Auth\Access\AuthorizesRequests (+5 more)

### Community 17 - "FixedAssetController"
Cohesion: 0.14
Nodes (10): App\Models\AssetCategory, App\Models\FixedAsset, App\Services\FixedAssets\AssetCategoryAccountService, App\Services\FixedAssets\DepreciationScheduleService, App\Services\FixedAssets\FixedAssetDepreciationPostingService, App\Services\FixedAssets\FixedAssetVoucherService, AssetCategory, AssetCategoryController (+2 more)

### Community 18 - "BikeRegistrationController"
Cohesion: 0.14
Nodes (6): App\Models\BikeRegistration, App\Models\BikeRegistrationAccount, App\Repositories\BikeRegistrationsRepository, BikeRegistration, BikeRegistrationAccount, BikeRegistrationController

### Community 19 - "LeasingCompaniesController"
Cohesion: 0.08
Nodes (5): App\Http\Requests\CreateLeasingCompaniesRequest, App\Http\Requests\UpdateLeasingCompaniesRequest, App\Repositories\LeasingCompaniesRepository, App\Repositories\LeasingCompanyInvoicesRepository, LeasingCompaniesController

### Community 20 - "LoansController"
Cohesion: 0.12
Nodes (8): App\Http\Requests\CreateLoanRequest, App\Http\Requests\UpdateLoanRequest, App\Models\Loan, App\Repositories\LoanRepository, App\Services\LoanAmortizationService, App\Services\LoanVoucherService, Loan, LoansController

### Community 22 - "RiderActivitiesController"
Cohesion: 0.10
Nodes (8): App\Http\Requests\CreateRiderActivitiesRequest, App\Http\Requests\UpdateRiderActivitiesRequest, App\Repositories\RiderActivitiesRepository, App\Services\RiderActivities\RiderActivityImportMappingService, RiderActivityImportMappingService, RiderActivitiesController, RiderActivityImportMappingService, RiderActivityImportSettingsController

### Community 24 - "AccountsController"
Cohesion: 0.15
Nodes (4): AccountsController, Accounts, App\DataTables\AccountsDataTable, App\Models\Accounts

### Community 25 - "CustomersController"
Cohesion: 0.11
Nodes (5): App\DataTables\FilesDataTable, App\Http\Requests\CreateCustomersRequest, App\Http\Requests\UpdateCustomersRequest, App\Repositories\CustomersRepository, CustomersController

### Community 26 - "AdminPermissionsController"
Cohesion: 0.15
Nodes (5): AdminPermissionsController, AdminRolesController, AdminPermission, App\Models\AdminPermission, App\Models\AdminRole

### Community 27 - "LegalCaseController"
Cohesion: 0.13
Nodes (6): App\Http\Requests\StoreLegalCaseRequest, App\Http\Requests\UpdateLegalCaseRequest, App\Models\LegalCaseAccount, App\Repositories\LegalCasesRepository, LegalCaseAccount, LegalCaseController

### Community 28 - "ExpenseController"
Cohesion: 0.15
Nodes (3): ExpenseController, Accounts, Collection

### Community 29 - "LicenseexpenseController"
Cohesion: 0.15
Nodes (3): App\Repositories\LicenseExpensesRepository, LicenseexpenseController, ExpenseAccount

### Community 31 - "AdminGlobalAccountsController"
Cohesion: 0.23
Nodes (7): AdminGlobalAccountsController, Accounts, App\Models\GlobalAccount, App\Repositories\AccountsRepository, App\Services\GlobalAccountResolver, GlobalAccount, Illuminate\View\View

### Community 32 - "ManagesVisaInstallments.php"
Cohesion: 0.20
Nodes (21): App\Models\ExpenseAccount, applyInstallmentRiderScope(), autoMarkInstallmentsAsPaid(), checkAndAutoMarkInstallments(), cleanupInstallmentLedgerAfterDelete(), createInstallmentPlan(), createInstallmentPlanForm(), deleteInstallment() (+13 more)

### Community 33 - "BikeRentCompaniesController"
Cohesion: 0.13
Nodes (4): App\Http\Requests\CreateBikeRentCompaniesRequest, App\Http\Requests\UpdateBikeRentCompaniesRequest, App\Repositories\BikeRentCompaniesRepository, BikeRentCompaniesController

### Community 34 - "VatController"
Cohesion: 0.16
Nodes (3): App\Models\VatReturn, VatController, VatReturn

### Community 36 - "App\Http\Controllers\Concerns\SavesModuleMenuIcons"
Cohesion: 0.12
Nodes (4): AccountFieldSettingsController, App\Http\Controllers\Concerns\SavesModuleDisplayLabel, App\Http\Controllers\Concerns\SavesModuleMenuIcons, ModuleMenuIconController

### Community 37 - "BanksController"
Cohesion: 0.13
Nodes (7): App\Http\Requests\CreateBanksRequest, App\Http\Requests\UpdateBanksRequest, App\Models\Cheques, App\Models\Receipt, App\Repositories\BanksRepository, BanksController, Response

### Community 38 - "CompanyRegistrationController"
Cohesion: 0.13
Nodes (5): App\Models\Company, CompanyAuthController, Company, CompanyRegistrationController, Company

### Community 41 - "FuelCompaniesController"
Cohesion: 0.15
Nodes (6): App\Http\Requests\CreateFuelCompaniesRequest, App\Http\Requests\StoreFuelCompanyTopUpRequest, App\Http\Requests\UpdateFuelCompaniesRequest, App\Repositories\FuelCompaniesRepository, App\Services\FuelCompanyTopUpService, FuelCompaniesController

### Community 42 - "RecruitersController"
Cohesion: 0.13
Nodes (4): App\Http\Requests\CreateRecruitersRequest, App\Http\Requests\UpdateRecruitersRequest, App\Repositories\RecruitersRepository, RecruitersController

### Community 43 - "RtaFinesController"
Cohesion: 0.14
Nodes (3): App\Http\Requests\CreateRtaFinesRequest, App\Repositories\RtaFinesRepository, RtaFinesController

### Community 44 - "AttendanceController"
Cohesion: 0.19
Nodes (3): App\Models\Attendance, Attendance, AttendanceController

### Community 47 - "AdminUsersController"
Cohesion: 0.20
Nodes (6): AdminDashboardController, AdminRole, AdminUsersController, AdminUser, App\DataTables\AdminUserDataTable, App\Models\AdminUser

### Community 48 - "TrashController"
Cohesion: 0.18
Nodes (5): App\Models\FuelData, applyModuleTopBarFilters(), moduleTopBarListingData(), Illuminate\Database\Eloquent\Builder, TrashController

### Community 50 - "AdminCompaniesController"
Cohesion: 0.28
Nodes (5): AdminCompaniesController, Company, AdminCompany, App\Models\AdminCompany, App\Services\FixedAssets\VehiclesCategoryService

### Community 51 - "App\Traits\HasTrashFunctionality"
Cohesion: 0.17
Nodes (5): App\Http\Requests\CreateSimCompaniesRequest, App\Http\Requests\UpdateSimCompaniesRequest, App\Repositories\SimCompaniesRepository, App\Traits\HasTrashFunctionality, SimCompaniesController

### Community 52 - "Cheques"
Cohesion: 0.20
Nodes (3): App\Services\Cheques\ChequeTopDateFilterService, Cheques, ChequesController

### Community 53 - "DepartmentsController"
Cohesion: 0.23
Nodes (5): App\DataTables\DepartmentsDataTable, App\Http\Requests\CreateDepartmentsRequest, App\Http\Requests\UpdateDepartmentsRequest, App\Repositories\DepartmentsRepository, DepartmentsController

### Community 54 - "DropdownsController"
Cohesion: 0.22
Nodes (5): App\DataTables\DropdownsDataTable, App\Http\Requests\CreateDropdownsRequest, App\Http\Requests\UpdateDropdownsRequest, App\Repositories\DropdownsRepository, DropdownsController

### Community 55 - "FilesController"
Cohesion: 0.20
Nodes (4): App\Http\Requests\CreateFilesRequest, App\Http\Requests\UpdateFilesRequest, App\Repositories\FilesRepository, FilesController

### Community 56 - "VendorsController"
Cohesion: 0.17
Nodes (4): App\Http\Requests\CreateVendorsRequest, App\Http\Requests\UpdateVendorsRequest, App\Repositories\VendorsRepository, VendorsController

### Community 58 - "EmployeeInvoicesController"
Cohesion: 0.18
Nodes (4): App\Http\Requests\CreateEmployeeInvoicesRequest, App\Http\Requests\UpdateEmployeeInvoicesRequest, App\Repositories\EmployeeInvoicesRepository, EmployeeInvoicesController

### Community 59 - "DeleteRequestsController"
Cohesion: 0.20
Nodes (5): App\Models\DeleteRequest, App\Models\UserNotification, DeleteRequest, DeleteRequestsController, UserNotification

### Community 62 - "BikeHistoryController"
Cohesion: 0.21
Nodes (4): App\Http\Requests\CreateBikeHistoryRequest, App\Http\Requests\UpdateBikeHistoryRequest, App\Repositories\BikeHistoryRepository, BikeHistoryController

### Community 63 - "GaragesController"
Cohesion: 0.22
Nodes (4): App\Http\Requests\CreateGaragesRequest, App\Http\Requests\UpdateGaragesRequest, App\Repositories\GaragesRepository, GaragesController

### Community 64 - "RiderAttendanceController"
Cohesion: 0.21
Nodes (4): App\Http\Requests\CreateRiderAttendanceRequest, App\Http\Requests\UpdateRiderAttendanceRequest, App\Repositories\RiderAttendanceRepository, RiderAttendanceController

### Community 66 - "Receipt"
Cohesion: 0.28
Nodes (3): App\Repositories\ReceiptsRepository, Receipt, ReceiptController

### Community 71 - "RiderEmailsController"
Cohesion: 0.23
Nodes (4): App\Http\Requests\CreateRiderEmailsRequest, App\Http\Requests\UpdateRiderEmailsRequest, App\Repositories\RiderEmailsRepository, RiderEmailsController

### Community 75 - "Illuminate\Http\JsonResponse"
Cohesion: 0.29
Nodes (4): App\Services\Deploy\DeployRunner, DeployWebhookController, Illuminate\Http\JsonResponse, UserTableSettingsController

### Community 78 - "ActivityLogController"
Cohesion: 0.29
Nodes (3): ActivityLog, ActivityLogController, App\Models\ActivityLog

### Community 79 - "AdminBlogsController"
Cohesion: 0.31
Nodes (3): AdminBlogsController, AdminBlog, App\Models\AdminBlog

### Community 80 - "AdminTestimonialsController"
Cohesion: 0.29
Nodes (3): AdminTestimonialsController, AdminTestimonial, App\Models\AdminTestimonial

### Community 81 - "ChequesSettingsController.php"
Cohesion: 0.20
Nodes (3): App\Models\ChequeCategory, App\Models\ChequeFieldCategoryAssignment, App\Models\ChequeTopCategory

### Community 94 - "EmployeeController.php"
Cohesion: 0.40
Nodes (3): App\Models\EmployeeCategory, App\Models\EmployeeTopCategory, Illuminate\Database\UniqueConstraintViolationException

## Knowledge Gaps
- **39 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Controller` connect `Controller` to `AgreementSettingsController`, `RolePermissionController`, `BikeMaintenanceController`, `RiderSettingsController`, `EmployeeSettingsController`, `UserController`, `SimsController`, `RiderInventoryController`, `App\Traits\GlobalPagination`, `BikeSettingsController`, `RiderInvoicesController`, `EmployeeController`, `Illuminate\Http\Request`, `FixedAssetController`, `ChequesSettingsController`, `RiderActivitiesController`, `ModuleSettingsController`, `AdminPermissionsController`, `AdminGlobalAccountsController`, `VatController`, `.vouchers`, `App\Http\Controllers\Concerns\SavesModuleMenuIcons`, `CompanyRegistrationController`, `AttendanceController`, `VatSettingsController`, `AdminUsersController`, `TrashController`, `BikeRegistrationStatusController`, `AdminCompaniesController`, `Cheques`, `ModuleTopBarSettingsController`, `DeleteRequestsController`, `FuelDataController`, `LicenseStatusController`, `Receipt`, `.settings`, `LegalCaseStatusController`, `VisaStatusController`, `FuelCardController`, `App\DataTables\LedgerDataTable`, `Illuminate\Http\JsonResponse`, `ReportController`, `RiderInvoiceTemplateSettingsController`, `ActivityLogController`, `AdminBlogsController`, `AdminTestimonialsController`, `CustomerInvoiceItemController`, `InventoryAdjustmentController`, `CustomerInvoicesController`, `AccountsReportController`, `ItemCategoriesController`, `riderhiringController`, `SimSettingsController`, `VisaRenewalCategoryController`, `AdminPolicyController`, `FileController`, `DashboardSettingsController`, `UserEmailSettingsController`, `SettingsPanelController`?**
  _High betweenness centrality (0.156) - this node is a cross-community bridge._
- **Why does `AppBaseController` connect `App\Traits\GlobalPagination` to `RolePermissionController`, `SalikController`, `BikeMaintenanceController`, `UserController`, `SimsController`, `RiderInventoryController`, `SupplierController`, `BikesController`, `RiderInvoicesController`, `Controller`, `FixedAssetController`, `BikeRegistrationController`, `LeasingCompaniesController`, `LoansController`, `RiderActivitiesController`, `AccountsController`, `CustomersController`, `LegalCaseController`, `ExpenseController`, `LicenseexpenseController`, `VisaexpenseController`, `BikeRentCompaniesController`, `BanksController`, `Employee`, `RidersController`, `FuelCompaniesController`, `RecruitersController`, `RtaFinesController`, `App\Traits\HasTrashFunctionality`, `DepartmentsController`, `DropdownsController`, `FilesController`, `VendorsController`, `EmployeeInvoicesController`, `BikeHistoryController`, `GaragesController`, `RiderAttendanceController`, `LeasingCompanyBillingInvoicesController`, `RiderEmailsController`?**
  _High betweenness centrality (0.136) - this node is a cross-community bridge._
- **Why does `RiderSettingsController` connect `RiderSettingsController` to `Controller`, `App\Http\Controllers\Concerns\SavesModuleMenuIcons`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Should `AgreementSettingsController` be split into smaller, more focused modules?**
  _Cohesion score 0.06793206793206794 - nodes in this community are weakly interconnected._
- **Should `RolePermissionController` be split into smaller, more focused modules?**
  _Cohesion score 0.0635814889336016 - nodes in this community are weakly interconnected._
- **Should `SalikController` be split into smaller, more focused modules?**
  _Cohesion score 0.06603346901854365 - nodes in this community are weakly interconnected._
- **Should `BikeMaintenanceController` be split into smaller, more focused modules?**
  _Cohesion score 0.05687645687645688 - nodes in this community are weakly interconnected._