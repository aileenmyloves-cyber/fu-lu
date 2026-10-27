<table class="custom-table currency-search-table">
    <thead>
        <tr>
    <thead>
            @if (admin_permission_by_name("admin.currency.bulk.status.enable") || admin_permission_by_name("admin.currency.bulk.status.disable"))
                <th>
                    <div class="d-flex align-items-center select-check-input">
                        <input type="checkbox" name="select_all" id="select-all">
                        <label for="select-all" class="form-check-label mb-0">{{ __("All") }}</label>
                    </div>
                </th>
            @endif

            <th>{{ __("Flag") }}</th>
            <th>{{ __("Name")}} | {{ __("Code")}}</th>
            <th>{{ __("Symbol")}}</th>
            <th>{{ __("type")}} | {{ __("Rate")}}</th>
            <th>{{ __("Role")}}</th>
            <th>{{ __("Status")}}</th>
            <th>{{__("action")}}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($currencies ?? [] as $item)
        <tr data-item="{{ $item->editData }}">
             @if ($item->isDefault())
                    <td></td>
                @else
                <td>
                    @if (admin_permission_by_name("admin.currency.bulk.status.enable") || admin_permission_by_name("admin.currency.bulk.status.disable"))
                        <input type="checkbox" class="w-auto" id="currency-{{ $item->id }}" name="select_currency[]" value="{{ $item->id }}">
                    @endif
                </td>
                @endif
            <td>
                <ul class="user-list">
                    <li><img src="{{ get_image($item->flag,'currency-flag') }}" alt="flag"></li>
                </ul>
            </td>
            <td>{{ $item->country }}
                @if ($item->default)
                    <span class="badge badge--success ms-1">{{ __("Default") }}</span>
                @endif
                <br> <span>{{ $item->code }}</span></td>
            <td>{{ $item->symbol }}</td>
            <td><span class="text--info">{{ $item->type }}</span> <br>
                 1 {{ get_default_currency_code($default_currency) }} =  {{ get_amount($item->rate,$item->code, get_wallet_precision($item)) }}

            </td>
            <td>
                @if ($item->both)
                    <span class="badge badge--success">{{ __("Sender") }}</span>
                    <span class="badge badge--danger">{{ __("Receiver") }}</span>
                @elseif ($item->senderCurrency)
                    <span class="badge badge--success">{{ __("Sender") }}</span>
                @elseif ($item->receiverCurrency)
                    <span class="badge badge--danger">{{ __("Receiver") }}</span>
                @endif
            </td>
            <td>
                @include('admin.components.form.switcher',[
                    'name'          => 'currency_status',
                    'value'         => $item->status,
                    'options'       => ['Enable' => 1,'Disable' => 0],
                    'onload'        => true,
                    'data_target'   => $item->id,
                    'permission'    => "admin.currency.status.update",
                ])
            </td>
            <td>
                @include('admin.components.link.edit-default',[
                    'href'          => "javascript:void(0)",
                    'class'         => "edit-modal-button",
                    'permission'    => "admin.currency.update",
                ])
                @if (!$item->isDefault())
                    @include('admin.components.link.delete-default',[
                        'href'          => "javascript:void(0)",
                        'class'         => "delete-modal-button",
                        'permission'    => "admin.currency.delete",
                    ])
                @endif
            </td>
        </tr>
        @empty
            @include('admin.components.alerts.empty',['colspan' => 7])
        @endforelse
    </tbody>
</table>

@push("script")
    <script>
        $(document).ready(function(){
            // Switcher
            switcherAjax("{{ setRoute('admin.currency.status.update') }}");
        })
    </script>

 <script>
        $(document).on('change','#select-all', function () {
            let isChecked = $(this).is(':checked');
            $('input[name="select_currency[]"]').prop('checked', isChecked);
            toggleActionBtn();
        });
        $(document).on('change', 'input[name="select_currency[]"]', function () {
            let total = $('input[name="select_currency[]"]').length;
            let checked = $('input[name="select_currency[]"]:checked').length;
            $('#select-all').prop('checked', total === checked);
            toggleActionBtn();
        });
        function toggleActionBtn() {
            let selectedCount = $('input[name="select_currency[]"]:checked').length;
            if (selectedCount > 0) {
                $('.action-btn-wrapper').removeClass('d-none');
            } else {
                $('.action-btn-wrapper').addClass('d-none');
            }
        }
        // $(document).ready(function () {
        //     $('.action-btn-wrapper').addClass('d-none');
        //     switcherAjax("{{ setRoute('admin.currency.status.update') }}");
        // });
    </script>
@endpush
