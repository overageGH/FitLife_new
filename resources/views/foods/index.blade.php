@extends('layouts.app')

@section('content')
<div class="mt-page">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Page Header --}}
    <div class="mt-header">
        <div class="mt-header__icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                <line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>
            </svg>
        </div>
        <div class="mt-header__text">
            <h1>{{ __('food.meal_tracker') }}</h1>
            <p>{{ __('food.log_nutrition') }}</p>
        </div>
    </div>

    {{-- Notification --}}
    <div class="mt-notification" id="notification" role="alert"></div>

    {{-- Summary Result (after calculation) --}}
    @if(session('result'))
    <section class="mt-card mt-result-section" aria-labelledby="result-heading">
        <div class="mt-card__header">
            <div class="mt-card__header-icon mt-card__header-icon--warning">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 21c4-4 6-11 6-17"/><path d="M20 7a4 4 0 11-8 0"/>
                </svg>
            </div>
            <div>
                <h2 id="result-heading">{{ __('food.meal_summary') }}</h2>
                <p>{{ __('food.your_latest_calculation') ?? 'Your latest meal calculation' }}</p>
            </div>
        </div>
        <div class="mt-result">
            <div class="mt-result__icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M4 21c4-4 6-11 6-17"/><path d="M20 7a4 4 0 11-8 0"/>
                </svg>
            </div>
            <div class="mt-result__body">
                <span class="mt-result__label">{{ __('food.total_calories') }}</span>
                <span class="mt-result__value">{{ session('result')['calories'] }} {{ __('food.kcal') }}</span>
                <span class="mt-result__comment">{{ session('result')['comment'] }}</span>
            </div>
        </div>
    </section>
    @endif

    {{-- Meal Log Form --}}
    <section class="mt-card" aria-labelledby="meal-form-heading">
        <div class="mt-card__header">
            <div class="mt-card__header-icon mt-card__header-icon--warning">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
            </div>
            <div>
                <h2 id="meal-form-heading">{{ __('food.log_meals') }}</h2>
                <p>{{ __('food.select_foods_desc') ?? 'Select foods and enter quantities for each meal' }}</p>
            </div>
        </div>

        <form action="{{ route('foods.calculate') }}" method="POST" class="mt-form" id="meal-form">
            @csrf
            @php $meals = ['Breakfast', 'Lunch', 'Dinner', 'Snack']; @endphp

            <div class="mt-meals-grid">
                @foreach($meals as $meal)
                <div class="mt-meal">
                    <div class="mt-meal__header">
                        <span class="mt-meal__icon mt-meal__icon--{{ strtolower($meal) }}">
                            @switch(strtolower($meal))
                                @case('breakfast')
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>
                                    @break
                                @case('lunch')
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21c4-4 6-11 6-17"/><path d="M20 7a4 4 0 11-8 0"/></svg>
                                    @break
                                @case('dinner')
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>
                                    @break
                                @default
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                            @endswitch
                        </span>
                        <h3 class="mt-meal__title">{{ __('food.meal_' . strtolower($meal)) ?? $meal }}</h3>
                    </div>

                    <div class="mt-meal__items" data-meal="{{ $meal }}">
                        <div class="mt-meal__item meal-item">
                            <select class="mt-select food-select" name="meals[{{ $meal }}][0][food]" aria-label="{{ __('food.select_food_for', ['meal' => $meal]) }}">
                                <option value="">{{ __('food.select_food') }}</option>
                                @foreach($foods as $food => $cal)
                                    <option value="{{ $food }}" data-calories="{{ $cal }}">{{ $food }} ({{ $cal }} {{ __('food.kcal') }})</option>
                                @endforeach
                            </select>
                            <input type="number" class="mt-input quantity-input" name="meals[{{ $meal }}][0][quantity]" placeholder="{{ __('food.g_ml') }}" style="display:none;" min="0" step="1" aria-label="{{ __('food.quantity_for', ['meal' => $meal]) }}">
                            <div class="mt-cal-preview calorie-preview" data-calories="0">0 {{ __('food.kcal') }}</div>
                            <button type="button" class="mt-remove-btn remove-food-btn" style="display:none;" aria-label="{{ __('food.remove_item') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="button" class="mt-add-btn add-food-btn" data-meal="{{ $meal }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        {{ __('food.add_item') }}
                    </button>

                    <div class="mt-meal__total total-calories" data-total-calories="0">{{ __('food.total') }}: 0 {{ __('food.kcal') }}</div>

                    @error("meals.{$meal}.*")
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
                @endforeach
            </div>

            <div class="mt-form__actions">
                <button type="submit" class="mt-btn mt-btn--primary" id="calculate-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ __('food.calculate_calories') }}
                </button>
            </div>
        </form>
    </section>

    {{-- Meal History --}}
    <section class="mt-card" id="history-section" aria-labelledby="history-heading">
        <div class="mt-card__header">
            <div class="mt-card__header-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div>
                <h2 id="history-heading">{{ __('food.history') }}</h2>
                <p>{{ __('food.history_desc') ?? 'Your recently logged meals' }}</p>
            </div>
        </div>
        @php $logs = $mealLogs ?? session('mealLogs') ?? collect(); @endphp
        @include('profile.partials.meal_table', ['mealLogs' => $logs])
    </section>
</div>

<script>
    window.foodCalories = @json($foods);
    window.foodOptionsHTML = `<option value="">{{ __('food.select_food') }}</option>
        @foreach($foods as $food => $cal)
            <option value="{{ $food }}" data-calories="{{ $cal }}">{{ $food }} ({{ $cal }} {{ __('food.kcal') }})</option>
        @endforeach`;
    window.translations = {
        kcal: '{{ __('food.kcal') }}',
        total: '{{ __('food.total') }}',
        selectFood: '{{ __('food.select_food') }}',
        gMl: '{{ __('food.g_ml') }}',
        removeItem: '{{ __('food.remove_item') }}'
    };
</script>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="{{ asset('js/foods.js') }}"></script>
@endsection
