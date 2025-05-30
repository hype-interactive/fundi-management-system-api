<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }

        .header {
            padding-bottom: 1rem;
            border-bottom: 2px solid #ddd;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            height: 50px;
        }

        .header-info {
            text-align: right;
            color: #666;
        }

        .report-title {
            text-align: center;
            color: #333;
            margin: 2rem 0;
            font-size: 24px;
            font-weight: bold;
        }

        .section {
            margin-bottom: 2rem;
            page-break-inside: avoid;
        }

        .section-title {
            background-color: #f8f9fa;
            padding: 0.75rem;
            border-left: 4px solid #5CD3F3;
            font-weight: bold;
            font-size: 16px;
            color: #333;
            margin-bottom: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .info-item {
            display: flex;
            border-bottom: 1px solid #eee;
            padding: 0.5rem 0;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            min-width: 120px;
            margin-right: 1rem;
        }

        .info-value {
            color: #333;
            flex: 1;
        }

        .status-approved {
            color: #3FAE28;
            font-weight: bold;
            background-color: #d4edda;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        .status-pending {
            color: #DDA80B;
            font-weight: bold;
            background-color: #fff3cd;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        .status-rejected {
            color: #721c24;
            font-weight: bold;
            background-color: #f8d7da;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 1rem;
        }

        td, th {
            border: 1px solid #ddd;
            padding: 0.75rem;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .photo-item {
            text-align: center;
            border: 1px solid #ddd;
            padding: 0.5rem;
            border-radius: 4px;
        }

        .photo-placeholder {
            width: 100%;
            height: 150px;
            background-color: #f8f9fa;
            border: 2px dashed #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .coordinates {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
        }

        .description-box {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 4px;
            min-height: 60px;
        }

        .rejection-reason {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }

        .footer {
            text-align: center;
            margin-top: 3rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            page-break-inside: avoid;
        }

        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .signature-box {
            border: 1px solid #ddd;
            padding: 1rem;
            height: 80px;
            text-align: center;
            position: relative;
        }

        .signature-label {
            position: absolute;
            bottom: 0.5rem;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            color: #666;
        }

        @media print {
            body {
                margin: 0;
                padding: 15px;
            }

            .section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <img src="{{ assetToBase64('images/logo.png') }}" class="logo" alt="Magic Builders Logo">
        <div class="header-info">
            <div>Report Generated: {{ date('d/m/Y H:i') }}</div>
            <div>Report ID: {{ $siteVisit->id ?? 'SV-001' }}</div>
        </div>
    </div>

    <!-- Report Title -->
    <div class="report-title">
        SITE VISIT REPORT
    </div>

    <!-- Basic Information Section -->
    <div class="section">
        <div class="section-title">Site Visit Information</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Site Name:</span>
                <span class="info-value">{{ $siteVisit->site_name ?? 'Sample Construction Site Alpha' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Visit Date:</span>
                <span class="info-value">{{ $siteVisit->visit_date ? $siteVisit->visit_date->format('d/m/Y') : '15/05/2025' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Operator:</span>
                <span class="info-value">{{ $siteVisit->operator->name ?? 'John Doe Construction Ltd.' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    @if(($siteVisit->status ?? 'Approved') == 'Approved')
                        <span class="status-approved">Approved</span>
                    @elseif(($siteVisit->status ?? 'Approved') == 'Rejected')
                        <span class="status-rejected">Rejected</span>
                    @else
                        <span class="status-pending">Pending</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Location Information Section -->
    <div class="section">
        <div class="section-title">Location Details</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Ward:</span>
                <span class="info-value">{{ $siteVisit->ward->name ?? 'Kinondoni Ward' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">District:</span>
                <span class="info-value">{{ $siteVisit->ward->district->name ?? 'Kinondoni District' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Region:</span>
                <span class="info-value">{{ $siteVisit->ward->district->region->name ?? 'Dar es Salaam' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">GPS Coordinates:</span>
                <span class="info-value coordinates">
                    {{ $siteVisit->gps_latitude ?? '-6.7924' }}, {{ $siteVisit->gps_longitude ?? '39.2083' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Site Description Section -->
    <div class="section">
        <div class="section-title">Site Description</div>
        <div class="description-box">
            {{ $siteVisit->site_description ?? 'This is a residential construction project consisting of a 3-story apartment building with modern amenities. The site covers approximately 2,000 square meters and includes parking facilities, green spaces, and utility connections. Construction began in March 2025 and is expected to be completed by December 2025.' }}
        </div>
    </div>

    <!-- Products Section -->
    @if(isset($siteVisit) && $siteVisit->products->count() > 0)
    <div class="section">
        <div class="section-title">Products Used</div>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($siteVisit->products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->pivot->quantity }}</td>
                    <td>{{ $product->pivot->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Fundis Section -->
    @if(isset($siteVisit) && $siteVisit->fundis->count() > 0)
    <div class="section">
        <div class="section-title">Mafundi (Skilled Workers)</div>
        <table>
            <thead>
                <tr>
                    <th>Fundi Name</th>
                    <th>Specialization</th>
                    <th>Contact</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($siteVisit->fundis as $fundi)
                <tr>
                    <td>{{ $fundi->name }}</td>
                    <td>{{ $fundi->specialization ?? '-' }}</td>
                    <td>{{ $fundi->phone ?? '-' }}</td>
                    <td>{{ $fundi->pivot->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Photos Section -->
    <div class="section">
        <div class="section-title">Site Photos</div>
        @if(isset($siteVisit) && $siteVisit->photos->count() > 0)
        <div class="photos-grid">
            @foreach($siteVisit->photos as $photo)
            <div class="photo-item">
                <img src="{{ $photo->photo_path }}" alt="Site Photo" style="width: 100%; height: 150px; object-fit: cover;">
                <div style="font-size: 12px; color: #666; margin-top: 0.5rem;">
                    Photo {{ $loop->iteration }}
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="photos-grid">
            <div class="photo-item">
                <div class="photo-placeholder">
                    Site Overview Photo
                </div>
                <div style="font-size: 12px; color: #666;">Photo 1</div>
            </div>
            <div class="photo-item">
                <div class="photo-placeholder">
                    Foundation Work
                </div>
                <div style="font-size: 12px; color: #666;">Photo 2</div>
            </div>
            <div class="photo-item">
                <div class="photo-placeholder">
                    Materials Storage
                </div>
                <div style="font-size: 12px; color: #666;">Photo 3</div>
            </div>
        </div>
        @endif
    </div>

    <!-- Approval Information -->
    @if(isset($siteVisit) && $siteVisit->status == 'approved' && $siteVisit->approved_by)
    <div class="section">
        <div class="section-title">Approval Information</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Approved By:</span>
                <span class="info-value">{{ $siteVisit->approver->name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Approval Date:</span>
                <span class="info-value">{{ $siteVisit->approved_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Rejection Reason (if applicable) -->
    @if(isset($siteVisit) && $siteVisit->status == 'rejected' && $siteVisit->rejection_reason)
    <div class="section">
        <div class="section-title">Rejection Information</div>
        <div class="rejection-reason">
            <strong>Reason for Rejection:</strong><br>
            {{ $siteVisit->rejection_reason }}
        </div>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="section">
        <div class="section-title">Signatures</div>
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">Site Operator Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">Inspector Signature</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>Magic Builders - Site Visit Report</strong></p>
        <p>Generated on {{ date('Y-m-d H:i:s') }} | This document is confidential and intended for internal use only.</p>
        <p>For inquiries, contact: info@magicbuilders.com | +255 XX XXX XXXX</p>
    </div>
</body>

</html>
