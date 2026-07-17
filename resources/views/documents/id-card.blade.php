<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ID Card</title>

    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 305px;
        }

        .header {
            width: 100%;
            /* Top padding = original 14px + 5.665pt (~2mm) vertical bleed so the
               navy background reaches the true page edge while the logo/title
               stay positioned as if the page were still the 5.5cm live area. */
            padding: 21.55px 18px 14px 18px;
            background: #141A5A;
            color: white;
        }

        .header table {
            width: 100%;
            border-collapse: collapse;
        }

        .company-title {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .id-number {
            font-size: 8px;
            letter-spacing: 1px;
            margin-top: 3px;
        }

        .logo-box {
            width: 44px;
            height: 44px;
            /* background: white; */
            border-radius: 4px;
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
                <td width="44px" style="border-right:1px solid #e0e0e0; padding-right:25px;">
                    <div class="log-box">
                        <img src="{{ $logo }}" height="27">
                    </div>
                </td>

                <td style="padding-left:11px;">
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
           style="margin-top:16px; padding:0 6px; border-collapse:collapse;">

        <tr>
              <!-- QR CODE RIGHT SIDE -->
            <td valign="top" width="35%" align="center"
                style="border-right:1px solid #e0e0e0; padding-right:12px; ">

                <div style="width:90px; height:90px;
                            border:1px solid #141A5A; background:white;
                            overflow:hidden; margin-bottom:6px; margin-top:6px; ">
                    <img src="{{ $qrcode }}" style="width:100%; height:100%; object-fit:contain;">
                </div>

                <div style="width:90px; font-size:7px; color:#555; font-weight:bold; text-alighn:left">
                    SCAN TO VERIFY
                </div>

            </td>

            <!-- LEFT INFO -->
            <td valign="top" style="padding-left: 17px">

                <table cellspacing="0" cellpadding="4" style="border-collapse:collapse; font-size:9px;">
                    <tr>
                        <td style="color:#141A5A;">Name: <span style="font-weight:bold; color:#222;">{{ $name }}</span></td>
                    </tr>

                    <tr>
                        <td style="color:#141A5A;">Gender: <span style="font-weight:bold; color:#222;">{{ $gender }}</span></td>
                    </tr>

                    <tr>
                        <td style="color:#141A5A;">Address: <span style="font-weight:bold; color:#222;">{{ $address }}</span></td>
                    </tr>

                    <tr>
                        <td style="color:#141A5A;">Registered: <span style="font-weight:bold; color:#222;">{{ $registered }}</span></td>
                    </tr>

                    <tr>
                        <td style="color:#141A5A;">Expire: <span style="font-weight:bold; color:#222;">{{ $expire }}</span></td>
                    </tr>
                </table>

            </td>


          
        </tr>

    </table>

</body>
</html>