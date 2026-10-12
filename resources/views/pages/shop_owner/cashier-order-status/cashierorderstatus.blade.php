<div class="space-y-8 animate-in fade-in duration-500">
    <div class="flex items-center justify-between rounded-2xl bg-white p-6 border border-stone-200">
        <div class="relative w-full max-w-md">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search orders..." class="w-full rounded-xl border-stone-200 bg-stone-50 py-3 pl-10 text-[10px] font-bold uppercase tracking-widest focus:border-amber-600 focus:ring-0">
            <svg class="absolute left-3 top-3 h-4 w-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2"/></svg>
        </div>
        <p class="hidden text-[10px] font-bold uppercase tracking-widest text-stone-400 md:block">Real-time Fulfillment Board</p>
    </div>

    <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
        @php $stages = [
            ['status' => 'pending', 'label' => 'New Requests', 'color' => 'bg-indigo-500'],
            ['status' => 'preparing', 'label' => 'In Production', 'color' => 'bg-amber-500'],
            ['status' => 'ready', 'label' => 'Ready to Serve', 'color' => 'bg-green-500']
        ]; @endphp

        @foreach($stages as $stage)
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <span class="h-2 w-2 rounded-full {{ $stage['color'] }} shadow-[0_0_8px_rgba(0,0,0,0.1)]"></span>
                <h2 class="text-[11px] font-black uppercase tracking-[0.3em] text-stone-900">{{ $stage['label'] }}</h2>
            </div>
            
            <div class="space-y-4">
                @forelse($activeOrders->where('status', $stage['status']) as $order)
                <div class="rounded-2xl border border-stone-200 bg-white p-6 transition-all hover:shadow-lg">
                    <div class="mb-4 flex items-start justify-between">
                        <span class="font-serif text-2xl italic">#{{ $order->order_number }}</span>
                        <span class="text-[8px] font-black uppercase tracking-tighter text-stone-400">{{ $order->created_at->diffForHumans() }}</span>
                    </div>
                    
                    <div class="mb-6 space-y-1.5 border-b border-stone-50 pb-4">
                        @foreach($order->items as $item)
                            <p class="text-[10px] font-medium uppercase text-stone-500">{{ $item->quantity }}× {{ $item->product->name }}</p>
                        @endforeach
                    </div>

                    <div class="flex gap-2">
                        @php 
                            $nextStatus = $stage['status'] === 'pending' ? 'preparing' : ($stage['status'] === 'preparing' ? 'ready' : 'completed');
                            $btnLabel = $stage['status'] === 'pending' ? 'Accept' : ($stage['status'] === 'preparing' ? 'Mark Ready' : 'Dispatch');
                        @endphp
                        <button wire:click="updateOrderStatus({{ $order->id }}, '{{ $nextStatus }}')" class="flex-1 rounded-lg bg-stone-900 py-3 text-[9px] font-black uppercase tracking-widest text-white transition-all hover:bg-amber-600">
                            {{ $btnLabel }}
                        </button>
                        @if($stage['status'] === 'pending')
                        <button wire:click="updateOrderStatus({{ $order->id }}, 'cancelled')" class="rounded-lg border border-stone-200 px-3 text-stone-400 hover:text-red-500 transition-colors">✕</button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center py-10 rounded-2xl border-2 border-dashed border-stone-100 opacity-30">
                    <p class="text-[9px] font-black uppercase tracking-widest text-stone-400">Clear</p>
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>
