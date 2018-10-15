
<html>
    <body>

    <div> {{ $title}}</div>


    <div>
        @foreach($msg as $item)
           <div> {{ $item }} </div>
            @endforeach
    </div>

    <div> {{ $registrationlink}}</div>

    </body>
</html>
