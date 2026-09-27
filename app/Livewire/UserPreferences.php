<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\On;
use Livewire\Component;

class UserPreferences extends Component
{
    #[On('preferences-updated')]
    public function save(array $preferences): void
    {
        $validated = Validator::make($preferences, [
            'theme' => ['required', 'string', 'in:system,light,dark'],
            'sidebar_variant' => ['required', 'string', 'in:sidebar,inset,floating'],
            'layout' => ['required', 'string', 'in:default,compact,full'],
            'direction' => ['required', 'string', 'in:ltr,rtl'],
        ])->validate();

        Auth::user()->forceFill($validated)->save();
    }

    public function render()
    {
        return view('livewire.user-preferences');
    }
}
