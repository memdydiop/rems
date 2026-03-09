<?php

namespace App\Livewire\Components;

use Livewire\Component;

class OnboardingFlash extends Component
{
    public string $step;
    public string $title;
    public string $description;
    public string $align = 'bottom'; // 'bottom', 'top', 'left', 'right'
    public ?string $requiredStep = null;
    public int $currentStepNumber = 1;
    public int $totalSteps = 1;

    public bool $hasSeen = false;

    public function mount()
    {
        if (!auth()->check()) {
            $this->hasSeen = true;
            return;
        }

        $user = auth()->user();

        // If already seen, don't show
        if ($user->hasSeenOnboarding($this->step)) {
            $this->hasSeen = true;
            return;
        }

        // If a required step is NOT seen yet, don't show this one yet
        if ($this->requiredStep && !$user->hasSeenOnboarding($this->requiredStep)) {
            $this->hasSeen = true; // Technically not seen, but we hide it for now
            return;
        }

        $this->hasSeen = false;
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
