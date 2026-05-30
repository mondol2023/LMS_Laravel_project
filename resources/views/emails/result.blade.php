<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your IELTS Exam Results</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">IELTS Exam Results</h1>
        <p style="color: #f0f0f0; margin: 10px 0 0 0;">Your exam results are ready!</p>
    </div>

    <div style="background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none;">
        <h2 style="color: #667eea; margin-top: 0;">Hello {{ $result->username }},</h2>

        <p style="font-size: 16px; margin-bottom: 20px;">
            We're pleased to share your IELTS exam results with you. Your complete result report is attached as a PDF document.
        </p>

        @if($overallScore !== null)
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 25px; border-radius: 12px; margin: 25px 0; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
            <p style="color: #d1fae5; margin: 0 0 8px 0; font-size: 14px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;">Overall Band Score</p>
            <p style="color: white; margin: 0; font-size: 48px; font-weight: bold; line-height: 1.2;">{{ number_format($overallScore, 1) }}</p>
            <p style="color: #d1fae5; margin: 8px 0 0 0; font-size: 13px;">Congratulations on completing your IELTS exam!</p>
        </div>
        @endif

        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #333;">Result Summary</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                        <strong>Exam:</strong>
                    </td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0; text-align: right;">
                        {{ $result->local_exam_id }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                        <strong>Email:</strong>
                    </td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0; text-align: right;">
                        {{ $result->email }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                        <strong>Exam Date:</strong>
                    </td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0; text-align: right;">
                        {{ $result->created_at->format('F j, Y') }}
                    </td>
                </tr>
            </table>
        </div>

        <div style="background: #e8f4fd; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; color: #1e40af;">
                <strong>📎 Attachment Included</strong><br>
                Your detailed exam results, including all answers and scores, are attached as a PDF file.
            </p>
        </div>

        <p style="font-size: 14px; color: #666; margin-top: 30px;">
            If you have any questions about your results, please don't hesitate to contact us.
        </p>

        <p style="font-size: 14px; color: #666; margin-top: 20px;">
            Best regards,<br>
            <strong>The IELTS Team</strong>
        </p>
    </div>

    <div style="background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; color: #666;">
        <p style="margin: 0;">
            This is an automated email. Please do not reply to this message.
        </p>
    </div>
</body>
</html>
