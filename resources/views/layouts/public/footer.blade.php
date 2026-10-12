<footer class="bg-stone-950 w-full border-t border-stone-900 font-sans">
  <div class="max-w-7xl mx-auto px-6 md:px-12 py-16">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">

      <div class="col-span-1">
        <a href="{{ url('/') }}" class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-amber-600 flex items-center justify-center text-white font-bold text-xl">
            V
          </div>
          <span class="serif text-xl text-white tracking-tight">Victorias</span>
        </a>
        <p class="text-stone-500 text-sm leading-relaxed">
          A curated sanctuary for the craft of coffee in the heart of Negros.
        </p>
      </div>

      <div>
        <h5 class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-600 mb-6">Explore</h5>
        <ul class="space-y-3">
          <li>
            <a href="{{ route('customer.shoplist') }}" class="text-[11px] uppercase tracking-widest text-stone-300 hover:text-white transition-colors">
              Browse Shops
            </a>
          </li>
          <li>
            <a href="{{ url('/#products') }}" class="text-[11px] uppercase tracking-widest text-stone-300 hover:text-white transition-colors">
              Top Products
            </a>
          </li>
          <li>
            <a href="{{ url('/#faq') }}" class="text-[11px] uppercase tracking-widest text-stone-300 hover:text-white transition-colors">
              FAQ
            </a>
          </li>
        </ul>
      </div>

      <div>
        <h5 class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-600 mb-6">Connect</h5>
        <ul class="space-y-3">
          <li><a href="#" class="text-[11px] uppercase tracking-widest text-stone-300 hover:text-white transition-colors">Instagram</a></li>
          <li><a href="#" class="text-[11px] uppercase tracking-widest text-stone-300 hover:text-white transition-colors">Facebook</a></li>
        </ul>
      </div>

      <div>
        <h5 class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-600 mb-6">Visit</h5>
        <p class="text-[11px] uppercase tracking-widest text-stone-400 leading-loose">
          Heritage Way, Victorias<br>
          Open Daily: 7AM — 10PM
        </p>
      </div>

    </div>

    <div class="flex flex-col md:flex-row justify-between items-center pt-8 border-t border-stone-900 gap-4">
      <span class="text-[9px] uppercase tracking-[0.4em] text-stone-600">
        © {{ date('Y') }} VICTORIAS ROASTERY
      </span>
      <div class="flex gap-6">
        @auth
          <a href="{{ url('/customer/dashboard') }}" class="text-[9px] uppercase tracking-[0.4em] text-stone-600 hover:text-white transition-colors">
            My Dashboard
          </a>
        @else
          <a href="{{ route('login') }}" class="text-[9px] uppercase tracking-[0.4em] text-stone-600 hover:text-white transition-colors">
            Customer Log In
          </a>
        @endauth
      </div>
    </div>

  </div>
</footer>