<?php

namespace App\Exports;

use App\Models\SparePart;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SparePartsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return SparePart::with(['category', 'stocks'])->get();
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Nama',
            'Kategori',
            'Merek',
            'Model',
            'Nomor Part',
            'Barcode',
            'Deskripsi',
            'Harga Pokok',
            'Harga Jual',
            'Stok Minimum',
            'Stok Maksimum',
            'Titik Pemesanan Ulang',
            'Satuan',
            'Status',
            'Total Stok',
        ];
    }

    public function map($sparePart): array
    {
        return [
            $sparePart->sku,
            $sparePart->name,
            $sparePart->category->name,
            $sparePart->brand,
            $sparePart->model,
            $sparePart->part_number,
            $sparePart->barcode,
            $sparePart->description,
            $sparePart->cost_price,
            $sparePart->selling_price,
            $sparePart->min_stock_level,
            $sparePart->max_stock_level,
            $sparePart->reorder_point,
            $sparePart->unit,
            $sparePart->is_active ? 'Aktif' : 'Tidak Aktif',
            $sparePart->stocks->sum('quantity'),
        ];
    }
}
