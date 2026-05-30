<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your IELTS Exam Result PDF</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;">IELTS Exam Result PDF</h1>
        <p style="color: #dbeafe; margin: 10px 0 0 0;">Your annotated result PDF is attached!</p>
    </div>

    <div style="background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none;">
        <h2 style="color: #3b82f6; margin-top: 0;">Hello {{ $result->username }},</h2>

        <p style="font-size: 16px; margin-bottom: 20px;">
            We're sending you your IELTS exam result as a PDF document. This annotated PDF includes all your answers, correct answers, and detailed scoring information.
        </p>

        <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 20px; margin: 25px 0; border-radius: 4px;">
            <h3 style="margin-top: 0; color: #1e40af; font-size: 16px;">
                <span style="font-size: 20px;">📎</span> PDF Attachment
            </h3>
            <p style="margin: 10px 0 0 0; color: #1e3a8a; font-size: 14px;">
                Your complete exam result with detailed breakdown is attached as <strong>ielts-exam-result.pdf</strong>
            </p>
        </div>

        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #333;">What's Included:</h3>
            <ul style="margin: 10px 0; padding-left: 20px; color: #666;">
                <li style="margin-bottom: 8px;">Complete candidate information and exam details</li>
                <li style="margin-bottom: 8px;">Overall band score calculation</li>
                <li style="margin-bottom: 8px;">Listening section - All answers with correct/incorrect marking</li>
                <li style="margin-bottom: 8px;">Reading section - All answers with correct/incorrect marking</li>
                <li style="margin-bottom: 8px;">Writing section - Tasks and feedback (if available)</li>
                <li style="margin-bottom: 8px;">Speaking section - Evaluation (if available)</li>
            </ul>
        </div>

        <p style="font-size: 14px; color: #666; margin-top: 30px;">
            If you have any questions about your results or need clarification, please don't hesitate to contact us.
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
