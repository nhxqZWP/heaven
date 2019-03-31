<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>订单深度列表</title>
</head>
<body>
    <div>
        <table class="table">
            @foreach($data['asks'] as $item)
                <tr>
                    <td style="color: blue;">{{$item[0]}}</td>
                    <td style="color: blue; text-align: right">{{$item[1]}}</td>
                    <td style="color: blue; text-align: right">{{$item[2]}}</td>
                </tr>
            @endforeach
            @foreach($data['bids'] as $item)
            <tr>
                <td style="color: crimson">{{$item[0]}}</td>
                <td style="color: crimson; text-align: right">{{$item[1]}}</td>
                <td style="color: crimson; text-align: right">{{$item[2]}}</td>
            </tr>
            @endforeach
        </table>
    </div>
    <div>
        时间：{{date('Y-m-d H:i:s', strtotime('+ 8 hours'))}}
    </div>
</body>
</html>