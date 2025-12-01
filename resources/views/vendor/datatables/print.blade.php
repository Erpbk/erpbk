<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Print Table</title>
        <meta charset="UTF-8">
        <meta name=description content="">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Bootstrap CSS -->
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
        <style>
          body {margin: 20px}
          @media print
{
  .no-print, .no-print *
  {
      display: none !important;
  }
}
      </style>
  </head>
  <body>
    <a href="{{url()->previous()}}" class="btn btn-primary no-print">Back</a>
    @php
    $settings = App\Helpers\Common::settings();

@endphp
    <table width="100%" style="font-family: sans-serif;margin-bottom:20px;">
      <tr>
          <td width="33.33%"><img src="{{ URL::asset('assets/img/logo-full.png') }}" width="150" /></td>
          <td width="33.33%" style="text-align: center;"><h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px;">{{$settings['company_name']}}</h4>
              <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">{{$settings['company_address']}}</p>
              <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;"> TRN {{$settings['vat_number']}}</p>
          <td width="33.33%" style="text-align: right;"></td>
      </tr>

  </table>

        <table class="table table-condensed">
            @foreach($data as $row)
                @if ($loop->first)
                    <tr>
                        @foreach($row as $key => $value)
                            <th>{!! $key !!}</th>
                        @endforeach
                    </tr>
                @endif
                <tr>
                    @foreach($row as $key => $value)
                        @if(is_string($value) || is_numeric($value))
                            <td>{!! $value !!}</td>
                        @else
                            <td></td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </table>
        <script>
          window.print();
        </script>
    </body>
</html>
