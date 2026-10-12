@extends('layouts.app')

@section('title', 'Payment Cancelled')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-[#0A0C10]/90 flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full bg-white dark:bg-[#11131A] rounded-3xl shadow-2xl p-8 text-center border border-yellow-500/20">
        
        <div class="w-24 h-24 bg-yellow-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-12 h-12 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>

        <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Payment Cancelled</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-1">Your payment was not completed.</p>
        
        <div class="mt-6 p-4 bg-gray-50 dark:bg-[#1A1D26] rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">Order Number</span>
                <span class="text-sm font-bold text-gray-800 dark:text-white">#{{ $order->order_number }}</span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Total Amount</span>
                <span class="text-sm font-bold text-[#d2964b]">₱{{ number_format($order->total_amount, 2) }}</span>
            </div>
        </div>

        <div class="mt-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl">
            <p class="text-sm text-yellow-700 dark:text-yellow-400">
                <i class="fa fa-exclamation-circle mr-2"></i>
                Your payment was cancelled. You can try again or use a different payment method.
            </p>
        </div>

        <div class="mt-8 space-y-3">
            <a href="{{ route('customer.shopmenu', ['tenant' => $order->tenant_id, 'branch' => $order->branch_id ?? 0]) }}" 
               class="flex items-center justify-center w-full px-6 py-3 bg-gradient-to-r from-[#d2964b] to-[#8b5a2b] text-white rounded-xl font-bold text-sm hover:shadow-lg transition-all">
                <i class="fa fa-mug-hot mr-2"></i> Start a New Order
            </a>
            <a href="{{ route('customer.orders') }}" 
               class="flex items-center justify-center w-full px-6 py-3 bg-gray-200 dark:bg-[#1A1D26] text-gray-700 dark:text-gray-300 rounded-xl font-bold text-sm hover:bg-gray-300 dark:hover:bg-[#2A2D36] transition-all">
                <i class="fa fa-list mr-2"></i> View My Orders
            </a>
            <a href="{{ route('customer.dashboard') }}" 
               class="flex items-center justify-center w-full px-6 py-3 text-sm text-gray-500 dark:text-gray-400 hover:text-[#d2964b] transition-colors">
                <i class="fa fa-home mr-2"></i> Back to Home
            </a>
        </div>

        <p class="mt-6 text-xs text-gray-400 dark:text-gray-500">
            Need help? Contact our support team for assistance.
        </p>
    </div>
</div>
@endsection
