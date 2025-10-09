@extends('layouts.app')

@section('title', 'Manajemen Produk')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Daftar Produk</h1>
    <a href="{{ route('products.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
        <i class="fas fa-plus mr-2"></i>
        Tambah Produk
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Produk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exp/Lot/NIE</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($products as $product)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $product->code }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                @if($product->description)
                                    @if(strlen($product->description) > 50)
                                        <div class="text-sm text-gray-500 max-w-xs">
                                            <span class="truncate block">{{ Str::limit($product->description, 50) }}</span>
                                            <button onclick="showDescriptionModal('{{ addslashes($product->description) }}')" 
                                                    class="text-blue-600 hover:text-blue-800 cursor-pointer inline-flex items-center mt-1">
                                                <i class="fas fa-eye text-xs mr-1"></i>
                                                <span class="text-xs">Lihat lengkap</span>
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500">{{ $product->description }}</div>
                                    @endif
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $product->category->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">{{ $product->current_stock }} {{ $product->unit }}</div>
                            <div class="text-sm text-gray-500">Min: {{ $product->minimum_stock }}</div>
                            
                            <!-- Stock Status Badge -->
                            <div class="mt-1">
                                @if($product->current_stock == 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>
                                        Habis
                                    </span>
                                @elseif($product->isLowStock())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Stok Menipis
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Stok Aman
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                @if($product->expired_date)
                                    @php
                                        $isExpired = $product->expired_date->isPast();
                                        $daysUntilExpiry = $isExpired ? 0 : now()->diffInDays($product->expired_date);
                                        $isExpiringIn1Month = !$isExpired && $daysUntilExpiry <= 30;
                                        $isExpiringIn2Months = !$isExpired && $daysUntilExpiry > 30 && $daysUntilExpiry <= 60;
                                        $isGood = !$isExpired && $daysUntilExpiry > 60;
                                    @endphp
                                    <div class="text-xs {{ $isExpired || $isExpiringIn1Month ? 'text-red-600 font-semibold' : ($isExpiringIn2Months ? 'text-yellow-600 font-medium' : 'text-green-600 font-medium') }}">
                                        {{ $product->expired_date->format('d/m/Y') }}
                                        @if($isExpired || $isExpiringIn1Month)
                                            <i class="fas fa-exclamation-triangle ml-1"></i>
                                        @elseif($isExpiringIn2Months)
                                            <i class="fas fa-clock ml-1"></i>
                                        @endif
                                    </div>
                                @else
                                    <div class="text-xs text-gray-400 italic">-</div>
                                @endif
                                
                                @if($product->lot_number)
                                    <div class="text-gray-900 font-medium">{{ $product->lot_number }}</div>
                                @else
                                    <div class="text-gray-400 italic">-</div>
                                @endif
                                
                                @if($product->distribution_permit)
                                    <div class="text-xs text-gray-500 truncate max-w-xs" title="{{ $product->distribution_permit }}">
                                        {{ Str::limit($product->distribution_permit, 15) }}
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('products.show', $product) }}" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('products.edit', $product) }}" class="text-yellow-600 hover:text-yellow-900">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada produk</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
</div>

<!-- Modal untuk menampilkan deskripsi lengkap -->
<div id="descriptionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Deskripsi Produk</h3>
                <button onclick="closeDescriptionModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="mt-2 px-2 py-2">
                <p id="descriptionContent" class="text-sm text-gray-700 whitespace-pre-wrap"></p>
            </div>
            <div class="items-center px-4 py-3">
                <button onclick="closeDescriptionModal()" 
                        class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showDescriptionModal(description) {
    document.getElementById('descriptionContent').textContent = description;
    document.getElementById('descriptionModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeDescriptionModal() {
    document.getElementById('descriptionModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('descriptionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDescriptionModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDescriptionModal();
    }
});
</script>
@endsection
