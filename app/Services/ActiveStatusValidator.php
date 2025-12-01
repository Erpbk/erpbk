<?php

namespace App\Services;

use App\Models\Riders;
use App\Models\Accounts;
use App\Models\Banks;
use App\Models\Customers;
use App\Models\Supplier;
use App\Models\Vendors;
use App\Models\Departments;
use App\Models\Services;
use App\Models\LeasingCompanies;
use Illuminate\Support\Facades\Log;

/**
 * Class ActiveStatusValidator
 * 
 * This service provides centralized validation for checking if entities are active
 * before allowing entries/transactions to be created against them.
 */
class ActiveStatusValidator
{
    /**
     * Validate if a rider is active
     * 
     * @param int $riderId
     * @return array ['valid' => bool, 'message' => string|null]
     */
    public function validateRider($riderId)
    {
        if (empty($riderId)) {
            return ['valid' => true, 'message' => null]; // Allow empty rider (optional field)
        }

        $rider = Riders::find($riderId);

        if (!$rider) {
            return [
                'valid' => false,
                'message' => "Rider with ID {$riderId} not found."
            ];
        }

        if ($rider->status != 1) {
            return [
                'valid' => false,
                'message' => "Cannot create entry for inactive rider: {$rider->name} (Rider ID: {$rider->rider_id}). Please activate the rider first."
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Validate if an account is active
     * 
     * @param int $accountId
     * @return array ['valid' => bool, 'message' => string|null]
     */
    public function validateAccount($accountId)
    {
        if (empty($accountId)) {
            return ['valid' => true, 'message' => null];
        }

        $account = Accounts::find($accountId);

        if (!$account) {
            return [
                'valid' => false,
                'message' => "Account with ID {$accountId} not found."
            ];
        }

        // Check if account is inactive (removed locked account check - locked accounts are allowed)
        if (empty($account->status)) {
            return [
                'valid' => false,
                'message' => "Cannot create entry for inactive account: {$account->name} ({$account->account_code}). Please activate the account first."
            ];
        }

        // If the account is linked to a rider, validate the rider too
        if ($account->ref_name === 'Rider' && !empty($account->ref_id)) {
            return $this->validateRider($account->ref_id);
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Validate if a bank is active
     * 
     * @param int $bankId
     * @return array ['valid' => bool, 'message' => string|null]
     */
    public function validateBank($bankId)
    {
        if (empty($bankId)) {
            return ['valid' => true, 'message' => null];
        }

        $bank = Banks::find($bankId);

        if (!$bank) {
            return [
                'valid' => false,
                'message' => "Bank with ID {$bankId} not found."
            ];
        }

        if (empty($bank->status)) {
            return [
                'valid' => false,
                'message' => "Cannot create entry for inactive bank: {$bank->name} ({$bank->account_no}). Please activate the bank first."
            ];
        }

        // Also validate the bank's account if it exists
        if (!empty($bank->account_id)) {
            return $this->validateAccount($bank->account_id);
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Validate if a customer is active
     * 
     * @param int $customerId
     * @return array ['valid' => bool, 'message' => string|null]
     */
    public function validateCustomer($customerId)
    {
        if (empty($customerId)) {
            return ['valid' => true, 'message' => null];
        }

        $customer = Customers::find($customerId);

        if (!$customer) {
            return [
                'valid' => false,
                'message' => "Customer with ID {$customerId} not found."
            ];
        }

        if (empty($customer->status)) {
            return [
                'valid' => false,
                'message' => "Cannot create entry for inactive customer: {$customer->name}. Please activate the customer first."
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Validate if a supplier is active
     * 
     * @param int $supplierId
     * @return array ['valid' => bool, 'message' => string|null]
     */
    public function validateSupplier($supplierId)
    {
        if (empty($supplierId)) {
            return ['valid' => true, 'message' => null];
        }

        $supplier = Supplier::find($supplierId);

        if (!$supplier) {
            return [
                'valid' => false,
                'message' => "Supplier with ID {$supplierId} not found."
            ];
        }

        if (empty($supplier->status)) {
            return [
                'valid' => false,
                'message' => "Cannot create entry for inactive supplier: {$supplier->name}. Please activate the supplier first."
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Validate if a vendor is active
     * 
     * @param int $vendorId
     * @return array ['valid' => bool, 'message' => string|null]
     */
    public function validateVendor($vendorId)
    {
        if (empty($vendorId)) {
            return ['valid' => true, 'message' => null];
        }

        $vendor = Vendors::find($vendorId);

        if (!$vendor) {
            return [
                'valid' => false,
                'message' => "Vendor with ID {$vendorId} not found."
            ];
        }

        if (empty($vendor->status)) {
            return [
                'valid' => false,
                'message' => "Cannot create entry for inactive vendor: {$vendor->name}. Please activate the vendor first."
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Validate multiple accounts at once
     * 
     * @param array $accountIds
     * @return array ['valid' => bool, 'message' => string|null, 'invalidAccounts' => array]
     */
    public function validateMultipleAccounts(array $accountIds)
    {
        $invalidAccounts = [];

        foreach ($accountIds as $accountId) {
            if (empty($accountId)) {
                continue;
            }

            $result = $this->validateAccount($accountId);
            if (!$result['valid']) {
                $invalidAccounts[] = [
                    'account_id' => $accountId,
                    'message' => $result['message']
                ];
            }
        }

        if (!empty($invalidAccounts)) {
            $messages = array_column($invalidAccounts, 'message');
            return [
                'valid' => false,
                'message' => 'One or more accounts are inactive: ' . implode('; ', $messages),
                'invalidAccounts' => $invalidAccounts
            ];
        }

        return ['valid' => true, 'message' => null, 'invalidAccounts' => []];
    }

    /**
     * Validate entity by type and ID
     * 
     * @param string $entityType (rider, account, bank, customer, supplier, vendor)
     * @param int $entityId
     * @return array ['valid' => bool, 'message' => string|null]
     */
    public function validate($entityType, $entityId)
    {
        switch (strtolower($entityType)) {
            case 'rider':
                return $this->validateRider($entityId);
            case 'account':
                return $this->validateAccount($entityId);
            case 'bank':
                return $this->validateBank($entityId);
            case 'customer':
                return $this->validateCustomer($entityId);
            case 'supplier':
                return $this->validateSupplier($entityId);
            case 'vendor':
                return $this->validateVendor($entityId);
            default:
                Log::warning("Unknown entity type for validation: {$entityType}");
                return ['valid' => true, 'message' => null]; // Allow unknown types
        }
    }

    /**
     * Throw an exception if validation fails
     * 
     * @param string $entityType
     * @param int $entityId
     * @throws \Exception
     */
    public function validateOrFail($entityType, $entityId)
    {
        $result = $this->validate($entityType, $entityId);

        if (!$result['valid']) {
            throw new \Exception($result['message']);
        }
    }
}
