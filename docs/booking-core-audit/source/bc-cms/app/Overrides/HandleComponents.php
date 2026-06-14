<?php

namespace App\Overrides;

use Livewire\Mechanisms\HandleComponents\HandleComponents as BaseHandleComponents;
use Livewire\Exceptions\PublicPropertyNotFoundException;

class HandleComponents extends BaseHandleComponents
{
    public function updateProperty($component, $path, $value, $context)
    {
        try {
            // Gọi lại logic gốc của Livewire
            return parent::updateProperty($component, $path, $value, $context);
        } catch (PublicPropertyNotFoundException $e) {
            // Nếu property không tồn tại thì chỉ bỏ qua (không throw lỗi)
            return null;
        }
    }
}
