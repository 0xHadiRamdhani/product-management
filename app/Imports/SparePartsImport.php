<?php

namespace App\Imports;

use App\Models\SparePart;
use App\Models\Category;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SparePartsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $category = Category::firstOrCreate(['name' => $row['kategori']]);
        
        return new SparePart([
            'sku' => $row['sku'],
            'name' => $row['nama'],
            'slug' => \Str::slug($row['nama']),
            'category_id' => $category->id,
            'brand' => $row['merek'] ?? null,
            'model' => $row['model'] ?? null,
            'part_number' => $row['nomor_part'] ?? null,
            'barcode' => $row['barcode'] ?? null,
            'description' => $row['deskripsi'] ?? null,
            'cost_price' => $row['harga_pokok'] ?? 0,
            'selling_price' => $row['harga_jual'] ?? 0,
            'min_stock_level' => $row['stok_minimum'] ?? 0,
            'max_stock_level' => $row['stok_maksimum'] ?? 0,
            'reorder_point' => $row['titik_pemesanan_ulang'] ?? 0,
            'unit' => $row['satuan'] ?? 'pcs',
            'is_active' => true,
        ]);
    }

    public function rules(): array
    {
        return [
            'sku' => 'required|unique:spare_parts,sku',
            'nama' => 'required',
            'kategori' => 'required',
            'harga_pokok' => 'numeric|min:0',
            'harga_jual' => 'numeric|min:0',
        ];
    }
}
