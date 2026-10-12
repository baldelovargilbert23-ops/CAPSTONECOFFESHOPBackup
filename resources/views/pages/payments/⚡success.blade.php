@extends('layouts.customer')

@section('title', 'Payment Successful')

@section('content')
<div class="flex items-center justify-center min-h-[80vh]">
    <div class="max-w-md w-full bg-white dark:bg-[#11131A] rounded-2xl shadow-2xl p-8 text-center border border-gray-200 dark:border-gray-800">

        {{-- Success Icon --}}
        <div class="w-24 h-24 mx-auto bg-emerald-500/10 rounded-full flex items-center justify-center mb-6">
            <i class="fa fa-check-circle text-5xl text-emerald-500"></i>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Payment Successful! 🎉</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-6">Your order has been confirmed and sent to the barista.</p>

        {{-- Order Details --}}
        <div class="bg-gray-50 dark:bg-[#1A1D26] rounded-xl p-4 mb-6 text-left">
            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">Order Number</span>
                <span class="text-sm font-bold text-[#d2964b]">#{{ $order->order_number ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">Total Amount</span>
                <span class="text-sm font-bold text-gray-800 dark:text-white">₱{{ number_format($order->total_amount ?? 0, 2) }}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Payment Method</span>
                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ ucfirst($order->payment_method ?? 'N/A') }}</span>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="space-y-3">
            <a href="{{ route('customer.orders') }}"
               class="block w-full py-3 bg-[#d2964b] hover:bg-[#b87c3a] text-white font-bold rounded-xl transition text-center">
                <i class="fa fa-list mr-2"></i> View My Orders
            </a>
            <a href="{{ route('customer.shopmenu', ['tenant' => $order->tenant_id, 'branch' => $order->branch_id ?? 0]) }}"
               class="block w-full py-3 border-2 border-[#d2964b] text-[#d2964b] hover:bg-[#d2964b] hover:text-white font-bold rounded-xl transition text-center">
                <i class="fa fa-shopping-bag mr-2"></i> Continue Shopping
            </a>
        </div>

        {{-- Footer Note --}}
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-6">
            <i class="fa fa-clock mr-1"></i> Your order is now being prepared by our barista.
        </p>
    </div>
</div>
@endsection