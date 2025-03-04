<?php
namespace App\Api;


class Permissions {
    const MANAGE_PRODUCTS = 'manageProducts';      
    const VIEW_PRODUCTS = 'viewProducts';          
    
    const MANAGE_PLATFORM = 'managePlatform';      
    const MANAGE_APPEARANCE = 'manageAppearance';    
    
    const MANAGE_USERS = 'manageUsers';            
    const VIEW_USERS = 'viewUsers';                
    
    const UPDATE_PROFILE = 'updateProfile';         
    const CHANGE_PASSWORD = 'changePassword';       
    
    const MANAGE_CART = 'manageCart';
    const CHECKOUT = 'checkout';                    
    
    const MANAGE_ORDERS = 'manageOrders';
    const UPDATE_ORDER_STATUS = 'updateOrderStatus';
    const CREATE_ORDER = 'createOrder';
    const SEARCH_ORDERS = 'searchOrders';
    const VIEW_ORDERS = 'viewOrders';

    const MANAGE_VOUCHERS = 'manageVouchers';
    const VALIDATE_VOUCHER = 'validateVoucher';
    const GENERATE_VOUCHER = 'generateVoucher';
    const GET_VOUCHERS = 'getVouchers';
    const DELETE_VOUCHER = 'deleteVoucher';
    const UPDATE_VOUCHER = 'updateVoucher';

    public static function getAllPermissions(): array {
        return [
            self::MANAGE_PRODUCTS,
            self::VIEW_PRODUCTS,
            self::MANAGE_PLATFORM,
            self::MANAGE_APPEARANCE,
            self::MANAGE_USERS,
            self::VIEW_USERS,
            self::UPDATE_PROFILE,
            self::CHANGE_PASSWORD,
            self::MANAGE_CART,
            self::CHECKOUT,
            self::MANAGE_ORDERS,
            self::UPDATE_ORDER_STATUS,
            self::CREATE_ORDER,
            self::SEARCH_ORDERS,
            self::VIEW_ORDERS,
            self::MANAGE_VOUCHERS,
            self::VALIDATE_VOUCHER,
            self::GENERATE_VOUCHER,
            self::GET_VOUCHERS,
            self::DELETE_VOUCHER,
            self::UPDATE_VOUCHER
        ];
    }
} 