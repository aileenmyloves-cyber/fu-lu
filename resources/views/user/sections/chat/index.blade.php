@extends('user.layouts.master')

@push('css')

@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __(@$page_title)])
@endsection

@section('content')
    <div class="body-wrapper">
        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{__(@$page_title)}}</h3>
            </div>
        </div>
        <div class="row mb-30-none">
            <div class="table-area mt-10">
                <div class="table-wrapper">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{ __($page_title) }}</h4>
                    <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <button class="btn--base btn" data-bs-toggle="modal" data-bs-target="#chatModal" ><i class="las la-plus me-1"></i> {{ __('New Chat') }} </button>
                    </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                    <thead>
                        <tr>
                        <th>{{ __('Image') }}</th>
                        <th>{{ __('Fullname') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Last Replied') }}</th>
                        <th></th>
                        </tr>
                    </thead>
                    <tbody>

                        @forelse ($chatBox as $item)
                        @php
                            if ($item->user->id == auth()->id()) {
                                $user = $item->sender->fullname;
                                $email = $item->sender->email;
                                $image = $item->sender->userImage;
                            }else {
                                $user = $item->user->fullname;
                                $email = $item->user->email;
                                $image = $item->user->userImage;
                            }

                        @endphp
                            <tr>
                                <td><img class="img rounded-circle" height="80" width="80" src="{{ $image }}" alt=""></td>
                                <td><span class="text--info">{{ $user ?? '' }}</span></td>
                                <td><span class="text--success"> {{ $email ?? '' }} </span></td>
                                <td>{{ $item->created_at->format("Y-m-d H:i A") }}</td>
                                <td><a href="{{ setRoute('user.p2p.chat.conversation',$item->id)}}" class="btn btn--base"><i class="las la-comment"></i></a> </td>                            </td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty',['colspan' => 7])
                        @endforelse

                    </tbody>
                    </table>
                </div>
                </div>

                {{ $chatBox->links() }}
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="ChatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ setRoute('user.p2p.chat.create') }}" method="post">
                    @csrf
                    <input type="hidden" name="receiver_id">
                    <div class="modal-body">
                        <div class="search-chat-form">
                            <div class="search-chat-logo">
                                <img src="{{ get_fav($basic_settings) }}" />
                            </div>
                            <div class="search-form-title text-center">
                                <h3 class="title">{{ __('User Search') }}</h3>
                            </div>
                            <div class="search-chat-acrea ptb-20 mb-10-none">

                                <div class="search-input mb-10">
                                    <label>{{ __('phone Number') }}</label>
                                    <input name="phone" type="text" class="form--control" placeholder="{{ __('User phone Number') }}" />
                                </div>
                                <div class="search-input mb-10">
                                    <label>{{__('User Email')}}</label>
                                    <input name="email" type="email" class="form--control" placeholder="{{ __('User Email') }}" />
                                </div>

                            </div>
                        </div>
                        <div class="search-results"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn--base bg-secondary w-100 btn-loading submit-btn" disabled> {{ __('Start Chat') }} </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script')
<script>

    $(document).ready(function () {
        $(".search-chat-acrea input").on("keyup", function () {
            let phone = $("input[name='phone']").val();
            let email = $("input[name='email']").val();
            let chatButton = $(".submit-btn");

            $.ajax({
                url: "{{ route('user.p2p.chat.user.check') }}", // Replace with your actual route
                method: "POST",
                data: {
                    phone: phone,
                    email: email,
                    _token: "{{ csrf_token() }}" // CSRF protection
                },
                success: function (response) {
                    console.log(response);

                    if (response.status === 200) {
                    // console.log(response.data);

                        $(".search-results").html(`<p class="text-success">${response.data.firstname} ${response.data.lastname}</p>`);
                        chatButton.removeClass("bg-secondary").prop("disabled", false); // Enable button
                        $("input[name='receiver_id']").val(response.data.id);
                    } else {
                        $(".search-results").html(`<p class="text-danger">${response.message}</p>`);
                        chatButton.addClass("bg-secondary").prop("disabled", true); // Disable button
                    }
                },
                error: function (xhr) {
                    console.log(xhr.responseText); // Handle errors
                }
            });
        });
    });


    // let timer;
    // $(".search-input input").on("keyup", function () {
    //     clearTimeout(timer); // Clear previous timeout

    //     timer = setTimeout(function () {
    //         var data = {
    //             _token: laravelCsrf(),
    //             user_name: $(".user-name").val(),
    //             user_email: $(".user-email").val(),
    //         };

    //         $.post("{{ setRoute('user.p2p.chat.user.check') }}", data, function (response) {
    //             $(".search-chat-acrea").html(response);
    //         });
    //     }, 500); // Delay AJAX call by 500ms
    // });
</script>
@endpush
