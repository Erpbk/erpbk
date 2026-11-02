<?php

namespace App\Helpers;

use App\Models\Dropdowns;
use App\Models\Settings;
use App\Models\User;
use App\Support\CompanyGlobalPreferences;

class Common
{
    public static function getSetting($setting_name)
    {

        return Settings::where('name', $setting_name)->where('company_id', auth()->user()->company_id)->value('value');
    }

    /*  public static function settings()
     {
       return Settings::all()->pluck('value', 'name');
     } */

    public static function settings($key = null, $default = null)
    {
        $settings = cache()->rememberForever('settings', function () {
            return Settings::where('company_id', auth()->user()->company_id)->pluck('value', 'name')->toArray();
        });

        return $key ? ($settings[$key] ?? $default) : $settings;
    }

    public static function AccountTypes($id = null)
    {
        $types = [
            'Asset' => 'Asset',
            'Liability' => 'Liability',
            'Equity' => 'Equity',
            'Revenue' => 'Revenue',
            'Expense' => 'Expense',

        ];
        if ($id) {
            return $types[$id];
        } else {
            return $types;
        }
    }

    public static function dropdown($items, $selected = null, $parentId = null, $prefix = '')
    {
        $html = '';
        $select = '';
        if (isset($items[$parentId])) {
            foreach ($items[$parentId] as $item) {
                if ($selected) {
                    if ($item->id == $selected) {
                        $select = 'selected';
                    }
                }

                $html .= '<option value="'.$item->id.'" '.$select.'>'.$prefix.$item->name.'</option>';
                $html .= self::dropdown($items, $selected, $item->id, $prefix.'⮞ ');
            }
        }

        return $html;
    }

    public static function DateFormat($date)
    {
        if ($date === null || $date === '') {
            return '';
        }

        $timestamp = strtotime((string) $date);
        if ($timestamp === false) {
            return (string) $date;
        }

        return date(CompanyGlobalPreferences::phpDateFormat(), $timestamp);
    }

    public static function MonthFormat($date)
    {
        if ($date === null || $date === '') {
            return '';
        }

        $timestamp = strtotime((string) $date);
        if ($timestamp === false) {
            return (string) $date;
        }

        $format = CompanyGlobalPreferences::phpDateFormat();
        // Keep month+year only, derived from the company date order.
        $monthFormat = match ($format) {
            'Y-m-d' => 'Y-m',
            'm-d-Y' => 'm-Y',
            default => 'm-Y',
        };

        return date($monthFormat, $timestamp);
    }

    public static function DateTimeFormat($date)
    {
        if ($date === null || $date === '') {
            return '';
        }

        $timestamp = strtotime((string) $date);
        if ($timestamp === false) {
            return (string) $date;
        }

        return date(CompanyGlobalPreferences::phpDateFormat() . ' h:i A', $timestamp);
    }

    public static function Dropdowns($key)
    {
        $dropdown = Dropdowns::where('key', $key)->first();

        // If dropdown doesn't exist, return empty array
        if (! $dropdown) {
            return [];
        }

        $values = json_decode($dropdown->values);

        // If values are empty or invalid, return empty array
        if (! $values || ! is_array($values)) {
            return [];
        }

        return array_combine($values, $values);
    }

    public static function UserName($id)
    {
        $user = User::find($id);
        if ($user) {
            return $user->name;
        }
    }

    public static function status($status)
    {
        if ($status == 1) {
            return '<span class="badge bg-label-success rounded-0">Active</span>';
        } elseif ($status == 2) {
            return '<span class="badge bg-label-danger rounded-0">Inactive</span>';
        } else {
            return '<span class="badge bg-label-warning rounded-0">pending</span>';
        }
    }
}
