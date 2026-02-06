<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ID Card</title>

    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            width: 550px;
        }

        .header {
            width: 100%;
            padding: 20px 30px;
            background: #141A5A;
            color: white;
        }

        .header table {
            width: 100%;
            border-collapse: collapse;
        }

        .company-title {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .id-number {
            font-size: 16px;
            letter-spacing: 2px;
            margin-top: 4px;
        }

        .logo-box {
            width: 80px;
            height: 80px;
            /* background: white; */
            border-radius: 6px;
            overflow: hidden;
        }

        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
    </style>
</head>

<body>

    <!-- ===========================
         TOP HEADER (LOGO + TITLE)
    ============================== -->
    <div class="header">
        <table>
            <tr>
                <td width="80px" style="border-right:2px solid #e0e0e0; padding-right:45px;">
                    <div class="log-box">
                        <img src="{{ $logo }}" height="50">
                    </div>
                </td>

                <td style="padding-left:20px;">
                    <div class="company-title">{{ $company_name }}</div>
                    <div class="id-number">ID: {{ $id_number }}</div>
                </td>
            </tr>
        </table>
    </div>


    <!-- ===========================
         MAIN CONTENT (LEFT + QR)
    ============================== -->
    <table  cellspacing="0" cellpadding="0" 
           style="margin-top:25px; padding:0 10px; border-collapse:collapse;">

        <tr>
              <!-- QR CODE RIGHT SIDE -->
            <td valign="top" width="35%" align="center"
                style="border-right:2px solid #e0e0e0; padding-right:25px; ">

                <div style="width:130px; height:130px; 
                            border:2px solid #141A5A; background:white; 
                            overflow:hidden; margin-bottom:10px; margin-top:10px; ">
                    <img src="{{ $qrcode }}" style="width:100%; height:100%; object-fit:contain;">
                </div>

                <div style="width:130px; font-size:11px; color:#555; font-weight:bold; text-alighn:left">
                    SCAN TO VERIFY
                </div>

            </td>

            <!-- LEFT INFO -->
            <td valign="top" style="padding-left: 30px">

                <table  cellspacing="0" cellpadding="5" style="border-collapse:collapse;">
                    <tr>
                        <td style="font-weight:bold; color:#141A5A; width:120px;">Name:</td>
                        <td style="color:#222;">{{ $name }}</td>
                    </tr>

                    <tr>
                        <td style="font-weight:bold; color:#141A5A;">Gender:</td>
                        <td style="color:#222;">{{ $gender }}</td>
                    </tr>

                    <tr>
                        <td style="font-weight:bold; color:#141A5A;">Address:</td>
                        <td style="color:#222;">{{ $address }}</td>
                    </tr>

                    <tr>
                        <td style="font-weight:bold; color:#141A5A;">Registered:</td>
                        <td style="color:#222;">{{ $registered }}</td>
                    </tr>

                    <tr>
                        <td style="font-weight:bold; color:#141A5A;">Expire:</td>
                        <td style="color:#222;">{{ $expire }}</td>
                    </tr>
                </table>

            </td>


          
        </tr>

    </table>

</body>
</html>