<?php
/**
 * Reservation Cancelled Email Template (Mobile & Dark-Mode Optimized)
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Conference Room Reservation Cancelled</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #1e293b; margin: 0; padding: 15px; background-color: #f1f5f9;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <!-- Header with Dark-Mode Resilient White Logo Wrapper Card -->
        <tr>
            <td align="center" style="background-color: #ffffff; padding: 25px 20px; text-align: center; border-top: 5px solid #951a1d; border-bottom: 1px solid #e2e8f0;">
                <div style="background-color: #ffffff; padding: 10px 18px; border-radius: 8px; display: inline-block; border: 1px solid #f1f5f9;">
                    <img src="cid:diwa_logo" alt="DIWA Logo" style="max-height: 50px; width: auto; max-width: 100%; display: block; margin: 0 auto;">
                </div>
                <h2 style="margin: 16px 0 0 0; font-size: 20px; font-weight: 700; color: #951a1d; letter-spacing: -0.3px;">Conference Room Reservation Cancelled</h2>
            </td>
        </tr>
        <tr>
            <td style="padding: 25px 20px;">
                <p style="font-size: 15px; margin-bottom: 15px; color: #334155;">Dear <strong>{{ requester_name }}</strong>,</p>
                <p style="margin-bottom: 20px; font-size: 15px; color: #334155;">This email is to notify you that your conference room reservation has been cancelled.</p>
                
                <!-- Bootstrap Alert Callout Box with DIWA Pastel Red background -->
                <div style="background-color: #fdf2f2; border: 1px solid #fecaca; border-left: 5px solid #951a1d; padding: 18px 20px; margin: 20px 0; border-radius: 6px;">
                    <h3 style="margin-top: 0; margin-bottom: 12px; color: #951a1d; font-size: 16px; font-weight: 700; border-bottom: 1px dashed #fca5a5; padding-bottom: 6px;">Cancelled Reservation Details</h3>
                    <table width="100%" border="0" cellspacing="0" cellpadding="5" style="font-size: 14px;">
                        <tr>
                            <td width="38%" style="color: #7f1d1d; font-weight: 700; padding: 4px 0;">Reservation ID:</td>
                            <td width="62%" style="color: #951a1d; font-weight: 700; font-family: monospace, 'Courier New', monospace; font-size: 15px; padding: 4px 0;">{{ reservation_id }}</td>
                        </tr>
                        <tr>
                            <td style="color: #7f1d1d; font-weight: 700; padding: 4px 0;">Facility:</td>
                            <td style="color: #1e293b; padding: 4px 0;">{{ room_name }}</td>
                        </tr>
                        <tr>
                            <td style="color: #7f1d1d; font-weight: 700; padding: 4px 0;">Date:</td>
                            <td style="color: #1e293b; padding: 4px 0;">{{ reservation_date }}</td>
                        </tr>
                        <tr>
                            <td style="color: #7f1d1d; font-weight: 700; padding: 4px 0;">Time:</td>
                            <td style="color: #1e293b; padding: 4px 0;">{{ start_time }} &ndash; {{ end_time }}</td>
                        </tr>
                        <tr>
                            <td style="color: #7f1d1d; font-weight: 700; padding: 4px 0;">Purpose:</td>
                            <td style="color: #1e293b; padding: 4px 0;">{{ purpose }}</td>
                        </tr>
                        <tr>
                            <td style="color: #7f1d1d; font-weight: 700; padding: 4px 0;">Reason for Cancellation:</td>
                            <td style="color: #991b1b; font-weight: 700; padding: 4px 0;">{{ cancellation_reason }}</td>
                        </tr>
                    </table>
                </div>

                <p style="font-size: 14px; color: #475569;">If you believe this cancellation was made in error, please contact DIWA Center Conference Services.</p>
                
                <div style="margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                    {{ email_signature }}
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
