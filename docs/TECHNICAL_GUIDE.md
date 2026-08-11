# Technical Guide

## 1. Stack

- Laravel 13
- PHP 8.3
- Filament admin panel
- PostgreSQL
- D365 Finance & Operations integration via OData
- Spatie Permission

## 2. Core domain

Main model:
- `App\Models\ItemCreationRequest`

Supporting models:
- `App\Models\ItemCreationRequestStatusLog`
- `App\Models\D365ItemGroup`
- `App\Models\D365ItemModelGroup`
- `App\Models\D365ItemServiceCategory`
- `App\Models\NumberSequence`
- `App\Models\User`

## 3. Workflow summary

### Submission
- `CreateItemCreationRequest` sets `requested_by` and `status = pending`
- observer writes first status log
- accounting/commercial users get notification

### Accounting
- `ItemCreationRequestResource::getClassifyAction()` sets classification fields and status `classified`
- `getNeedsInfoAction()` sets `status = needs_info` and stores clarification in `info_request_note`
- `getRejectAction()` sets `status = rejected`

### Requester response
- `EditItemCreationRequest::mutateFormDataBeforeSave()` changes `needs_info` back to `pending`
- request form includes `requester_response_note`
- observer copies response into history note and status log row

### Commercial / D365
- `CreateReleasedProductInD365` job sends request to D365
- `D365ODataClient` handles OAuth token + OData calls
- success/failure updates request state

## 4. D365 sync

### Item groups
- Command: `php artisan d365:sync-item-groups`
- Source entity set: `InventItemGroupCDREntities`
- Local table: `d365_item_groups`

### Model groups
- Command exists: `SyncD365ItemModelGroups`
- Local table: `d365_item_model_groups`

## 5. Important fields

### On item_creation_requests
- `info_request_note` = Accounting clarification
- `requester_response_note` = requester answer
- `rejection_reason` = rejection text
- `assigned_item_number` = D365 item number
- `sync_error` = D365 failure detail

### On status logs
- `note` = audit context
- `requester_response_note` = saved answer for `needs_info` response

## 6. Notifications

Seen notification classes:
- `NewItemCreationRequestNotification`
- `ItemReadyForCommercialNotification`
- `ItemResubmittedNotification`
- `ItemWorkflowNotifier`

## 7. Console commands

- `d365:sync-item-groups`
- `d365:sync-item-model-groups`
- `SendAgingItemRequestsDigest`

## 8. Queue / jobs

- `CreateReleasedProductInD365` is dispatched async
- polling in list page checks status `creating`

## 9. Migrations needed for current UX

If you pulled latest docs/code, run:

```bash
php artisan migrate
```

This adds:
- `item_creation_requests.requester_response_note`
- `item_creation_request_status_logs.requester_response_note`

## 10. How to run locally

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
npm install
npm run dev
```

## 11. Troubleshooting

### Undefined column errors
- Migration missing
- Run `php artisan migrate`

### No D365 data
- Check `config/services.php`
- Check env values for D365 tenant/client/resource URL

### Notifications not sent
- Check role names exist: `accounting`, `commercial`
- Check queue worker if notifications/jobs are queued
