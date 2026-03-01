<?php

namespace App\Livewire\Components;

use Livewire\Component;

class OnboardingFlash extends Component
{
    public string $step;
    public string $title;
    public string $description;
    public string $align = 'bottom'; // 'bottom', 'top', 'left', 'right'

    public bool $hasSeen = false;

    public function mount()
    {
        $this->hasSeen = auth()->check() ? auth()->user()->hasSeenOnboarding($this->step) : true;
    }

    public function dismiss()
    {
        if (auth()->check()) {
            auth()->user()->markOnboardingAsSeen($this->step);
        }
        $this->hasSeen = true;
    }

    public function render()
    {
        return view('livewire.components.onboarding-flash');
    }
}
