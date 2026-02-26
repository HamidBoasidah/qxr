<?php

namespace App\Services;

use App\Models\LandingSection;
use App\Models\LandingSectionItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LandingSectionItemService
{
    /**
     * Get all items for a section
     */
    public function getAllForSection(LandingSection $section)
    {
        return $section->items()->orderBy('order')->get();
    }

    /**
     * Create new item
     */
    public function create(LandingSection $section, array $data): LandingSectionItem
    {
        // Handle image upload
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image_path'] = $this->uploadImage($data['image']);
            unset($data['image']);
        }

        // Get the max order and increment
        $maxOrder = $section->items()->max('order') ?? 0;
        $data['order'] = $data['order'] ?? ($maxOrder + 1);
        $data['landing_section_id'] = $section->id;

        return LandingSectionItem::create($data);
    }

    /**
     * Update item
     */
    public function update(LandingSectionItem $item, array $data): LandingSectionItem
    {
        // Handle image upload
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            // Delete old image
            if ($item->image_path) {
                $this->deleteImage($item->image_path);
            }
            
            $data['image_path'] = $this->uploadImage($data['image']);
            unset($data['image']);
        }

        $item->update($data);
        return $item->fresh();
    }

    /**
     * Delete item
     */
    public function delete(LandingSectionItem $item): bool
    {
        // Delete image if exists
        if ($item->image_path) {
            $this->deleteImage($item->image_path);
        }

        return $item->delete();
    }

    /**
     * Reorder items
     */
    public function reorder(LandingSection $section, array $itemIds): void
    {
        DB::transaction(function () use ($section, $itemIds) {
            foreach ($itemIds as $index => $itemId) {
                LandingSectionItem::where('id', $itemId)
                    ->where('landing_section_id', $section->id)
                    ->update(['order' => $index + 1]);
            }
        });
    }

    /**
     * Toggle item visibility
     */
    public function toggleVisibility(LandingSectionItem $item): LandingSectionItem
    {
        $item->update(['is_active' => !$item->is_active]);
        return $item->fresh();
    }

    /**
     * Upload image
     */
    protected function uploadImage(UploadedFile $file): string
    {
        return $file->store('landing/images', 'public');
    }

    /**
     * Delete image
     */
    protected function deleteImage(string $path): void
    {
        if (!str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
