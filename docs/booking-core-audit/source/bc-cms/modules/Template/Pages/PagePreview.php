<?php
namespace Modules\Template\Pages;

use App\BaseComponent;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Modules\Template\Models\Template;

class PagePreview extends BaseComponent
{
    public $templateId;

    #[Url]
    public $lang;

    public function mount(Template $template)
    {
        $this->templateId = $template->id;
        $this->lang = $this->lang ?? app()->getLocale();
    }

    #[Computed]
    public function template()
    {
        return Template::find($this->templateId);
    }

    #[Computed]
    public function translation()
    {
        $template = $this->template;
        return $template ? $template->translate($this->lang) : null;
    }

    public function render()
    {
        return view('Template::frontend.preview')->extends('Layout::app');
    }
}
