<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ url('/css/chat.css') }}">
    <title>ChatApp</title>
</head>

<body>
    <div class="container content">
        <div class="row justify-centent-center">
            <div class="col-xl-8 col-lg-8 col-md-6 col-sm-12 col-12">
                <div class="card">
                    <div class="card-header">Chat</div>
                    <div class="card-body height3">
                        <ul class="chat-list" id="chat-section">

                        </ul>
                        <div class="row">
                            <div class="col-lg-9">
                                <input type="text" id="username" value="{{ $name }}" hidden>
                                <input type="text" placeholder="Write message here.." id="chat_message">

                            </div>
                            <div class="col-lg-3">
                                <button class="btn btn-primary rounded" onclick="broacastMethod()">Send</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/app.js')
    <script>
        function broacastMethod() {
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                url: '{{ route('broadcast.chat') }}',
                type: 'POST',
                data : { username : $('#username').val(), msg : $('#chat_message').val() },
                success: function(result) {
 
                }
            });
        }

    setTimeout(() => {
        window.Echo.channel('chat-message').listen('chat', (data)=>{
            if(data.username == $('#username').val()){
            newMessage=`<li class="out">
                            <div class="chat-img">
                                <img alt="Avtar" src="https://bootdey.com/img/Content/avatar/avatar1.png">
                            </div>
                            <div class="chat-body">
                                <div class="chat-message">
                                    <h5>${data.username}</h5>
                                    <p>${data.message}</p>
                                </div>
                            </div>
                        </li>`;
            }else{
                newMessage=`<li class="in">
                            <div class="chat-img">
                                <img alt="Avtar" src="https://bootdey.com/img/Content/avatar/avatar1.png">
                            </div>
                            <div class="chat-body">
                                <div class="chat-message">
                                    <h5>${data.username}</h5>
                                    <p>${data.message}</p>
                                </div>
                            </div>
                        </li>`;
            }
            console.log(data);
            $("#chat-section").append(newMessage);
        })
    }, 100);
    </script>
</body>

</html>