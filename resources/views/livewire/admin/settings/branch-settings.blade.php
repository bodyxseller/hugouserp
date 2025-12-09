{{-- resources/views/livewire/admin/settings/branch-settings.blade.php --}}
<div class="space-y-4">
    <div class="flex flex-col gap-1">
        <h1 class="text-lg font-semibold text-slate-800">
            {{ __('Branch Settings') }}
        </h1>
        <p class="text-sm text-slate-500">
            {{ __('Settings stored per branch (discount limits, POS, payroll tax, etc.).') }}
        </p>
    </div>

    <div class="space-y-4">
        <div class="space-y-1">
            <label class="block text-sm font-medium text-slate-700">
                {{ __('Branch') }}
            </label>
            <select wire:model="branchId"
                    class="erp-input">
                @foreach($branches as $branch)
                    <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                @endforeach
            </select>
            @error('branchId')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <form wire:submit.prevent="save" class="space-y-4">
            <div class="flex justify-between items-center">
                <h2 class="text-sm font-medium text-slate-700">
                    {{ __('Branch-specific key/value pairs') }}
                </h2>
                <button type="button"
                        wire:click="addRow"
                        class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm shadow-emerald-500/30 hover:bg-slate-800">
                    {{ __('Add setting') }}
                </button>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm shadow-emerald-500/10">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">
                                {{ __('Key') }}
                            </th>
                            <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500">
                                {{ __('Value') }}
                            </th>
                            <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($rows as $index => $row)
                            <tr class="hover:bg-emerald-50/40">
                                <td class="px-3 py-2 align-top">
                                    <input type="text" wire:model="rows.{{ $index }}.key"
                                           class="erp-input text-xs">
                                    @error('rows.' . $index . '.key')
                                        <p class="mt-1 text-[0.7rem] text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <input type="text" wire:model="rows.{{ $index }}.value"
                                           class="erp-input text-xs">
                                </td>
                                <td class="px-3 py-2 align-top text-end">
                                    <button type="button"
                                            wire:click="removeRow({{ $index }})"
                                            class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-[0.7rem] font-semibold text-red-700 hover:bg-red-100">
                                        {{ __('Remove') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="erp-btn-primary">
                    {{ __('Save branch settings') }}
                </button>
            </div>
        </form>
    </div>
</div>
