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
            <div class="support-card p2p-chat-box mt-10">
                <div class="support-card-wrapper">
                    <div class="card-header">
                        <div class="card-header-user-area">
                            <img class="avatar" src="{{ @$chatBox->senderImage->image}}" alt="client">
                            <div class="card-header-user-content">
                                <h6 class="title">{{ @$chatBox->senderImage->fullname }}</h6>
                                <span class="sub-title">Email : <span
                                        class="text--warning">{{ $chatBox->senderImage->email }}</span></span>
                            </div>
                        </div>
                        <div class="info-btn">
                            <i class="las la-info-circle"></i>
                        </div>
                    </div>
                    <div class="support-chat-area">
                        <div class="chat-container messages">
                            <ul>
                                @foreach ($chatBox->conversations ?? [] as $item)
                                    <li class="media media-chat @if ($item->sender == Auth::user()->id) media-chat-reverse sent @else replies @endif">
                                        <img class="avatar" src="{{ $item->senderImage }}" alt="Profile">
                                        <div class="media-body">
                                            <p>{{ $item->message }}</p>
                                        </div>
                                    </li>
                                @endforeach


                            </ul>
                        </div>
                        <form class="chat-form message-input">
                            <div class="publisher">
                                <div class="chatbox-message-part">
                                    <textarea class="publisher-input message-input message-input-event" name="message"
                                        placeholder="Write something...."></textarea>
                                </div>
                                <div class="chatbox-send-part">
                                    <button type="button" class="submit chat-submit-btn-event chat-submit-btn"><i class="lab la-telegram-plane"> </i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('script')

@if (isset($chatBox) && isset($chatBox->id))
    @if ($basic_settings->broadcast_config != null && $basic_settings->broadcast_config->method == "pusher")
        <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
        <script>
            var primaryKey = "{{ $basic_settings->broadcast_config->primary_key ?? '' }}";
            var cluster = "{{ $basic_settings->broadcast_config->cluster ?? "" }}";
            var userProfile = "{{ get_image(auth()->user()->userImage,'user-profile') }}";
            var pusher = new Pusher(primaryKey, { cluster: cluster });
            var token = "{{ $chatBox->id ?? "" }}";
            var URL = "{{ setRoute('user.p2p.chat.message.send') ?? "" }}";
            var fileUploadUrl = "{{ setRoute('user.p2p.chat.file.upload') }}";
            var conversationFilePath = "{{ files_asset_path('conversation') }}";
            var channel = pusher.subscribe('support.chat.conversation.' + token);


            channel.bind('conversation', function(data) {

                data = JSON.stringify(data);
                data = JSON.parse(data);

                var addClass = "";
                if (data.conversation.sender == {{ auth()->user()->id }}) {
                    addClass = "media-chat-reverse";
                }

                var imageFiles = '';
                var otherFiles = '';
                if (data.attachments.length > 0) {
                    $.each(data.attachments, function(index, item) {
                        if (item.attachment_info.type.split("/").shift() == "image") {
                            imageFiles += `
                                <div class="image-attach">
                                    <img src="${conversationFilePath}/${item.attachment}" alt="image" width="320">
                                </div>
                            `;
                        } else {
                            otherFiles += `
                                <div class="file-attach">
                                    <div class="icon-area">
                                        <div class="content">
                                            <h6 class="title">${item.attachment}</h6>
                                        </div>
                                    </div>
                                    <a href="${FileDownloadBaseURL}/${item.attachment}" class="download-btn"><i class="las la-cloud-download-alt"></i></a>
                                </div>
                            `;
                        }
                    });
                }

                var chatBlock = `
                    <li class="media media-chat ${addClass} replies">
                        <img class="avatar" src="${data.senderImage}" alt="user">
                        <div class="media-body">
                            ${data.conversation.message !== null ? `<p>${data.conversation.message}</p>` : ''}
                            <div class="image-attach-wrapper" ${imageFiles.length > 0 ? `style="min-height:30px"` : ""}>
                                ${imageFiles}
                            </div>
                            ${otherFiles}
                        </div>
                    </li>
                `;

                $(".support-chat-area .messages ul").append(chatBlock);
            });

            let isLocked = false;

            function lockInputTemporarily() {
                isLocked = true;
                $(".chat-submit-btn").prop("disabled", true); // Disable button
                setTimeout(() => {
                    isLocked = false;
                    $(".chat-submit-btn").prop("disabled", false); // Re-enable after 2s
                }, 2000);
            }

            $(document).on("keyup", ".publisher-input", function(e) {
                if (e.which == 13 && !isLocked) {
                    e.preventDefault();
                    lockInputTemporarily(); // Lock inputs for 2s
                    $(this).closest('form').submit();
                }
            });

            $(document).on("click", ".chat-submit-btn", function(e) {
                e.preventDefault();
                if (!isLocked) {
                    lockInputTemporarily(); // Lock inputs for 2s
                    $(this).closest('form').submit();
                }
            });

            $('form.chat-form').on('submit', function(e) {
                e.preventDefault();

                let CSRF = "{{ csrf_token() }}";
                let inputValue = $(".publisher-input").val();
                let fileInput = $("#fileUpload")[0];

                if ($.trim(inputValue) === "" && (!fileInput || !fileInput.files[0])) {
                    return; // prevent empty message and no file
                }

                if (fileInput && fileInput.files[0]) {
                    let formData = new FormData();
                    formData.append('_token', CSRF);
                    formData.append('file', fileInput.files[0]);

                    $.ajax({
                        url: fileUploadUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            sendMessage(inputValue, response.data);
                        },
                        error: function(response) {
                            handleError(response);
                        }
                    });
                } else {
                    sendMessage(inputValue, null);
                }
            });

            function sendMessage(message, fileData) {
                let data = {
                    _token: "{{ csrf_token() }}",
                    chatBox: token,
                    message: message,
                    file: fileData,
                };

                $.ajax({
                    url: URL,
                    type: "POST",
                    data: data,
                    success: function() {
                        $(".publisher-input").val("");
                        $("#file-preview").html("");
                        $("#fileUpload").val("");
                    },
                    error: function(response) {
                        handleError(response);
                    }
                });
            }

            function handleError(response) {
                var responseObj = JSON.parse(response.responseText);
                // alert(responseObj.message.error);
            }

            $('#fileUpload').on('change', function (e) {
                var fileInput = e.target;
                var file = fileInput.files[0];
                if (file) {
                    // Check if the file is an image
                    if (file.type.match('image.*')) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            $('#file-preview').html(`<div class="preview"><img src="${e.target.result}" alt="File Preview" class="previewImage" width="220"></div>`);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        // For non-image files, just display the file name
                        $('#file-preview').html(`<div class="preview"><p>${file.name}</p></div>`);
                    }
                }
            });


        </script>
    @endif
@endif
@endpush
