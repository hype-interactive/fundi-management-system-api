<!DOCTYPE html>
<html>

<head>
    <style>
        table {
            border-collapse: collapse;
        }

        td,
        th {
            border: 1px solid #999;
            padding: 0.5rem;
            text-align: left;
        }

        .header {
            padding-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ assetToBase64('images/logo.png') }}" height="30" alt="Logo" srcset="">
    </div>
    <div class="table-responsive" id="sailorTableArea">
        <h3>Orodha ya {{ $title }} <span style="float:right; font-size:15px;">{{ $date }}</span></h3>
        <table id="sailorTable" class="table" width="100%">
            <thead class="thead-light">
                <tr>
                    <th scope="col">Jina</th>
                    <th scope="col">Anuani</th>
                    <th scope="col">Simu</th>
                    <th scope="col">Jinsia</th>
                    <th scope="col">Daraja</th>
                    <th scope="col">Hali</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['address'] }}</td>
                        <td>{{ $row['phone'] }}</td>
                        <td>{{ $row['gender'] }}</td>
                        <td>{{ $row['ranking'] }}</td>
                        <td>{{ $row['status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
